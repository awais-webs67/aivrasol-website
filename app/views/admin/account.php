<?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<section class="card narrow">
  <h2>Change password</h2>
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <label class="field"><span>Current password</span><input class="input" type="password" name="current_password" required autocomplete="current-password"></label>
    <label class="field"><span>New password (10+ characters, letters and numbers)</span><input class="input" type="password" name="new_password" required minlength="10" autocomplete="new-password"></label>
    <label class="field"><span>Repeat new password</span><input class="input" type="password" name="confirm_password" required minlength="10" autocomplete="new-password"></label>
    <button class="btn" type="submit">Change password</button>
  </form>
</section>
