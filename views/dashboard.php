<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <? if(flash('login')): ?>
    <div class="ui success message">
      <div class="header">Welcome!</div>
      <p>You are logged in as <?= $_SESSION['email'] ?>!</p>
    </div>
  <? endif ?>

  <? if(count($endpoints)): ?>
    <section class="content">
      <h3>Your Micropub Endpoints</h3>

      <p>Select one of your Micropub endpoints to begin the tests</p>

      <table class="ui table">
        <? foreach($endpoints as $endpoint): ?>
        <tr>
          <td><a href="/server-tests?endpoint=<?= $endpoint->id ?>"><?= $endpoint->me ?: $endpoint->micropub_endpoint ?></a></td>
        </tr>
        <? endforeach ?>
      </table>
      <a href="" id="add-new-server-btn" class="small">Add New Endpoint</a>
    </section>
  <? endif ?>

  <section class="content <?= count($endpoints) ? 'hidden' : '' ?>" id="add-new-endpoint">
    <h3>Add New Endpoint</h3>

    <div class="ui top attached tabular menu">
      <a class="active item" data-tab="first">Sign In</a>
      <a class="item" data-tab="second">Manual</a>
    </div>
    <div class="ui bottom attached active tab segment" data-tab="first">
      <p>Enter a profile URL below that advertises its Micropub endpoint. The Micropub endpoint will be discovered from that URL and you will be asked to authenticate against the authorization server specified.</p>
      <form action="/endpoints/new" method="POST">
        <div class="ui fluid action input">
          <input type="url" id="new-endpoint-url" name="me" placeholder="https://me.example.com">
          <button class="ui button">Sign In</button>
        </div>
      </form>
    </div>
    <div class="ui bottom attached tab segment" data-tab="second">
      <p>Enter a Micropub endpoint and access token below to add an endpoint manually.</p>
      <form action="/endpoints/new" method="POST">
        <div class="ui fluid action input">
          <input type="url" name="micropub_endpoint" placeholder="https://me.example.com/micropub" required="required">
          <input type="password" name="access_token" placeholder="access token" style="border-top-left-radius:0; border-bottom-left-radius:0;" required="required">
          <button class="ui button">Add Endpoint</button>
        </div>
      </form>
    </div>
  </section>

  <? if(count($clients)): ?>
    <section class="content">
      <h3>Your Micropub Clients</h3>

      <p>Select one of your Micropub clients to begin the tests</p>

      <table class="ui table">
        <? foreach($clients as $client): ?>
        <tr>
          <td><a href="/client/<?= $client->token ?>"><?= $client->name ?></a></td>
        </tr>
        <? endforeach ?>
      </table>
      <a href="" id="add-new-client-btn" class="small">Add New Client</a>
    </section>
  <? endif ?>

  <section class="content <?= count($clients) ? 'hidden' : '' ?>" id="add-new-client">
    <h3>Add New Client</h3>

    <form action="/clients/new" method="POST">
      <div class="ui fluid action input">
        <input type="text" id="new-client-name" name="name" placeholder="Client Name">
        <button class="ui button">Create</button>
      </div>
    </form>

  </section>

</div>
<script>
$(function(){
  $(".menu .item").tab();
  $("#add-new-server-btn").click(function(){
    $("#add-new-endpoint").removeClass('hidden');
    $("#new-endpoint-url").focus();
    return false;
  });
  $("#add-new-client-btn").click(function(){
    $("#add-new-client").removeClass('hidden');
    $("#new-client-name").focus();
    return false;
  });
});
</script>