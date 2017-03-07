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

  public static function storePostImage($client, $num, $key, $img) {
    $key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':img';
    redis()->setex($key, 60*60*24*7, base64_encode($img));
  }

  public static function getPostImage($client, $num, $key) {
    $key = Config::$base . ':' . $client . ':' . $num . ':' . $key . ':img';
    $data = redis()->get($key);
    if($data) 
      return base64_decode($data);
    return null;
  }
}
