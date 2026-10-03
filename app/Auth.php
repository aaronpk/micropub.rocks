<?php
namespace App;

use Rocks\Http\Request;
use Rocks\Http\Response;
use Rocks\Passkeys;
use ORM;
use Config;
use RuntimeException;
use Throwable;

class Auth {

  // Emails a login link to an existing account. Only available until
  // email_login_ends(), to give people time to add a passkey.
  public function start(Request $request, $args = []) {
    session_setup();

    if(!email_login_open())
      return self::email_login_ended();

    $params = $request->post;

    if(($params['galaxy'] ?? '') != '42') {
      return Response::make(200, page('auth-email', [
        'title' => 'Error - Micropub Rocks!',
      ]));
    }

    if(!isset($_SESSION['login_confirm']) || ($params['confirm'] ?? '') != $_SESSION['login_confirm']) {
      return Response::make(200, page('auth-error', [
        'title' => 'Error - Micropub Rocks!',
        'error' => 'Login Error',
        'error_description' => 'Please make sure you enter the number given.',
      ]));
    }

    $email = trim((string)($params['email'] ?? ''));

    // New accounts are created with a passkey, not by email. Bots were
    // entering other people's addresses here, so only accounts that have
    // signed in before get a link. Everyone sees the same page either way,
    // so this doesn't reveal which addresses have accounts.
    $user = $email === '' ? false : ORM::for_table('users')
      ->where('email', $email)
      ->where_not_null('last_login')
      ->find_one();

    // The development login can still sign in as any address
    if(!$user && Config::$skipauth && $email !== '') {
      $user = ORM::for_table('users')->create();
      $user->email = $email;
      $user->date_created = date('Y-m-d H:i:s');
    }

    if(!$user) {
      return Response::make(200, page('auth-email', [
        'title' => 'Sign In - Micropub Rocks!',
      ]));
    }

    $user->auth_code = $code = bin2hex(random_bytes(32));
    $user->auth_code_exp = date('Y-m-d H:i:s', time()+60*30);
    $user->save();

    $login_url = Config::$base . 'auth/code?code=' . $code;

    if(Config::$skipauth) {
      return Response::redirect($login_url);
    }

    send_email($user->email, 'Your micropub.rocks Login URL',
      "Click on the link below to sign in to micropub.rocks\n\n$login_url\n\n"
      . "Email sign-in will stop working on " . date('F j, Y', email_login_ends()) . ". "
      . "After you sign in, set up a passkey to keep access to your account.\n");

    return Response::make(200, page('auth-email', [
      'title' => 'Sign In - Micropub Rocks!',
    ]));
  }

  public function code(Request $request, $args = []) {
    if(!email_login_open())
      return self::email_login_ended();

    $code = $request->query('code');

    if($code === null || $code === '') {
      return Response::redirect('/');
    }

    $user = ORM::for_table('users')
      ->where('auth_code', $code)
      ->where_gt('auth_code_exp', date('Y-m-d H:i:s'))
      ->find_one();

    if(!$user) {
      return Response::make(200, page('auth-error', [
        'title' => 'Error - Micropub Rocks!',
        'error' => 'Invalid Link',
        'error_description' => 'The link you followed is invalid or has expired. Please try again.',
      ]));
    }

    $user->auth_code = '';
    $user->auth_code_exp = null;
    log_in($user);

    // Prompt anyone without a passkey to set one up before email login ends
    if(!user_has_passkey($user->id))
      return Response::redirect('/account/passkey');

    return Response::redirect('/dashboard');
  }

  // The passkey endpoints below are called with fetch() and only accept JSON.
  // A cross-site page can't send a JSON body without a CORS preflight, which
  // these endpoints never grant, so that also stands in for a CSRF token here.

  public function register_challenge(Request $request, $args = []) {
    if($error = self::check_request($request))
      return $error;

    $email = trim((string)($request->json()['email'] ?? ''));

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return self::error('Enter a valid email address.');
    }

