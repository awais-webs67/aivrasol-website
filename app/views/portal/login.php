<h1 class="display-3">Client sign in</h1>
<span class="bar"></span>
<?php if (!empty($error)): ?><p class="form-status is-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<p class="muted">See your project progress, talk to our team and check payments.</p>
<form method="post" action="<?= e(url('portal/login')) ?>" class="form-grid">
  <?= csrf_field() ?>
  <label class="field"><span>Email</span><input class="input" type="email" name="email" autocomplete="username" required autofocus maxlength="190"></label>
  <label class="field"><span>Password</span><input class="input" type="password" name="password" autocomplete="current-password" required maxlength="200"></label>
  <button class="btn btn-block" type="submit">Sign in</button>
</form>
<p class="small"><a href="<?= e(url('portal/forgot')) ?>">Forgot your password?</a></p>
<p class="small muted">No account yet? Your project manager sends you an invitation when your project starts.</p>
