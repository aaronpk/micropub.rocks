<div class="post-container">
  <div class="post-main">
    <div class="left p-author h-card">
      <a href="/"><img src="/assets/micropub-rocks-icon.png" width="80" class="u-photo" alt="Micropub Rocks!"></a>
    </div>
    <div class="right">
      <? if(isset($name)): ?>
        <h1 class="p-name content"><?= $this->e($name) ?></h1>
      <? endif ?>
      <? if(isset($content)): ?>
        <div class="e-content content"><?= $this->e($content) ?></div>
      <? endif ?>
      <? if(isset($photo)): ?>
        <div class="photo"><img src="<?= $this->e($photo) ?>" class="u-photo"></div>
      <? endif ?>
      <div class="meta">
        <? if(isset($category)): ?>
          <div class="tags">
            <?= $this->e(implode(' ', array_map(function($el){ return '#'.$el; }, $category))) ?>
          </div>
        <? endif ?>
        <time><?= date('F j, Y \a\t g:ia T') ?></time>
      </div>
    </div>
  </div>
</div>
