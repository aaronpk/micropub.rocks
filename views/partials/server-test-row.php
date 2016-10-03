        <tr>
          <td width="40" style="padding-right: 0"><?= result_icon($tests[$num]['passed']) ?></td>
          <td><a href="<?= test_url($num, $endpoint->id) ?>"><?= $num ?></a></td>
          <td><a href="<?= test_url($num, $endpoint->id) ?>"><?= $tests[$num]['name'] ?></a></td>
        </tr>
