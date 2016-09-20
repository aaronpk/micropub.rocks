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

    if(Config::$skipauth) {
      $login_url = Config::$base . 'auth/code?code=' . $code;
      return $response->withHeader('Location', $login_url)->withStatus(302);
    }

    // TODO: Email the login URL to the user

    $response->getBody()->write(view('auth-start', [
      'title' => 'Sign In - Webmention Rocks!',
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
    $_SESSION['email'] = $user->email;
    $_SESSION['login'] = 'success';
    return $response->withHeader('Location', '/tests')->withStatus(302);
  }

  public function signout(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup(true);
    unset($_SESSION['user_id']);
    unset($_SESSION['email']);
    $_SESSION = [];
    session_destroy();
    return $response->withHeader('Location', '/')->withStatus(302);
  }

}

