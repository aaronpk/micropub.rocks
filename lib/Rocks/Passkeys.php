<?php
namespace Rocks;

use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use ORM;
use Config;
use RuntimeException;
use Throwable;

/**
 * The WebAuthn registration and authentication ceremonies.
 *
 * All the cryptography is lbuchs/webauthn's. What lives here is which relying
 * party id and origin are accepted, where challenges are kept, and what
 * happens when a signature counter goes backwards.
 *
 * Challenges are kept in the PHP session, which is stored server-side, so the
 * client can't choose one. Every failure throws a RuntimeException with a
 * message that's safe to show the user.
 */
class Passkeys {

  // Long enough for a fingerprint prompt or a security key tap
  const CHALLENGE_TTL = 300;

  // 'none' covers platform authenticators that attest nothing, which is the
  // common case for a passkey. The rest are accepted so a security key that
  // does attest isn't turned away.
  const FORMATS = ['none', 'packed', 'fido-u2f', 'apple', 'android-key', 'android-safetynet', 'tpm'];

  // The relying party id is the host of the configured base URL, never the
  // request's Host header. Credentials are bound to it, so changing it orphans
  // every registered passkey.
  public static function relying_party_id() {
    return strtolower(trim((string)parse_url(Config::$base, PHP_URL_HOST), '[]'));
  }

  public static function origin() {
    $url = parse_url(Config::$base);
    $origin = strtolower($url['scheme'] ?? '') . '://' . strtolower($url['host'] ?? '');
    if(isset($url['port']))
      $origin .= ':' . $url['port'];
    return $origin;
  }

  // WebAuthn needs a secure context, so plain http only works on localhost
  public static function available() {
    return parse_url(Config::$base, PHP_URL_SCHEME) == 'https'
      || self::relying_party_id() == 'localhost';
  }

  // Returns the PublicKeyCredentialCreationOptions for navigator.credentials.create().
  // $data is kept with the challenge and handed back by complete_registration.
  public static function begin_registration($ceremony, $user_handle, $name, $exclude_credential_ids=[], $data=[]) {
    $webauthn = self::webauthn();

    $args = $webauthn->getCreateArgs(
      $user_handle,
      $name,
      $name,
      self::CHALLENGE_TTL,
      // A discoverable credential, so signing in doesn't need a username typed first
      true,
      false,
      null,
      // Excluded so the authenticator says "you already have one" instead of making a second
      array_map([self::class, 'decode'], $exclude_credential_ids)
    );

    self::store_challenge($ceremony, $webauthn->getChallenge(), $data);

    return json_decode(json_encode($args), true);
  }

  public static function complete_registration($ceremony, $client_data_json, $attestation_object) {
    $record = self::take_challenge($ceremony);
    self::check_origin($client_data_json);

    try {
      $result = self::webauthn()->processCreate(
        $client_data_json,
        $attestation_object,
        new ByteBuffer($record['challenge']),
        false,
        true,
        // Attestation isn't verified against manufacturer roots. Only possession
        // of the key matters here, and requiring roots would turn away good passkeys.
        false
      );
    } catch(Throwable $e) {
      throw new RuntimeException('That passkey could not be registered: ' . $e->getMessage(), 0, $e);
    }

    if(!is_string($result->credentialId) || $result->credentialId === '') {
      throw new RuntimeException('The authenticator did not return a credential id.');
    }

    $credential_id = self::encode($result->credentialId);

    if(ORM::for_table('passkeys')->where('credential_id', $credential_id)->count()) {
      throw new RuntimeException('That passkey is already registered.');
    }

    $aaguid = $result->AAGUID ?? null;
    if($aaguid instanceof ByteBuffer)
      $aaguid = $aaguid->getBinaryString();

    return [
      'credential_id' => $credential_id,
      'public_key' => (string)$result->credentialPublicKey,
      'sign_count' => (int)($result->signatureCounter ?? 0),
      'aaguid' => is_string($aaguid) && $aaguid !== '' ? bin2hex($aaguid) : null,
      'data' => $record['data'],
    ];
  }

  public static function save($user_id, $credential, $label='Passkey') {
    $passkey = ORM::for_table('passkeys')->create();
    $passkey->user_id = $user_id;
    $passkey->credential_id = $credential['credential_id'];
    $passkey->public_key = $credential['public_key'];
    $passkey->label = self::clean_label($label);
    $passkey->sign_count = $credential['sign_count'];
    $passkey->aaguid = $credential['aaguid'];
    $passkey->date_created = date('Y-m-d H:i:s');
    $passkey->save();
    return $passkey;
  }

