<h1 class="display-3">Admin sign in</h1>
<span class="bar"></span>
<?php if (!empty($error)): ?><p class="form-status is-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" action="<?= e(url('admin/login')) ?>" class="form-grid">
  <?= csrf_field() ?>
  <label class="field"><span>Email</span><input class="input" type="email" name="email" autocomplete="username" required autofocus maxlength="190"></label>
  <label class="field"><span>Password</span><input class="input" type="password" name="password" autocomplete="current-password" required maxlength="200"></label>
  <button class="btn btn-block" type="submit">Sign in</button>
</form>
<p class="small muted">For security, accounts lock for 15 minutes after 5 wrong passwords.</p>
