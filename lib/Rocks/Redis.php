<?php
namespace Rocks;

use Config;

class Redis {

  public static function storePostHTML($client, $num, $key, $html, $raw, $properties=false) {
    $redis_key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':html';
    redis()->setex($redis_key, 60*60*24*7, $html);
    $redis_key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':raw';
    redis()->setex($redis_key, 60*60*24*7, $raw);
    if($properties) {
      $redis_key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':properties';
      redis()->setex($redis_key, 60*60*24*7, json_encode($properties));
    }
  }

  public static function getPostHTML($client, $num, $key) {
    $redis_key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':html';
    $html = redis()->get($redis_key);
    $redis_key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':raw';
    $raw = redis()->get($redis_key);
    $redis_key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':properties';
    $properties = json_decode(redis()->get($redis_key), true);
    return [$html, $raw, $properties];
  }

  public static function storePostImage($client, $num, $key, $img) {
    $key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':img';
    redis()->setex($key, 60*60*24*1, base64_encode($img));
  }

  public static function getPostImage($client, $num, $key) {
    $key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':img';
    $data = redis()->get($key);
    if($data) 
      return base64_decode($data);
    return null;
  }
}
