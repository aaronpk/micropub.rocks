<div class="single-column">
  <div id="header-graphic"><img src="/assets/micropub-rocks.png"></div>

  <section class="content">
    <h3>About this site</h3>
    <p><b><i>Micropub Rocks!</i></b> is a validator to help you test your <a href="https://www.w3.org/TR/micropub/">Micropub</a> implementation. Several kinds of tests are available on the site.</p>
  </section>

  <section class="content">
    <h3>Implementation Reports</h3>

    <p>
      <a href="/implementation-reports/clients/">
       13 Micropub Client Reports
      </a>
      <span class="last-updated">
        Last updated 2017-03-22
      </span>
    </p>
    <p>
      <a href="/implementation-reports/servers/">
        <span class="flipnum"><?= $num_server_reports ?></span> Micropub Server Reports
      </a>
      <span class="last-updated">
        <?php if($last_server_report_date): ?>Last updated <?= date('Y-m-d', strtotime($last_server_report_date)) ?><?php endif ?>
      </span>
    </p>
  </section>

  <?php if(!is_logged_in()): ?>
    <div class="ui warning message">
      <?php if($email_login_open): ?>
        <div class="header">Passkeys are required from <?= date('F j, Y', $email_login_ends) ?></div>
        <p>micropub.rocks is moving from email sign-in links to passkeys. If you already have an account, sign in with your email address below and add a passkey before <?= date('F j, Y', $email_login_ends) ?>, when email sign-in stops working.</p>
      <?php else: ?>
        <div class="header">Email sign-in has ended</div>
        <p>As of <?= date('F j, Y', $email_login_ends) ?>, micropub.rocks uses passkeys to sign in. Email sign-in links no longer work.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <section class="content">
  <?php if(!is_logged_in()): ?>
    <h3>Sign in to begin</h3>

    <?php if($passkeys_available): ?>
      <p>
        <button class="ui primary button" id="passkey-signin">Sign in with a passkey</button>
      </p>

      <p>New here? Create an account with a passkey.</p>
      <form class="ui form" onsubmit="return false">
        <div class="ui fluid action input">
          <input type="email" id="passkey-email" placeholder="you@example.com" autocomplete="email webauthn">
          <button type="submit" class="ui button" id="passkey-register">Create Account</button>
        </div>
      </form>

      <div class="ui negative message hidden" id="passkey-status"></div>
    <?php else: ?>
      <p>Passkey sign-in needs this site to be served over https, or from localhost.</p>
    <?php endif; ?>

    <?php if($email_login_open): ?>
      <h4>Existing accounts: sign in with email</h4>

      <form action="/auth/start" method="POST">
        <div class="ui fluid action input">
          <input type="email" name="email" placeholder="you@example.com">
          <button class="ui button">Email Me a Link</button>
        </div>
        <div class="ui fluid input">
          <input type="text" name="confirm" placeholder="Please type <?= $confirm ?> in this field">
        </div>
        <input type="hidden" name="galaxy" id="galaxy" value="41">
      </form>

      <p>Use the email address you've used here before, and you'll receive a link to sign in. After signing in, you'll be asked to set up a passkey.<?php if($skipauth): ?> Authentication is bypassed on this server, so any email address works.<?php endif ?></p>
    <?php endif; ?>
  <?php else: ?>
    <h3>Welcome!</h3>
    <p>You are already signed in.</p>
    <p><a href="/dashboard" class="ui button">Continue</a></p>
  <?php endif; ?>
  </section>

  <section class="content small">
    <p>This code is <a href="https://github.com/aaronpk/micropub.rocks">open source</a>. Feel free to <a href="https://github.com/aaronpk/micropub.rocks/issues">file an issue</a> if you notice any errors.</p>
  </section>

</div>
