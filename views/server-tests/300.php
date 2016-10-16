<?php $this->layout('layout', [
                      'title' => $test->name,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p>This is a basic test of posting a photo to the Micropub endpoint.</p>
    <p>Clicking "Run" will make a multipart request to your endpoint containing one photo.</p>
  </section>

  <section class="content code">
    <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?>

...multipart data...
</pre>
  </section>

  <section class="content">
    <button class="ui green button" id="run">Run</button>
    <ul class="result-list">
      <li><span id="passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>201</code> or <code>202</code></li>
      <li><span id="passed_location" class="ui circular label">&nbsp;</span> Returned a <code>Location</code> header <span id="location_header_value"></span></li>
      <li>
        <div><span id="passed_photo" class="ui circular label">&nbsp;</span> Check that the photo appears in the post</div>
        <div class="step_instructions hidden">Look at <a href="" target="_blank">your post</a> and check this box if the post shows the photo.</div>
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

var params = {
  h: "entry",
  content: "Nice sunset tonight"
};
var files = {
  photo: 'sunset.jpg'
};

set_up_multipart_test(test, endpoint, null, params, files, function(data){
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
