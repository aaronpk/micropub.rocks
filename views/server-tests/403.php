<?php 
$this->layout('layout', [
                'title' => $test->name,
              ]);

$this->insert('partials/update-test-basic', [
  'test' => $test,
  'endpoint' => $endpoint,
  'description' => 'This test will create a post with two categories, then attempt to remove one of the categories.',
  'postbody' => '{
  "type": ["h-entry"],
  "properties": {
    "content": ["This test removes a category from the post. After you run the update, this post should have only the category test1."],
    "category": ["test1", "test2"]
  }
}',
  'updatebody' => '{
  "action": "update",
  "url": "%%%",
  "remove": {
    "category": ["test2"]
  }
}',
]);
