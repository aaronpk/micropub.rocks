<?php
namespace App;

use Rocks\Http\Request;
use Rocks\Http\Response;
use Rocks\Passkeys;
use ORM;
use RuntimeException;

class Account {

  public function index(Request $request, $args = []) {
    if(!$user = self::user())
      return Response::redirect('/');

    $passkeys = ORM::for_table('passkeys')
      ->where('user_id', $user->id)
      ->order_by_asc('id')
      ->find_array();

    return Response::make(200, page('account', [
      'title' => 'Your Account - Micropub Rocks!',
      'email' => $user->email,
      'passkeys' => $passkeys,
      'passkeys_available' => Passkeys::available(),
      'csrf' => csrf_token(),
      'message' => flash('account_message'),
      'email_login_open' => email_login_open(),
      'email_login_ends' => email_login_ends(),
    ]));
  }

  // Shown after signing in with an email link, to anyone without a passkey yet
  public function passkey_prompt(Request $request, $args = []) {
    if(!$user = self::user())
      return Response::redirect('/');

    if(user_has_passkey($user->id))
      return Response::redirect('/dashboard');

    return Response::make(200, page('passkey-prompt', [
      'title' => 'Set Up a Passkey - Micropub Rocks!',
      'email' => $user->email,
      'passkeys_available' => Passkeys::available(),
      'csrf' => csrf_token(),
      'email_login_ends' => email_login_ends(),
    ]));
  }

  public function add_challenge(Request $request, $args = []) {
    if(!$user = self::user())
      return self::error('You are not signed in.', 401);
    if($error = self::check_request($request))
      return $error;

    // Accounts made with the development login don't have a user handle yet
    if(!$user->webauthn_id) {
      $user->webauthn_id = bin2hex(random_bytes(16));
      $user->save();
    }

    $existing = array_column(ORM::for_table('passkeys')
      ->where('user_id', $user->id)
      ->select('credential_id')
      ->find_array(), 'credential_id');

    return Response::json(Passkeys::begin_registration('add', $user->webauthn_id, $user->email, $existing, [
      'user_id' => $user->id,
    ]));
  }

  public function add(Request $request, $args = []) {
    if(!$user = self::user())
      return self::error('You are not signed in.', 401);
    if($error = self::check_request($request))
      return $error;

    $body = $request->json();

    try {
      $credential = Passkeys::complete_registration('add',
        Passkeys::decode($body['clientDataJSON'] ?? ''),
        Passkeys::decode($body['attestationObject'] ?? ''));
    } catch(RuntimeException $e) {
      return self::error($e->getMessage());
    }

    // The ceremony was started by this account, so it can't be finished for another
    if(($credential['data']['user_id'] ?? null) != $user->id) {
      return self::error('That request was started by a different account.');
    }

    Passkeys::save($user->id, $credential, (string)($body['label'] ?? ''));
    $_SESSION['account_message'] = 'Your new passkey was added.';

    return Response::json(['redirect' => '/account']);
  }

  public function rename(Request $request, $args) {
    if(!$user = self::user())
      return Response::redirect('/');
    if(!csrf_valid($request))
      return Response::text("Invalid request\n", 403);

    $passkey = self::passkey($user, $args['id']);
    if(!$passkey)
      return Response::make(404);

    $passkey->label = Passkeys::clean_label($request->post('label'));
    $passkey->save();

    return Response::redirect('/account');
  }

  public function remove(Request $request, $args) {
    if(!$user = self::user())
      return Response::redirect('/');
    if(!csrf_valid($request))
      return Response::text("Invalid request\n", 403);

    $passkey = self::passkey($user, $args['id']);
    if(!$passkey)
      return Response::make(404);

    $count = ORM::for_table('passkeys')->where('user_id', $user->id)->count();
    if($count <= 1) {
      $_SESSION['account_message'] = 'You can\'t remove your only passkey, or you wouldn\'t be able to sign in again.';
    } else {
      $passkey->delete();
      $_SESSION['account_message'] = 'The passkey was removed.';
    }

    return Response::redirect('/account');
  }

  private static function user() {
    session_setup();
    return is_logged_in() ? logged_in_user() : false;
  }

  // Only ever one of the signed-in user's own passkeys
  private static function passkey($user, $id) {
    return ORM::for_table('passkeys')
      ->where('id', $id)
      ->where('user_id', $user->id)
      ->find_one();
  }

  private static function check_request(Request $request) {
    if(!$request->isJson())
      return self::error('Expected a JSON request.', 415);
    if(!csrf_valid($request))
      return self::error('Invalid request. Reload the page and try again.', 403);
    if(!Passkeys::available())
      return self::error('Passkeys need this site to be served over https, or from localhost.');
    return null;
  }

  private static function error($message, $status=400) {
    return Response::json(['error' => $message], $status);
  }

}
