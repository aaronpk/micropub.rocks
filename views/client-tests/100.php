<?php $this->layout('layout', [
                      'title' => $test->name,
                    ]); ?>
<div class="single-column">

  <section class="content">
    <h2><?= e($test->number . ': ' . $test->name) ?></h2>

    <p>This is a basic test of creating an h-entry post from your client in form-encoded format.</p>
    <p>To pass this test, use your client to create an h-entry post with plain text content. It doesn't matter what text you send.</p>
    <p>Keep this page open and post from your client. Your post will appear here.</p>
  </section>

  <section class="hidden" id="result">

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
      }
    }    
  } else {
    $("#result").removeClass("hidden").html('<div class="ui error message">Error: Your browser does not support <a href="http://caniuse.com/eventsource">EventSource</a> so the test results will not appear.</div>');
  }
});
</script>
