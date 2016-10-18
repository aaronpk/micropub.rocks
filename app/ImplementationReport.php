<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use ORM;
use GuzzleHttp;
use Config;
use GuzzleHttp\Exception\RequestException;

class ImplementationReport {

  private $user;
  private $endpoint;
  private $client;

  public function get_server_report(ServerRequestInterface $request, ResponseInterface $response, $args) {
    if($check = $this->_server_report($request, $response, $args))
      return $check;

    $results = ORM::for_table('tests')
      ->raw_query('SELECT features.*, feature_results.implements FROM features
        LEFT JOIN feature_results ON features.number = feature_results.feature_num
          AND feature_results.endpoint_id = :endpoint_id
        ORDER BY features.number', ['endpoint_id'=>$this->endpoint->id])
      ->find_many();

    $response->getBody()->write(view('implementation-report', [
      'title' => 'Micropub Rocks!',
      'endpoint' => $this->endpoint,
      'results' => $results,
    ]));
    return $response;
  }

  public function view_server_report(ServerRequestInterface $request, ResponseInterface $response, $args) {
    if($check = $this->_server_report($request, $response, $args))
      return $check;

    $results = ORM::for_table('tests')
      ->raw_query('SELECT features.*, feature_results.implements FROM features
        LEFT JOIN feature_results ON features.number = feature_results.feature_num
          AND feature_results.endpoint_id = :endpoint_id
        ORDER BY features.number', ['endpoint_id'=>$this->endpoint->id])
      ->find_many();

    if(is_logged_in())
      $this->user = logged_in_user();

    $response->getBody()->write(view('view-implementation-report', [
      'title' => 'Micropub Rocks!',
      'endpoint' => $this->endpoint,
      'user' => $this->user,
      'results' => $results,
    ]));
    return $response;
  }

  public function save_report(ServerRequestInterface $request, ResponseInterface $response) {
    if($check = $this->_check_permissions($request, $response, 'body'))
      return $check;

    $params = $request->getParsedBody();

    if($this->endpoint) {
      foreach($params['data'] as $k=>$v) {
        $this->endpoint->{$k} = $v;
      }
      $this->endpoint->save();
    } elseif($this->client) {

    }

    return new JsonResponse([
      'result' => 'ok',
    ], 200);
  }

  public function publish_report(ServerRequestInterface $request, ResponseInterface $response) {
    if($check = $this->_check_permissions($request, $response, 'body'))
      return $check;

    $params = $request->getParsedBody();

    if($this->endpoint) {
      if($this->endpoint->share_token == '') {
        $this->endpoint->share_token = random_string(20);
        $this->endpoint->save();
      }
      $token = $this->endpoint->share_token;
    } elseif($this->client) {

    }

    return new JsonResponse([
      'result' => 'ok',
      'location' => Config::$base . 'implementation-report/'.$params['type'].'/'.$params['id'].'/'.$token
    ], 200);
  }


  private function _server_report(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();

    if(array_key_exists('token', $args)) {
      $this->endpoint = ORM::for_table('micropub_endpoints')
        ->where('share_token', $args['token'])
        ->where('id', $args['id'])
        ->find_one();

      if(!$this->endpoint) {
        return $response->withHeader('Location', '/?error=404')->withStatus(302);
      }

    } else {
      if(!is_logged_in()) {
        return login_required($response);
      }

      $this->user = logged_in_user();

      $this->endpoint = ORM::for_table('micropub_endpoints')
        ->where('user_id', $this->user->id)
        ->where('id', $args['id'])
        ->find_one();
    }
    
    return null;
  }

  private function _check_permissions(&$request, &$response, $source='query') {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    if($source == 'body')
      $params = $request->getParsedBody();
    else
      $params = $request->getQueryParams();
    
    $this->user = logged_in_user();

    // Verify an endpoint is specified and the user has permission to access it
    if(!isset($params['id']) || !isset($params['type']) || !in_array($params['type'], ['client','server']))
      return $response->withHeader('Location', '/dashboard?error='.$params['type'])->withStatus(302);

    if($params['type'] == 'server') {
      $this->endpoint = ORM::for_table('micropub_endpoints')
        ->where('user_id', $this->user->id)
        ->where('id', $params['id'])
        ->find_one();

      if(!$this->endpoint)
        return $response->withHeader('Location', '/dashboard?error=404')->withStatus(302);
    } else {
      $this->client = ORM::for_table('micropub_clients')
        ->where('user_id', $this->user->id)
        ->where('id', $params['id'])
        ->find_one();

      if(!$this->client)
        return $response->withHeader('Location', '/dashboard?error=404')->withStatus(302);
    }

    return null;    
  }

  public static function store_server_feature($endpoint_id, $feature_num, $implements, $test_id) {
    $result = ORM::for_table('feature_results')
      ->where('endpoint_id', $endpoint_id)
      ->where('feature_num', $feature_num)
      ->find_one();

    if(!$result) {
      // New result
      $result = ORM::for_table('feature_results')->create();
      $result->endpoint_id = $endpoint_id;
      $result->feature_num = $feature_num;
      $result->created_at = date('Y-m-d H:i:s');
      $result->implements = $implements;
    } else {
      // Updating a result, only set to fail (-1) if the new result is from the same test
      if($implements == 1) {
        $result->implements = $implements;
      } else {
        if($result->source_test_id == $test_id) {
          $result->implements = $implements;
        }
      }
    }

    $result->source_test_id = $test_id;
    $result->updated_at = date('Y-m-d H:i:s');
    $result->save();

    // Publish this result on the streaming API
    streaming_publish('endpoint-'.$endpoint_id, [
      'feature' => $feature_num,
      'implements' => $implements
    ]);
  }

  public function store_result(ServerRequestInterface $request, ResponseInterface $response) {
    if($check = $this->_check_permissions($request, $response, 'body'))
      return $check;

    $params = $request->getParsedBody();

    $col = $params['type'] == 'server' ? 'endpoint_id' : 'client_id';
    $id = $params['id'];

    self::store_server_feature($id, $params['feature_num'], $params['implements'], $params['source_test']);

    return new JsonResponse([
      'result' => 'ok'
    ], 200);
  }

  public function show_reports(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    $endpoints = [];
    $results = [];

    $query = ORM::for_table('micropub_endpoints')
      ->where_not_null('share_token')
      ->find_many();
    foreach($query as $q) {
      $endpoints[] = $q;

      $endpoint_results = ORM::for_table('tests')
        ->raw_query('SELECT features.*, feature_results.implements FROM features
          LEFT JOIN feature_results ON features.number = feature_results.feature_num
            AND feature_results.endpoint_id = :endpoint_id
          ORDER BY features.number', ['endpoint_id'=>$q->id])
        ->find_many();

      foreach($endpoint_results as $endpoint_result) {
        if(!array_key_exists($endpoint_result->number, $results))
          $results[$endpoint_result->number] = [];
        $results[$endpoint_result->number][$q->id] = $endpoint_result->implements;
      }
    }

    $response->getBody()->write(view('show-reports', [
      'title' => 'Micropub Rocks!',
      'endpoints' => $endpoints,
      'results' => $results
    ]));
    return $response;
  }

}
