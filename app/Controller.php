<?php
namespace App;

use Rocks\Http\Request;
use Rocks\Http\Response;
use Rocks\View\Raw;
use Rocks\Passkeys;
use ORM;
use IndieAuth;
use Config;

class Controller {

  private function _redirectURI() {
    return Config::$base.'endpoints/callback';
  }

  public function index(Request $request, $args = []) {
    $response = Response::make();
    session_setup(true);

    $num_server_reports = ORM::for_table('micropub_endpoints')
      ->where_not_null('share_token')
      ->count();

    $last_server_report_date = ORM::for_table('micropub_endpoints')
      ->select('last_test_at')
      ->where_not_null('share_token')
      ->max('last_test_at');

    $_SESSION['login_confirm'] = random_int(100, 999);

    $response = $response->withBody(page('index', [
      'title' => 'Micropub Rocks!',
      'confirm' => $_SESSION['login_confirm'],
      'num_server_reports' => $num_server_reports,
      'last_server_report_date' => $last_server_report_date,
      'passkeys_available' => Passkeys::available(),
      'email_login_open' => email_login_open(),
      'email_login_ends' => email_login_ends(),
      'skipauth' => Config::$skipauth,
    ]));
    return $response;
  }

  public function redirect_home(Request $request, $args = []) {
    return Response::redirect('/');
  }

  public function redirect_reports(Request $request, $args = []) {
    return Response::redirect('/implementation-reports/servers/', 301);
  }

  public function dashboard(Request $request, $args = []) {
    $response = Response::make();
    session_setup();

    if(!is_logged_in()) {
      return login_required();
    }

    $user = logged_in_user();

    $endpoints = ORM::for_table('micropub_endpoints')->where('user_id', $user->id)->find_many();
    $clients = ORM::for_table('micropub_clients')->where('user_id', $user->id)->find_many();

    $response = $response->withBody(page('dashboard', [
      'title' => 'Micropub Rocks!',
      'endpoints' => $endpoints,
      'clients' => $clients,
      'needs_passkey' => !user_has_passkey($user->id),
      'email_login_ends' => email_login_ends(),
    ]));
    return $response;
  }

  public function new_client(Request $request, $args = []) {
    $response = Response::make();
    session_setup();

    if(!is_logged_in()) {
      return login_required();
    }

    $params = $request->post;

    $user = logged_in_user();

    $client = ORM::for_table('micropub_clients')
      ->where('user_id', $user->id)
      ->where('name', (string)($params['name'] ?? ''))
      ->find_one();
    if(!$client) {
      $client = ORM::for_table('micropub_clients')->create();
      $client->user_id = $user->id;
      $client->name = (string)($params['name'] ?? '');
      $client->token = random_string(16);
      $client->created_at = date('Y-m-d H:i:s');
    }

    $client->save();

    return $response->withHeader('Location', '/client/'.$client->token)->withStatus(302);
  }

  public function edit_client(Request $request, $args = []) {
    $response = Response::make();
    session_setup();

    if(!is_logged_in()) {
      return login_required();
    }

    $params = $request->post;

    $user = logged_in_user();

    $client = ORM::for_table('micropub_clients')
      ->where('user_id', $user->id)
      ->where('id', $args['id'])
      ->find_one();

    if(!$client)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    $response = $response->withBody(page('edit-client', [
      'title' => 'Edit Micropub Client - Micropub Rocks!',
      'client' => $client,
    ]));
    return $response;
  }

  public function save_client(Request $request, $args = []) {
    $response = Response::make();
    session_setup();

    if(!is_logged_in()) {
      return login_required();
    }

    $params = $request->post;

    $user = logged_in_user();

    $client = ORM::for_table('micropub_clients')
      ->where('user_id', $user->id)
      ->where('id', $params['id'])
      ->find_one();

    if(!$client)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    $client->name = $params['name'] ?? $client->name;
    $client->profile_url = $params['profile_url'] ?? $client->profile_url;
    $client->save();

    return $response->withHeader('Location', '/client/'.$client->token)->withStatus(302);
  }

