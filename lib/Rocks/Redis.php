<?php
namespace Rocks;

use Config;

class Redis {

  public static function storePostHTML($client, $num, $key, $html, $raw) {
    $key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':html';
    redis()->setex($key, 60*60*24*7, $html);
    $key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':raw';
    redis()->setex($key, 60*60*24*7, $raw);
  }

  public static function getPostHTML($client, $num, $key) {
    $key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':html';
    $html = redis()->get($key);
    $key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':raw';
    $raw = redis()->get($key);
    return [$html, $raw];
  }

}
