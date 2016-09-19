<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">
  <div id="header-graphic"><img src="/assets/micropub-rocks.png"></div>

  <div class="ui success message">
    <div class="header">Welcome!</div>
    <p>You are logged in as <?= $email ?>!</p>
  </div>
</div>
