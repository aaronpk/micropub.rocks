<?php
  $this->layout('layout', [
    'title' => $test->name,
  ]);
?>
<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p>This test makes an invalid request to your endpoint. Your endpoint should reject this with an HTTP 400 response.</p>
    <p>Clicking "Run" will first create a post, and after you've confirmed it's been created, you can click "Continue" to make the edit.</p>
  </section>

  <section class="content code">
    <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?>
Content-type: application/json

<span id="postbody">{
  "type": ["h-entry"],
  "properties": {
    "content": ["Micropub update test #405."]
  }
}</span></pre>
  </section>

  <section class="content">
    <button class="ui green button" id="run">Run</button>
    <ul class="result-list">
      <li><span id="passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>201</code> or <code>202</code></li>
      <li><span id="passed_location" class="ui circular label">&nbsp;</span> Returned a <code>Location</code> header <span id="location_header_value"></span></li>
    </ul>
  </section>

  <section class="content hidden" id="response-section">
    <h3>Response</h3>
    <pre id="response" class="small"></pre>
  </section>

  <div id="continue" class="hidden">
    <section class="content">
      <h3>Great!</h3>

      <p>Creating the post worked. Click below to run the update. Your endpoint should reject this request.</p>
    </section>

    <section class="content code">
      <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?>
Content-type: application/json

<span id="updatebody">{
  "action": "update",
  "url": "%%%",
  "replace": "This is not a valid update request."
}</span></pre>
    </section>

    <section class="content">
      <button class="ui green button" id="run-update">Continue</button>
      <ul class="result-list">
        <li><span id="update_passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>400</code></li>
      </ul>
    </section>

    <section class="content hidden" id="update-response-section">
      <h3>Response</h3>
      <pre id="update-response" class="small"></pre>
    </section>
  </div>

</div>
<script>
var test = <?= $test->id ?>;
var endpoint = <?= $endpoint->id ?>;

set_up_json_test(test, endpoint, function(data){
  $("#run").removeClass('green').addClass('disabled');
  var passed_code = false;
  var passed_location = false;
  if(data.code == 201 || data.code == 202) {
    passed_code = true;
  }
  set_result_icon("#passed_code", passed_code ? 1 : -1);
  if(data.location) {
    passed_location = true;
    $("#location_header_value").html('<a href="'+data.location+'" target="_blank">view post</a>');
  }
  set_result_icon("#passed_location", passed_location ? 1 : -1);
  store_result(test, endpoint, (passed_code && passed_location ? 0 : -1));
  if(passed_code && passed_location) {
    $("#response-section").addClass("hidden");
    $("#continue").removeClass("hidden");
    $("#updatebody").text($("#updatebody").text().replace("%%%", data.location));
    set_up_update_test(test, endpoint, function(data2) {
      var passed_code = false;
      if(data2.code == 400) {
        passed_code = true;
      }
      set_result_icon("#update_passed_code", passed_code ? 1 : -1);
      store_result(test, endpoint, (passed_code ? 1 : -1));
    })
  }
});

</script>
