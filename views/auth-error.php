<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">
  <div id="header-graphic"><img src="/assets/micropub-rocks.png"></div>

  <div class="ui error message">
    <div class="header"><?= $this->e($error) ?></div>
    <p><?= $this->e($error_description) ?></p>
    <a href="/">Start Over</a>
  </div>

  <?php 
    if(isset($include)) {
      $this->insert($include);
    }
  ?>
</div>
