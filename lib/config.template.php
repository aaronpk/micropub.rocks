<?php
class Config {
  public static $base = 'http://micropubrocks.dev/';

  public static $redis = 'tcp://127.0.0.1:6379';

  // URLs entered by users (websites, Micropub endpoints, media URLs) are only
  // fetched if they resolve to public addresses, so the site can't be used to
  // reach your private network. List hostnames, IP addresses or CIDR ranges
  // here to allow them anyway, e.g. ['localhost'] to test a local endpoint.
  public static $http_allow = [];

  public static $dbhost = '127.0.0.1';
  public static $dbname = 'micropubrocks';
  public static $dbuser = 'micropubrocks';
  public static $dbpass = 'micropubrocks';

  // When set to true, authentication is bypassed, and you can log in by 
  // entering any email you want in the login form. This is useful when developing
  // this or running it locally where passkeys can't work, which is anywhere
  // except https:// or http://localhost.
  public static $skipauth = false;

  // Used when an encryption key is needed. Set to a long random string.
  public static $secret = 'xxxx';

  // Sign-in by emailed link stops working on this date (UTC), after which
  // passkeys are the only way to sign in. Until then, accounts that have
  // signed in before can get a link, and are asked to add a passkey.
  public static $email_login_ends = '2027-03-01';

  // Used to send email login links until $email_login_ends
  public static $mailgun = [
    'key' => '',
    'domain' => '',
    'from' => '"micropub.rocks" <login@micropub.rocks>'
  ];
}