  // An empty credential list asks the authenticator to offer any discoverable
  // credential it holds for this site, which is the point of a passkey.
  public static function begin_login() {
    $webauthn = self::webauthn();
    $args = $webauthn->getGetArgs([], self::CHALLENGE_TTL);
    self::store_challenge('login', $webauthn->getChallenge());
    return json_decode(json_encode($args), true);
  }

  // Returns the user the passkey belongs to
  public static function complete_login($credential_id, $client_data_json, $authenticator_data, $signature, $user_handle) {
    $record = self::take_challenge('login');
    self::check_origin($client_data_json);

    $passkey = ORM::for_table('passkeys')->where('credential_id', self::encode($credential_id))->find_one();
    $user = $passkey ? ORM::for_table('users')->find_one($passkey->user_id) : false;

    if(!$passkey || !$user) {
      throw new RuntimeException('That passkey is not registered here.');
    }

    // The user handle is the authenticator's claim about whose credential this
    // is. It must agree with the stored owner, though the signature is what
    // actually proves possession.
    if($user_handle !== '' && !hash_equals((string)$user->webauthn_id, $user_handle)) {
      throw new RuntimeException('That passkey is not registered here.');
    }

    try {
      self::webauthn()->processGet(
        $client_data_json,
        $authenticator_data,
        $signature,
        $passkey->public_key,
        new ByteBuffer($record['challenge']),
        // The stored counter, so a cloned or replayed credential is rejected
        $passkey->sign_count > 0 ? (int)$passkey->sign_count : null,
        false,
        true
      );
    } catch(Throwable $e) {
      throw new RuntimeException('That passkey could not be verified: ' . $e->getMessage(), 0, $e);
    }

    // Only ever moves forward. An authenticator without a counter reports zero
    // every time, which must not overwrite a real one.
    $passkey->sign_count = max(self::read_sign_count($authenticator_data), (int)$passkey->sign_count);
    $passkey->date_last_used = date('Y-m-d H:i:s');
    $passkey->save();

    return $user;
  }

  public static function encode($raw) {
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
  }

  public static function decode($encoded) {
    return (string)base64_decode(strtr((string)$encoded, '-_', '+/'), true);
  }

  public static function clean_label($label) {
    $label = trim((string)preg_replace('/\s+/', ' ', (string)$label));
    $label = (string)preg_replace('/[^\x20-\x7e]/', '', $label);
    if($label === '')
      return 'Passkey';
    return substr($label, 0, 48);
  }

  private static function webauthn() {
    // The last argument switches binary values to base64url, which the browser
    // side can decode with a few lines of JavaScript
    return new WebAuthn('micropub.rocks', self::relying_party_id(), self::FORMATS, true);
  }

  // lbuchs only checks that the origin's host ends with the relying party id,
  // and ignores the port, so require an exact match with the configured base URL.
  private static function check_origin($client_data_json) {
    $client_data = json_decode($client_data_json, true);
    if(!is_array($client_data) || ($client_data['origin'] ?? null) !== self::origin()) {
      throw new RuntimeException('That passkey response came from the wrong site.');
    }
  }

  private static function store_challenge($ceremony, ByteBuffer $challenge, $data=[]) {
    $_SESSION['webauthn'][$ceremony] = [
      'challenge' => base64_encode($challenge->getBinaryString()),
      'expires' => time() + self::CHALLENGE_TTL,
      'data' => $data,
    ];
  }

  // Read and consume a challenge. It's removed before it's checked, so a
  // challenge can only be used once whether the ceremony succeeds or not.
  private static function take_challenge($ceremony) {
    $record = $_SESSION['webauthn'][$ceremony] ?? null;
    unset($_SESSION['webauthn'][$ceremony]);

    $challenge = is_array($record) ? base64_decode((string)($record['challenge'] ?? ''), true) : false;

    if(!$challenge || ($record['expires'] ?? 0) < time()) {
      throw new RuntimeException('That request has expired. Passkey prompts are only valid for a few minutes, so please try again.');
    }

    return [
      'challenge' => $challenge,
      'data' => $record['data'] ?? [],
    ];
  }

  // Bytes 33-36 of authenticator data, big-endian, after the 32-byte RP id hash and the flags byte
  private static function read_sign_count($authenticator_data) {
    if(strlen($authenticator_data) < 37)
      return 0;
    return unpack('N', substr($authenticator_data, 33, 4))[1] ?? 0;
  }

}
