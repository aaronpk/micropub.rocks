<?php
use Rocks\Http\Response;
use Rocks\View\Raw;
use Rocks\View\Template;

date_default_timezone_set('UTC');

if(getenv('ENV')) {
  require(dirname(__FILE__).'/config.'.getenv('ENV').'.php');
} else {
  require(dirname(__FILE__).'/config.php');
}

ORM::configure('mysql:host=' . Config::$dbhost . ';dbname=' . Config::$dbname);
ORM::configure('username', Config::$dbuser);
ORM::configure('password', Config::$dbpass);

// Session cookies are only ever needed by this site's own pages
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if(parse_url(Config::$base, PHP_URL_SCHEME) == 'https')
  ini_set('session.cookie_secure', '1');

function templates() {
  static $templates = null;
  if(!$templates)
    $templates = new Template(dirname(__FILE__).'/../views');
  return $templates;
}

// Renders a template on its own, for partials and non-HTML output
function view($template, $data=[]) {
  return templates()->render($template, $data);
}

// Renders a page template inside the shared layout
function page($template, $data=[]) {
  return templates()->render('layout', [
    'title' => $data['title'] ?? 'Micropub Rocks!',
    'link_tag' => new Raw((string)($data['link_tag'] ?? '')),
    'client' => $data['client'] ?? null,
    'content' => new Raw(view($template, $data)),
  ]);
}

function redis() {
  static $client = false;
  if(!$client)
    $client = new Predis\Client(Config::$redis);
  return $client;
}

function flash($key) {
  if(isset($_SESSION) && isset($_SESSION[$key])) {
    $value = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $value;
  }
}

// Marks every string in a (nested) list as trusted HTML. For messages built
// from fixed text with any user-supplied values already escaped with e().
function raw_list($list) {
  return array_map(function($item) {
    return is_array($item) ? raw_list($item) : new Raw((string)$item);
  }, $list);
}

function e($text) {
  return htmlspecialchars((string)$text);
}

// Always return a string
function mf2_val($in) {
  if(is_string($in)) return $in;
  if(is_array($in)) {
    if(array_key_exists(0, $in) && is_string($in[0])) 
      return $in[0];
    if(array_key_exists(0, $in) && is_array($in[0])) {
      if(array_key_exists('value', $in[0]))
        return $in[0]['value'];
    }
    if(array_key_exists('value', $in))
      return $in['value'];
  }
  return '';
}

function random_string($len) {
  $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  $str = '';
  $c = strlen($charset)-1;
  for($i=0; $i<$len; $i++) {
    $str .= $charset[random_int(0, $c)];
  }
  return $str;
}

// Sets up the session.
// If create is true, the session will be created even if there is no cookie yet.
// If create is false, the session will only be set up in PHP if they already have a session cookie.
// Does nothing if the session has already been started in this request.
function session_setup($create=false) {
  if(session_status() == PHP_SESSION_ACTIVE)
    return;
  if($create || isset($_COOKIE[session_name()])) {
    session_set_cookie_params(86400*30);
    session_start();
  }
}

// Also checks the account still exists, so a session for a deleted user is
// treated as logged out rather than failing later
function is_logged_in() {
  if(!isset($_SESSION) || !array_key_exists('user_id', $_SESSION))
    return false;
  if(!logged_in_user()) {
    unset($_SESSION['user_id'], $_SESSION['email']);
    return false;
  }
  return true;
}

function login_required() {
  return Response::redirect('/?login_required');
}

function logged_in_user() {
  static $users = [];
  $id = $_SESSION['user_id'] ?? null;
  if(!$id)
    return false;
  if(!array_key_exists($id, $users))
    $users[$id] = ORM::for_table('users')->where('id', $id)->find_one();
  return $users[$id];
}

function log_in($user) {
  session_setup(true);
  // A new session id on login, so one planted before signing in is useless afterwards
  session_regenerate_id(true);
  $user->last_login = date('Y-m-d H:i:s');
  $user->save();
  $_SESSION['user_id'] = $user->id;
  $_SESSION['email'] = $user->email;
  $_SESSION['login'] = 'success';
}

function user_has_passkey($user_id) {
  return ORM::for_table('passkeys')->where('user_id', $user_id)->count() > 0;
}

// Email login links stop working on this date, after which passkeys are the only way to sign in
function email_login_ends() {
  return strtotime((Config::$email_login_ends ?? '2027-03-01') . ' 00:00:00 UTC');
}

function email_login_open() {
  return time() < email_login_ends();
}

// A per-session token that state-changing requests from logged-in pages must echo back
function csrf_token() {
  if(empty($_SESSION['csrf']))
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}

