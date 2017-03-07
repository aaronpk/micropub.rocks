<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content">
  <!--
    <div id="publish-btn" class="hidden" style="float:right;">
      <button class="ui button" id="edit">Edit</button>
      <button class="ui primary button" id="publish">Publish</button>
    </div>
    <div id="save-btn" class="hidden" style="float:right;">
      <button class="ui button" id="save">Save</button>
    </div>
  -->

    <h3>Implementation Report</h3>

    <p>
    <form class="ui form">
      <table style="width:100%;" id="report-details">
        <tr>
          <td>Client Name</td>
          <td><?= $client->name ?></td>
        </tr>
        <!--
        <tr>
          <td>Software Name</td>
          <td>
            <input class="hidden" type="text" id="implementation_name" value="<?= htmlspecialchars($client->implementation_name) ?>">
            <span class="hidden value"><?= htmlspecialchars($client->implementation_name) ?></span>
          </td>
        </tr>
        <tr>
          <td>Software Home Page</td>
          <td>
            <input class="hidden" type="url" id="implementation_url" value="<?= htmlspecialchars($client->implementation_url) ?>">
            <a class="hidden value" href="<?= $client->implementation_url ?>">
              <?= $client->implementation_url ?>
            </a>
          </td>
        </tr>
        <tr>
          <td>Developer Name</td>
          <td>
            <input class="hidden" type="text" id="developer_name" value="<?= htmlspecialchars($client->developer_name) ?>">
            <span class="hidden value"><?= htmlspecialchars($client->developer_name) ?></span>
          </td>
        </tr>
        <tr>
          <td>Developer Home Page</td>
          <td>
            <input class="hidden" type="url" id="developer_url" value="<?= htmlspecialchars($client->developer_url) ?>">
            <a class="hidden value" href="<?= $client->developer_url ?>">
              <?= $client->developer_url ?>
            </a>
          </td>
        </tr>
        <tr>
          <td>Programming Language</td>
          <td>
            <input class="hidden" type="text" id="programming_language" value="<?= htmlspecialchars($client->programming_language) ?>">
            <span class="hidden value"><?= $client->programming_language ?></span>
          </td>
        </tr>
        -->
      </table>
    </form>
    </p>

    <div class="ui warning message hidden" id="public-info">This information will be made public as part of the implementation report.</div>

    <div class="ui message" id="results-info">The results below are automatically compiled from the various test results for your implementation. You can re-run tests to update the results here.</div>

    <table class="implementation-features">
      <? foreach($results as $result): ?>
        <tr id="feature-<?= $result->number ?>">
          <td class="num"><?= $result->number ?></td>
          <td class="result"><?= result_icon($result->implements) ?></td>
          <td><?= $result->description ?></td>
        </tr>
      <? endforeach; ?>
    </table>

    <h3>Implementation Report Template</h3>

    <p>Below is an implementation report template you can use to submit an implementation report. The answers have been pre-filled based on the checkboxes above, but there are some questions that do not have a corresponding automatic test. Please review the answers in this report and fill out any missing information based on your implementation. When you are complete, you can submit this as a new file <a href="https://github.com/w3c/Micropub/tree/master/implementation-reports#clients">on GitHub</a>.</p>

    <pre id="impl-report-text"># Implementation Name (Replace this header)

Implementation Home Page URL: 

Source code repo URL(s) (optional):
* [ ] 100% open source implementation

Programming Language(s): 

