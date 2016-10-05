<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h3>Server Tests</h3>

    <div class="endpoint-details">
      <?= $endpoint->micropub_endpoint ?>
      <a href="/endpoints/<?= $endpoint->id ?>"><i class="setting icon"></i></a>
    </div>

    <h4>Creating Posts (Form-Encoded)</h4>
    <table class="ui compact table">
      <? $this->insert('partials/server-test-row', ['num'=>100, 'tests'=>$tests, 'endpoint'=>$endpoint]); ?>
      <? $this->insert('partials/server-test-row', ['num'=>101, 'tests'=>$tests, 'endpoint'=>$endpoint]); ?>
      <? $this->insert('partials/server-test-row', ['num'=>104, 'tests'=>$tests, 'endpoint'=>$endpoint]); ?>
      <? $this->insert('partials/server-test-row', ['num'=>107, 'tests'=>$tests, 'endpoint'=>$endpoint]); ?>
    </table>

    <h4>Creating Posts (JSON)</h4>
    <table class="ui compact table">
      <? 
        for($i=200; $i<=205; $i++) {
          $this->insert('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>

    <? /* ?>
    <h4>Creating Posts (Multipart)</h4>
    <table class="ui compact table">
      <? 
        for($i=300; $i<=301; $i++) {
          $this->insert('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>
    <? */ ?>

    <h4>Updates</h4>
    <table class="ui compact table">
      <? 
        for($i=400; $i<=405; $i++) {
          $this->insert('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>

    <p>More tests <a href="https://github.com/aaronpk/micropub.rocks/issues?q=is%3Aissue+is%3Aopen+label%3Aserver-test">coming soon</a>.</p>
    <? /* ?>

    <h4>Deletes</h4>
    <table class="ui compact table">
      <? 
        for($i=500; $i<=503; $i++) {
          $this->insert('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>

    <h4>Query</h4>
    <table class="ui compact table">
      <? 
        for($i=600; $i<=603; $i++) {
          $this->insert('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>

    <h4>Media Endpoint</h4>
    <table class="ui compact table">
      <? 
        for($i=700; $i<=702; $i++) {
          $this->insert('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>

    <h4>Authentication</h4>
    <table class="ui compact table">
      <? 
        for($i=800; $i<=801; $i++) {
          $this->insert('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>

    <? */ ?>

  </section>

</div>
