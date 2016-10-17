<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <div id="publish-btn" class="hidden" style="float:right;">
      <button class="ui button" id="edit">Edit</button>
      <button class="ui primary button" id="publish">Publish</button>
    </div>
    <div id="save-btn" class="hidden" style="float:right;">
      <button class="ui button" id="save">Save</button>
    </div>

    <h3>Implementation Report</h3>

    <p>
    <form class="ui form">
      <table style="width:100%;" id="report-details">
        <tr>
          <td>Endpoint URL</td>
          <td><?= $endpoint->micropub_endpoint ?></td>
        </tr>
        <tr>
          <td>Software Name</td>
          <td>
            <input class="hidden" type="text" id="implementation_name" value="<?= htmlspecialchars($endpoint->implementation_name) ?>">
            <span class="hidden value"><?= htmlspecialchars($endpoint->implementation_name) ?></span>
          </td>
        </tr>
        <tr>
          <td>Software Home Page</td>
          <td>
            <input class="hidden" type="url" id="implementation_url" value="<?= htmlspecialchars($endpoint->implementation_url) ?>">
            <a class="hidden value" href="<?= $endpoint->implementation_url ?>">
              <?= $endpoint->implementation_url ?>
            </a>
          </td>
        </tr>
        <tr>
          <td>Developer Name</td>
          <td>
            <input class="hidden" type="text" id="developer_name" value="<?= htmlspecialchars($endpoint->developer_name) ?>">
            <span class="hidden value"><?= htmlspecialchars($endpoint->developer_name) ?></span>
          </td>
        </tr>
        <tr>
          <td>Developer Home Page</td>
          <td>
            <input class="hidden" type="url" id="developer_url" value="<?= htmlspecialchars($endpoint->developer_url) ?>">
            <a class="hidden value" href="<?= $endpoint->developer_url ?>">
              <?= $endpoint->developer_url ?>
            </a>
          </td>
        </tr>
        <tr>
          <td>Programming Language</td>
          <td>
            <input class="hidden" type="text" id="programming_language" value="<?= htmlspecialchars($endpoint->programming_language) ?>">
            <span class="hidden value"><?= $endpoint->programming_language ?></span>
          </td>
        </tr>
      </table>
    </form>
    </p>

    <div class="ui warning message hidden" id="public-info">This information will be made public as part of the implementation report.</div>

    <div class="ui message" id="results-info">The results below are automatically compiled from the various test results for your implementation. You can re-run tests to update the results here.</div>

    <table class="implementation-features">
      <? foreach($results as $result): ?>
        <tr>
          <td class="num"><?= $result->number ?></td>
          <td><?= result_icon($result->implements) ?></td>
          <td><?= $result->description ?></td>
        </tr>
      <? endforeach; ?>
    </table>

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

var endpoint_id = <?= $endpoint->id ?>;

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
});
</script>
