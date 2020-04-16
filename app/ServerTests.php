<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use ORM;
use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;

class ServerTests {

  private $user;
  private $endpoint;

  private function _check_permissions(&$request, &$response) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $params = $request->getQueryParams();

    $this->user = logged_in_user();

    // Verify an endpoint is specified and the user has permission to access it
    if(!isset($params['endpoint']))
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    $this->endpoint = ORM::for_table('micropub_endpoints')
      ->where('user_id', $this->user->id)
      ->where('id', $params['endpoint'])
      ->find_one();

    if(!$this->endpoint)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    return null;
  }

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    if($check = $this->_check_permissions($request, $response))
      return $check;

    $data = ORM::for_table('tests')
      ->raw_query('SELECT tests.*, test_results.passed FROM tests
        LEFT JOIN test_results ON tests.id = test_results.test_id AND test_results.endpoint_id = :endpoint_id
        WHERE tests.group = :group
        ORDER BY tests.number', ['endpoint_id'=>$this->endpoint->id, 'group'=>'server'])
      ->find_many();

    $tests = [];
    foreach($data as $test) {
      $tests[$test->number] = [
        'name' => $test->name,
        'passed' => $test->passed
      ];
    }

    $response->getBody()->write(view('server-tests', [
      'title' => 'Micropub Rocks!',
      'endpoint' => $this->endpoint,
      'tests' => $tests,
    ]));
    return $response;
  }

  public function get_test(ServerRequestInterface $request, ResponseInterface $response, $args) {
    if($check = $this->_check_permissions($request, $response))
      return $check;

    $test = ORM::for_table('tests')->where('group','server')->where('number',$args['num'])->find_one();

    if(!$test)
      return $response->withHeader('Location', '/server-tests?endpoint='.$this->endpoint->id)->withStatus(302);

    $response->getBody()->write(view('server-tests/'.$args['num'], [
      'title' => 'Micropub Rocks!',
      'endpoint' => $this->endpoint,
      'test' => $test,
    ]));
    return $response;
  }

  public function micropub_request(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return new JsonResponse(['error'=>'unauthorized'], 401);
    }

    $params = $request->getParsedBody();

    $user = logged_in_user();

    $endpoint = ORM::for_table('micropub_endpoints')
      ->where('user_id', $user->id)
      ->where('id', $params['endpoint'])
      ->find_one();

    if(!$endpoint) {
      return new JsonResponse(['error'=>'invalid_endpoint'], 400);
    }

    $client = new GuzzleHttp\Client();

    if(!(array_key_exists('skipauth', $params) && $params['skipauth'] == 1)) {
      $options = [
        'headers' => [
          'Authorization' => 'Bearer ' . (isset($params['access_token']) ? $params['access_token'] : $endpoint->access_token)
        ]
      ];
    }

    $options['allow_redirects'] = false;

    $endpoint_url = $endpoint->micropub_endpoint;
    switch($params['method']) {
      case 'get':
        $method = 'GET';
        $endpoint_url = $params['url'];
        $options['headers']['Accept'] = 'application/json';
        break;
      case 'post':
        $method = 'POST';
        $options['headers']['Content-type'] = 'application/x-www-form-urlencoded';
        $options['body'] = $params['body'];
        break;
      case 'postjson':
        $method = 'POST';
        $options['body'] = $params['body'];
        $options['headers']['Content-type'] = 'application/json';
        $options['headers']['Accept'] = 'application/json';
        break;
      case 'multipart':
        $method = 'POST';
        $options['multipart'] = [];
        if(isset($params['params'])) {
          foreach($params['params'] as $prop=>$val) {
            $options['multipart'][] = [
              'name' => $prop,
              'contents' => $val
            ];
          }
        }
        foreach($params['files'] as $prop=>$files) {
          if(!is_array($files)) $files = [$files];
          foreach($files as $file) {
            if(strpos($file,'/') === false) {
              $options['multipart'][] = [
                'name' => (count($files) == 1 ? $prop : $prop.'[]'),
                'contents' => fopen('public/media/'.$file, 'r'),
                'filename' => $file
              ];
            }
          }
        }
        if(array_key_exists('url', $params) && $params['url'])
          $endpoint_url = $params['url'];
        break;
    }

    if(!preg_match('/^https?:\/\//', $endpoint_url)) {
      return new JsonResponse([
        'code' => '',
        'location' => null,
        'content_type' => null,
        'headers' => [],
        'body' => '',
        'json' => [],
        'debug' => 'Invalid endpoint URL: '.$endpoint_url
      ], 200);
    }

    try {
      $res = $client->request($method, $endpoint_url, $options);
    } catch(RequestException $e) {
      $res = $e->getResponse();
      $debug = $e->getMessage();
    } catch(\Exception $e) {
      $res = null;
      $debug = $e->getMessage();
    }

    if(!$res) {
      return new JsonResponse([
        'code' => '',
        'location' => null,
        'content_type' => null,
        'headers' => [],
        'body' => '',
        'json' => [],
        'debug' => (isset($debug) ? $debug : 'Unknown error making request to the Micropub endpoint')
      ], 200);
    }

    $code = $res->getStatusCode();
    $location = $res->getHeader('Location');
    $content_type = $res->getHeader('Content-Type');
    $headers = $res->getHeaders();
    $body = ''.$res->getBody();

    if($location && array_key_exists(0, $location))
      $location = $location[0];
    else
      $location = false;

    if($content_type && array_key_exists(0, $content_type)) {
      $content_type = $content_type[0];
      if(preg_match('/application\/json/', $content_type)) {
        $content_type = 'application/json';
      }
    } else {
      $content_type = false;
    }

    $debug = 'HTTP/1.1 '.$code.' '.$res->getReasonPhrase()."\n";
    foreach($headers as $k=>$vs) {
      foreach($vs as $v) {
        $debug .= $k.': '.$v."\n";
      }
    }
    $debug .= "\n";

    $json = null;
    if($body && $body[0] == '{' && $content_type == 'application/json') {
      if($json = @json_decode($body))
        $debug .= json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
      else
        $debug .= $body;
    } else {
      $debug .= $body;
    }

    // Store the last response in the test_results table
    $last = ORM::for_table('test_results')
      ->where('endpoint_id', $endpoint->id)
      ->where('test_id', $params['test'])
      ->find_one();
    if(!$last) {
      $last = ORM::for_table('test_results')->create();
      $last->endpoint_id = $endpoint->id;
      $last->test_id = $params['test'];
      $last->created_at = date('Y-m-d H:i:s');
    }
    $last->passed = 0;
    $last->response = $debug;
    $last->location = $location;
    $last->last_result_at = date('Y-m-d H:i:s');
    $last->save();

    $endpoint->last_test_at = date('Y-m-d H:i:s');
    $endpoint->save();

    return new JsonResponse([
      'code' => $code,
      'location' => $location,
      'content_type' => $content_type,
      'headers' => $headers,
      'body' => $body,
      'json' => $json,
      'debug' => $debug
    ], 200);
  }

  public function media_check(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return new JsonResponse(['error'=>'unauthorized'], 401);
    }

    $params = $request->getParsedBody();

    $user = logged_in_user();

    $client = new GuzzleHttp\Client();

    try {
      $res = $client->request('GET', $params['url'], []);
    } catch(RequestException $e) {
      $res = $e->getResponse();
    }

    $code = $res->getStatusCode();
    $content_type = $res->getHeader('Content-Type');
    if($content_type) {
      $content_type = $content_type[0];
    }

    return new JsonResponse([
      'code' => $code,
      'http' => 'HTTP/1.1 '.$code.' '.$res->getReasonPhrase(),
      'content_type' => $content_type,
    ], 200);
  }

  public function store_result(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return new JsonResponse(['error'=>'unauthorized'], 401);
    }

    $params = $request->getParsedBody();

    $user = logged_in_user();

    $endpoint = ORM::for_table('micropub_endpoints')
      ->where('user_id', $user->id)
      ->where('id', $params['endpoint'])
      ->find_one();

    if(!$endpoint) {
      return new JsonResponse(['error'=>'invalid_endpoint'], 400);
    }

    $last = ORM::for_table('test_results')
      ->where('endpoint_id', $endpoint->id)
      ->where('test_id', $params['test'])
      ->find_one();

    if($last) {
      $last->passed = $params['passed'];
      $last->save();
    }

    return new JsonResponse([
      'result' => 'ok'
    ], 200);
  }
}
