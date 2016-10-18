<?php 
$this->layout('layout', [
                'title' => $test->name,
              ]);

$this->insert('partials/update-test-basic', [
  'test' => $test,
  'endpoint' => $endpoint,
  'description' => 'This test will create a post, then attempt to add a value to an existing property in the post.',
  'feature_num' => 17,
  'postbody' => '{
  "type": ["h-entry"],
  "properties": {
    "content": ["Micropub update test for adding a category. After you run the update, this post should have two categories: test1 and test2."],
    "category": ["test1"]
  }
}',
  'updatebody' => '{
  "action": "update",
  "url": "%%%",
  "add": {
    "category": ["test2"]
  }
}',
]);