Developer(s): [Name](https://you.example.com)

## Discovery
* [<?= result_checkbox($results, 1) ?>] The client discovers the Micropub endpoint given the profile URL of a user (e.g. the sign-in form asks the user to enter their URL, which is used to find the Micropub endpoint)

## Authentication
* [<?= result_checkbox($results, 2) ?>] The client sends the access token in the HTTP `Authorization` header.
* [<?= result_checkbox($results, 3) ?>] The client sends the access token in the post body for `x-www-form-urlencoded` requests.
* [<?= result_checkbox($results, 4) ?>] The client requests one or more `scope` values when obtaining user authorization.
 * (list scopes requested here)

## Syntax
* [<?= result_checkbox($results, 5) ?>] 100: Creates posts using `x-www-form-urlencoded` syntax.
* [<?= result_checkbox($results, 6) ?>] 200: Creates posts using JSON syntax.
* [<?= result_checkbox($results, 7) ?>] 101: Creates posts using `x-www-form-urlencoded` syntax with multiple values of the same property name (e.g. tags).
* [<?= result_checkbox($results, 8) ?>] 201: Creates posts using JSON syntax with multiple values of the same property name (e.g. tags).
* [<?= result_checkbox($results, 33) ?>] 202: Creates posts with HTML content. (JSON)
* [<?= result_checkbox($results, 9) ?>] 204: Creates posts using JSON syntax including a nested Microformats2 object.
* [<?= result_checkbox($results, 10) ?>] 300: Creates posts including a file by sending the request as `multipart/form-data` to the Micropub endpoint.

## Creating Posts
* [<?= result_checkbox($results, 11) ?>] 104: Allows creating posts with a photo referenced by URL rather than uploading the photo as a Multipart request. (form-encoded)
* [<?= result_checkbox($results, 12) ?>] 203: Allows creating posts with a photo referenced by URL rather than uploading the photo as a Multipart request. (JSON)
* [<?= result_checkbox($results, 13) ?>] 205: Allows creating posts with a photo including image alt text.
* [<?= result_checkbox($results, 14) ?>] Recognizes HTTP 201 and 202 with a `Location` header as a successful response from the Micropub endpoint.
* [<?= result_checkbox($results, 15) ?>] 105: Allows the user to specify one or more syndication endpoints from their list of endpoints discovered in the `q=config` or `q=syndicate-to` query.

## Media Endpoint
* [<?= result_checkbox($results, 16) ?>] Checks to see if the Micropub endpoint specifies a Media Endpoint, and uploads photos there instead.
* [ ] Uses multipart requests only as a fallback when there is no Media Endpoint specified.

## Updates
* [ ] Supports replacing all values of a property (e.g. replacing the post content).
* [ ] Supports adding a value to a property (e.g. adding a tag).
* [ ] Supports removing a value from a property (e.g. removing a specific tag).
* [ ] Supports removing a property.
* [ ] Recognizes HTTP 200, 201 and 204 as a successful response from the Micropub endpoint.

## Deletes
* [ ] Sends deletion requests using `x-www-form-urlencoded` syntax.
* [ ] Sends deletion requests using JSON syntax.
* [ ] Sends undeletion requests using `x-www-form-urlencoded` syntax.
* [ ] Sends undeletion requests using JSON syntax.

## Querying
* [<?= result_checkbox($results, 27) ?>] Queries the Micropub endpoint with `q=config`
 * [ ] Looks in the response for the Media Endpoint
 * [ ] Looks in the response for syndication targets
* [<?= result_checkbox($results, 30) ?>] Queries the Micropub endpoint with `q=syndicate-to`
* [ ] Queries the Micropub endpoint for a post's source content without specifying a list of properties
* [ ] Queries the Micropub endpoint for a post's source content looking only for specific properties

## Extensions

Please list any [Micropub extensions](https://indieweb.org/Micropub-extensions) that the client supports.

## Vocabularies

Please list all vocabularies and properties the client supports, if applicable.

## Other Notes

Please use this space to document anything else significant about your implementation.
</pre>

  </section>
</div>

<script>
function show_editing() {
  $("#report-details input").removeClass("hidden");
  $("#public-info").removeClass("hidden");
  $("#save-btn").removeClass("hidden");

  $("#results-info").addClass("hidden");
  $("#publish-btn").addClass("hidden");
  $("#report-details .value").addClass("hidden");
}
function hide_editing() {
  $("#report-details input").addClass("hidden");
  $("#public-info").addClass("hidden");
  $("#save-btn").addClass("hidden");

  $("#results-info").removeClass("hidden");
  $("#publish-btn").removeClass("hidden");
  $("#report-details .value").removeClass("hidden");
}

var client_id = <?= $client->id ?>;

var icons = {
  "1": '<?= result_icon(1) ?>',
  "0": '<?= result_icon(0) ?>',
  "-1": '<?= result_icon(-1) ?>',
};

$(function(){
  if($("#implementation_name").val() == "") {
    show_editing();
  } else {
    hide_editing();
  }
  $("#save").click(function(){
    var data = {};
    $("#report-details input").each(function(){
      data[$(this).attr("id")] = $(this).val();
      $(this).siblings(".value").text($(this).val());
      $(this).siblings(".value").attr("href", $(this).val());
    });
    console.log(data);

    $.post("/implementation-report/save", {
      type: 'server',
      id: endpoint_id,
      data: data
    }, function(data){
      hide_editing();
    });

    return false;
  });
  $("#publish").click(function(){
    $.post("/implementation-report/publish", {
      type: 'server',
      id: endpoint_id
    }, function(data){
      window.location = data.location;
    });
  });
  $("#edit").click(function(){
    show_editing();
  });

  // Streaming API
  if(window.EventSource) {
    // Subscribe to the streaming channel and insert responses as they come in
    var socket = new EventSource('/streaming/sub?id=client-'+client_id);
    socket.onmessage = function(event) {
      var data = JSON.parse(event.data);
      $("#feature-"+data.text.feature+" .result").html(icons[data.text.implements]);
    }
  }
});
</script>
<style type="text/css">
#impl-report-text {
  font-size: 10pt;
  white-space: pre-wrap;
}
</style>