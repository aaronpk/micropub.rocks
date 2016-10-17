<?php 
$this->layout('layout', [
                  'title' => $test->name,
                ]); 

$query_url = build_micropub_query_url($endpoint->micropub_endpoint, [
  'q' => 'config',
]);
?>
<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p>This test will check if your endpoint supports the "config" query. To pass this test, your endpoint must return HTTP 200 and a JSON object in the response. The JSON object can be an empty object if the endpoint has nothing to report, or can include <code>media-endpoint</code> and/or <code>syndicate-to</code> properties.</p>
    <p>Clicking "Run" will make the following request to your endpoint.</p>
  </section>

  <section class="content code">
    <pre>GET <span id="query_url"><?= $query_url ?></span> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?></pre>
  </section>

  <section class="content">
    <button class="ui green button" id="run-query">Run</button>
    <ul class="result-list">
      <li><span id="passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>200</code></li>
      <li><span id="passed_body" class="ui circular label">&nbsp;</span> Returned a JSON object</li>
    </ul>
  </section>

  <section class="content hidden" id="query-response-section">
    <h3>Response</h3>
    <pre id="query-response" class="small"></pre>
  </section>

</div>
<script>
var test = <?= $test->id ?>;
var endpoint = <?= $endpoint->id ?>;

set_up_query_test(test, endpoint, function(data){
  var passed_code = false;
  var passed_body = false;
  if(data.code == 200) {
    passed_code = true;
  }
  set_result_icon("#passed_code", passed_code ? 1 : -1);
  if(data.json) {
    passed_body = true;
  }
  set_result_icon("#passed_body", passed_body ? 1 : -1);
  store_result(test, endpoint, (passed_code && passed_body ? 1 : -1));

  store_server_feature(endpoint, 28, (passed_code && passed_body ? 1 : -1), test);

});

</script>
