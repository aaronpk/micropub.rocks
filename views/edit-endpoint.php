<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">
  <section class="content">

    <form action="/endpoints/save" method="POST" class="ui form">

      <h3>Editing Endpoint</h3>

      <div class="field">
        <label>Endpoint URL</label>
        <input type="url" name="micropub_endpoint" placeholder="https://me.example.com/micropub" value="<?= $endpoint->micropub_endpoint ?>">
      </div>

      <div class="field">
        <label>Access Token</label>
        <input type="text" name="access_token" placeholder="access token" style="border-top-left-radius:0; border-bottom-left-radius:0;" value="<?= $endpoint->access_token ?>">
      </div>

      <button class="ui button" type="submit">Save</button>

      <input type="hidden" name="id" value="<?= $endpoint->id ?>">
    </form>

  </section>
</div>
