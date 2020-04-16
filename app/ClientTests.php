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

      $response = $this->_add_cors_headers($response);

      // Don't actually redirect here, instead return a public page about the client, with
      // the rel tags for discovery
      $response->getBody()->write(view('client-info', [
        'title' => $this->client->name,
        'client' => $this->client,
      ]));
      return $response->withHeader('Link', '<'.Config::$base.'client/'.$this->client->token.'/auth>; rel="authorization_endpoint"')
        ->withAddedHeader('Link', '<'.Config::$base.'client/'.$this->client->token.'/token>; rel="token_endpoint"')
        ->withAddedHeader('Link', '<'.Config::$base.'client/'.$this->client->token.'/micropub>; rel="micropub"');
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
      ImplementationReport::store_client_feature($this->client->id, 4, 1, 0);
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
    } elseif(!is_url_any_scheme($params['redirect_uri'])) {
      $errors[] = [
        'title' => 'invalid <code>redirect_uri</code>',
        'description' => 'The "redirect_uri" value provided was not a valid URL. You can use a URL with http, https or a custom scheme.'
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
      return $this->_conneg_response($request, $response, [
        'error' => 'invalid_request',
        'error_description' => 'This request must be made with a grant_type parameter set to authorization_code'
      ])->withStatus(400);
    }

    if($params['grant_type'] != 'authorization_code') {
      return $this->_conneg_response($request, $response, [
        'error' => 'unsupported_grant_type',
        'error_description' => 'Only the authorization_code grant is supported'
      ])->withStatus(400);
    }

    // First parse the authorization code and check if it's expired
    try {
      $data = JWT::decode($params['code'], Config::$secret, ['HS256']);
      if($data->type != 'authorization_code') {
        throw new \Exception();
      }
    } catch(\Firebase\JWT\ExpiredException $e) {
      return $this->_conneg_response($request, $response, [
        'error' => 'invalid_grant',
        'error_description' => 'The authorization code you provided has expired'
      ])->withStatus(400);
    } catch(\Exception $e) {
      return $this->_conneg_response($request, $response, [
        'error' => 'invalid_grant',
        'error_description' => 'The authorization code you provided is not valid',
      ])->withStatus(400);
    }

    // Check that the client ID in the request matches the one in the code

    if(!isset($params['client_id'])) {
      return $this->_conneg_response($request, $response, [
        'error' => 'invalid_grant',
        'error_description' => 'You must provide the client_id that was used to generate this authorization code in the request'
      ])->withStatus(400);
    }

    if($params['client_id'] != $data->client_id) {
      return $this->_conneg_response($request, $response, [
        'error' => 'invalid_grant',
        'error_description' => 'The client_id in this request did not match the client_id that was used to generate this authorization code'
      ])->withStatus(400);
    }

    // Check that the redirect URI in the request matches the one in the code

    if(!isset($params['redirect_uri'])) {
      return $this->_conneg_response($request, $response, [
        'error' => 'invalid_grant',
        'error_description' => 'You must provide the redirect_uri that was used to generate this authorization code in the request'
      ])->withStatus(400);
    }

    if($params['redirect_uri'] != $data->redirect_uri) {
      return $this->_conneg_response($request, $response, [
        'error' => 'invalid_grant',
        'error_description' => 'The redirect_uri in this request did not match the redirect_uri that was used to generate this authorization code'
      ])->withStatus(400);
    }

    $token = ORM::for_table('client_access_tokens')->create();
    $token->client_id = $this->client->id;
    $token->created_at = date('Y-m-d H:i:s');
    $token->token = random_string(128);
    $token->save();

    $this->client->redirect_uri = $data->redirect_uri;
    $this->client->save();

    // Publish to streaming clients that the login was successful
    streaming_publish('client-'.$this->client->token, [
      'action' => 'authorization-complete',
      'client_id' => $data->client_id
    ]);

    ImplementationReport::store_client_feature($this->client->id, 1, 1, 0);

    return $this->_conneg_response($request, $response, [
      'access_token' => $token->token,
      'scope' => 'create',
      'me' => $data->me
    ])->withStatus(200);
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
      list($post_html, $post_debug, $post_properties) = Redis::getPostHTML($this->client->token, $args['num'], $args['key']);
    } else {
      $post_html = '';
      $post_debug = '';
      $post_properties = false;
    }
    $post_url = false;

    switch($args['num']) {
      case 100:
      case 101:
      case 104:
      case 105:
      case 200:
      case 201:
      case 202:
      case 203:
      case 204:
      case 205:
      case 300:
        $template = 'basic';

        // If the referer is set to the same host as the client, then assume the
        // client redirected here on success so check off that feature.
        if(isset($_SERVER['HTTP_REFERER'])) {
          if(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) == parse_url($this->client->redirect_uri, PHP_URL_HOST)) {
            ImplementationReport::store_client_feature($this->client->id, 14, 1, 0);
          }
        }

        break;

      case 400:
        $post_properties = [
          'content' => ['Hello world']
        ];
        break;

      case 401:
        $post_properties = [
          'content' => ['Hello world'],
          'category' => ['bar']
        ];
        break;

      case 402:
      case 403:
      case 500:
      case 602:
      case 603:
        $post_properties = [
          'content' => ['Hello world'],
          'category' => ['foo','bar']
        ];
        break;

      case 502:
        $post_properties = [
          'content' => ['Hello world'],
          'category' => ['foo','bar']
        ];
        $post_html = '<span></span>';
        break;

      case 600:
      case 601:
      case 700:
        $template = 'basic'; break;

      default:
        $template = 'not-found'; break;
    }

    if($post_properties) {
      // Create a post that will be updated, deleted or queried
      $key = random_string(8);

      if(!$post_html)
        $post_html = view('client-tests/entry', $post_properties);
      Redis::storePostHTML($this->client->token, $args['num'], $key, $post_html, false, $post_properties);

      $post_url = Config::$base.'client/'.$this->client->token.'/'.$args['num'].'/'.$key;
      $template = 'update';
    }

    $response->getBody()->write(view('client-tests/'.$template, [
      'title' => 'Micropub Rocks!',
      'client' => $this->client,
      'test' => $test,
      'post_html' => $post_html,
      'post_debug' => $post_debug,
      'post_url' => $post_url
    ]));
    return $response;
  }

  public function micropub(ServerRequestInterface $request, ResponseInterface $response, $args) {
    // Allow un-cookied requests, but do check if this token endpoint exists
    if($check = $this->_check_permissions($request, $response, $args['token'])) {
      if(!$this->client)
        return $response->withStatus(404);
    }

    $response = $this->_add_cors_headers($response);

    $errors = [];
    $status = 400;

    $content_type = $request->getHeaderLine('Content-Type');
    $access_token = false;
    $access_token_in_post_body = false;
    if(preg_match('/application\/x-www-form-urlencoded/', $content_type)) {
      $params = $request->getParsedBody();
      if(array_key_exists('access_token', $params)) {
        $access_token = $params['access_token'];
        $access_token_in_post_body = true;
      }
    }

    // Check the access token
    $authorization = $request->getHeaderLine('Authorization');
    if(preg_match('/^Bearer (.+)$/', $authorization, $match) || $access_token) {
      $access_token = $access_token_in_post_body ? $access_token : $match[1];
      $check = ORM::for_table('client_access_tokens')
        ->where('client_id', $this->client->id)
        ->where('token', $access_token)
        ->find_one();
      if(!$check) {
        $errors[] = 'The access token provided was not valid.';
        $status = 403;
      } else {
        $check->last_used = date('Y-m-d H:i:s');
        $check->save();
        if($access_token_in_post_body) {
          ImplementationReport::store_client_feature($this->client->id, 3, 1, 0);
        }
        if($match) {
          ImplementationReport::store_client_feature($this->client->id, 2, 1, 0);
        }
      }
    } else {
      $errors[] = 'The client must send the access token in the Authorization header in the format <code>Authorization: Bearer xxxxx</code>, or in a form-encoded body parameter <code>access_token</code>. The header method is recommended.';
    }

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
    $debug = $request_method . "\n" . $request_headers . "\n" . str_replace('&', "&\n", $request_body);

    // Bail out now if there were any authentication errors
    if(count($errors)) {
      $html = view('client-tests/errors', ['errors'=>$errors]);
      streaming_publish('client-'.$this->client->token, [
        'action' => 'client-result',
        'html' => $html,
        'debug' => $debug
      ]);
      return $response->withStatus($status);
    }

    if($content_type == 'application/json') {
      $params = @json_decode($request_body, true);
      $format = 'json';
    } elseif(preg_match('/^multipart\/form-data; boundary=.+$/', $content_type)) {
      $params = $request->getParsedBody();
      $format = 'multipart';
    } else {
      $params = $request->getParsedBody();
      $format = 'form';
    }

    // Check what test was last viewed
    $num = $this->client->last_viewed_test;

    $html = false;
    $features = [];

    switch($num) {
      case 100:
        $features = [5];
        if($this->_requireFormEncoded($format, $errors)) {
          if($this->_requireFormHEntry($params, $errors)) {
            if(!isset($params['content']))
              $errors[] = 'The request did not include a "content" parameter.';
            elseif(!$params['content'])
              $errors[] = 'The request provided a "content" parameter that was empty. Make sure you include some text in your post.';
            elseif(!is_string($params['content']))
              $errors[] = 'To pass this test you must provide content as a string';
            $properties = $params;
          }
        }

        break;

      case 200:
        $features = [6];
        if($this->_requireJSONEncoded($format, $errors)) {
          if($this->_requireJSONHEntry($params, $errors)) {
            if($properties=$this->_validateJSONProperties($params, $errors)) {
              if(!isset($properties['content']))
                $errors[] = 'The request did not include a "content" parameter.';
              elseif(!$properties['content'])
                $errors[] = 'The request provided a "content" parameter that was empty. Make sure you include some text in your post.';
              elseif(!is_string($properties['content'][0]))
                $errors[] = 'To pass this test you must provide content as a string';
            }
          }
        }

        break;

      case 101:
        $features = [7];
        if($this->_requireFormEncoded($format, $errors)) {
          if($this->_requireFormHEntry($params, $errors)) {
            if(!isset($params['category']))
              $errors[] = 'The request did not include a "category" parameter.';
            elseif(!$params['category'])
              $errors[] = 'The request provided a "category" parameter that was empty. Make sure you include two or more categories.';
            elseif(!is_array($params['category']))
              $errors[] = 'The "category" parameter in the request was sent as a string. Ensure you are using the form-encoded square bracket notation to specify multiple values.';
            elseif(count($params['category']) < 2)
              $errors[] = 'The request provided the "category" parameter as an array, but only had one value. Ensure your request contains multiple values for this parameter.';
            $properties = $params;
          }
        }

        break;

      case 201:
        $features = [8];
        if($this->_requireJSONEncoded($format, $errors)) {
          if($this->_requireJSONHEntry($params, $errors)) {
            if($properties=$this->_validateJSONProperties($params, $errors)) {
              if(!isset($properties['category']))
                $errors[] = 'The request did not include a "category" parameter.';
              elseif(!$properties['category'])
                $errors[] = 'The request provided a "category" parameter that was empty. Make sure you include two or more categories.';
              elseif(!is_array($properties['category']))
                $errors[] = 'The "category" parameter in the request was sent as a string. Ensure you are using the form-encoded square bracket notation to specify multiple values.';
              elseif(count($properties['category']) < 2)
                $errors[] = 'The request provided the "category" parameter as an array, but only had one value. Ensure your request contains multiple values for this parameter.';
            }
          }
        }

        break;

      case 104:
        $features = [11];
        if($this->_requireFormEncoded($format, $errors)) {
          if($this->_requireFormHEntry($params, $errors)) {
            if(!isset($params['photo']) && !isset($params['video']) && !isset($params['audio'])) {
              $errors[] = 'The request did not include a "photo", "video" or "audio" parameter.';
            } else {
              if(isset($params['photo'])) {
                $prop = 'photo';
              } elseif(isset($params['video'])) {
                $prop = 'video';
              } elseif(isset($params['audio'])) {
                $prop = 'audio';
              }

              if(!isset($params[$prop]))
                $errors[] = 'The request did not include a "'.$prop.'" parameter.';
              elseif(!$params[$prop])
                $errors[] = 'The "'.$prop.'" parameter was empty';
              elseif(!is_string($params[$prop]))
                $errors[] = 'The "'.$prop.'" parameter provided was not a string. Ensure the client is sending only one URL in the parameter';
              elseif(!is_url($params[$prop]))
                $errors[] = 'The value of the "'.$prop.'" parameter does not appear to be a URL.';
            }
            $properties = $params;
          }
        }

        break;

      case 105:
        $features = [15];
        if($this->_requireFormEncoded($format, $errors)) {
          if($this->_requireFormHEntry($params, $errors)) {
            if(!isset($params['mp-syndicate-to']))
              $errors[] = 'The request did not include a "mp-syndicate-to" parameter.';
            elseif(!$params['mp-syndicate-to'])
              $errors[] = 'The "mp-syndicate-to" parameter was empty';
            $properties = $params;
            if(!is_array($properties['mp-syndicate-to']))
              $properties['mp-syndicate-to'] = [$properties['mp-syndicate-to']];
            $passed = false;
            foreach($properties['mp-syndicate-to'] as $syn) {
              if($syn == 'https://news.indieweb.org/en')
                $passed = true;
            }
            if(!$passed) {
              $errors[] = 'The "mp-syndicate-to" parameter was not set to one of the valid options returned by the endpoint.';
            }
          }
        }

        break;

      case 202:
        $features = [33];
        if($this->_requireJSONEncoded($format, $errors)) {
          if($this->_requireJSONHEntry($params, $errors)) {
            if($properties=$this->_validateJSONProperties($params, $errors)) {
              if(!isset($properties['content']))
                $errors[] = 'The request did not include a "content" parameter.';
              elseif(!$properties['content'])
                $errors[] = 'The request provided a "content" parameter that was empty. Make sure you include some HTML in your post.';
              elseif(!is_array($properties['content'][0]))
                $errors[] = 'To pass this test you must provide content as an object.';
              elseif(!array_key_exists('html', $properties['content'][0]))
                $errors[] = 'The "content" parameter must be an object containing a key "html".';
            }
          }
        }
        break;

      case 203:
        $features = [12];
        if($this->_requireJSONEncoded($format, $errors)) {
          if($this->_requireJSONHEntry($params, $errors)) {
            if($properties=$this->_validateJSONProperties($params, $errors)) {
              if(!isset($properties['photo']) && !isset($properties['video']) && !isset($properties['audio'])) {
                $errors[] = 'The request did not include a "photo", "video" or "audio" parameter.';
              } else {
                if(isset($properties['photo'])) {
                  $prop = 'photo';
                } elseif(isset($properties['video'])) {
                  $prop = 'video';
                } elseif(isset($properties['audio'])) {
                  $prop = 'audio';
                }

                if(!$properties[$prop])
                  $errors[] = 'The "'.$prop.'" parameter was empty';
                elseif(!array_key_exists(0, $properties[$prop]))
                  $errors[] = 'The value of the "'.$prop.'" parameter must be an array containing the file URL.';
                else {
                  if(is_url($properties[$prop][0])) {
                    // okay
                  } elseif(is_array($properties['photo'][0])) {
                    if(!array_key_exists('value', $properties['photo'][0]) || !is_url($properties['photo'][0]['value']))
                      $errors[] = 'When the "photo" property is not a plain URL, it must be an object with a "value" key containing the photo URL. See the section on posting images with alt text.';
                  } else
                    $errors[] = 'The value of the "'.$prop.'" parameter was not a URL or image with alt text.';
                }
              }
            }
          }
        }
        break;

      case 204:
        $features = [9];
        if($this->_requireJSONEncoded($format, $errors)) {
          if($properties=$this->_validateJSONProperties($params, $errors)) {
            // Check that at least one of the values is a mf2 object
            $has_nested_object = false;
            foreach($properties as $k=>$values) {
              if(is_array($values)) {
                foreach($values as $v) {
                  if(isset($v['type']) && is_array($v['type'])
                    && isset($v['type'][0]) && preg_match('/^h-.+/', $v['type'][0])) {
                    if(isset($v['properties']) && is_array($v['properties'])) {
                      foreach($v['properties'] as $v2) {
                        if(is_array($v2) && array_key_exists(0, $v2)) {
                          $has_nested_object = true;
                        }
                      }
                    }
                  }
                }
              }
            }
            if(!$has_nested_object) {
              $errors[] = 'None of the values provided look like nested Microformats 2 objects.';
            }
          }
        }
        break;

      case 205:
        $features = [13];
        if($this->_requireJSONEncoded($format, $errors)) {
          if($this->_requireJSONHEntry($params, $errors)) {
            if($properties=$this->_validateJSONProperties($params, $errors)) {
              if(!isset($properties['photo']))
                $errors[] = 'The request did not include a "photo" parameter.';
              elseif(!$properties['photo'])
                $errors[] = 'The "photo" parameter was empty';
              elseif(!is_array($properties['photo'][0]))
                $errors[] = 'The value of the "photo" parameter does not appear to include alt text.';
              else {
                $photo = $properties['photo'][0];
                if(!isset($photo['value'])) {
                  $errors[] = 'The photo value is missing a URL. Provide the URL in the "value" property.';
                }
                if(!isset($photo['alt'])) {
                  $errors[] = 'The photo value is missing alt text. Provide the image alt text in the "alt" property.';
                }
              }
            }
          }
        }
        break;

      case 300:
        $features = [10];
        if($this->_requireMultipartEncoded($format, $errors)) {
          if($this->_requireFormHEntry($params, $errors)) {
            $files = $request->getUploadedFiles();

            if(!isset($files['photo']) && !isset($files['video']) && !isset($files['audio'])) {
              $errors[] = 'You must upload a file in a part named "photo", "video" or "audio".';
            } else {
              if(isset($files['photo'])) {
                $file = $files['photo'];
                $param = 'photo';
                $name = 'photo.jpg';
              } elseif(isset($files['video'])) {
                $file = $files['video'];
                $param = 'video';
                $name = 'video.mp4';
              } elseif(isset($files['audio'])) {
                $file = $files['audio'];
                $param = 'audio';
                $name = 'audio.mp3';
              }

              $file_data = $file->getStream()->__toString();

              $key = random_string(8);
              Redis::storePostImage($this->client->token, $num, $key, $file_data);

              $params[$param] = Config::$base.'client/'.$this->client->token.'/'.$num.'/'.$key.'/'.$name;

              $properties = $params;
            }
          }
        }

        break;

      case 400:
        $features = [18];
        if($this->_requireJSONEncoded($format, $errors)) {
          list($post_html, $post_raw, $post_properties, $key) = $this->_requireUpdateAction($params, $num, $errors);
          if($post_html) {
            if(!isset($params['replace'])) {
              $errors[] = 'Include a property <code>replace</code> containing the list of properties to replace.';
            } elseif(!is_array($params['replace'])) {
              $errors[] = 'The <code>replace</code> property must be an object containing the list of properties to replace.';
            } elseif(!array_key_exists('content', $params['replace'])) {
              $errors[] = 'This test requires replacing the value of the "content" property.';
            } elseif(!is_array($params['replace']['content']) || !array_key_exists(0, $params['replace']['content'])) {
              $errors[] = 'Remember that the values of everything you are replacing must be an array, even if there is only a single value.';
            } elseif(!is_string($params['replace']['content'][0])) {
              $errors[] = 'This test requires replacing the content of this post with a string.';
            } elseif(count(explode(' ', $params['replace']['content'][0])) < 3) {
              $errors[] = 'This test requires replacing the content of this post with a string containing 3 words or more.';
            } else {
              $properties = $post_properties;
              $properties['content'] = $params['replace']['content'][0];
              $existing_key = $key;
            }

          }
        }
        break;

      case 401:
        $features = [19];
        if($this->_requireJSONEncoded($format, $errors)) {
          list($post_html, $post_raw, $post_properties, $key) = $this->_requireUpdateAction($params, $num, $errors);
          if($post_html) {
            if(!isset($params['add'])) {
              $errors[] = 'Include a property <code>add</code> containing the list of properties to add.';
            } elseif(!is_array($params['add']) || array_key_exists(0, $params['add'])) {
              $errors[] = 'The <code>add</code> property must be an object containing the list of values to add.';
            } elseif(!array_key_exists('category', $params['add'])) {
              $errors[] = 'This test requires adding a value to the "category" property.';
            } elseif(!is_array($params['add']['category']) || !array_key_exists(0, $params['add']['category'])) {
              $errors[] = 'Remember that the values of everything you are adding must be an array, even if there is only a single value.';
            } elseif(!is_string($params['add']['category'][0])) {
              $errors[] = 'This test requires adding a value to the category property.';
            } elseif($params['add']['category'][0] !== 'foo') {
              $errors[] = 'This test requires adding the value "foo" to the category property.';
            } else {
              $properties = $post_properties;
              $properties['category'][] = $params['add']['category'][0];
              $existing_key = $key;
            }
          }
        }
        break;

      case 402:
        $features = [20];
        if($this->_requireJSONEncoded($format, $errors)) {
          list($post_html, $post_raw, $post_properties, $key) = $this->_requireUpdateAction($params, $num, $errors);
          if($post_html) {
            if(!isset($params['delete'])) {
              $errors[] = 'Include a property <code>delete</code> containing the list of properties to remove.';
            } elseif(!is_array($params['delete']) || array_key_exists(0, $params['delete'])) {
              $errors[] = 'The <code>delete</code> property must be an object containing the list of values to remove.';
            } elseif(!array_key_exists('category', $params['delete'])) {
              $errors[] = 'This test requires removing a value from the "category" property.';
            } elseif(!is_array($params['delete']['category']) || !array_key_exists(0, $params['delete']['category'])) {
              $errors[] = 'Remember that the values of everything you are removing must be an array, even if there is only a single value.';
            } elseif(!is_string($params['delete']['category'][0])) {
              $errors[] = 'This test requires removing a value from the category property.';
            } elseif($params['delete']['category'][0] !== 'foo') {
              $errors[] = 'This test requires removing the value "foo" from the category property.';
            } else {
              $properties = $post_properties;
              $properties['category'] = array_diff($properties['category'], [$params['delete']['category'][0]]);
              $existing_key = $key;
            }
          }
        }
        break;

      case 403:
        $features = [21];
        if($this->_requireJSONEncoded($format, $errors)) {
          list($post_html, $post_raw, $post_properties, $key) = $this->_requireUpdateAction($params, $num, $errors);
          if($post_html) {
            if(!isset($params['delete'])) {
              $errors[] = 'Include a property <code>delete</code> containing the name of the property to remove.';
            } elseif(!is_array($params['delete']) || !array_key_exists(0, $params['delete'])) {
              $errors[] = 'The <code>delete</code> property must be an array containing the list of properties to remove.';
            } elseif(!in_array('category', $params['delete'])) {
              $errors[] = 'This test requires removing the "category" property. Ensure the string "category" is in the list of properties to remove.';
            } else {
              $properties = $post_properties;
              unset($properties['category']);
              $existing_key = $key;
            }
          }
        }
        break;

      case 500:
        if($format == 'json') {
          $features = [24];
          if($this->_requireJSONEncoded($format, $errors)) {
            list($post_html, $post_raw, $post_properties, $key) = $this->_requireDeleteAction($params, $num, $errors);
            if($post_html) {
              $post_html = '';
              $properties = false;
            }
          }
        } elseif($format == 'form') {
          $features = [23];
          if($this->_requireFormEncoded($format, $errors)) {
            list($post_html, $post_raw, $post_properties, $key) = $this->_requireDeleteAction($params, $num, $errors);
            if($post_html) {
              $post_html = '';
              $properties = false;
            }
          }
        }
        break;

      case 502:
        if($format == 'json') {
          $features = [26];
          if($this->_requireJSONEncoded($format, $errors)) {
            list($post_html, $post_raw, $post_properties, $key) = $this->_requireUndeleteAction($params, $num, $errors);
            if($post_html) {
              $properties = $post_properties;
            }
          }
        } elseif($format == 'form') {
          $features = [25];
          if($this->_requireFormEncoded($format, $errors)) {
            list($post_html, $post_raw, $post_properties, $key) = $this->_requireUndeleteAction($params, $num, $errors);
            if($post_html) {
              $properties = $post_properties;
            }
          }
        }
        break;

      case 602:
      case 603:
        $errors[] = 'Query requests must be sent via GET, not POST';
        break;

      case 700:
        if($format == 'json') {

          // This is just here so that the post will appear in the interface for test 700.
          // This does not actually imply the feature is or is not implemented.
          if($this->_requireJSONEncoded($format, $errors)) {
            if($this->_requireJSONHEntry($params, $errors)) {
              if($properties=$this->_validateJSONProperties($params, $errors)) {
                if(!isset($properties['photo']))
                  $errors[] = 'The request did not include a "photo" parameter.';
                elseif(!$properties['photo'])
                  $errors[] = 'The "photo" parameter was empty';
                elseif(!array_key_exists(0, $properties['photo']) || !is_url($properties['photo'][0]))
                  $errors[] = 'The value of the "photo" parameter does not appear to be a URL.';
              }
            }
          }

        } elseif($format == 'form') {

          // This is just here so that the post will appear in the interface for test 700.
          // This does not actually imply the feature is or is not implemented.
          if($this->_requireFormEncoded($format, $errors)) {
            if($this->_requireFormHEntry($params, $errors)) {
              if(!isset($params['photo']))
                $errors[] = 'The request did not include a "photo" parameter.';
              elseif(!$params['photo'])
                $errors[] = 'The "photo" parameter was empty';
              elseif(!is_string($params['photo']))
                $errors[] = 'The "photo" parameter provided was not a string. Ensure the client is sending only one URL in the photo parameter';
              elseif(!is_url($params['photo']))
                $errors[] = 'The value of the "photo" parameter does not appear to be a URL.';
              $properties = $params;
            }
          }

        } else {
          $errors[] = 'You should not send file upload to the Micropub endpoint if you uploaded the photo to the Media Endpoint';
          $features[] = 16;
        }

        break;

      default:
        $status = 500;
        $errors[] = 'This test is not yet implemented';
        break;
    }


    $test = ORM::for_table('tests')
      ->where('group', 'client')
      ->where('number', $num)
      ->find_one();
    $last = ORM::for_table('test_results')
      ->where('client_id', $this->client->id)
      ->where('test_id', $test->id)
      ->find_one();
    if(!$last) {
      $last = ORM::for_table('test_results')->create();
      $last->client_id = $this->client->id;
      $last->test_id = $test->id;
      $last->created_at = date('Y-m-d H:i:s');
    }
    $last->passed = count($errors) == 0 ? 1 : -1;
    $last->response = $debug;
    $last->last_result_at = date('Y-m-d H:i:s');
    $last->save();

    foreach($features as $feature) {
      ImplementationReport::store_client_feature($this->client->id, $feature, count($errors) == 0 ? 1 : -1, $test->id);
    }


    if(count($errors)) {
      $html = view('client-tests/errors', ['errors'=>$errors]);
      $status = 400;
    } else {
      if($properties)
        $html = view('client-tests/entry', $properties);
      else
        $html = '';
      $html = view('client-tests/success', ['num'=>$num]).$html;

      // Cache the HTML so that it can be rendered in a permalink
      if(isset($existing_key))
        $key = $existing_key;
      else
        $key = random_string(8);
      Redis::storePostHTML($this->client->token, $num, $key, $html, $debug);

      $response = $response->withHeader('Location', Config::$base.'client/'.$this->client->token.'/'.$num.'/'.$key);
      $status = 201;
    }

    if($html) {
      streaming_publish('client-'.$this->client->token, [
        'action' => 'client-result',
        'html' => $html,
        'debug' => $debug
      ]);
    }

    return $response->withStatus($status);
  }

  public function media_endpoint(ServerRequestInterface $request, ResponseInterface $response, $args) {
    // Allow un-cookied requests, but do check if this token endpoint exists
    if($check = $this->_check_permissions($request, $response, $args['token'])) {
      if(!$this->client)
        return $response->withStatus(404);
    }

    $response = $this->_add_cors_headers($response);

    list($errors, $status) = $this->_check_access_token_header($request);

    if(count($errors)) {
      return $response->withStatus($status);
    }

    // Check what test was last viewed
    $num = $this->client->last_viewed_test;


    $content_type = $request->getHeaderLine('Content-Type');
    if(preg_match('/^multipart\/form-data; boundary=.+$/', $content_type))
      $format = 'multipart';
    else
      $format = false;


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



    $url = false;

    $status = 400;

    if($this->_requireMultipartEncoded($format, $errors)) {
      $files = $request->getUploadedFiles();

      if(!isset($files['file'])) {
        $errors[] = 'You must upload a file in a part named "file".';
      } else {
        $file = $files['file'];
        $img = $file->getStream()->__toString();

        $key = random_string(8);
        Redis::storePostImage($this->client->token, $num, $key, $img);

        $url = Config::$base.'client/'.$this->client->token.'/'.$num.'/'.$key.'/file';
      }
    }

    $html = false;

    if($url && $num) {
      switch($num) {
        case 700:
          $html = '<div class="post-container"><img src="'.$url.'" style="width:100%"></div>';
          $features = [16];
          break;
      }
    }


    $test = ORM::for_table('tests')
      ->where('group', 'client')
      ->where('number', $num)
      ->find_one();
    if($test) {
      $last = ORM::for_table('test_results')
        ->where('client_id', $this->client->id)
        ->where('test_id', $test->id)
        ->find_one();
      if(!$last) {
        $last = ORM::for_table('test_results')->create();
        $last->client_id = $this->client->id;
        $last->test_id = $test->id;
        $last->created_at = date('Y-m-d H:i:s');
      }
      $last->passed = count($errors) == 0 ? 1 : -1;
      $last->response = $debug;
      $last->last_result_at = date('Y-m-d H:i:s');
      $last->save();

      foreach($features as $feature) {
        ImplementationReport::store_client_feature($this->client->id, $feature, count($errors) == 0 ? 1 : -1, $test->id);
      }
    }

    if($html) {
      streaming_publish('client-'.$this->client->token, [
        'action' => 'client-result',
        'html' => $html,
        'debug' => $debug
      ]);
    }

    if($url)
      return $response->withHeader('Location', $url)->withStatus(201);
    else
      return $response->withStatus($status);
  }

  public function get_image(ServerRequestInterface $request, ResponseInterface $response, $args) {
    // First check that this client exists and belongs to the logged-in user
    $test = ORM::for_table('tests')->where('group','client')->where('number',$args['num'])->find_one();

    $img = Redis::getPostImage($args['token'], $args['num'], $args['key']);
    if($img) {
      $response = $response->withHeader('Content-Type', 'image/jpeg');
      $response->getBody()->write($img);
      return $response;
    } else {
      return $response->withStatus(404);
    }
  }

  public function get_audio(ServerRequestInterface $request, ResponseInterface $response, $args) {
    // First check that this client exists and belongs to the logged-in user
    $test = ORM::for_table('tests')->where('group','client')->where('number',$args['num'])->find_one();

    $img = Redis::getPostImage($args['token'], $args['num'], $args['key']);
    if($img) {
      $response = $response->withHeader('Content-Type', 'audio/mpeg');
      $response->getBody()->write($img);
      return $response;
    } else {
      return $response->withStatus(404);
    }
  }

  public function get_video(ServerRequestInterface $request, ResponseInterface $response, $args) {
    // First check that this client exists and belongs to the logged-in user
    $test = ORM::for_table('tests')->where('group','client')->where('number',$args['num'])->find_one();

    $img = Redis::getPostImage($args['token'], $args['num'], $args['key']);
    if($img) {
      $response = $response->withHeader('Content-Type', 'video/mp4');
      $response->getBody()->write($img);
      return $response;
    } else {
      return $response->withStatus(404);
    }
  }

  private function _requireFormEncoded($format, &$errors) {
    if($format != 'form') {
      $errors[] = 'The request was not a form-encoded request. Ensure you are sending a proper form-encoded request with valid parameters.';
      return false;
    } {
      return true;
    }
  }

  private function _requireMultipartEncoded($format, &$errors) {
    if($format != 'multipart') {
      $errors[] = 'The request was not a multipart-encoded request. Ensure you are sending a proper multipart request with valid parameters.';
      return false;
    } {
      return true;
    }
  }

  private function _requireJSONEncoded($format, &$errors) {
    if($format != 'json') {
      $errors[] = 'The request was not a JSON request. Ensure you are sending a proper JSON request with valid parameters.';
      return false;
    } else {
      return true;
    }
  }

  private function _requireFormHEntry($params, &$errors) {
    if(!isset($params['h']) || $params['h'] != 'entry') {
      $errors[] = 'The request to create an h-entry must include a parameter "h" set to "entry"';
      return false;
    } else {
      return true;
    }
  }

  private function _requireJSONHEntry($params, &$errors) {
    if(!isset($params['type']) || !is_array($params['type']) || !in_array('h-entry', $params['type'])) {
      $errors[] = 'The request to create an h-entry must include a parameter "type" set to ["h-entry"]';
      return false;
    } else {
      return true;
    }
  }

  private function _validateJSONProperties($params, &$errors) {
    if(!isset($params['properties'])) {
      $errors[] = 'JSON requests must send a Microformats 2 object where the values are in a key named "properties".';
      return false;
    }

    $properties = $params['properties'];

    $has_error = false;
    foreach($properties as $k=>$v) {
      if(!is_array($v) || (count($v) > 0 && !array_key_exists(0, $v))) {
        $errors[] = 'The "'.$k.'" parameter was not provided as an array. In JSON format, all values are arrays, even if there is only one value.';
        $has_error = true;
      }
    }

    if($has_error) {
      return false;
    } else {
      return $properties;
    }
  }

  private function _requireDeleteAction($params, $num, &$errors) {
    if(!isset($params['action']) || $params['action'] !== 'delete')
      $errors[] = 'To make a delete request, include <code>"action":"delete"</code> in the JSON request, or <code>action=delete</code> in the form-encoded request.';
    elseif(!isset($params['url']))
      $errors[] = 'A delete request must specify the URL of the post that is being deleted. Include a parameter <code>"url"</code> with the URL of the post you\'re deleting.';
    else
      return $this->_loadPostFromRedis($params['url'], $num, $errors);
  }

  private function _requireUndeleteAction($params, $num, &$errors) {
    if(!isset($params['action']) || $params['action'] !== 'undelete')
      $errors[] = 'To make an undelete request, include <code>"action":"undelete"</code> in the JSON request, or <code>action=undelete</code> in the form-encoded request.';
    elseif(!isset($params['url']))
      $errors[] = 'An undelete request must specify the URL of the post that is being undeleted. Include a parameter <code>"url"</code> with the URL of the post you\'re undeleting.';
    else
      return $this->_loadPostFromRedis($params['url'], $num, $errors);
  }

  private function _requireUpdateAction($params, $num, &$errors) {
    if(!isset($params['action']) || $params['action'] !== 'update')
      $errors[] = 'To make an update request, include <code>"action":"update"</code> in the JSON request.';
    elseif(!isset($params['url']))
      $errors[] = 'An update request must specify the URL of the post that is being updated. Include a parameter <code>"url"</code> with the URL of the post you\'re updating.';
    else
      return $this->_loadPostFromRedis($params['url'], $num, $errors);
  }

  private function _loadPostFromRedis($url, $num, &$errors) {
    $regex = '/'.str_replace('/','\\/',Config::$base).'client\/'.$this->client->token.'\/'.$num.'\/([a-zA-Z0-9]+)/';
    if(!is_url($url)) {
      $errors[] = 'The value of the <code>"url"</code> parameter does not look like a URL. Ensure you are sending the full URL of the post to update.';
    } elseif(!preg_match($regex, $url, $match)) {
      $errors[] = 'The URL provided is not supported. Verify that you are sending the correct URL based on the test you are trying to pass.';
    } else {
      $key = $match[1];
      list($post_html, $post_raw, $post_properties) = Redis::getPostHTML($this->client->token, $num, $key);
      if(!$post_html) {
        $errors[] = 'The post you are trying to edit has expired.';
      } else {
        return [$post_html, $post_raw, $post_properties, $key];
      }
    }
    return [false,false,false,false];
  }

  private function _getPostProperties($url, $params) {
    if(preg_match('/client\/([a-zA-Z0-9]+)\/(\d+)\/([a-zA-Z0-9]+)/', $url, $match)) {
      list($match, $client_token, $num, $key) = $match;
      list($post_html, $post_raw, $post_properties) = Redis::getPostHTML($client_token, $num, $key);
      if($post_html) {
        if(isset($params['properties']) && is_array($params['properties'])) {
          $feature = 32;
          $post_properties = array_filter($post_properties, function($k) use($params) {
            return in_array($k, $params['properties']);
          }, ARRAY_FILTER_USE_KEY);
        } else {
          $feature = 31;
        }
        ImplementationReport::store_client_feature($this->client->id, $feature, 1, 0);

        return $post_properties;
      }
    }
    return false;
  }

  private function _check_access_token_header($request) {
    $errors = [];
    $status = 400;

    $authorization = $request->getHeaderLine('Authorization');
    if(preg_match('/^Bearer (.+)$/', $authorization, $match)) {
      $access_token = $match[1];
      $check = ORM::for_table('client_access_tokens')
        ->where('client_id', $this->client->id)
        ->where('token', $access_token)
        ->find_one();
      if(!$check) {
        $errors[] = 'The access token provided was not valid.';
        $status = 403;
      } else {
        $check->last_used = date('Y-m-d H:i:s');
        $check->save();
      }
    } else {
      $errors[] = 'The client must send the access token in the Authorization header in the format <code>Authorization: Bearer xxxxx</code>';
    }

    return [$errors, $status];
  }

  public function micropub_get(ServerRequestInterface $request, ResponseInterface $response, $args) {
    // Allow un-cookied requests, but do check if this token endpoint exists
    if($check = $this->_check_permissions($request, $response, $args['token'])) {
      if(!$this->client)
        return $response->withStatus(404);
    }

    $response = $this->_add_cors_headers($response);

    $params = $request->getQueryParams();

    list($errors, $status) = $this->_check_access_token_header($request);

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
    $debug = $request_method . "\n" . $request_headers;

    // Bail out now if there were any authentication errors
    if(count($errors)) {
      $html = view('client-tests/errors', ['errors'=>$errors]);
      streaming_publish('client-'.$this->client->token, [
        'action' => 'client-result',
        'html' => $html,
        'debug' => $debug
      ]);
      return $response->withStatus($status);
    }


    // Check what test was last viewed
    $num = $this->client->last_viewed_test;

    $test = false;
    if($num) {
      $test = ORM::for_table('tests')
        ->where('group', 'client')
        ->where('number', $num)
        ->find_one();
    }

    $status = 400;
    $html = '';

    $features = [];

    switch($num) {
      case 602:
      case 603:
        $features[] = ($num == 602 ? 31 : 32);

        if(!isset($params['q']) || !isset($params['url'])) {
          $errors[] = 'The source query must contain two parameters, <code>q=source</code> and <code>url</code>';
        } elseif($params['q'] != 'source') {
          $errors[] = 'The source query must contain <code>q=source</code>';
        } else {
          if($num == 602) {
            if(isset($params['properties'])) {
              $errors[] = 'To query all properties of a post, do not request any specific properties.';
            }
            $post_properties = $this->_getPostProperties($params['url'], $params);
          } else {
            if(!isset($params['properties'])) {
              $errors[] = 'To query specific properties of a post, include one or more <code>properties[]</code> parameters.';
            } else {
              if(!is_array($params['properties']))
                $params['properties'] = [$params['properties']];

              if(!array_key_exists(0, $params['properties'])) {
                $errors[] = 'Something looks wrong. Ensure you are only requesting string values as properties.';
              } else {
                $check = true;
                foreach($params['properties'] as $p) {
                  if(!is_string($p)) {
                    $check = false;
                  }
                }
                if(!$check) {
                  $errors[] = 'One or more values were not a string. Ensure you only include string values when requesting properties.';
                } else {
                  $post_properties = $this->_getPostProperties($params['url'], $params);
                  if(!is_array($post_properties)) {
                    $errors[] = 'The post URL provided was not found';
                  }
                }
              }
            }
          }

        }

        if(count($errors) == 0) {
          $html = view('client-tests/success', ['num'=>$num]);
          $status = 200;
        }

        break;

      case 400:
      case 401:
      case 402:
      case 403:
        if(isset($params['q']) && $params['q'] == 'source') {
          if(isset($params['url'])) {
            $url = $params['url'];
            $post_properties = $this->_getPostProperties($url, $params);
            if($post_properties) {
              $response = (new JsonResponse([
                'properties' => $post_properties
              ]));
              return $response;
            }
          }
        }

        break;

      case 600:
        $features[] = 27;

        if(count($params) > 1) {
          $errors[] = 'The configuration query must have only one query parameter, q=config';
        } else if(!array_key_exists('q', $params) || $params['q'] != 'config') {
          $errors[] = 'The configuration query must have one parameter, q=config';
        }

        if(count($errors) == 0) {
          $html = view('client-tests/success', ['num'=>$num]);
          $status = 200;
        }

        break;

      case 601:
        $features[] = 30;

        if(count($params) > 1) {
          $errors[] = 'The configuration query must have only one query parameter, q=syndicate-to';
        } else if(!array_key_exists('q', $params) || $params['q'] != 'syndicate-to') {
          $errors[] = 'The configuration query must have one parameter, q=syndicate-to';
        }

        if(count($errors) == 0) {
          $html = view('client-tests/success', ['num'=>$num]);
          $status = 200;
        }

        break;
    }

    foreach($features as $feature) {
      ImplementationReport::store_client_feature($this->client->id, $feature, count($errors) == 0 ? 1 : -1, ($test ? $test->id : 0));
    }

    if($test) {
      $last = ORM::for_table('test_results')
        ->where('client_id', $this->client->id)
        ->where('test_id', $test->id)
        ->find_one();
      if(!$last) {
        $last = ORM::for_table('test_results')->create();
        $last->client_id = $this->client->id;
        $last->test_id = $test->id;
        $last->created_at = date('Y-m-d H:i:s');
      }
      $last->passed = count($errors) == 0 ? 1 : -1;
      $last->response = $debug;
      $last->last_result_at = date('Y-m-d H:i:s');
      $last->save();
    }

    // In addition to providing the testing features above, we also need to respond to
    // configuration queries like a normal server, since clients may be querying this outside
    // of the context of running a specific query test.
    // We can also check off features based on the request, and not mark them as fails here.

    $syndicate_to = [
            [
              'uid' => 'https://news.indieweb.org/en',
              'name' => 'IndieNews'
            ]
          ];

    if(count($errors)) {
      $html = view('client-tests/errors', ['errors'=>$errors]);
      $status = 400;
    } else {
      $status = 200;
      if(isset($params['q']) && $params['q'] == 'config') {
        $config = [
          'syndicate-to' => $syndicate_to,
        ];

        // Advertise the media endpoint unless the last viewed test was 300.
        if($num != 300)
          $config['media-endpoint'] = Config::$base.'client/'.$this->client->token.'/media';

        $response = (new JsonResponse($config));
        ImplementationReport::store_client_feature($this->client->id, 27, 1, $num ?: 0);
      } elseif(isset($params['q']) && $params['q'] == 'syndicate-to') {
        $response = (new JsonResponse([
          'syndicate-to' => $syndicate_to
        ]));
        ImplementationReport::store_client_feature($this->client->id, 30, 1, $num ?: 0);
      } elseif(isset($params['q']) && $params['q'] == 'source') {
        $response = (new JsonResponse([
          'type' => ['h-entry'],
          'properties' => $post_properties
        ]));
        // Feature is recorded above
      }
    }

    if($html) {
      streaming_publish('client-'.$this->client->token, [
        'action' => 'client-result',
        'html' => $html,
        'debug' => $debug
      ]);
    }

    return $response->withStatus($status);
  }

  public function options(ServerRequestInterface $request, ResponseInterface $response, $args) {
    $response = $this->_add_cors_headers($response);
    return $response->withStatus(200);
  }

  private function _add_cors_headers($response) {
    return $response->withHeader('Access-Control-Allow-Origin', '*')
      ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
      ->withHeader('Access-Control-Allow-Methods', 'GET, POST');
  }

  private function _conneg_response($request, $response, $params) {
    $accept = $request->getHeaderLine('Accept');
    if(preg_match('/json/', $accept)) {
      $response = new JsonResponse($params);
    } else {
      $response = $response->withHeader('Content-Type', 'application/x-www-form-urlencoded');
      $response->getBody()->write(http_build_query($params));
    }
    return $response;
  }
}
