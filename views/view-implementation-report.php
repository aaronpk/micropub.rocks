<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content">
  <? if(is_logged_in() && $user->id == $endpoint->user_id): ?>
    <div class="ui success message">
      <p>Your report is published! Copy the URL below to share your report.</p>
      <input type="url" style="width:100%" value="<?= Config::$base ?>implementation-report/server/<?= $endpoint->id ?>/<?= $endpoint->share_token ?>" onclick="select()" readonly>
    </div>
      <a href="/implementation-report/server/<?= $endpoint->id ?>" style="float:right;">edit</a>
  <? endif; ?>

    <h2>Implementation Report</h2>

    <h3>
      <a href="<?= htmlspecialchars($endpoint->implementation_url) ?>">
        <?= htmlspecialchars($endpoint->implementation_name) ?>
      </a>
    </h3>

    <p>by <a href="<?= htmlspecialchars($endpoint->developer_url) ?>">
        <?= htmlspecialchars($endpoint->developer_name) ?>
      </a>
    </p>

    <p>Programming Language: <?= htmlspecialchars($endpoint->programming_language) ?></p>

    <table class="implementation-features">
      <? foreach($results as $result): ?>
        <tr>
          <td class="num"><?= $result->number ?></td>
          <td><?= result_icon($result->implements) ?></td>
          <td><?= $result->description ?></td>
        </tr>
      <? endforeach; ?>
    </table>

  </section>
</div>
