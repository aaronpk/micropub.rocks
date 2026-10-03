<?php 
$query_url = build_micropub_query_url($endpoint->micropub_endpoint, [
  'q' => 'config',
]);


echo $view->partial('partials/media-endpoint-test', [
  'test' => $test,
  'endpoint' => $endpoint,
  'filename' => 'sunset.jpg',
  'content_type' => 'image/jpeg',
  'query_url' => $query_url
]);
