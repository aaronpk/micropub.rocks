<?php 
$this->layout('layout', [
                  'title' => $test->name,
                ]); 

$query_url = build_micropub_query_url($endpoint->micropub_endpoint, [
  'q' => 'source',
  'url' => '%%%',
]);
?>

<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p>This test will ensure your endpoint does not store the access token in the post. It does this by creating a post while providing the access token in the post body, then querying the endpoint for the source properties of the post to ensure the access token is not returned as part of the content. In order to pass this test, you will also have to support the <a href="/server-tests/602?endpoint=<?= $endpoint->id ?>">Source Query</a> test.</p>
    <p>Clicking "Run" will make the following request to your endpoint.</p>
  </section>

  <section class="content code">
    <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Content-type: application/x-www-form-urlencoded; charset=utf-8

<span id="postbody">h=entry&amp;
content=Testing+accepting+access+token+in+post+body&amp;
access_token=<?= $endpoint->access_token ?></span></pre>
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

    <section class="content code">
      <pre>GET <span id="query_url"><?= $query_url ?></span> HTTP/1.1
  Authorization: Bearer <?= $endpoint->access_token."\n" ?></pre>
    </section>

    <section class="content">
      <button class="ui green button" id="run-query">Run</button>
      <ul class="result-list">
        <li><span id="query_passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>200</code></li>
        <li><span id="query_passed_body" class="ui circular label">&nbsp;</span> Returned the source content and did not include the access token.</li>
      </ul>
    </section>

    <section class="content hidden" id="query-response-section">
      <h3>Response</h3>
      <pre id="query-response" class="small"></pre>
    </section>

  </div>

</div>
<script>
var test = <?= $test->id ?>;
var endpoint = <?= $endpoint->id ?>;

set_up_form_test(test, endpoint, function(data){
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
  $("#continue").removeClass("hidden");
  $("#query_url").text($("#query_url").text().replace("%25%25%25", encodeURIComponent(data.location)));
});

set_up_query_test(test, endpoint, function(data){
  var passed_code = false;
  var passed_body = false;
  if(data.code == 200) {
    passed_code = true;
  }
  set_result_icon("#query_passed_code", passed_code ? 1 : -1);
  if(data.json && data.json.properties) {
    var props = data.json.properties;
    if(props.content && !props.access_token) {
      if(props.content[0] == "Testing accepting access token in post body") {
        passed_body = true;
      } else if (props.content[0] && props.content[0].value && props.content[0].value == "Testing accepting access token in post body") {
        passed_body = true;
      }
    }
  }
  set_result_icon("#query_passed_body", passed_body ? 1 : -1);

  store_result(test, endpoint, (passed_code && passed_body ? 1 : -1));
});

</script>
