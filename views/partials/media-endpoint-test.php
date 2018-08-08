<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p>This test will discover your Media Endpoint, and then upload an image to it, and check that the response from the Media Endpoint is correct.</p>
    <p>Clicking "Run" will make the following request to your Micropub endpoint to discover the Media Endpoint.</p>
  </section>

  <section class="content code">
    <pre>GET <span id="query_url"><?= $query_url ?></span> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?></pre>
  </section>

  <section class="content">
    <button class="ui green button" id="run-query">Start</button>
    <ul class="result-list">
      <li><span id="passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>200</code></li>
      <li><span id="passed_body" class="ui circular label">&nbsp;</span> Returned a JSON object with a <code>media-endpoint</code> property with the full URL of the endpoint</li>
    </ul>
  </section>

  <section class="content hidden" id="query-response-section">
    <h3>Response</h3>
    <pre id="query-response" class="small"></pre>
  </section>

  <div id="continue" class="hidden">
    <section class="content">
      <h3>Great!</h3>

      <p>We found the Media Endpoint and are ready to upload the image to it. Click "Continue" below to upload the file. This will make a multipart request to the URL below, with one part, a file named "file".</p>

      <pre id="media-endpoint"></pre>
    </section>

    <section class="content">
      <button class="ui green button" id="run">Upload</button>
      <ul class="result-list">
        <li><span id="upload_passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>201</code></li>
        <li><span id="upload_passed_location" class="ui circular label">&nbsp;</span> Returned a <code>Location</code> header</li>
        <li>
          <div><span id="passed_upload" class="ui circular label">&nbsp;</span> The URL exists and has the expected content type (<?= $content_type ?>). <a id="upload_location" target="_blank"></a></div>
        </li>
      </ul>
    </section>

    <section class="content hidden" id="response-section">
      <h3>Media Endpoint Response</h3>
      <pre id="response" class="small"></pre>
    </section>

    <section class="content hidden" id="media-response-section">
      <h3>File Info</h3>
      <pre id="media-response" class="small"></pre>
    </section>

  </div>

</div>
<script>
var test = <?= $test->id ?>;
var endpoint = <?= $endpoint->id ?>;
var filename = '<?= $filename ?>';
var expected_content_type = '<?= $content_type ?>';
var media_endpoint;

set_up_query_test(test, endpoint, function(data){
  var passed_code = false;
  var passed_body = false;
  if(data.code == 200) {
    passed_code = true;
  }
  set_result_icon("#passed_code", passed_code ? 1 : -1);
  if(data.json && data.json['media-endpoint']) {
    if(typeof data.json['media-endpoint'] == "string") {
      // basic regex to validate this is a URL with a path
      if(data.json['media-endpoint'].match(/^https?:\/\/[^\/]+\/[^ ]*$/)) {
        passed_body = true;
        media_endpoint = data.json['media-endpoint'];
      }
    }
  }
  set_result_icon("#passed_body", passed_body ? 1 : -1);
  store_result(test, endpoint, (passed_code && passed_body && media_endpoint ? 0 : -1));
  if(media_endpoint) {
    $("#query-response-section").addClass("hidden");
    $("#media-endpoint").text(media_endpoint);
    $("#continue").removeClass("hidden");

    var files = {
      file: filename
    };
    set_up_multipart_test(test, endpoint, media_endpoint, {}, files, function(data2){
      var passed_code = data2.code == 201;
      var passed_location = false;
      set_result_icon("#upload_passed_code", passed_code ? 1 : -1);
      if(data2.location) {
        $("#upload_location").attr("href", data2.location).text("View File");
        passed_location = true;
      }
      set_result_icon("#upload_passed_location", passed_code ? 1 : -1);
      store_result(test, endpoint, (passed_code && passed_location ? 0 : -1));

      if(passed_code && passed_location) {
        $("#run").removeClass('green').addClass('disabled');

        $.post('/server-tests/media-check', {
          url: data2.location
        }, function(data) {
          $("#run").addClass('green').removeClass('disabled');
          console.log(data);
          var passed_upload = data.code == 200 && data.content_type && data.content_type == expected_content_type;

          if(!passed_upload) {
            $("#media-response").text(data.http + "\n" + "Content-Type: "+data.content_type);
            $("#media-response-section").removeClass("hidden");
          }

          set_result_icon("#passed_upload", passed_upload ? 1 : -1);
          store_result(test, endpoint, (passed_upload ? 1 : -1));

          store_server_feature(endpoint, 10, (passed_upload ? 1 : -1), test);
          if(passed_upload) {
            store_server_feature(endpoint, 27, 1, test);
          }
        });
      }
    });

  }
});

</script>
