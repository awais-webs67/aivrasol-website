<?php
/** @var ?array $emp @var array $fields @var array $values @var array $errors @var array $docs @var ?array $login */
$hasPhoto = $emp && Upload::privatePath($emp['photo']);
?>
<p><a class="link-arrow small" href="<?= e(url('admin/employees')) ?>">← Back to employees</a></p>
<?php if ($errors): ?><p class="form-status is-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>
<?php if ($emp): ?>
<section class="card emp-summary">
  <div class="emp-photo"><?php if ($hasPhoto): ?><img src="<?= e(url('admin/employees/' . $emp['id'] . '/photo')) ?>" alt="" width="96" height="120"><?php else: ?><span><?= e(mb_strtoupper(mb_substr($emp['full_name'], 0, 1))) ?></span><?php endif; ?></div>
  <div class="emp-info">
    <p class="project-meta"><?= e($emp['employee_code']) ?> · <?= e(Documents::STATUSES[$emp['status']] ?? $emp['status']) ?></p>
    <h2><?= e($emp['full_name']) ?></h2>
    <p class="muted"><?= e($emp['designation']) ?><?= $emp['department'] !== '' ? ', ' . e($emp['department']) : '' ?></p>
  </div>
  <div class="btn-group emp-actions">
    <a class="btn btn-sm" href="<?= e(url('admin/employees/' . $emp['id'] . '/card')) ?>"><?= icon('download') ?>ID card PDF</a>
    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/employees/' . $emp['id'] . '/card?view=1')) ?>" target="_blank" rel="noopener">Preview card</a>
    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/employees/' . $emp['id'] . '/letter')) ?>"><?= icon('file') ?>Experience letter</a>
  </div>
</section>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card form-grid cols-2 edit-form">
  <?= csrf_field() ?>
  <h2 class="span-2">Employee details</h2>
  <?php foreach ($fields as $name => [$label, $type, $opt, $required]): $val = (string) ($values[$name] ?? ''); $err = $errors[$name] ?? null; $id = 'e-' . $name; ?>
  <div class="field<?= $type === 'textarea' ? ' span-2' : '' ?>">
    <label for="<?= e($id) ?>"><span><?= e($label) ?><?= $required ? ' *' : '' ?></span></label>
    <?php if ($type === 'select'): ?>
    <select class="select" id="<?= e($id) ?>" name="<?= e($name) ?>"<?= $err ? ' aria-invalid="true"' : '' ?>><?php foreach ($opt as $ov => $ol): ?><option value="<?= e((string) $ov) ?>"<?= $val === (string) $ov ? ' selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?></select>
    <?php elseif ($type === 'textarea'): ?>
    <textarea class="textarea" id="<?= e($id) ?>" name="<?= e($name) ?>" maxlength="<?= (int) $opt ?>"><?= e($val) ?></textarea>
    <?php else: ?>
    <input class="input" id="<?= e($id) ?>" type="<?= $type === 'email' ? 'email' : ($type === 'date' ? 'date' : 'text') ?>" name="<?= e($name) ?>" value="<?= e($val) ?>"<?= is_int($opt) ? ' maxlength="' . $opt . '"' : '' ?><?= $required ? ' required' : '' ?><?= $err ? ' aria-invalid="true"' : '' ?><?= $name === 'national_id' ? ' autocomplete="off"' : '' ?>>
    <?php endif; ?>
    <?php if ($name === 'national_id'): ?><span class="small muted">Stored encrypted. Only used in letters when the template asks for it.</span><?php endif; ?>
    <?php if ($err): ?><span class="field-error"><?= e($err) ?></span><?php endif; ?>
  </div>
  <?php endforeach; ?>
  <div class="field span-2">
    <label for="e-photo"><span>Photo for the ID card</span></label>
    <?php if ($hasPhoto): ?><label class="check"><input type="checkbox" name="photo_remove" value="1"> <span>Remove the current photo</span></label><?php endif; ?>
    <input class="file-input" id="e-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
    <span class="small muted">A clear head-and-shoulders photo on a plain background. It is cropped to 4:5 and kept private (never public on the website).</span>
    <?php if (isset($errors['photo'])): ?><span class="field-error"><?= e($errors['photo']) ?></span><?php endif; ?>
  </div>
  <div class="span-2 btn-group"><button class="btn" type="submit"><?= $emp ? 'Save changes' : 'Add employee' ?></button></div>
