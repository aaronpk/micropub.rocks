<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h3>Server Tests</h3>

    <p><?= $endpoint->micropub_endpoint ?></p>

    <h4>Creating Posts (Form-Encoded)</h4>
    <table class="ui compact table">
      <? $this->insert('partials/server-test-row', ['num'=>100, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>101, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>102, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>103, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>104, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>105, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>106, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
    </table>

    <h4>Creating Posts (JSON)</h4>
    <table class="ui compact table">
      <? $this->insert('partials/server-test-row', ['num'=>200, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>201, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>202, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>203, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>204, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
    </table>

    <h4>Creating Posts (Multipart)</h4>
    <table class="ui compact table">
      <? $this->insert('partials/server-test-row', ['num'=>300, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>301, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
    </table>

    <h4>Updates</h4>
    <table class="ui compact table">
      <? $this->insert('partials/server-test-row', ['num'=>400, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>401, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>402, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>403, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>404, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>405, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
    </table>

    <h4>Deletes</h4>
    <table class="ui compact table">
      <? $this->insert('partials/server-test-row', ['num'=>500, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>501, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>502, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>503, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
    </table>

    <h4>Query</h4>
    <table class="ui compact table">
      <? $this->insert('partials/server-test-row', ['num'=>600, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>601, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>602, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>603, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
    </table>

    <h4>Media Endpoint</h4>
    <table class="ui compact table">
      <? $this->insert('partials/server-test-row', ['num'=>700, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>701, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>702, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
    </table>

    <h4>Authentication</h4>
    <table class="ui compact table">
      <? $this->insert('partials/server-test-row', ['num'=>800, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
      <? $this->insert('partials/server-test-row', ['num'=>801, 'tests'=>$tests, 'endpoint'=>$endpoint]) ?>
    </table>

  </section>

</div>