function csrf_valid($request) {
  $sent = $request->header('X-CSRF-Token') ?? $request->post('csrf') ?? '';
  return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

// Hosts, IP addresses or CIDR ranges that may be fetched even though they're
// private, e.g. localhost when testing a local Micropub endpoint in development
function http_allow() {
  return Config::$http_allow ?? [];
}

// URLs that users enter are only fetched if they resolve to public addresses,
// so the site can't be used to reach the private network it runs on. The
// IndieAuth client keeps its own user agent and timeout.
function indieauth_safe_mode() {
  IndieAuth\Client::setUpHTTP();
  IndieAuth\Client::$http->set_safe_mode(true, http_allow());
}

// Makes a Guzzle request to a URL a user entered, refusing private addresses.
// curl is pinned to the addresses that were checked, so a DNS answer that
// changes in between makes no difference. Redirects are followed one hop at a
// time (up to $max_redirects) and each hop is checked the same way.
// Throws a RuntimeException when a URL is refused.
function safe_request($method, $url, $options=[], $max_redirects=0) {
  $guard = new p3k\HTTP\Guard(http_allow());
  $client = new GuzzleHttp\Client();

  for($hop = 0; ; $hop++) {
    $check = $guard->check($url);
    if(isset($check['error']))
      throw new RuntimeException('Refused to fetch ' . $url . ': ' . $check['error_description']);

    $addresses = array_map(function($address) {
      return strpos($address, ':') !== false ? '[' . $address . ']' : $address;
    }, $check['addresses']);

    $request_options = $options;
    $request_options['allow_redirects'] = false;
    $request_options['curl'] = ($options['curl'] ?? []) + [
      CURLOPT_RESOLVE => [$check['host'] . ':' . $check['port'] . ':' . implode(',', $addresses)],
      CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
    ];

    $response = $client->request($method, $url, $request_options);

    $code = $response->getStatusCode();
    if($hop >= $max_redirects || !in_array($code, [301, 302, 303, 307, 308]) || !$response->hasHeader('Location'))
      return $response;

    $url = (string)GuzzleHttp\Psr7\UriResolver::resolve(
      new GuzzleHttp\Psr7\Uri($url),
      new GuzzleHttp\Psr7\Uri($response->getHeaderLine('Location')));
    if($code == 303)
      $method = 'GET';
  }
}

// php-jwt requires HMAC keys of at least 256 bits, so derive one from the configured secret
function jwt_key() {
  return hash('sha256', Config::$secret, true);
}

// Sends a plain text email through Mailgun's API. Only used for email login
// links, which end on email_login_ends().
function send_email($to, $subject, $text) {
  $ch = curl_init('https://api.mailgun.net/v3/' . rawurlencode(Config::$mailgun['domain']) . '/messages');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => 'api:' . Config::$mailgun['key'],
    CURLOPT_POSTFIELDS => [
      'from' => Config::$mailgun['from'],
      'to' => $to,
      'subject' => $subject,
      'text' => $text,
    ],
    CURLOPT_TIMEOUT => 10,
  ]);
  curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  if($code != 200)
    error_log('Mailgun returned HTTP ' . $code . ' sending to ' . $to);
  return $code == 200;
}

function domains_are_equal($a, $b) {
  return parse_url($a, PHP_URL_HOST) == parse_url($b, PHP_URL_HOST);
}

function display_url($url) {
  # remove scheme
  $url = preg_replace('/^https?:\/\//', '', $url);
  # if the remaining string has no path components but has a trailing slash, remove the trailing slash
  $url = preg_replace('/^([^\/]+)\/$/', '$1', $url);
  return $url;
}

function is_url($url) {
  return is_string($url) && preg_match('/^https?:\/\/[a-z0-9\.\-]\/?/', $url);
}

function is_url_any_scheme($url) {
  return is_string($url) && preg_match('/^[a-z0-9\+\-\.]+?:\/\/[a-z0-9\.\-]\/?/', $url);
}

function add_parameters_to_url($url, $add_params) {
  $parts = parse_url($url);
  if(array_key_exists('query', $parts) && $parts['query']) {
    parse_str($parts['query'], $params);
  } else {
    $params = [];
  }

  foreach($add_params as $k=>$v) {
    $params[$k] = $v;
  }

  $parts['query'] = http_build_query($params);

  return http_build_url($parts);
}

if(!function_exists('http_build_url')) {
  function http_build_url($parsed_url) {
    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
    $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
    $pass     = ($user || $pass) ? "$pass@" : '';
    $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return "$scheme$user$pass$host$port$path$query$fragment";
  }
}

function http_header_case($str) {
  $str = str_replace('-', ' ', $str);
  $str = ucwords($str);
  $str = str_replace(' ', '-', $str);
  return $str;
}

function result_icon($passed, $id=false) {
  if($passed == 1) {
    return '<span id="'.$id.'" class="ui green circular label">&#x2714;</span>';
  } elseif($passed == -1) {
    return '<span id="'.$id.'" class="ui red circular label">&#x2716;</span>';
  } elseif($passed == 0) {
    return '<span id="'.$id.'" class="ui circular label">&nbsp;</span>';
  } else {
    return '';
  }
}

function result_checkbox($results, $num) {
  foreach($results as $result) {
    if($result->number == $num) {
      return $result->implements == 1 ? 'x' : ' ';
    }
  }
  return ' ';
}

function test_url($test_num, $endpoint_id) {
  return '/server-tests/' . $test_num . '?endpoint=' . $endpoint_id;
}

function client_test_url($test_num, $token) {
  return '/client/' . $token . '/' . $test_num;
}

function build_micropub_query_url($endpoint, $params) {
  $url = parse_url($endpoint);
  if(!array_key_exists('query', $url))
    $url['query'] = http_build_query($params);
  else
    $url['query'] .= '&' . http_build_query($params);
  $url = http_build_url($url);
  return preg_replace('/%5B[0-9]+%5D=/simU', '%5B%5D=', $url);
}

function streaming_publish($channel, $data) {
  $ch = curl_init(Config::$base . 'streaming/pub?id='.$channel);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  // Live updates are best-effort, so don't let a stuck push service hold up the test request
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
  curl_setopt($ch, CURLOPT_TIMEOUT, 3);
  curl_exec($ch);
}

