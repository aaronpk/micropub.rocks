<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

<section class="content">
  <h2>Micropub Server Implementation Reports</h2>

  <table class="implementation-features">  
    <tr>
      <td></td>
      <? foreach($endpoints as $i=>$endpoint): ?>
        <td>
          <a href="/implementation-reports/servers/<?= $endpoint->id ?>/<?= $endpoint->share_token ?>">
            <?= $i+1 ?>
          </a>
        </td>
      <? endforeach; ?>
    </tr>
    <? foreach($results as $num=>$result): ?>
      <tr class="<?= $num % 2 == 0 ? 'even' : 'odd' ?>-row">
        <td class="num" rowspan="2"><?= $num ?></td>
        <td class="small" colspan="<?= count($endpoints) ?>"><?= $features[$num] ?></td>
      </tr>
      <tr class="<?= $num % 2 == 0 ? 'even' : 'odd' ?>-row">
        <? foreach($endpoints as $i=>$endpoint): ?>
          <td>
            <?= result_icon( isset($result[$endpoint->id]) ? $result[$endpoint->id] : 0 ) ?>
          </td>
        <? endforeach; ?>
      </tr>
    <? endforeach; ?>
  </table>

  <ul>
    <? foreach($endpoints as $i=>$endpoint): ?>
      <li>
        <?= $i+1 ?>: 
        <a href="/implementation-reports/servers/<?= $endpoint->id ?>/<?= $endpoint->share_token ?>">
          <?= $endpoint->implementation_name ?: $endpoint->micropub_endpoint ?>
        </a>
      </li>
    <? endforeach; ?>
  </ul>

  <p><a href="summary/">view server report summary</a>
</section>

</div>
