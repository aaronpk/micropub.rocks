<?php $this->layout('layout', [
                      'title' => $test->name,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p>This is a test of creating an h-entry post in JSON format including a <code>photo</code> property that references multiple URLs. Your endpoint should recognize the photo property, and either download them to your own server, or render the post with the hotlinked URLs.</p>
    <p>Clicking "Run" will make the following request to your endpoint.</p>
  </section>

  <section class="content code">
    <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?>
Content-type: application/json

<span id="postbody">{
  "type": ["h-entry"],
  "properties": {
    "content": ["Micropub test of creating multiple photos referenced by URL. This post should include a photo of a sunset and a photo of a city at night."],
    "photo": [
      "<?= Config::$base ?>media/sunset.jpg",
      "<?= Config::$base ?>media/city-at-night.jpg"
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
        <div><span id="passed_photo" class="ui circular label">&nbsp;</span> Check that the photos appears in the post</div>
        <div class="step_instructions hidden">Look at <a href="" target="_blank">your post</a> and check this box if the post shows both photos.</div>
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
  $("#passed_photo").addClass("prompt");
  $(".step_instructions").removeClass("hidden");
  $(".step_instructions a").attr('href', data.location);
  $("#passed_photo").click(function(){
    set_result_icon("#passed_photo", 1);
    $(".step_instructions").addClass("hidden");
    store_result(test, endpoint, (passed_code && passed_location ? 1 : -1));
  });
});

</script>
