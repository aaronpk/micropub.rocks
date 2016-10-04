<?php 
$this->layout('layout', [
                'title' => $test->name,
              ]);

$this->insert('partials/update-test-basic', [
  'test' => $test,
  'endpoint' => $endpoint,
  'description' => 'This test will create a post, then attempt to add a category property to the post.',
  'postbody' => '{
  "type": ["h-entry"],
  "properties": {
    "content": ["This test adds a category property to a post that previously had no category. After you run the update, this post should have the category test1."]
  }
}',
  'updatebody' => '{
  "action": "update",
  "url": "%%%",
  "add": {
    "category": ["test1"]
  }
}',
]);
