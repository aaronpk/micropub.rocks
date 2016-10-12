<?php $this->layout('layout', [
                      'title' => $test->name,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p>This is a test of creating an h-entry post in JSON format including a photo property with alt text.</p>
    <p>Clicking "Run" will make the following request to your endpoint.</p>
  </section>

  <section class="content code">
    <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?>
Content-type: application/json

<span id="postbody">{
  "type": ["h-entry"],
  "properties": {
    "content": ["Micropub test of creating a photo referenced by URL with alt text. This post should include a photo of a sunset."],
    "photo": [
      {
        "value": "<?= Config::$base ?>media/sunset.jpg",
        "alt": "Photo of a sunset"
      }
    ]
  }
}</span></pre>
  </section>

  <section class="content">
    <button class="ui green button" id="run">Run</button>
    <ul class="result-list">
      <li><?= result_icon(0, 'passed_code') ?> Returned HTTP <code>201</code> or <code>202</code></li>
      <li><?= result_icon(0, 'passed_location') ?> Returned a <code>Location</code> header <span id="location_header_value"></span></li>
      <li>
        <div><span id="passed_object" class="ui circular label">&nbsp;</span> Check that the photo and alt text were stored</div>
        <div class="step_instructions hidden">Look at <a href="" target="_blank">your post</a>, and check the box if the photo appears with the image alt text.</div>
      </li>
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

set_up_json_test(test, endpoint, function(data){
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
  // Store the test as failing if either code or location was not returned
  if(!(passed_location && passed_code)) {
    store_result(test, endpoint, -1);
  }
  set_result_icon("#passed_location", passed_location ? 1 : -1);
  $("#passed_object").addClass("prompt");
  $(".step_instructions").removeClass("hidden");
  $(".step_instructions a").attr('href', data.location);
  $("#passed_object").click(function(){
    set_result_icon("#passed_object", 1);
    $(".step_instructions").addClass("hidden");
    store_result(test, endpoint, (passed_code && passed_location ? 1 : -1));
  });
});

</script>
