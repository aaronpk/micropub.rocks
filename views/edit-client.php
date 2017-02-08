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

      <button class="ui button" type="submit">Save</button>

      <input type="hidden" name="id" value="<?= $client->id ?>">
    </form>

  </section>
</div>
