<?php
class Config {
  public static $base = 'http://micropubrocks.dev/';

  public static $redis = 'tcp://127.0.0.1:6379';

  public static $dbhost = '127.0.0.1';
  public static $dbname = 'micropubrocks';
  public static $dbuser = 'micropubrocks';
  public static $dbpass = 'micropubrocks';

  // Used when an encryption key is needed. Set to something random.
  public static $secret = 'xxxx';
}
