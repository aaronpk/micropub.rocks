<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p><?= htmlspecialchars($description) ?></p>
    <p>Clicking "Run" will first create a post, and after you've confirmed it's been created, you can click "Continue" to make the edit.</p>
  </section>

  <section class="content code">
    <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?>
Content-type: application/json

<span id="postbody"><?= $postbody ?></span></pre>
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

      <p>Creating the post worked. Click below to run the update.</p>
    </section>

    <section class="content code">
      <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?>
Content-type: application/json

<span id="updatebody"><?= $updatebody ?></span></pre>
    </section>

    <section class="content">
      <button class="ui green button" id="run-update">Continue</button>
      <ul class="result-list">
        <li><span id="update_passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>201</code>, <code>202</code> or <code>204</code></li>
        <li id="update_location_row" class="hidden"><a href="" id="update_location">View Post</a></li>
        <li>
          <div><span id="passed_update" class="ui circular label">&nbsp;</span> Check that the post reflects the updated content</div>
        </li>
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
      if(data2.code == 200 || data2.code == 204) {
        passed_code = true;
      } else if(data2.code == 201 && data2.location) {
        passed_code = true;
      }
      set_result_icon("#update_passed_code", passed_code ? 1 : -1);
      if(data2.location) {
        $("#update_location").attr("href", data2.location);
      } else {
        $("#update_location").attr("href", data.location);
      }
      $("#update_location_row").removeClass("hidden");
      $("#passed_update").addClass("prompt");
      $("#passed_update").click(function(){
        set_result_icon("#passed_update", 1);
        store_result(test, endpoint, (passed_code ? 1 : -1));
        store_server_feature(endpoint, <?= $feature_num ?>, (passed_code ? 1 : -1), test);
        var response_feature = false;
        if(data2.code == 200) {
          response_feature = 20;
        } else if(data2.code == 201) {
          response_feature = 21;
        } else if(data2.code == 204) {
          response_feature = 22;
        }
        if(response_feature) {
          store_server_feature(endpoint, response_feature, (passed_code ? 1 : -1), test);
        }
      });
    })
  }
});

</script>
