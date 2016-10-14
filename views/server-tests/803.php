<?php $this->layout('layout', [
                      'title' => $test->name,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p>This test ensures your endpoint does not allow unauthenticated requests. This request does not have an access token and your server must reply with an HTTP "Forbidden" response.</p>
    <p>Clicking "Run" will make the following request to your endpoint.</p>
  </section>

  <section class="content code">
    <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Content-type: application/x-www-form-urlencoded; charset=utf-8

<span id="postbody">h=entry&amp;
content=Testing+unauthenticated+request.+This+should+not+create+a+post.</span></pre>
  </section>

  <section class="content">
    <button class="ui green button" id="run">Run</button>
    <ul class="result-list">
      <li><span id="passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>401</code></li>
      <li id="passed_error_body_line" class="hidden"><span id="passed_error_body" class="ui circular label">&nbsp;</span> Returned a correct error response (error: unauthorized)</li>
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

set_up_form_test(test, endpoint, function(data){
  var passed_code = false;
  var passed_error_body = false;
  if(data.code == 401) {
    passed_code = true;
  }
  set_result_icon("#passed_code", passed_code ? 1 : -1);
  if(data.json) {
    $("#passed_error_body_line").removeClass("hidden");
    if(data.json.error && data.json.error == "unauthorized") {
      passed_error_body = true;
    }
  } else {
    passed_error_body = true;
  }
  set_result_icon("#passed_error_body", passed_error_body ? 1 : -1);
  store_result(test, endpoint, (passed_code && passed_error_body ? 1 : -1));
}, true);

</script>
