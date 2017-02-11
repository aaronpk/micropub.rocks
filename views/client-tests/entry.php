<div class="post-container">
  <div class="post-main">
    <div class="left p-author h-card">
      <a href="/"><img src="/assets/micropub-rocks-icon.png" width="80" class="u-photo" alt="Micropub Rocks!"></a>
    </div>
    <div class="right">
      <? if(isset($name)): ?>
        <h1 class="p-name"><?= $this->e($name) ?></h1>
      <? endif ?>
      <? if(isset($content)): ?>
        <div class="e-content"><?= $this->e($content) ?></div>
      <? endif ?>
    </div>
  </div>
</div>
