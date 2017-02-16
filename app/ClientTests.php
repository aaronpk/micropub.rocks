<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use ORM;
use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;
use Config;
use Firebase\JWT\JWT;
use Rocks\Redis;

class ClientTests {

  private $user;
  private $client;

  private function _check_permissions(&$request, &$response, $token) {
    session_setup();

    $this->client = ORM::for_table('micropub_clients')
      ->where('token', $token)
      ->find_one();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $this->user = logged_in_user();

    if(!$this->client || $this->client->user_id != $this->user->id)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    return null;    
  }

  public function index(ServerRequestInterface $request, ResponseInterface $response, $args) {
    if($check = $this->_check_permissions($request, $response, $args['token'])) {
      if(!$this->client) 
        return $response->withStatus(404);

      $response = $response->withHeader('Link', '<'.Config::$base.'client/'.$this->client->token.'/auth; rel="authorization_endpoint">');

      $response = $response->withHeader('X-Foo', 'bar');

      // Don't actually redirect here, instead return a public page about the client
      $response->getBody()->write(view('client-info', [
        'title' => $this->client->name,
        'client' => $this->client,
      ]));
      return $response;
    }

    $data = ORM::for_table('tests')
      ->raw_query('SELECT tests.*, test_results.passed FROM tests
        LEFT JOIN test_results ON tests.id = test_results.test_id AND test_results.client_id = :client_id
        WHERE tests.group = :group 
        ORDER BY tests.number', ['client_id'=>$this->client->id, 'group'=>'client'])
      ->find_many();

    $tests = [];
    foreach($data as $test) {
      $tests[$test->number] = [
        'name' => $test->name,
        'passed' => $test->passed
      ];
    }

    $response->getBody()->write(view('client-tests', [
      'title' => 'Micropub Rocks!',
      'client' => $this->client,
      'tests' => $tests
    ]));
    return $response;
  }

  public function auth(ServerRequestInterface $request, ResponseInterface $response, $args) {
    // Require the user is already logged in. A real OAuth server would probably not do this, but it makes 
    // our lives easier for this code.
    // First check that this client exists and belongs to the logged-in user
    $check = $this->_check_permissions($request, $response, $args['token']);
    if(!$this->client) 
      return $response->withStatus(404);

    if($check)
      return $check;

    // Validate the input parameters, providing documentation on any problems
    $params = $request->getQueryParams();

    $errors = [];
    $scope = false;
    $me = false;
    $client_id = false;
    $redirect_uri = false;
    $state = false;

    if(!array_key_exists('response_type', $params)) {
       $errors[] = [
        'title' => 'missing <code>response_type</code>',
        'description' => 'The "response_type" parameter was missing. You must set the <code>response_type</code> parameter to <code>code</code>'
      ];
    } elseif($params['response_type'] != 'code') {
       $errors[] = [
        'title' => 'invalid <code>response_type</code>',
        'description' => 'The "response_type" parameter must be set to <code>code</code>. This indicates to the authorization server that your application is requesting an authorization code.'
      ];
    }

    if(!array_key_exists('scope', $params)) {
      $errors[] = [
        'title' => 'missing <code>scope</code>',
        'description' => 'Your client should request at least the "create" scope. The supported scope values will be dependent on the particular implementation, but the list of "create", "update" and "delete" should be supported by most servers.'
      ];
    } elseif(strpos($params['scope'], 'create') === false) {
      $errors[] = [
        'title' => 'missing "create" <code>scope</code>',
        'description' => 'Your client should request at least the "create" scope. The supported scope values will be dependent on the particular implementation, but the list of "create", "update" and "delete" should be supported by most servers.'
      ];
    } else {
      $scope = $params['scope'];
    }

    if(!array_key_exists('me', $params)) {
      $errors[] = [
        'title' => 'missing <code>me</code>',
        'description' => 'The "me" parameter was missing. You need to provide a parameter "me" with the URL of the user who is signing in.'
      ];
    } elseif(!is_url($params['me'])) {
      $errors[] = [
        'title' => 'invalid <code>me</code>',
        'description' => 'The "me" value provided was not a valid URL. Only http and https schemes are supported.'
      ];
    } else {
      $me = $params['me'];
    }

    if(!array_key_exists('client_id', $params)) {
      $errors[] = [
        'title' => 'missing <code>client_id</code>',
        'description' => 'A "client_id" parameter is required, and must be a full URL that represents your client. Typically this is the home page or other informative page describing the client.'
      ];
    } elseif(!is_url($params['client_id'])) {
      $errors[] = [
        'title' => 'invalid <code>client_id</code>',
        'description' => 'The "client_id" value provided was not a valid URL. Only http and https schemes are supported.'
      ];
    } else {
      $client_id = $params['client_id'];
    }

    if(!array_key_exists('redirect_uri', $params)) {
      $errors[] = [
        'title' => 'missing <code>redirect_uri</code>',
        'description' => 'A "redirect_uri" parameter is required, and must be a full URL that you\'ll be sent to after approving this application.'
      ];
    } elseif(!is_url($params['redirect_uri'])) {
      $errors[] = [
        'title' => 'invalid <code>redirect_uri</code>',
        'description' => 'The "redirect_uri" value provided was not a valid URL. Only http and https schemes are supported.'
      ];
    } else {
      $redirect_uri = $params['redirect_uri'];
    }

    if(!array_key_exists('state', $params)) {
      $errors[] = [
        'title' => 'missing <code>state</code>',
        'description' => 'A "state" parameter is required. Your client should generate a unique state value and provide it in this request, then check that the state matches after the user is redirected back to your application. This helps prevent against attacks.'
      ];
    } else {
      $state = $params['state'];
    }

    // Generate a JWT with all of this data so that we can avoid re-checking it when the ALLOW button is pressed
    if(count($errors) == 0) {
      $jwt = JWT::encode([
        'type' => 'confirm',
        'scope' => $scope,
        'me' => $me,
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'state' => $state,
        'created_at' => time(),
        'exp' => time()+300
      ], Config::$secret);
    } else {
      $jwt = false;
    }

    $response->getBody()->write(view('client-auth', [
      'title' => 'Authorize Application - Micropub Rocks!',
      'errors' => $errors,
      'jwt' => $jwt,
      'client_id' => $client_id,
      'token' => $args['token']
    ]));
    return $response;
  }

  // The "ALLOW" button posts here with a JWT containing all the authorized data
  public function auth_confirm(ServerRequestInterface $request, ResponseInterface $response, $args) {
    // Restrict access to the signed-in user that created this app
    $check = $this->_check_permissions($request, $response, $args['token']);
    if(!$this->client) 
      return $response->withStatus(404);

    if($check)
      return $check;

    $params = $request->getParsedBody();

    if(!isset($params['authorization']))
      return $response->withStatus(400);

    try {
      $data = JWT::decode($params['authorization'], Config::$secret, ['HS256']);
      if($data->type != 'confirm') {
        throw new \Exception();
      }
    } catch(\Exception $e) {
      return $response->withStatus(400);
    }

    // Generate the authorization code
    $data->type = 'authorization_code';
    $data->created_at = time();
    $data->exp = time()+60;
    $data->nonce = random_string(10);
    $code = JWT::encode($data, Config::$secret);

    // Build the redirect URI
    $redirect = add_parameters_to_url($data->redirect_uri, [
      'code' => $code,
      'state' => $data->state,
      'me' => $data->me
    ]);

    return $response->withHeader('Location', $redirect)->withStatus(302);
  }

  public function token(ServerRequestInterface $request, ResponseInterface $response, $args) {
    // Allow un-cookied requests, but do check if this token endpoint exists
    if($check = $this->_check_permissions($request, $response, $args['token'])) {
      if(!$this->client) 
        return $response->withStatus(404);
    }

    $params = $request->getParsedBody();

    // Require grant_type=authorization_code

    if(!isset($params['grant_type'])) {
      return (new JsonResponse([
        'error' => 'invalid_request',
        'error_description' => 'This request must be made with a grant_type parameter set to authorization_code'
      ]))->withStatus(400);
    }

    if($params['grant_type'] != 'authorization_code') {
      return (new JsonResponse([
        'error' => 'unsupported_grant_type',
        'error_description' => 'Only the authorization_code grant is supported'
      ]))->withStatus(400);
    }

    // First parse the authorization code and check if it's expired
    try {
      $data = JWT::decode($params['code'], Config::$secret, ['HS256']);
      if($data->type != 'authorization_code') {
        throw new \Exception();
      }
    } catch(\Firebase\JWT\ExpiredException $e) {
      $response = new JsonResponse([
        'error' => 'invalid_grant',
        'error_description' => 'The authorization code you provided has expired'
      ]);
      return $response->withStatus(400);
    } catch(\Exception $e) {
      $response = new JsonResponse([
        'error' => 'invalid_grant',
        'error_description' => 'The authorization code you provided is not valid',
      ]);
      return $response->withStatus(400);
    }

    // Check that the client ID in the request matches the one in the code

    if(!isset($params['client_id'])) {
      return (new JsonResponse([
        'error' => 'invalid_grant',
        'error_description' => 'You must provide the client_id that was used to generate this authorization code in the request'
      ]))->withStatus(400);
    }

    if($params['client_id'] != $data->client_id) {
      return (new JsonResponse([
        'error' => 'invalid_grant',
        'error_description' => 'The client_id in this request did not match the client_id that was used to generate this authorization code'
      ]))->withStatus(400);
    }

    // Check that the redirect URI in the request matches the one in the code

    if(!isset($params['redirect_uri'])) {
      return (new JsonResponse([
        'error' => 'invalid_grant',
        'error_description' => 'You must provide the redirect_uri that was used to generate this authorization code in the request'
      ]))->withStatus(400);
    }

    if($params['redirect_uri'] != $data->redirect_uri) {
      return (new JsonResponse([
        'error' => 'invalid_grant',
        'error_description' => 'The redirect_uri in this request did not match the redirect_uri that was used to generate this authorization code'
      ]))->withStatus(400);
    }

    $token = ORM::for_table('client_access_tokens')->create();
    $token->client_id = $this->client->id;
    $token->created_at = date('Y-m-d H:i:s');
    $token->token = random_string(128);
    $token->save();

    // Publish to streaming clients that the login was successful
    streaming_publish('client-'.$this->client->token, [
      'action' => 'authorization-complete',
      'client_id' => $data->client_id
    ]);

    return (new JsonResponse([
      'access_token' => $token->token,
      'scope' => 'create',
      'me' => $data->me
    ]))->withStatus(200);
  }

  public function get_test(ServerRequestInterface $request, ResponseInterface $response, $args) {
    // First check that this client exists and belongs to the logged-in user
    $check = $this->_check_permissions($request, $response, $args['token']);
    if(!$this->client) 
      return $response->withStatus(404);

    if($check)
      return $check;

    $test = ORM::for_table('tests')->where('group','client')->where('number',$args['num'])->find_one();

    if(!$test)
      return $response->withHeader('Location', '/client/'.$this->client->token)->withStatus(302);

    $this->client->last_viewed_test = $args['num'];
    $this->client->save();

    if(array_key_exists('key', $args)) {
      list($post_html, $post_debug) = Redis::getPostHTML($this->client->token, $args['num'], $args['key']);
    } else {
      $post_html = '';
      $post_debug = '';
    }

    $response->getBody()->write(view('client-tests/'.$args['num'], [
      'title' => 'Micropub Rocks!',
      'client' => $this->client,
      'test' => $test,
      'post_html' => $post_html,
      'post_debug' => $post_debug,
    ]));
    return $response;
  }

  public function micropub(ServerRequestInterface $request, ResponseInterface $response, $args) {
    // Allow un-cookied requests, but do check if this token endpoint exists
    if($check = $this->_check_permissions($request, $response, $args['token'])) {
      if(!$this->client) 
        return $response->withStatus(404);
    }

    $params = $request->getParsedBody();

    // Check what test was last viewed
    $num = $this->client->last_viewed_test;

    // Include the original info from the request
    // Method
    $request_method = $request->getMethod() . " " . $request->getUri() . " HTTP/" . $request->getProtocolVersion();
    // Headers
    $request_headers = "";
    foreach($request->getHeaders() as $k=>$vs) {
      foreach($vs as $v) {
        $request_headers .= http_header_case($k) . ': ' . $v . "\n";
      }
    }
    // Body
    $request_body = (string)$request->getBody();
    $request_body = str_replace('&', "&\n", $request_body);
    $debug = $request_method . "\n" . $request_headers . "\n" . $request_body;

    switch($num) {
      case 100:
        // Check for required parameters
        $errors = [];
        if(!isset($params['h']))
          $errors[] = 'The request to create an h-entry must include a parameter "h" set to "entry"';

        if(!isset($params['content']))
          $errors[] = 'The request did not include a "content" parameter.';
        elseif(!$params['content'])
          $errors[] = 'The request provided a "content" parameter that was empty. Make sure you include some text in your post.';

        if(count($errors)) {
          $html = view('client-tests/errors', ['errors'=>$errors]);
          $status = 400;
        } else {
          $html = view('client-tests/entry', $params);

          // Cache the HTML so that it can be rendered in a permalink
          $key = random_string(8);
          Redis::storePostHTML($this->client->token, $num, $key, $html, $debug);

          $response = $response->withHeader('Location', Config::$base.'client/'.$this->client->token.'/'.$num.'/'.$key);
          $status = 201;
        }

        streaming_publish('client-'.$this->client->token, [
          'action' => 'client-result',
          'html' => $html,
          'debug' => $debug
        ]);
        break;

      default:
        $status = 400;
        break;
    }

    return $response->withStatus($status);
  }

  public function micropub_get(ServerRequestInterface $request, ResponseInterface $response, $args) {
    // Allow un-cookied requests, but do check if this token endpoint exists
    if($check = $this->_check_permissions($request, $response, $args['token'])) {
      if(!$this->client) 
        return $response->withStatus(404);
    }

    $params = $request->getQueryParams();

    // Check what test was last viewed
    $num = $this->client->last_viewed_test;


    
    
    
    return $response;
  }
}
