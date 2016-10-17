<?php 
$this->layout('layout', [
                'title' => $test->name,
              ]);

$this->insert('partials/undelete-test', [
  'test' => $test,
  'endpoint' => $endpoint,
  'description' => 'This test creates a post, deletes it, and then undeletes it.',
  'postbody' => '{"type":["h-entry"],"properties":{"content":["This post will be deleted, and should be restored after undeleting it."]}}',
  'content_type' => 'json',
  'deletebody' => '{"action":"delete","url":"%%%"}',
  'undeletebody' => '{"action":"undelete","url":"%%%"}',
  'feature_num' => 26,
]);