    // An account that has been used before can't be claimed by registering
    // a passkey with its email address. Its owner signs in with an email
    // link (until it ends) and adds a passkey from there.
    $existing = ORM::for_table('users')
      ->where('email', $email)
      ->where_raw('(last_login IS NOT NULL OR EXISTS (SELECT 1 FROM passkeys WHERE passkeys.user_id = users.id))')
      ->count();
    if($existing) {
      $message = email_login_open()
        ? 'There is already an account for that email address. Sign in with an email link, then add a passkey to your account.'
        : 'There is already an account for that email address. Sign in with your passkey instead.';
      return self::error($message, 409);
    }

    session_setup(true);

    // Always a new account. An older row with this email that was never
    // used isn't reused, since nothing here proves the person owns the address.
    $webauthn_id = bin2hex(random_bytes(16));

    return Response::json(Passkeys::begin_registration('register', $webauthn_id, $email, [], [
      'email' => $email,
      'webauthn_id' => $webauthn_id,
    ]));
  }

  public function register(Request $request, $args = []) {
    if($error = self::check_request($request))
      return $error;

    session_setup(true);
    $body = $request->json();

    try {
      $credential = Passkeys::complete_registration('register',
        Passkeys::decode($body['clientDataJSON'] ?? ''),
        Passkeys::decode($body['attestationObject'] ?? ''));
    } catch(RuntimeException $e) {
      return self::error($e->getMessage());
    }

    $db = ORM::get_db();
    $db->beginTransaction();
    try {
      $user = ORM::for_table('users')->create();
      $user->email = $credential['data']['email'];
      $user->webauthn_id = $credential['data']['webauthn_id'];
      $user->date_created = date('Y-m-d H:i:s');
      $user->save();

      Passkeys::save($user->id, $credential);
      $db->commit();
    } catch(Throwable $e) {
      $db->rollBack();
      return self::error('Your account could not be created. Please try again.', 500);
    }

    log_in($user);

    return Response::json(['redirect' => '/dashboard']);
  }

  public function login_challenge(Request $request, $args = []) {
    if($error = self::check_request($request))
      return $error;

    session_setup(true);

    return Response::json(Passkeys::begin_login());
  }

  public function login(Request $request, $args = []) {
    if($error = self::check_request($request))
      return $error;

    session_setup(true);
    $body = $request->json();

    try {
      $user = Passkeys::complete_login(
        Passkeys::decode($body['id'] ?? ''),
        Passkeys::decode($body['clientDataJSON'] ?? ''),
        Passkeys::decode($body['authenticatorData'] ?? ''),
        Passkeys::decode($body['signature'] ?? ''),
        Passkeys::decode($body['userHandle'] ?? ''));
    } catch(RuntimeException $e) {
      return self::error($e->getMessage());
    }

    log_in($user);

    return Response::json(['redirect' => '/dashboard']);
  }

  public function signout(Request $request, $args = []) {
    session_setup(true);
    unset($_SESSION['user_id']);
    unset($_SESSION['email']);
    $_SESSION = [];
    session_destroy();
    return Response::redirect('/');
  }

  private static function email_login_ended() {
    return Response::make(410, page('auth-error', [
      'title' => 'Email Sign-In Has Ended - Micropub Rocks!',
      'error' => 'Email sign-in has ended',
      'error_description' => 'Signing in with an email link stopped working on ' . date('F j, Y', email_login_ends()) . '. Sign in with your passkey instead.',
    ]));
  }

  private static function check_request(Request $request) {
    if(!$request->isJson()) {
      return self::error('Expected a JSON request.', 415);
    }
    if(!Passkeys::available()) {
      return self::error('Passkeys need this site to be served over https, or from localhost.');
    }
    return null;
  }

  private static function error($message, $status=400) {
    return Response::json(['error' => $message], $status);
  }

}
