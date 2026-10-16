<h1 class="display-3">Choose a password</h1>
<span class="bar"></span>
<?php if (!$valid): ?>
<p class="form-status is-error" role="alert">This link has expired or was already used.</p>
<p><a class="link-arrow" href="<?= e(url('portal/forgot')) ?>">Get a new link <?= icon('arrow-right') ?></a></p>
<?php else: ?>
<?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<form method="post" class="form-grid">
  <?= csrf_field() ?>
  <label class="field"><span>New password (10+ characters, letters and numbers)</span><input class="input" type="password" name="new_password" required minlength="10" maxlength="200" autocomplete="new-password" autofocus></label>
  <label class="field"><span>Repeat new password</span><input class="input" type="password" name="confirm_password" required minlength="10" maxlength="200" autocomplete="new-password"></label>
  <button class="btn btn-block" type="submit">Save password</button>
</form>
<?php endif; ?>
