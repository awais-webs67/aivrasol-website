<?php $role = Auth::ROLES[$user['role']] ?? null; ?>
<?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<?php if ($forced): ?><p class="form-status is-error" role="alert">Your account was given a temporary password. Choose your own password to continue.</p><?php endif; ?>
<?php if (!empty($employee) && !$forced): require_once APP_PATH . '/lib/Documents.php'; ?>
<section class="card emp-summary">
  <div class="emp-photo"><?php if (Upload::privatePath($employee['photo'])): ?><img src="<?= e(url('admin/employees/' . $employee['id'] . '/photo')) ?>" alt="" width="96" height="120"><?php else: ?><span><?= e(mb_strtoupper(mb_substr($employee['full_name'], 0, 1))) ?></span><?php endif; ?></div>
  <div class="emp-info">
    <p class="project-meta"><?= e($employee['employee_code']) ?></p>
    <h2><?= e($employee['full_name']) ?></h2>
    <p class="muted"><?= e($employee['designation']) ?><?= $employee['department'] !== '' ? ', ' . e($employee['department']) : '' ?> · since <?= e(date('M Y', strtotime($employee['join_date']))) ?></p>
  </div>
  <div class="btn-group emp-actions">
    <?php if ($employee['approval'] === 'approved'): ?><a class="btn btn-sm" href="<?= e(url('admin/employees/' . $employee['id'] . '/card')) ?>"><?= icon('download') ?>My ID card (PDF)</a><?php endif; ?>
  </div>
</section>
<?php if ($letters): ?>
<section class="card">
  <h2>My documents</h2>
  <ul class="doc-list">
    <?php foreach ($letters as $l): ?>
    <li><span><strong><?= e($l['title']) ?></strong><br><span class="small muted"><?= e($l['ref_no']) ?> · issued <?= e(date('j M Y', strtotime($l['issued_at']))) ?></span></span><a class="btn btn-sm btn-outline" href="<?= e(url('admin/letters/' . $l['id'])) ?>"><?= icon('download') ?>Download PDF</a></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
<?php endif; ?>
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
