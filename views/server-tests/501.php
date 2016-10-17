<?php 
$this->layout('layout', [
                'title' => $test->name,
              ]);

$this->insert('partials/delete-test', [
  'test' => $test,
  'endpoint' => $endpoint,
  'description' => 'This test creates a post, and then deletes it with a JSON payload.',
  'postbody' => '{"type":["h-entry"],"properties":{"content":["This post will be deleted when the test succeeds."]}}',
  'content_type' => 'json',
  'deletebody' => '{"action":"delete","url":"%%%"}',
  'feature_num' => 24,
]);