  public function create_client_access_token(Request $request, $args = []) {
    $response = Response::make();
    session_setup();

    if(!is_logged_in()) {
      return login_required();
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

    return Response::json([
      'token' => $token->token
    ]);
  }

  public function new_endpoint(Request $request, $args = []) {
    $response = Response::make();
    session_setup();

    if(!is_logged_in()) {
      return login_required();
    }

    $params = $request->post;

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

      // Servers that publish IndieAuth server metadata (rel=indieauth-metadata)
      // are discovered through it, and the authorization and token endpoints
      // are read from the metadata. This has to happen first: the library only
      // consults metadata discovered for this URL, and otherwise falls back to
      // the older rel=authorization_endpoint and rel=token_endpoint links.
      $issuer = null;
      $metadataEndpoint = IndieAuth\Client::discoverMetadataEndpoint($me);
      if($metadataEndpoint) {
        $issuer = IndieAuth\Client::discoverIssuer($metadataEndpoint);
        if($issuer instanceof IndieAuth\ErrorResponse) {
          [, $error] = $issuer->getArray();
          return $response->withBody(page('auth-error', [
            'title' => 'Auth Error - Micropub Rocks!',
            'error' => 'Invalid IndieAuth Server Metadata',
            'error_description' => $error['error_description'] . '. The "issuer" in the metadata at ' . $metadataEndpoint . ' must be a URL that the metadata URL begins with.',
          ]));
        }
      }

      $authorizationEndpoint = IndieAuth\Client::discoverAuthorizationEndpoint($me);
      $tokenEndpoint = IndieAuth\Client::discoverTokenEndpoint($me);
      $micropubEndpoint = IndieAuth\Client::discoverMicropubEndpoint($me);

      if($tokenEndpoint && $micropubEndpoint && $authorizationEndpoint) {
        // Generate a "state" parameter and a PKCE code verifier for the request
        $state = IndieAuth\Client::generateStateParameter();
        $codeVerifier = IndieAuth\Client::generatePKCECodeVerifier();
        $_SESSION['auth'] = [
          'state' => $state,
          'code_verifier' => $codeVerifier,
          'issuer' => $issuer,
          'me' => $me,
          'token_endpoint' => $tokenEndpoint,
          'micropub_endpoint' => $micropubEndpoint
        ];

        $authorizationURL = IndieAuth\Client::buildAuthorizationURL($authorizationEndpoint, [
          'me' => $me,
          'redirect_uri' => self::_redirectURI(),
          'client_id' => Config::$base,
          'state' => $state,
          'scope' => 'create update delete undelete',
          'code_verifier' => $codeVerifier,
        ]);
      } else {
        $authorizationURL = false;
      }

      $response = $response->withBody(page('auth-start', [
        'title' => 'Begin Micropub Authorization',
        'tokenEndpoint' => $tokenEndpoint,
        'authorizationEndpoint' => $authorizationEndpoint,
        'micropubEndpoint' => $micropubEndpoint,
        'metadataEndpoint' => $metadataEndpoint,
        'issuer' => $issuer,
        'me' => $me,
        'meParts' => $url,
        'authorizationURL' => $authorizationURL
      ]));
      return $response;

    } else {
      if(empty($params['micropub_endpoint']) || empty($params['access_token'])) {
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

  public function endpoint_callback(Request $request, $args = []) {
    $response = Response::make();
    session_setup();

    if(!is_logged_in()) {
      return login_required();
    }
    $user = logged_in_user();

    $params = $request->query;

    if(!array_key_exists('state', $params)) {
      $response = $response->withBody(page('auth-error', [
        'title' => 'Auth Error - Micropub Rocks!',
        'error' => 'Missing State',
        'error_description' => 'The authorization server did not include the "state" parameter. Ensure that the authorization server passes the state parameter back in the redirect.',
      ]));
      return $response;
    }

    if(!isset($_SESSION['auth']['state']) || $_SESSION['auth']['state'] != $params['state']) {
      $response = $response->withBody(page('auth-error', [
        'title' => 'Auth Error - Micropub Rocks!',
        'error' => 'Invalid State',
        'error_description' => 'The "state" parameter provided in the redirect did not match the one that this server created when it started the flow.',
      ]));
      return $response;
    }

    // When the server advertised an issuer in its metadata, the redirect must
    // include a matching "iss" parameter (RFC 9207), so a response from a
    // different authorization server can't be mixed in.
    if(!empty($_SESSION['auth']['issuer'])) {
      $issuerError = IndieAuth\Client::validateIssuerMatch($params, $_SESSION['auth']['issuer']);
      if($issuerError) {
        [, $error] = $issuerError->getArray();
        return $response->withBody(page('auth-error', [
          'title' => 'Auth Error - Micropub Rocks!',
          'error' => $error['error'] == 'missing_iss' ? 'Missing Issuer' : 'Invalid Issuer',
          'error_description' => $error['error_description'] . '. The authorization server\'s metadata lists its issuer as ' . $_SESSION['auth']['issuer'] . ', so the redirect must include an "iss" parameter with that value.',
        ]));
      }
    }

    if(!isset($params['code'])) {
      return $response->withBody(page('auth-error', [
        'title' => 'Auth Error - Micropub Rocks!',
        'error' => 'Missing Code',
        'error_description' => 'The authorization server did not include the "code" parameter in the redirect.',
      ]));
    }

    $tokenEndpoint = $_SESSION['auth']['token_endpoint'];
    $micropubEndpoint = $_SESSION['auth']['micropub_endpoint'];

    $token = IndieAuth\Client::exchangeAuthorizationCode($tokenEndpoint, [
      'code' => $params['code'],
      'redirect_uri' => self::_redirectURI(),
      'client_id' => Config::$base,
      'code_verifier' => $_SESSION['auth']['code_verifier'] ?? null,
    ]);
    $tokenResponse = (string)$token['raw_response'];
    $data = $token['response'];

    if(!$data) {
      $response = $response->withBody(page('auth-error', [
        'title' => 'Auth Error - Micropub Rocks!',
        'error' => 'Error Requesting Access Token',
        'error_description' => 'The token endpoint sent back an invalid response.',
        'error_debug' => $tokenResponse
      ]));
      return $response;
    }

    if(!isset($data['access_token'])) {
      $response = $response->withBody(page('auth-error', [
        'title' => 'Auth Error - Micropub Rocks!',
        'error' => 'Error Requesting Access Token',
        'error_description' => 'The token endpoint response did not include an access token. Below is the response the endpoint returned. Ensure the endpoint returns a property called "access_token".',
        'error_debug' => $tokenResponse
      ]));
      return $response;
    }

    if(!isset($data['me'])) {
      $response = $response->withBody(page('auth-error', [
        'title' => 'Auth Error - Micropub Rocks!',
        'error' => 'Error Requesting Access Token',
        'error_description' => 'The token endpoint response did not include the user that authenticated. Below is the response the endpoint returned. Ensure the endpoint returns a property called "me".',
        'error_debug' => $tokenResponse
      ]));
      return $response;
    }

    if(parse_url($data['me'], PHP_URL_HOST) != parse_url($_SESSION['auth']['me'], PHP_URL_HOST)) {
      $response = $response->withBody(page('auth-error', [
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

  public function edit_endpoint(Request $request, $args = []) {
    $response = Response::make();
    session_setup();

    if(!is_logged_in()) {
      return login_required();
    }

    $params = $request->post;

    $user = logged_in_user();

    $endpoint = ORM::for_table('micropub_endpoints')
      ->where('user_id', $user->id)
      ->where('id', $args['id'])
      ->find_one();

    if(!$endpoint)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    $response = $response->withBody(page('edit-endpoint', [
      'title' => 'Edit Micropub Endpoint - Micropub Rocks!',
      'endpoint' => $endpoint,
    ]));
    return $response;
  }

  public function save_endpoint(Request $request, $args = []) {
    $response = Response::make();
    session_setup();

    if(!is_logged_in()) {
      return login_required();
    }

    $params = $request->post;

    $user = logged_in_user();

    $endpoint = ORM::for_table('micropub_endpoints')
      ->where('user_id', $user->id)
      ->where('id', $params['id'])
      ->find_one();

    if(!$endpoint)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    $endpoint->micropub_endpoint = $params['micropub_endpoint'] ?? $endpoint->micropub_endpoint;
    $endpoint->access_token = $params['access_token'] ?? $endpoint->access_token;
    $endpoint->save();

    return $response->withHeader('Location', '/server-tests?endpoint='.$endpoint->id)->withStatus(302);
  }

}
