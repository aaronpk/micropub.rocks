        <tr>
          <!-- <td width="40" style="padding-right: 0"><?= result_icon($tests[$num]['passed']) ?></td> -->
          <td><a href="<?= client_test_url($num, $client->token) ?>"><?= $num ?></a></td>
          <td><a href="<?= client_test_url($num, $client->token) ?>"><?= $tests[$num]['name'] ?></a></td>
        </tr>
