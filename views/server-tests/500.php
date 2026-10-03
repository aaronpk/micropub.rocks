<?php 
echo $view->partial('partials/delete-test', [
  'test' => $test,
  'endpoint' => $endpoint,
  'description' => 'This test creates a post, and then deletes it.',
  'postbody' => 'h=entry&amp;content=This+post+will+be+deleted+when+the+test+succeeds.',
  'content_type' => 'form',
  'deletebody' => 'action=delete&amp;url=%%%',
  'feature_num' => 23,
]);
