<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">
  <div id="header-graphic"><img src="/assets/micropub-rocks.png"></div>

  <? if($authorizationURL): ?>
    <div class="ui success message">
      <div class="header">Ready!</div>
      <p>Clicking the button below will take you to <strong>your</strong> authorization server which is where you will allow this app to be able to post to your site.</p>
      <a href="<?= $authorizationURL ?>" class="ui blue button">Authorize</a>
    </div>
  <? endif; ?>

  <section class="content">
    <h3>Authorization Endpoint</h3>

    <p><i>The authorization endpoint tells this app where to direct your browser to sign you in.</i></p>

    <?php if($authorizationEndpoint): ?>
      <div class="ui success message">Found your authorization endpoint: <code><?= $authorizationEndpoint ?></code></div>
    <?php else: ?>
      <div class="ui error message">Could not find your authorization endpoint!</div>
      <p>You need to set your authorization endpoint in a <code>&lt;link&gt;</code> tag on your home page, or by including a <code>Link</code> header in the HTTP response.</p>
      <p>You can create your own authorization endpoint, but it's easier to use an existing service such as <a href="https://indieauth.com/">IndieAuth.com</a>. To delegate to IndieAuth.com, you can use the markup provided below.</p>
      <p><pre class="small"><code>&lt;link rel="authorization_endpoint" href="https://indieauth.com/auth"&gt;</code></pre></p>
      <p>Alternately, you can return the following HTTP Link header.</p>
      <p><pre class="small"><code>Link: &lt;https://indieauth.com/auth&gt;; rel="authorization_endpoint"</code></pre></p>
    <?php endif; ?>
  </section>

  <section class="content">
    <h3>Token Endpoint</h3>

    <p><i>The token endpoint is where this app will make a request to get an access token after obtaining an authorization code.</i></p>

    <?php if($tokenEndpoint): ?>
      <div class="ui success message">Found your token endpoint: <code><?= $tokenEndpoint ?></code></div>
    <?php else: ?>
      <div class="ui error message">Could not find your token endpoint!</div>
      <p>You need to set your token endpoint in a <code>&lt;link&gt;</code> tag on your home page, or by including a .</p>
      <p>You can <a href="https://indieweb.org/token-endpoint">create your own token endpoint</a> for 
         your website which can issue access tokens when given an authorization code, but 
         it's easier to get started by using an existing service such as <a href="https://tokens.indieauth.com">tokens.indieauth.com</a>. To use this service as your token endpoint, use the markup provided below.</p>
      <p><pre class="small"><code>&lt;link rel="token_endpoint" href="https://tokens.indieauth.com/token"&gt;</code></pre></p>
      <p>Alternately, you can return the following HTTP Link header.</p>
      <p><pre class="small"><code>Link: &lt;https://tokens.indieauth.com/auth&gt;; rel="token_endpoint"</code></pre></p>

    <?php endif; ?>
  </section>

  <section class="content">
    <h3>Micropub Endpoint</h3>

    <p><i>The Micropub endpoint is the URL this app will make requests to that include the access token.</i></p>

    <?php if($micropubEndpoint): ?>
      <div class="ui success message">
        Found your Micropub endpoint: <code><?= $micropubEndpoint ?></code>
      </div>
    <?php else: ?>
      <div class="ui error message">Could not find your Micropub endpoint!</div>
      <p>You need to set your Micropub endpoint in a <code>&lt;link&gt;</code> tag on your home page.</p>
      <p>You will need to <a href="https://www.w3.org/TR/micropub/">create a Micropub endpoint</a> for your website which can create posts on your site. Once you've created the Micropub endpoint, you can indicate its location using the markup below.</p>
      <p><pre class="small"><code>&lt;link rel="micropub" href="https://<?= $meParts['host'] ?>/micropub"&gt;</code></pre></p>
      <p>Alternately, you can return the following HTTP Link header.</p>
      <p><pre class="small"><code>Link: &lt;https://<?= $meParts['host'] ?>/micropub&gt;; rel="micropub"</code></pre></p>

    <?php endif; ?>

  </div>

</div>
