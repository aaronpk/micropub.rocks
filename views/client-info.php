<?php $this->layout('layout', [
                      'title' => $title,
                      'link_tag' => '
  <link rel="authorization_endpoint" href="'.Config::$base.'client/'.$client->token.'/auth">
  <link rel="token_endpoint" href="'.Config::$base.'client/'.$client->token.'/token">
  <link rel="micropub" href="'.Config::$base.'client/'.$client->token.'/micropub">
'
                    ]); ?>

<div class="single-column">
  <h2><?= $this->e($client->name) ?></h2>
  <p>This a Micropub endpoint.</p>
</div>
