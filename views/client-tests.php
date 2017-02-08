<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <div class="endpoint-details">
      <h3>
        <?= $client->name ?>
        <a href="/clients/<?= $client->id ?>"><i class="setting icon"></i></a>
      </h3>
    </div>

    <div id="sign-in-options" style="padding-top: 1em;">
      <div class="ui top attached tabular menu">
        <a class="active item" data-tab="first">Sign In</a>
        <a class="item" data-tab="second">Manual</a>
      </div>
      <div class="ui bottom attached active tab segment" data-tab="first">
        <p>Copy the URL below to use when signing in to your client.</p>

        <div class="ui fluid input">
          <input type="url" readonly="readonly" value="<?= Config::$base ?>client/<?= $client->token ?>" onclick="this.select()">
        </div>
        <br>

        <p>See <a href="https://indieweb.org/obtaining-an-access-token">Obtaining an Access Token</a> for documentation on how to discover the endpoints from this URL and build the request for authorization.</p>
      </div>
      <div class="ui bottom attached tab segment" data-tab="second">
        <p>Generate an access token below and copy and paste it into your client.</p>

        <label>Micropub Endpoint</label>
        <div class="ui fluid input">
          <input type="url" readonly="readonly" value="<?= Config::$base ?>client/<?= $client->token ?>/micropub" onclick="this.select()">
        </div>

        <label>Access Token</label>
        <div class="ui fluid input">
          <input id="client_access_token" type="text" readonly="readonly" value="" placeholder="Click to generate an access token">
        </div>
      </div>
    </div>

    <div class="ui warning message">Note: The client tests for Micropub.rocks are still in progress. Please check back later, or follow the <a href="https://github.com/aaronpk/micropub.rocks/issues">issues</a> for progress updates.</div>

  </section>
</div>
<script>
$(function(){
  $('.menu .item').tab();

  $("#client_access_token").click(function(){
    if(!$(this).val()) {
      $.post('/clients/<?= $client->id ?>/new_access_token', function(response) {
        $("#client_access_token").val(response.token).select();
      });
    } else {
      $(this).select();
    }
  });
});
</script>