</form>

<?php if ($emp): ?>
<div class="admin-grid">
  <section class="card" id="documents">
    <div class="card-head"><h2>Letters issued</h2><a class="btn btn-sm btn-outline" href="<?= e(url('admin/employees/' . $emp['id'] . '/letter')) ?>">Issue a letter</a></div>
    <?php if (!$docs): ?>
    <p class="muted">No letters yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Reference</th><th>Issued</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
      <tbody>
      <?php foreach ($docs as $d): ?>
      <tr>
        <td><a href="<?= e(url('admin/letters/' . $d['id'])) ?>"><?= e($d['ref_no']) ?></a><br><span class="small muted"><?= e($d['title']) ?></span></td>
        <td class="small"><?= e(date('j M Y', strtotime($d['issued_at']))) ?><?= $d['issuer'] ? '<br>by ' . e($d['issuer']) : '' ?></td>
        <td><span class="status status-<?= $d['revoked_at'] ? 'lost' : 'won' ?>"><?= $d['revoked_at'] ? 'Withdrawn' : 'Valid' ?></span></td>
        <td class="nowrap">
          <a class="btn btn-sm btn-outline" href="<?= e(url('admin/letters/' . $d['id'])) ?>"><?= icon('download') ?>PDF</a>
          <form method="post" action="<?= e(url('admin/letters/' . $d['id'] . '/revoke')) ?>" class="inline-form" data-confirm="<?= $d['revoked_at'] ? 'Make this letter valid again?' : 'Withdraw this letter? Anyone checking it will see it is no longer valid.' ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline" type="submit"><?= $d['revoked_at'] ? 'Restore' : 'Withdraw' ?></button></form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </section>
  <section class="card" id="login">
    <h2>Sign-in for this employee</h2>
    <?php if ($login): ?>
    <dl class="dl mb">
      <dt>Email</dt><dd><?= e($login['email']) ?></dd>
      <dt>Role</dt><dd><?= e(Auth::roleLabel($login['role'])) ?></dd>
      <dt>Status</dt><dd><?= $login['is_active'] ? 'On' : 'Off' ?></dd>
      <dt>Last sign in</dt><dd><?= $login['last_login_at'] ? e(date('j M Y, H:i', strtotime($login['last_login_at'] . ' UTC'))) : 'Never' ?></dd>
    </dl>
    <div class="btn-group">
      <form method="post" action="<?= e(url('admin/employees/' . $emp['id'] . '/login')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="link"><button class="btn btn-sm btn-outline" type="submit"><?= icon('mail') ?>Email a new password link</button></form>
      <form method="post" action="<?= e(url('admin/employees/' . $emp['id'] . '/login')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><button class="btn btn-sm <?= $login['is_active'] ? 'btn-danger' : 'btn-outline' ?>" type="submit"><?= $login['is_active'] ? 'Turn login off' : 'Turn login on' ?></button></form>
    </div>
    <?php else: ?>
    <p class="muted">Give this employee their own sign-in. They can download their ID card and letters, and change their password. They cannot see anyone else.</p>
    <form method="post" action="<?= e(url('admin/employees/' . $emp['id'] . '/login')) ?>" class="form-grid">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <label class="field"><span>Login email</span><input class="input" type="email" name="email" required maxlength="190" value="<?= e($emp['work_email'] ?: $emp['personal_email']) ?>"></label>
      <?php if (Auth::can('users', $user)): ?>
      <label class="field"><span>Role</span><select class="select" name="role"><?php foreach (Auth::ROLES as $k => $ro): ?><option value="<?= e($k) ?>"<?= $k === 'employee' ? ' selected' : '' ?>><?= e($ro['label']) ?></option><?php endforeach; ?></select></label>
      <?php endif; ?>
      <button class="btn btn-sm" type="submit">Create login and email a set-password link</button>
    </form>
    <?php endif; ?>
  </section>
</div>
<?php if (Auth::can('settings', $user)): ?>
<form method="post" class="danger-zone" data-confirm="Delete this employee record and all their letters for good?">
  <?= csrf_field() ?><input type="hidden" name="action" value="delete">
  <button class="btn btn-sm btn-danger" type="submit"><?= icon('trash') ?>Delete employee record</button>
</form>
<?php endif; ?>
<?php endif; ?>
