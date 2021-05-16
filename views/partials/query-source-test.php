<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p>This test will check if your endpoint supports the "source" query to retrieve the original content in a post. This test starts by creating a post at your endpoint, then when you click "Continue", it will query your endpoint to ask for the source content.</p>
    <p>Clicking "Run" will make the following request to your endpoint.</p>
  </section>

  <section class="content code">
    <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?>
Content-type: application/json

<span id="postbody">{
  "type": ["h-entry"],
  "properties": {
    "content": ["Test of querying the endpoint for the source content"],
    "category": ["micropub", "test"]
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

    <section class="content code">
      <pre>GET <span id="query_url"><?= $query_url ?></span> HTTP/1.1
  Authorization: Bearer <?= $endpoint->access_token."\n" ?></pre>
    </section>

    <section class="content">
      <button class="ui green button" id="run-query">Run</button>
      <ul class="result-list">
        <li><span id="query_passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>200</code></li>
        <li><span id="query_passed_body" class="ui circular label">&nbsp;</span> Returned the requested properties with the appropriate values.</li>
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
    if(props.content && props.category) {
      if($.inArray("micropub", props.category) >= 0 
        && $.inArray("test", props.category)
        && (typeof props.content[0] === 'object' && props.content[0] !== null) ?
         props.content[0].value == "Test of querying the endpoint for the source content" :
         props.content[0] == "Test of querying the endpoint for the source content"
      ) {
        passed_body = true;
      }
    }
  }
  set_result_icon("#query_passed_body", passed_body ? 1 : -1);

  store_result(test, endpoint, (passed_code && passed_body ? 1 : -1));

  store_server_feature(endpoint, <?= $feature_num ?>, (passed_code && passed_body ? 1 : -1), test);
});

</script>
