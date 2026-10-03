<div class="single-column">

  <section class="content">
    <h2>Set Up a Passkey</h2>

    <div class="ui warning message">
      <div class="header">Email sign-in ends on <?= date('F j, Y', $email_login_ends) ?></div>
      <p>After that date, the only way to sign in to micropub.rocks will be with a passkey. Set one up now to keep access to your account and test results.</p>
    </div>

    <p>You're signed in as <b><?= $email ?></b>. A passkey lets you sign in with your device's fingerprint, face or screen lock, or with a security key, instead of waiting for an email.</p>

    <?php if($passkeys_available): ?>
      <form class="ui form" onsubmit="return false">
        <div class="ui fluid action input">
          <input type="text" id="passkey-label" placeholder="Name, e.g. Work laptop" maxlength="48">
          <button type="submit" class="ui primary button" id="passkey-add" data-csrf="<?= $csrf ?>">Set Up a Passkey</button>
        </div>
      </form>
      <div class="ui negative message hidden" id="passkey-status"></div>
    <?php else: ?>
      <p>Passkeys need this site to be served over https, or from localhost.</p>
    <?php endif; ?>

    <p><a href="/dashboard">Not now</a>. You can set one up later from your account page, which is linked from your email address at the top of every page.</p>
  </section>

</div>
