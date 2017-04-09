<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">
  <section class="content">

    <form action="/clients/save" method="POST" class="ui form">

      <h3>Editing Client</h3>

      <div class="field">
        <label>Client Name</label>
        <input type="text" name="name" placeholder="" value="<?= $client->name ?>">
      </div>

      <div class="field">
        <label>Profile URL</label>
        <p>If your Micropub client expects to be able to discover an alternate profile URL for users, you can enter a URL here. This will be rendered on the simulated user's profile page in a rel=me link.</p>
        <input type="url" name="profile_url" placeholder="" value="<?= $client->profile_url ?>">
      </div>

      <button class="ui button" type="submit">Save</button>

      <input type="hidden" name="id" value="<?= $client->id ?>">
    </form>

  </section>
</div>
