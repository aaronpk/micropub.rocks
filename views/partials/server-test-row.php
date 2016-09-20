        <tr>
          <td><a href="<?= test_url($num, $endpoint->id) ?>"><?= $num ?></a></td>
          <td><a href="<?= test_url($num, $endpoint->id) ?>"><?= $tests[$num]['name'] ?></a></td>
          <td><?= result_icon($tests[$num]['passed']) ?></td>
        </tr>
