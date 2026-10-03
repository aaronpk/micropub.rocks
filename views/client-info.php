<div class="single-column" style="margin-top: 1em;">
  <div style="border: 1px #aaa solid; border-radius: 6px; padding: 20px; background: white;">
    <h2><?= ($client->name) ?></h2>
    <p>This a user profile page for testing the Micropub client <?= ($client->name) ?>. Typically this would be the user's home page. This page advertises the user's Micropub endpoint and authorization endpoint that clients use when signing the user in.</p>

    <?php if($client->profile_url): ?>
      <a href="<?= ($client->profile_url) ?>" rel="me"><?= ($client->profile_url) ?></a>
    <?php endif ?>
  </div>
</div>
