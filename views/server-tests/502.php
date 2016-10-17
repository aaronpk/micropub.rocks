<?php 
$this->layout('layout', [
                'title' => $test->name,
              ]);

$this->insert('partials/undelete-test', [
  'test' => $test,
  'endpoint' => $endpoint,
  'description' => 'This test creates a post, deletes it, and then undeletes it.',
  'postbody' => 'h=entry&amp;content=This+post+will+be+deleted,+and+should+be+restored+after+undeleting+it.',
  'content_type' => 'form',
  'deletebody' => 'action=delete&amp;url=%%%',
  'undeletebody' => 'action=undelete&amp;url=%%%',
  'feature_num' => 25,
]);
