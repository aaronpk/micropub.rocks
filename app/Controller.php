<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use ORM;
use IndieAuth;
use Config;

class Controller {

  private function _redirectURI() {
    return Config::$base.'endpoints/callback';
  }

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    $num_server_reports = ORM::for_table('micropub_endpoints')
      ->where_not_null('share_token')
      ->count();

    $last_server_report_date = ORM::for_table('micropub_endpoints')
      ->select('last_test_at')
      ->where_not_null('share_token')
      ->max('last_test_at');

    $_SESSION['login_confirm'] = mt_rand(100, 999);

    $response->getBody()->write(view('index', [
      'title' => 'Micropub Rocks!',
      'confirm' => $_SESSION['login_confirm'],
      'num_server_reports' => $num_server_reports,
      'last_server_report_date' => $last_server_report_date
    ]));
    return $response;
  }

  public function dashboard(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $user = logged_in_user();

    $endpoints = ORM::for_table('micropub_endpoints')->where('user_id', $user->id)->find_many();
    $clients = ORM::for_table('micropub_clients')->where('user_id', $user->id)->find_many();

    $response->getBody()->write(view('dashboard', [
      'title' => 'Micropub Rocks!',
      'endpoints' => $endpoints,
      'clients' => $clients
    ]));
    return $response;
  }

  public function new_client(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $params = $request->getParsedBody();

    $user = logged_in_user();

    $client = ORM::for_table('micropub_clients')
      ->where('user_id', $user->id)
      ->where('name', $params['name'])
      ->find_one();
    if(!$client) {
      $client = ORM::for_table('micropub_clients')->create();
      $client->user_id = $user->id;
      $client->name = $params['name'];
      $client->token = random_string(16);
      $client->created_at = date('Y-m-d H:i:s');
    }

    $client->save();

    return $response->withHeader('Location', '/client/'.$client->token)->withStatus(302);
  }

  public function edit_client(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $params = $request->getParsedBody();

    $user = logged_in_user();

    $client = ORM::for_table('micropub_clients')
      ->where('user_id', $user->id)
      ->where('id', $args['id'])
      ->find_one();

    if(!$client)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    $response->getBody()->write(view('edit-client', [
      'title' => 'Edit Micropub Client - Micropub Rocks!',
      'client' => $client,
    ]));
    return $response;
  }

  public function save_client(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $params = $request->getParsedBody();

    $user = logged_in_user();

    $client = ORM::for_table('micropub_clients')
      ->where('user_id', $user->id)
      ->where('id', $params['id'])
      ->find_one();

    if(!$client)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    $client->name = $params['name'];
    $client->profile_url = $params['profile_url'];
    $client->save();

    return $response->withHeader('Location', '/client/'.$client->token)->withStatus(302);
  }

  public function create_client_access_token(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $user = logged_in_user();

    $client = ORM::for_table('micropub_clients')
      ->where('user_id', $user->id)
      ->where('id', $args['id'])
      ->find_one();

    if(!$client)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    $token = ORM::for_table('client_access_tokens')->create();
    $token->client_id = $client->id;
    $token->created_at = date('Y-m-d H:i:s');
    $token->token = random_string(128);
    $token->save();

    return new JsonResponse([
      'token' => $token->token
    ]);
  }

  public function new_endpoint(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $params = $request->getParsedBody();

    $user = logged_in_user();

    // If they entered an IndieAuth URL, start logging them in
    if(isset($params['me']) && $params['me']) {
      $url = parse_url($params['me']);

      // Do some basic checks to make sure this is a URL
      if(!($url && isset($url['scheme'])
           && in_array($url['scheme'], ['http','https'])
           && isset($url['host']))) {
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
      }

      $me = $params['me'];

      $authorizationEndpoint = IndieAuth\Client::discoverAuthorizationEndpoint($me);
      $tokenEndpoint = IndieAuth\Client::discoverTokenEndpoint($me);
      $micropubEndpoint = IndieAuth\Client::discoverMicropubEndpoint($me);

      if($tokenEndpoint && $micropubEndpoint && $authorizationEndpoint) {
        // Generate a "state" parameter for the request
        $state = IndieAuth\Client::generateStateParameter();
        $_SESSION['auth'] = [
          'state' => $state,
          'me' => $me,
          'token_endpoint' => $tokenEndpoint,
          'micropub_endpoint' => $micropubEndpoint
        ];

        $scope = 'create update delete undelete';
        $authorizationURL = IndieAuth\Client::buildAuthorizationURL($authorizationEndpoint, $me, self::_redirectURI(), Config::$base, $state, $scope);
      } else {
        $authorizationURL = false;
      }

      $response->getBody()->write(view('auth-start', [
        'title' => 'Begin Micropub Authorization',
        'tokenEndpoint' => $tokenEndpoint,
        'authorizationEndpoint' => $authorizationEndpoint,
        'micropubEndpoint' => $micropubEndpoint,
        'me' => $me,
        'meParts' => $url,
        'authorizationURL' => $authorizationURL
      ]));
      return $response;

    } else {
      if(!$params['micropub_endpoint'] || !$params['access_token']) {
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
      }

      // Check if the endpoint already exists and update if so
      $endpoint = ORM::for_table('micropub_endpoints')
        ->where('user_id', $user->id)
        ->where('micropub_endpoint', $params['micropub_endpoint'])
        ->find_one();
      if(!$endpoint) {
        $endpoint = ORM::for_table('micropub_endpoints')->create();
        $endpoint->user_id = $user->id;
        $endpoint->micropub_endpoint = $params['micropub_endpoint'];
        $endpoint->created_at = date('Y-m-d H:i:s');
      }

      $endpoint->access_token = $params['access_token'];
      $endpoint->save();

      return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }
  }

  public function endpoint_callback(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }
    $user = logged_in_user();

    $params = $request->getQueryParams();

    if(!array_key_exists('state', $params)) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Auth Error - Micropub Rocks!',
        'error' => 'Missing State',
        'error_description' => 'The authorization server did not include the "state" parameter. Ensure that the authorization server passes the state parameter back in the redirect.',
      ]));
      return $response;
    }

    if(!isset($_SESSION['auth']['state']) || $_SESSION['auth']['state'] != $params['state']) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Auth Error - Micropub Rocks!',
        'error' => 'Invalid State',
        'error_description' => 'The "state" parameter provided in the redirect did not match the one that this server created when it started the flow.',
      ]));
      return $response;
    }

    $tokenEndpoint = $_SESSION['auth']['token_endpoint'];
    $micropubEndpoint = $_SESSION['auth']['micropub_endpoint'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
      'grant_type' => 'authorization_code',
      'me' => $_SESSION['auth']['me'],
      'code' => $params['code'],
      'redirect_uri' => self::_redirectURI(),
      'client_id' => Config::$base
    )));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Accept: application/json, application/x-www-form-urlencoded;q=0.8'
    ]);
    $tokenResponse = curl_exec($ch);

    $data = @json_decode($tokenResponse, true);
    if(!$data) {
      $data = [];
      parse_str($tokenResponse, $data);
    }

    if(!$data) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Auth Error - Micropub Rocks!',
        'error' => 'Error Requesting Access Token',
        'error_description' => 'The token endpoint sent back an invalid response.',
        'error_debug' => $tokenResponse
      ]));
      return $response;
    }

    if(!isset($data['access_token'])) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Auth Error - Micropub Rocks!',
        'error' => 'Error Requesting Access Token',
        'error_description' => 'The token endpoint response did not include an access token. Below is the response the endpoint returned. Ensure the endpoint returns a property called "access_token".',
        'error_debug' => $tokenResponse
      ]));
      return $response;
    }

    if(!isset($data['me'])) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Auth Error - Micropub Rocks!',
        'error' => 'Error Requesting Access Token',
        'error_description' => 'The token endpoint response did not include the user that authenticated. Below is the response the endpoint returned. Ensure the endpoint returns a property called "me".',
        'error_debug' => $tokenResponse
      ]));
      return $response;
    }

    if(parse_url($data['me'], PHP_URL_HOST) != parse_url($_SESSION['auth']['me'], PHP_URL_HOST)) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Auth Error - Micropub Rocks!',
        'error' => 'Error Authenticating',
        'error_description' => 'The token endpoint returned a URL for a user on a different domain. Ensure the domain of the "me" URL returned from the token endpoint matches the domain of the URL you use to sign in.',
        'error_debug' => $tokenResponse
      ]));
      return $response;
    }

    // Got everything we need, so store the endpoint now

    // Check if the endpoint already exists and update if so
    $endpoint = ORM::for_table('micropub_endpoints')
      ->where('user_id', $user->id)
      ->where('micropub_endpoint', $micropubEndpoint)
      ->find_one();
    if(!$endpoint) {
      $endpoint = ORM::for_table('micropub_endpoints')->create();
      $endpoint->user_id = $user->id;
      $endpoint->micropub_endpoint = $micropubEndpoint;
      $endpoint->created_at = date('Y-m-d H:i:s');
    }

    $endpoint->scope = isset($data['scope']) ? $data['scope'] : '';
    $endpoint->me = isset($data['me']) ? $data['me'] : '';
    $endpoint->access_token = $data['access_token'];
    $endpoint->save();

    // Record that discovery worked
    ImplementationReport::store_server_feature($endpoint->id, 1, 1, 0);

    return $response->withHeader('Location', '/server-tests?endpoint='.$endpoint->id)->withStatus(302);
  }

  public function edit_endpoint(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $params = $request->getParsedBody();

    $user = logged_in_user();

    $endpoint = ORM::for_table('micropub_endpoints')
      ->where('user_id', $user->id)
      ->where('id', $args['id'])
      ->find_one();

    if(!$endpoint)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    $response->getBody()->write(view('edit-endpoint', [
      'title' => 'Edit Micropub Endpoint - Micropub Rocks!',
      'endpoint' => $endpoint,
    ]));
    return $response;
  }

  public function save_endpoint(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $params = $request->getParsedBody();

    $user = logged_in_user();

    $endpoint = ORM::for_table('micropub_endpoints')
      ->where('user_id', $user->id)
      ->where('id', $params['id'])
      ->find_one();

    if(!$endpoint)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    $endpoint->micropub_endpoint = $params['micropub_endpoint'];
    $endpoint->access_token = $params['access_token'];
    $endpoint->save();

    return $response->withHeader('Location', '/server-tests?endpoint='.$endpoint->id)->withStatus(302);
  }

}
