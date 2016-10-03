<?php 
$this->layout('layout', [
                'title' => $test->name,
              ]);

$this->insert('partials/update-test-basic', [
  'test' => $test,
  'endpoint' => $endpoint,
  'description' => 'This test will create a post, then attempt to replace a property in the post.',
  'postbody' => '{
  "type": ["h-entry"],
  "properties": {
    "content": ["Micropub update test. This text should be replaced if the test succeeds."]
  }
}',
  'updatebody' => '{
  "action": "update",
  "url": "%%%",
  "replace": {
    "content": ["This is the updated text. If you can see this you passed the test!"]
  }
}',
]);
