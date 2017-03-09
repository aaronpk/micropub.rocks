<?php $this->layout('layout', [
                      'title' => $test->name,
                      'client' => $client,
                      'test' => $test
                    ]); ?>
<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>
    <?php if(!$post_debug): ?>
      <?= $test->description ?>
      <p>The post's URL to reference in the update is:<br>
        <input type="url" style="width:100%" value="<?= $post_url ?>"></p>
    <?php endif ?>
  </section>

  <section id="result">
    <?= $post_html ?>
  </section>
  <section class="content hidden" id="debug">
    <h4>Raw Request</h4>
    <pre><?= $post_debug ?></pre>
  </section>

</div>
<script>
$(function(){
  if(window.EventSource) {
    var socket = new EventSource('/streaming/sub?id=client-<?= $client->token ?>');

    socket.onmessage = function(event) {
      var data = JSON.parse(event.data);
      if(data.text.action == 'client-result') {
        $("#result").removeClass("hidden").html(data.text.html);
        $("#debug").removeClass("hidden");
        $("#debug pre").text(data.text.debug);
      }
    }    
  } else {
    $("#result").removeClass("hidden").html('<div class="ui error message">Error: Your browser does not support <a href="http://caniuse.com/eventsource">EventSource</a> so the test results will not appear.</div>');
  }
  <?php if($post_debug): ?>
    $("#debug").removeClass("hidden");
  <?php endif ?>
});
</script>
