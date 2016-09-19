<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ORM;
use Config;

class Auth extends Controller {

  public function start(ServerRequestInterface $request, ResponseInterface $response) {
    $params = $request->getParsedBody();

    $user = ORM::for_table('users')->where('email', $params['email'])->find_one();

    if(!$user) {
      $user = ORM::for_table('users')->create();
      $user->email = $params['email'];
    }

    $user->auth_code = $code = random_string(64);
    $user->auth_code_exp = date('Y-m-d H:i:s', time()+300);
    $user->save();

    $login_url = Config::$base . 'auth/code?code=' . $code;

    $response->getBody()->write(view('auth-start', [
      'title' => 'Sign In - Webmention Rocks!',
      'login_url' => $login_url
    ]));
    return $response;
  }

  public function code(ServerRequestInterface $request, ResponseInterface $response) {
    $params = $request->getQueryParams();

    if(!array_key_exists('code', $params)) {
      return $response->withHeader('Location', '/')->withStatus(302);
    }

    $user = ORM::for_table('users')
      ->where('auth_code', $params['code'])
      ->where_gt('auth_code_exp', date('Y-m-d H:i:s'))
      ->find_one();

    if(!$user) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Micropub Rocks!',
        'error' => 'Invalid Link',
        'error_description' => 'The link you followed is invalid or has expired. Please try again.',
      ]));
      return $response;
    }

    session_setup(true);
    $_SESSION['user_id'] = $user->id;
    $_SESSION['login'] = 'success';
    return $response->withHeader('Location', '/dashboard')->withStatus(302);
  }

  public function signout(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup(true);
    unset($_SESSION['user_id']);
    $_SESSION = [];
    session_destroy();
    return $response->withHeader('Location', '/')->withStatus(302);
  }

}

