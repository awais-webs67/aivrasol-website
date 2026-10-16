<h1 class="display-3">Forgot password</h1>
<span class="bar"></span>
<?php if ($sent): ?>
<p class="form-status is-success" role="status">If that email belongs to an active team account, a reset link is on its way. It works for 60 minutes. Check the spam folder too.</p>
<p><a class="link-arrow" href="<?= e(url('admin/login')) ?>">Back to sign in <?= icon('arrow-right') ?></a></p>
<?php else: ?>
<?php if ($error): ?><p class="form-status is-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<p class="muted">Enter the email you sign in with. We will send you a link to choose a new password.</p>
<form method="post" action="<?= e(url('admin/forgot')) ?>" class="form-grid">
  <?= csrf_field() ?>
  <label class="field"><span>Email</span><input class="input" type="email" name="email" autocomplete="username" required autofocus maxlength="190"></label>
  <button class="btn btn-block" type="submit">Send reset link</button>
</form>
<p class="small"><a href="<?= e(url('admin/login')) ?>">Back to sign in</a></p>
<?php endif; ?>
