<?php $role = Auth::ROLES[$user['role']] ?? null; ?>
<?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<?php if ($forced): ?><p class="form-status is-error" role="alert">Your account was given a temporary password. Choose your own password to continue.</p><?php endif; ?>
<div class="admin-grid">
  <section class="card">
    <h2>My details</h2>
    <dl class="dl mb">
      <dt>Email</dt><dd><?= e($user['email']) ?></dd>
      <dt>Role</dt><dd><?= e(Auth::roleLabel($user['role'])) ?><?php if ($role): ?><br><span class="small muted"><?= e($role['about']) ?></span><?php endif; ?></dd>
    </dl>
    <?php if (!$forced): ?>
    <form method="post" class="form-grid">
      <?= csrf_field() ?><input type="hidden" name="action" value="name">
      <label class="field"><span>Name</span><input class="input" type="text" name="name" value="<?= e($user['name']) ?>" required maxlength="80" autocomplete="name"></label>
      <button class="btn btn-outline" type="submit">Save name</button>
    </form>
    <?php endif; ?>
  </section>
  <section class="card">
    <h2><?= $forced ? 'Choose your password' : 'Change password' ?></h2>
    <form method="post" class="form-grid">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <label class="field"><span><?= $forced ? 'Temporary password' : 'Current password' ?></span><input class="input" type="password" name="current_password" required maxlength="200" autocomplete="current-password"></label>
      <label class="field"><span>New password (10+ characters, letters and numbers)</span><input class="input" type="password" name="new_password" required minlength="10" maxlength="200" autocomplete="new-password"></label>
      <label class="field"><span>Repeat new password</span><input class="input" type="password" name="confirm_password" required minlength="10" maxlength="200" autocomplete="new-password"></label>
      <button class="btn" type="submit">Save password</button>
    </form>
  </section>
</div>
