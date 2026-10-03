<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ORM;
use Config;
use Mailgun\Mailgun;

class Auth {

  public function start(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    $params = $request->getParsedBody();

    if($params['galaxy'] != 42) {
      $response->getBody()->write(view('auth-email', [
        'title' => 'Error - Micropub Rocks!',
      ]));
      return $response;
    }

    if($params['confirm'] != $_SESSION['login_confirm']) {
      $response->getBody()->write(view('auth-error', [
        'title' => 'Error - Micropub Rocks!',
        'error' => 'Login Error',
        'error_description' => 'Please make sure you enter the number given.',
      ]));
      return $response;
    }

    // New sign-ups by email are closed while login moves to passkeys. Bots
    // were entering other people's addresses here, so only accounts that
    // have signed in before get a link. Everyone sees the same page either
    // way, so this doesn't reveal which addresses have accounts.
    $user = ORM::for_table('users')
      ->where('email', $params['email'])
      ->where_not_null('last_login')
      ->find_one();

    // The development login can still sign in as any address
    if(!$user && Config::$skipauth) {
      $user = ORM::for_table('users')->create();
      $user->email = $params['email'];
    }

    if(!$user) {
      $response->getBody()->write(view('auth-email', [
        'title' => 'Sign In - Micropub Rocks!',
      ]));
      return $response;
    }

    $user->auth_code = $code = bin2hex(random_bytes(32));
    $user->auth_code_exp = date('Y-m-d H:i:s', time()+60*30);
    $user->save();

    $login_url = Config::$base . 'auth/code?code=' . $code;

    if(Config::$skipauth) {
      return $response->withHeader('Location', $login_url)->withStatus(302);
    }

    // Email the login URL to the user
    $mg = new Mailgun(Config::$mailgun['key']);
    $mg->sendMessage(Config::$mailgun['domain'], [
      'from'     => Config::$mailgun['from'],
      'to'       => $user->email,
      'subject'  => 'Your micropub.rocks Login URL',
      'text'     => "Click on the link below to sign in to micropub.rocks\n\n$login_url\n"
    ]);

    $response->getBody()->write(view('auth-email', [
      'title' => 'Sign In - Micropub Rocks!',
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

    $user->auth_code = '';
    $user->auth_code_exp = null;
    $user->last_login = date('Y-m-d H:i:s');
    $user->save();

    session_setup(true);
    // A new session id on login, so one planted before signing in is useless afterwards
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user->id;
    $_SESSION['email'] = $user->email;
    $_SESSION['login'] = 'success';
    return $response->withHeader('Location', '/dashboard')->withStatus(302);
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

