<?php $this->layout('layout', ['title' => $title]); ?>

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
        Last updated <?= date('Y-m-d', strtotime($last_server_report_date)) ?>
      </span>
    </p>
  </section>

  <section class="content">
  <? if(!is_logged_in()): ?>
    <h3>Sign in to begin</h3>

    <form action="/auth/start" method="POST">
      <div class="ui fluid action input">
        <input type="email" name="email" placeholder="you@example.com">
        <button class="ui button">Sign In</button>
      </div>
      <div class="ui fluid input">
        <input type="text" name="confirm" placeholder="Please type {{ $confirm }} in this field">
      </div>
      <input type="hidden" name="galaxy" id="galaxy" value="41">
    </form>

    <p>You will receive an email with a link to sign in.</p>

    <div class="small">
      <b>Why email sign-in?</b> Many of the tests here require different levels of authorization against your Micropub endpoint. Rather than complicating the test flow with authenticating against this site as well, authenticating with your email address simplifies the way we are able to handle the various tests against your Micropub endpoint.
    </div>
  <? else: ?>
    <h3>Welcome!</h3>
    <p>You are already signed in.</p>
    <p><a href="/dashboard" class="ui button">Continue</a></p>
  <? endif; ?>
  </section>

  <section class="content small">
    <p>This code is <a href="https://github.com/aaronpk/micropub.rocks">open source</a>. Feel free to <a href="https://github.com/aaronpk/micropub.rocks/issues">file an issue</a> if you notice any errors.</p>
  </section>

</div>
