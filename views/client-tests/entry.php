<div class="post-container">
  <div class="post-main">
    <div class="left p-author h-card">
      <a href="/"><img src="/assets/micropub-rocks-icon.png" width="80" class="u-photo" alt="Micropub Rocks!"></a>
    </div>
    <div class="right">
      <?php if(isset($name)): ?>
        <h1 class="p-name content"><?= (mf2_val($name)) ?></h1>
      <?php endif ?>
      <?php if(isset($content)): ?>
        <div class="e-content content"><?= is_array($content) ? (is_array($content[0] ?? null) ? ($content[0]['html'] ?? '') : ($content[0] ?? '')) : (mf2_val($content)) ?></div>
      <?php endif ?>
      <?php if(isset($photo)): ?>
        <div class="photo"><img src="<?= (mf2_val($photo)) ?>" class="u-photo"></div>
      <?php endif ?>
      <?php if(isset($audio)): ?>
        <div class="audio"><audio src="<?= (mf2_val($audio)) ?>" class="u-audio" controls style="width:100%"></audio></div>
      <?php endif ?>
      <?php if(isset($video)): ?>
        <div class="video"><video src="<?= (mf2_val($video)) ?>" class="u-video" controls style="width:100%"></video></div>
      <?php endif ?>
      <div class="meta">
        <?php if(isset($category)): ?>
          <div class="tags">
            <?= (implode(' ', array_map(function($el){ if($t=mf2_val($el)) { return '#'.$t; }; }, $category))) ?>
          </div>
        <?php endif ?>
        <time><?= date('F j, Y \a\t g:ia T') ?></time>
      </div>
    </div>
  </div>
</div>
