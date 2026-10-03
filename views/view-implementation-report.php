<div class="single-column">

  <section class="content">
  <?php if(is_logged_in() && $user->id == $endpoint->user_id): ?>
    <div class="ui success message">
      <p>Your report is published! Copy the URL below to share your report.</p>
      <input type="url" style="width:100%" value="<?= Config::$base ?>implementation-reports/servers/<?= $endpoint->id ?>/<?= $endpoint->share_token ?>" onclick="select()" readonly>
    </div>
      <a href="/implementation-reports/servers/<?= $endpoint->id ?>" style="float:right;">edit</a>
  <?php endif; ?>

    <h2>Implementation Report</h2>

    <h3>
      <a href="<?= $endpoint->implementation_url ?>">
        <?= $endpoint->implementation_name ?>
      </a>
    </h3>

    <p>by <a href="<?= $endpoint->developer_url ?>">
        <?= $endpoint->developer_name ?>
      </a>
    </p>

    <p>Programming Language: <?= $endpoint->programming_language ?></p>

    <table class="implementation-features">
      <?php foreach($results as $result): ?>
        <tr>
          <td class="num"><?= $result->number ?></td>
          <td><?= result_icon($result->implements) ?></td>
          <td><?= $result->description ?></td>
        </tr>
      <?php endforeach; ?>
    </table>

  </section>
</div>
