<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h3>Server Tests</h3>

    <p><?= $endpoint->micropub_endpoint ?></p>

    <h4>Creating Posts (Form-Encoded)</h4>
    <table class="ui compact table">
      <? $this->insert('partials/server-test-row', ['num'=>1, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>2, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>3, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
    </table>

    <h4>Creating Posts (JSON)</h4>
    <table class="ui compact table">
      <? $this->insert('partials/server-test-row', ['num'=>4, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>5, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>6, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
    </table>

    <h4>Creating Posts With Files</h4>
    <table class="ui compact table">
      <? $this->insert('partials/server-test-row', ['num'=>7, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
    </table>

    <h4>Updates</h4>
    <table class="ui compact table">
    </table>

    <h4>Deletes</h4>
    <table class="ui compact table">
    </table>

    <h4>Query</h4>
    <table class="ui compact table">
    </table>

    <h4>Media Endpoint</h4>
    <table class="ui compact table">
    </table>

    <h4>Authentication</h4>
    <table class="ui compact table">
    </table>

  </section>

</div>
