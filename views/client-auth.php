<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">
  <section class="content">
    <h2>An application would like to connect to your account</h2>

    <? if(count($errors)): ?>
      <p>An application is attempting to post to your micropub.rocks test website. However, there was a problem with the authorization request.</p>

      <? foreach($errors as $error): ?>
        <div class="ui error message">
          <div class="header"><?= $error['title'] ?></div>
          <?= $error['description'] ?>
        </div>
      <? endforeach ?>
    <? endif ?>

    <? if($jwt): ?>
      <p>Do you want to authorize <b><?= $this->e($client_id) ?></b> to post to your micropub.rocks test website?</p>

      <form action="/client/<?= $token ?>/auth" method="post">
        <input type="submit" class="ui primary button" value="Allow">
        <input type="hidden" name="authorization" value="<?= $jwt ?>">
      </form>
    <? endif ?>

  </section>
</div>
