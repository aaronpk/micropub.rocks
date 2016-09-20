<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <? if(flash('login')): ?>
    <div class="ui success message">
      <div class="header">Welcome!</div>
      <p>You are logged in as <?= $_SESSION['email'] ?>!</p>
    </div>
  <? endif; ?>

  <? if(count($endpoints)): ?>
    <section class="content">
      <h3>Your Micropub Endpoints</h3>

      <p>Select one of your Micropub endpoints to begin the tests</p>

      <table class="ui table">
        <? foreach($endpoints as $endpoint): ?>
        <tr>
          <td><a href="/tests?endpoint=<?= $endpoint->id ?>"><?= $endpoint->me ?: $endpoint->micropub_endpoint ?></a></td>
        </tr>
        <? endforeach; ?>
      </table>
    </section>
  <? endif ?>

  <section class="content">
    <h3>Add New Endpoint</h3>

    <p>Enter an <a href="https://indieweb.org/IndieAuth">IndieAuth URL</a> below. Your Micropub endpoint will be discovered from that URL and you will be asked to authenticate against your own authorization server.</p>
    <form action="/endpoints/new" method="POST">
      <div class="ui fluid action input">
        <input type="url" name="me" placeholder="https://me.example.com">
        <button class="ui button">Sign In</button>
      </div>
    </form>

    <br>

    <p>Enter a Micropub endpoint and access token below to add an endpoint manually.</p>
    <form action="/endpoints/new" method="POST">
      <div class="ui fluid action input">
        <input type="url" name="micropub_endpoint" placeholder="https://me.example.com/micropub">
        <input type="password" name="access_token" placeholder="access token" style="border-top-left-radius:0; border-bottom-left-radius:0;">
        <button class="ui button">Add Endpoint</button>
      </div>
    </form>
  </section>

</div>
