<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p><?= htmlspecialchars($description) ?></p>
    <p>Clicking "Run" will first create a post, and after you've confirmed it's been created, you can click "Continue" to delete the post, then "Continue" to undelete it.</p>
  </section>

  <section class="content code">
    <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?>
Content-type: application/x-www-form-urlencoded; charset=utf-8

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

      <p>Creating the post worked. Click "continue" below to delete the post.</p>
    </section>

    <section class="content code">
      <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?>
Content-type: <?= $content_type == 'json' ? 'application/json' : 'application/x-www-form-urlencoded; charset=utf-8' ?>


<span id="deletebody"><?= $deletebody ?></span></pre>
    </section>

    <section class="content">
      <button class="ui green button" id="run-delete">Continue</button>
      <ul class="result-list">
        <li><span id="delete_passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>201</code>, <code>202</code> or <code>204</code></li>
        <li>
          <div><span id="passed_delete" class="ui circular label">&nbsp;</span> Confirm that the post was deleted <a id="deleted_post_link"></a></div>
        </li>
      </ul>
    </section>

    <section class="content hidden" id="delete-response-section">
      <h3>Response</h3>
      <pre id="delete-response" class="small"></pre>
    </section>
  </div>


  <div id="continue2" class="hidden">
    <section class="content">
      <h3>Great!</h3>

      <p>Now that you've deleted the post, click "continue" below to undelete it.</p>
    </section>

    <section class="content code">
      <pre>POST <?= $endpoint->micropub_endpoint ?> HTTP/1.1
Authorization: Bearer <?= $endpoint->access_token."\n" ?>
Content-type: <?= $content_type == 'json' ? 'application/json' : 'application/x-www-form-urlencoded; charset=utf-8' ?>


<span id="undeletebody"><?= $undeletebody ?></span></pre>
    </section>

    <section class="content">
      <button class="ui green button" id="run-undelete">Continue</button>
      <ul class="result-list">
        <li><span id="undelete_passed_code" class="ui circular label">&nbsp;</span> Returned HTTP <code>201</code>, <code>202</code> or <code>204</code></li>
        <li>
          <div><span id="passed_undelete" class="ui circular label">&nbsp;</span> Confirm that the post was restored <a id="undeleted_post_link"></a></div>
        </li>
      </ul>
    </section>

    <section class="content hidden" id="undelete-response-section">
      <h3>Response</h3>
      <pre id="undelete-response" class="small"></pre>
    </section>
  </div>

</div>
<script>
var test = <?= $test->id ?>;
var endpoint = <?= $endpoint->id ?>;

set_up_<?= $content_type == 'json' ? 'json' : 'form' ?>_test(test, endpoint, function(data){
  $("#run").removeClass('green').addClass('disabled');
  var passed_code = false;
  var passed_location = false;
  if(data.code == 201 || data.code == 202) {
    passed_code = true;
  }
  set_result_icon("#passed_code", passed_code ? 1 : -1);
  if(data.location) {
    passed_location = true;
    $("#location_header_value").html('<a href="'+data.location+'">view post</a>');
  }
  set_result_icon("#passed_location", passed_location ? 1 : -1);
  store_result(test, endpoint, (passed_code && passed_location ? 0 : -1));
  if(passed_code && passed_location) {
    $("#response-section").addClass("hidden");
    $("#continue").removeClass("hidden");
    $("#deletebody").text($("#deletebody").text().replace("%%%", data.location));
    set_up_delete_test(test, '<?= ($content_type == 'json' ? 'postjson' : 'post') ?>', endpoint, function(data2) {
      var passed_code = false;
      if(data2.code == 200 || data2.code == 201 || data2.code == 204) {
        passed_code = true;
      }
      set_result_icon("#delete_passed_code", passed_code ? 1 : -1);
      if(passed_code) {
        $("#passed_delete").addClass("prompt");
        $("#deleted_post_link").attr("href", data.location).text(data.location);
        $("#passed_delete").click(function(){
          set_result_icon("#passed_delete", 1);
          store_result(test, endpoint, (passed_code ? 0 : -1));
          $("#continue2").removeClass("hidden");
          $("#undeletebody").text($("#undeletebody").text().replace("%%%", data.location));
          set_up_undelete_test(test, '<?= ($content_type == 'json' ? 'postjson' : 'post') ?>', endpoint, function(data3) {
            var passed_code2 = false;
            if(data3.code == 200 || data3.code == 201 || data3.code == 204) {
              passed_code2 = true;
            }
            set_result_icon("#undelete_passed_code", passed_code2 ? 1 : -1);
            store_result(test, endpoint, passed_code2 ? 0 : -1);
            if(passed_code2) {
              $("#passed_undelete").addClass("prompt");
              $("#undeleted_post_link").attr("href", data.location).text(data.location);
              $("#passed_undelete").click(function(){
                set_result_icon("#passed_undelete", 1);
                store_result(test, endpoint, passed_code2 ? 1 : -1);
              });
            }
          });
        });
      }
    })
  }
});

</script>
