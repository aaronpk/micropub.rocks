<div class="single-column">

  <section class="content">
    <h2>Your Account</h2>
    <p>You are signed in as <b><?= $email ?></b>.</p>

    <?php if($message): ?>
      <div class="ui info message"><?= $message ?></div>
    <?php endif; ?>

    <h3>Passkeys</h3>

    <?php if(!$passkeys && $email_login_open): ?>
      <div class="ui warning message">
        Email sign-in will stop working on <?= date('F j, Y', $email_login_ends) ?>. Add a passkey below to keep access to your account.
      </div>
    <?php endif; ?>

    <?php if($passkeys): ?>
      <table class="ui table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Added</th>
            <th>Last Used</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($passkeys as $passkey): ?>
            <tr>
              <td>
                <form class="ui form" action="/account/passkeys/<?= $passkey['id'] ?>/rename" method="POST">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <div class="ui small action input">
                    <input type="text" name="label" value="<?= $passkey['label'] ?>" maxlength="48" aria-label="Passkey name">
                    <button class="ui small button">Rename</button>
                  </div>
                </form>
              </td>
              <td><?= $passkey['date_created'] ? date('M j, Y', strtotime($passkey['date_created'])) : '' ?></td>
              <td><?= $passkey['date_last_used'] ? date('M j, Y', strtotime($passkey['date_last_used'])) : 'Never' ?></td>
              <td>
                <form action="/account/passkeys/<?= $passkey['id'] ?>/remove" method="POST">
                  <input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <button class="ui small red basic button">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>You don't have any passkeys yet.</p>
    <?php endif; ?>

    <h3>Add a Passkey</h3>

    <?php if($passkeys_available): ?>
      <p>Add another passkey to sign in from a different device or with a security key.</p>
      <form class="ui form" onsubmit="return false">
        <div class="ui fluid action input">
          <input type="text" id="passkey-label" placeholder="Name, e.g. Work laptop" maxlength="48">
          <button type="submit" class="ui primary button" id="passkey-add" data-csrf="<?= $csrf ?>">Add Passkey</button>
        </div>
      </form>
      <div class="ui negative message hidden" id="passkey-status"></div>
    <?php else: ?>
      <p>Passkeys need this site to be served over https, or from localhost.</p>
    <?php endif; ?>
  </section>

</div>
