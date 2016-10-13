<?php 
$this->layout('layout', [
                  'title' => $test->name,
                ]); 

$query_url = build_micropub_query_url($endpoint->micropub_endpoint, [
  'q' => 'source',
  'url' => '%%%',
]);

$description = 'This test will check if your endpoint supports the "source" query to retrieve the original content in a post. This test starts by creating a post at your endpoint, then when you click "Continue", it will query your endpoint to ask for the source content.';

$this->insert('partials/query-source-test', [
  'test' => $test,
  'endpoint' => $endpoint,
  'description' => $description,
  'query_url' => $query_url
]);
