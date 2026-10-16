<h1 class="display-3">Create the first admin</h1>
<span class="bar"></span>
<p class="small">This page works only once. After the first admin is created it is switched off.</p>
<?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<form method="post" action="<?= e(url('admin/setup')) ?>" class="form-grid">
  <?= csrf_field() ?>
  <label class="field"><span>Setup token (from app/config.php)</span><input class="input" type="password" name="token" required autocomplete="off"></label>
  <label class="field"><span>Your name</span><input class="input" type="text" name="name" required maxlength="80" value="<?= e($_POST['name'] ?? '') ?>"></label>
  <label class="field"><span>Email</span><input class="input" type="email" name="email" required maxlength="190" autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>"></label>
  <label class="field"><span>Password (10+ characters, letters and numbers)</span><input class="input" type="password" name="password" required minlength="10" autocomplete="new-password"></label>
  <button class="btn btn-block" type="submit">Create admin account</button>
</form>
