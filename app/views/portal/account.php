<?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<?php if ($forced): ?><p class="form-status is-error" role="alert">You signed in with a temporary password. Choose your own password to continue.</p><?php endif; ?>
<h1 class="portal-title">Account</h1>
<div class="portal-grid">
  <section class="p-card">
    <h2>Your details</h2>
    <dl class="p-dl">
      <dt>Name</dt><dd><?= e($client['contact_name']) ?></dd>
      <?php if ($client['company_name'] !== ''): ?><dt>Company</dt><dd><?= e($client['company_name']) ?></dd><?php endif; ?>
      <dt>Email</dt><dd><?= e($client['email']) ?></dd>
    </dl>
    <p class="small muted">To change these details, message your project manager.</p>
  </section>
  <section class="p-card">
    <h2><?= $forced ? 'Choose your password' : 'Change password' ?></h2>
    <form method="post" class="form-grid">
      <?= csrf_field() ?>
      <label class="field"><span><?= $forced ? 'Temporary password' : 'Current password' ?></span><input class="input" type="password" name="current_password" required maxlength="200" autocomplete="current-password"></label>
      <label class="field"><span>New password (10+ characters, letters and numbers)</span><input class="input" type="password" name="new_password" required minlength="10" maxlength="200" autocomplete="new-password"></label>
      <label class="field"><span>Repeat new password</span><input class="input" type="password" name="confirm_password" required minlength="10" maxlength="200" autocomplete="new-password"></label>
      <button class="btn" type="submit">Save password</button>
    </form>
  </section>
</div>
