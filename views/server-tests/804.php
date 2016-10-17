<?php $this->layout('layout', [
                      'title' => $test->name,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p>This test ensures your endpoint rejects requests from access tokens that are not allowed to create posts. You will first need to create an access token that should not be allowed to create posts, but is otherwise a valid access token for a user who would otherwise be allowed to post. This is testing your endpoint's ability to issue limited-scope access tokens to untrusted applications.</p>
  </section>

  <section class="content">
    <div class="ui fluid input">
      <input type="text" id="access-token-input" placeholder="Paste access token here">
    </div>  
  </section>

  <section class="content code">
    <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Authorization: Bearer <span id="access-token-request"></span>
Content-type: application/x-www-form-urlencoded; charset=utf-8

<span id="postbody">h=entry&amp;
content=Testing+a+request+with+an+unauthorized+access+token.+This+should+not+create+a+post.</span></pre>
  </section>

  <section class="content">
    <button class="ui green button" id="run">Run</button>
    <ul class="result-list">
      <li><span id="passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>401</code></li>
      <li id="passed_error_body_line" class="hidden"><span id="passed_error_body" class="ui circular label">&nbsp;</span> Returned a correct error response (error: insufficient_scope)</li>
    </ul>
  </section>

  <section class="content hidden" id="response-section">
    <h3>Response</h3>
    <pre id="response" class="small"></pre>
  </section>

</div>
<script>
var test = <?= $test->id ?>;
var endpoint = <?= $endpoint->id ?>;

$("#access-token-input").on('change keyup', function(){
  $("#access-token-request").text($(this).val());
})

set_up_form_test(test, endpoint, function(data){
  var passed_code = false;
  var passed_error_body = false;
  if(data.code == 401) {
    passed_code = true;
  }
  set_result_icon("#passed_code", passed_code ? 1 : -1);
  if(data.json) {
    $("#passed_error_body_line").removeClass("hidden");
    if(data.json.error) {
      if(data.json.error == "insufficient_scope") {
        passed_error_body = true;
      }
    }
  } else {
    passed_error_body = true;
  }
  set_result_icon("#passed_error_body", passed_error_body ? 1 : -1);
  store_result(test, endpoint, (passed_code && passed_error_body ? 1 : -1));

  store_server_feature(endpoint, 4, (passed_code && passed_error_body ? 1 : -1), test);
});

</script>
