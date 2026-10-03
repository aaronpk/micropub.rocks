<div class="single-column">
  <div id="header-graphic"><img src="/assets/micropub-rocks.png"></div>

  <div class="ui error message">
    <div class="header"><?= ($error) ?></div>
    <p><?= ($error_description) ?></p>
    <?php if(isset($error_debug)): ?>
      <pre class="small"><?= $error_debug ?></pre>
    <?php endif; ?>
    <a href="<?= is_logged_in() ? '/dashboard' : '/' ?>">Start Over</a>
  </div>

  <?php 
    if(isset($include)) {
      echo $view->partial($include);
    }
  ?>
</div>
