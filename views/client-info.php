<?php $this->layout('layout', [
                      'title' => $title,
                      'link_tag' => '
  <link rel="authorization_endpoint" href="'.Config::$base.'client/'.$client->token.'/auth">
  <link rel="token_endpoint" href="'.Config::$base.'client/'.$client->token.'/token">
  <link rel="micropub" href="'.Config::$base.'client/'.$client->token.'/micropub">
'
                    ]); ?>

<div class="single-column" style="margin-top: 1em;">
  <div style="border: 1px #aaa solid; border-radius: 6px; padding: 20px; background: white;">
    <h2><?= $this->e($client->name) ?></h2>
    <p>This a user profile page for testing the Micropub client <?= $this->e($client->name) ?>. Typically this would be the user's home page. This page advertises the user's Micropub endpoint and authorization endpoint that clients use when signing the user in.</p>

    <? if($client->profile_url): ?>
      <a href="<?= $this->e($client->profile_url) ?>" rel="me"><?= $this->e($client->profile_url) ?></a>
    <? endif ?>
  </div>
</div>
