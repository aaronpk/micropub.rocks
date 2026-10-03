<div class="single-column">

  <section class="content">
    <h3>Server Tests</h3>

    <div class="endpoint-details">
      <?= $endpoint->micropub_endpoint ?>
      <a href="/endpoints/<?= $endpoint->id ?>"><i class="setting icon"></i></a>
    </div>

    <div style="margin: 20px 0;">
      <a href="/implementation-reports/servers/<?= $endpoint->id ?>">Implementation Report</a>
    </div>

    <h4>Creating Posts (Form-Encoded)</h4>
    <table class="ui compact table">
      <?php echo $view->partial('partials/server-test-row', ['num'=>100, 'tests'=>$tests, 'endpoint'=>$endpoint]); ?>
      <?php echo $view->partial('partials/server-test-row', ['num'=>101, 'tests'=>$tests, 'endpoint'=>$endpoint]); ?>
      <?php echo $view->partial('partials/server-test-row', ['num'=>104, 'tests'=>$tests, 'endpoint'=>$endpoint]); ?>
      <?php echo $view->partial('partials/server-test-row', ['num'=>107, 'tests'=>$tests, 'endpoint'=>$endpoint]); ?>
    </table>

    <h4>Creating Posts (JSON)</h4>
    <table class="ui compact table">
      <?php 
        for($i=200; $i<=206; $i++) {
          echo $view->partial('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>

    <h4>Creating Posts (Multipart)</h4>
    <table class="ui compact table">
      <?php 
        for($i=300; $i<=301; $i++) {
          echo $view->partial('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>

    <h4>Updates</h4>
    <table class="ui compact table">
      <?php 
        for($i=400; $i<=405; $i++) {
          echo $view->partial('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>

    <h4>Deletes</h4>
    <table class="ui compact table">
      <?php 
        for($i=500; $i<=503; $i++) {
          echo $view->partial('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>

    <h4>Query</h4>
    <table class="ui compact table">
      <?php 
        for($i=600; $i<=603; $i++) {
          echo $view->partial('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>

    <h4>Media Endpoint</h4>
    <table class="ui compact table">
      <?php 
        for($i=700; $i<=702; $i++) {
          echo $view->partial('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]); 
        }
      ?>
    </table>

    <h4>Authentication</h4>
    <table class="ui compact table">
      <?php
        for($i=800; $i<=805; $i++) {
          echo $view->partial('partials/server-test-row', ['num'=>$i, 'tests'=>$tests, 'endpoint'=>$endpoint]);
        }
      ?>
    </table>

  </section>

</div>
