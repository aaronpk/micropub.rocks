<section class="content errors">
  <p><i>There were one or more problems with your request. Please see the details below.</i></p>
  <ul>
    <?php foreach($errors as $error): ?>
      <li><?= $error ?></li>
    <?php endforeach ?>
  </ul>
</div>
