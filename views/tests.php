<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">
  <div id="header-graphic"><img src="/assets/micropub-rocks.png"></div>

  <? if(flash('login')): ?>
  <div class="ui success message">
    <div class="header">Welcome!</div>
    <p>You are logged in as <?= $email ?>!</p>
  </div>
  <? endif; ?>

  <section class="content">
    <h3>Your Micropub Endpoints</h3>
    <ul>
      <li>Add New</li>
    </ul>
  </section>

</div>
