<?php
date_default_timezone_set('UTC');

if(getenv('ENV')) {
  require(dirname(__FILE__).'/config.'.getenv('ENV').'.php');
} else {
  require(dirname(__FILE__).'/config.php');
}

ORM::configure('mysql:host=' . Config::$dbhost . ';dbname=' . Config::$dbname);
ORM::configure('username', Config::$dbuser);
ORM::configure('password', Config::$dbpass);

function view($template, $data=[]) {
  global $templates;
  return $templates->render($template, $data);
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

function e($text) {
  return htmlspecialchars($text);
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
    $str .= $charset[mt_rand(0, $c)];
  }
  return $str;
}

// Sets up the session.
// If create is true, the session will be created even if there is no cookie yet.
// If create is false, the session will only be set up in PHP if they already have a session cookie.
function session_setup($create=false) {
  if($create || isset($_COOKIE[session_name()])) {
    session_set_cookie_params(86400*30);
    session_start();
  }
}

function is_logged_in() {
  return isset($_SESSION) && array_key_exists('user_id', $_SESSION);
}

function login_required(&$response) {
  return $response->withHeader('Location', '/?login_required')->withStatus(302);
}

function logged_in_user() {
  return ORM::for_table('users')->where('id', $_SESSION['user_id'])->find_one();
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
  curl_exec($ch);
}

