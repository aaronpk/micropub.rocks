<?php 
$this->layout('layout', [
                'title' => $test->name,
              ]);

$this->insert('partials/update-test-basic', [
  'test' => $test,
  'endpoint' => $endpoint,
  'description' => 'This test will create a post with two categories, then attempt to delete the category property completely.',
  'postbody' => '{
  "type": ["h-entry"],
  "properties": {
    "content": ["This test deletes the category property from the post. After you run the update, this post should have no categories."],
    "category": ["test1", "test2"]
  }
}',
  'updatebody' => '{
  "action": "update",
  "url": "%%%",
  "delete": [
    "category"
  ]
}',
]);
