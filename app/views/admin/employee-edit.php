<?php
/** @var ?array $emp @var array $fields @var array $values @var array $errors @var array $docs @var ?array $login */
$hasPhoto = $emp && Upload::privatePath($emp['photo']);
?>
<p><a class="link-arrow small" href="<?= e(url('admin/employees')) ?>">← Back to People</a></p>
<?php if ($emp && $emp['approval'] === 'pending'): ?>
<section class="card approval-bar">
  <div><strong>Waiting for approval.</strong> <span class="muted">Added by HR. The account and ID card turn on when an admin approves.</span></div>
  <?php if (Auth::can('users', $user)): ?>
  <div class="btn-group">
    <form method="post" action="<?= e(url('admin/employees/' . $emp['id'] . '/approval')) ?>"><?= csrf_field() ?><button class="btn btn-sm" name="action" value="approve" type="submit"><?= icon('check') ?>Approve</button></form>
    <form method="post" action="<?= e(url('admin/employees/' . $emp['id'] . '/approval')) ?>" data-confirm="Do not approve this person? Their account stays off."><?= csrf_field() ?><button class="btn btn-sm btn-outline" name="action" value="reject" type="submit">Do not approve</button></form>
  </div>
  <?php endif; ?>
</section>
<?php elseif ($emp && $emp['approval'] === 'rejected'): ?>
<section class="card approval-bar is-off">
  <div><strong>Not approved.</strong> <span class="muted">The account stays off.</span></div>
  <?php if (Auth::can('users', $user)): ?><form method="post" action="<?= e(url('admin/employees/' . $emp['id'] . '/approval')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline" name="action" value="approve" type="submit">Approve after all</button></form><?php endif; ?>
</section>
<?php endif; ?>
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
    <?php if ($emp['approval'] === 'approved'): ?>
    <a class="btn btn-sm" href="<?= e(url('admin/employees/' . $emp['id'] . '/card')) ?>"><?= icon('download') ?>ID card PDF</a>
    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/employees/' . $emp['id'] . '/card?view=1')) ?>" target="_blank" rel="noopener">Preview card</a>
    <?php endif; ?>
    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/messages/new?to[]=' . $emp['id'])) ?>"><?= icon('mail') ?>Send a message</a>
    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/issuance/new?employee=' . $emp['id'])) ?>"><?= icon('file') ?>Issue a certificate</a>
  </div>
</section>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card form-grid cols-2 edit-form">
  <?= csrf_field() ?>
  <?php if (!$emp): $canAll = Auth::can('users', $user); $lr = (string) ($values['login_role'] ?? 'employee'); ?>
  <h2 class="span-2">Account</h2>
  <p class="span-2 small muted"><?= $canAll ? 'Choose the role. It decides what this person can open in the admin panel. They get an email to set their own password.' : 'People you add get the Employee role. An admin approves them before their account and ID card turn on.' ?></p>
  <div class="field">
    <label for="e-login_email"><span>Sign-in email *</span></label>
    <input class="input" id="e-login_email" type="email" name="login_email" value="<?= e((string) ($values['login_email'] ?? '')) ?>" maxlength="190" autocomplete="off"<?= isset($errors['login_email']) ? ' aria-invalid="true"' : '' ?>>
    <?php if (isset($errors['login_email'])): ?><span class="field-error"><?= e($errors['login_email']) ?></span><?php endif; ?>
  </div>
  <div class="field">
    <label for="e-login_role"><span>Role</span></label>
    <select class="select" id="e-login_role" name="login_role"<?= isset($errors['login_role']) ? ' aria-invalid="true"' : '' ?>>
      <?php foreach (Auth::ROLES as $k => $ro): if (!$canAll && $k !== 'employee') continue; ?><option value="<?= e($k) ?>"<?= $lr === $k ? ' selected' : '' ?>><?= e($ro['label']) ?>: <?= e($ro['about']) ?></option><?php endforeach; ?>
      <option value=""<?= $lr === '' ? ' selected' : '' ?>>No login (record only, for example a past employee)</option>
    </select>
    <?php if (isset($errors['login_role'])): ?><span class="field-error"><?= e($errors['login_role']) ?></span><?php endif; ?>
  </div>
  <?php if ($canAll): ?><label class="check span-2"><input type="checkbox" name="login_invite" value="1"<?= !is_post() || !empty($values['login_invite']) ? ' checked' : '' ?>> <span>Email them a link to set their password now</span></label><?php endif; ?>
  <?php endif; ?>
  <h2 class="span-2">Personal and job details</h2>
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
  <div class="span-2 btn-group"><button class="btn" type="submit"><?= $emp ? 'Save changes' : (Auth::can('users', $user) ? 'Add person' : 'Add and send for approval') ?></button></div>
</form>

<?php if ($emp): ?>
<div class="admin-grid">
  <section class="card" id="documents">
    <div class="card-head"><h2>Certificates and letters</h2><a class="btn btn-sm btn-outline" href="<?= e(url('admin/issuance/new?employee=' . $emp['id'])) ?>">Issue one</a></div>
    <?php if (!$docs): ?>
    <p class="muted">Nothing issued yet.</p>
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
    <h2>Account</h2>
    <?php if ($login): $isMe = (int) $login['id'] === (int) $user['id']; ?>
    <dl class="dl mb">
      <dt>Sign-in email</dt><dd><?= e($login['email']) ?></dd>
      <dt>Role</dt><dd><?= e(Auth::roleLabel($login['role'])) ?><br><span class="small muted"><?= e(Auth::ROLES[$login['role']]['about'] ?? '') ?></span></dd>
      <dt>Status</dt><dd><span class="status status-<?= $login['is_active'] ? 'won' : 'lost' ?>"><?= $login['is_active'] ? 'On' : 'Off' ?></span></dd>
      <dt>Last sign in</dt><dd><?= $login['last_login_at'] ? e(date('j M Y, H:i', strtotime($login['last_login_at'] . ' UTC'))) : 'Never' ?></dd>
    </dl>
    <?php if (Auth::can('users', $user) && !$isMe): ?>
    <form method="post" action="<?= e(url('admin/employees/' . $emp['id'] . '/login')) ?>" class="inline-role"><?= csrf_field() ?><input type="hidden" name="action" value="role">
      <label class="sr-only" for="acc-role">Change role</label>
      <select class="select" id="acc-role" name="role"><?php foreach (Auth::ROLES as $k => $ro): ?><option value="<?= e($k) ?>"<?= $login['role'] === $k ? ' selected' : '' ?>><?= e($ro['label']) ?></option><?php endforeach; ?></select>
      <button class="btn btn-sm btn-outline" type="submit">Change role</button>
    </form>
    <?php endif; ?>
    <div class="btn-group">
      <?php if ($emp['approval'] === 'approved'): ?><form method="post" action="<?= e(url('admin/employees/' . $emp['id'] . '/login')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="link"><button class="btn btn-sm btn-outline" type="submit"><?= icon('mail') ?>Email a password link</button></form><?php endif; ?>
      <?php if (!$isMe && ($emp['approval'] === 'approved' || $login['is_active'])): ?><form method="post" action="<?= e(url('admin/employees/' . $emp['id'] . '/login')) ?>"<?= $login['is_active'] ? ' data-confirm="Turn this account off? They are signed out straight away."' : '' ?>><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><button class="btn btn-sm <?= $login['is_active'] ? 'btn-danger' : 'btn-outline' ?>" type="submit"><?= $login['is_active'] ? 'Turn account off' : 'Turn account on' ?></button></form><?php endif; ?>
    </div>
    <?php if (!$isMe && $emp['approval'] === 'approved'): ?>
    <details class="temp-pass">
      <summary class="small">Set a temporary password instead (when email is not working)</summary>
      <form method="post" action="<?= e(url('admin/employees/' . $emp['id'] . '/login')) ?>" class="filters-bar"><?= csrf_field() ?><input type="hidden" name="action" value="password">
        <label class="sr-only" for="acc-pass">Temporary password</label>
        <input class="input" id="acc-pass" type="text" name="password" minlength="10" maxlength="200" autocomplete="off" placeholder="10+ characters, letters and numbers" required>
        <button class="btn btn-sm btn-outline" type="submit">Set password</button>
      </form>
      <p class="small muted">Tell them in person. They must choose their own password at the next sign in.</p>
    </details>
    <?php endif; ?>
    <?php else: ?>
    <p class="muted">No sign-in yet. Give this person an account so they can see their ID card, documents and messages.</p>
    <form method="post" action="<?= e(url('admin/employees/' . $emp['id'] . '/login')) ?>" class="form-grid">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <label class="field"><span>Sign-in email</span><input class="input" type="email" name="email" required maxlength="190" value="<?= e($emp['work_email'] ?: $emp['personal_email']) ?>"></label>
      <?php if (Auth::can('users', $user)): ?>
      <label class="field"><span>Role</span><select class="select" name="role"><?php foreach (Auth::ROLES as $k => $ro): ?><option value="<?= e($k) ?>"<?= $k === 'employee' ? ' selected' : '' ?>><?= e($ro['label']) ?></option><?php endforeach; ?></select></label>
      <?php endif; ?>
      <button class="btn btn-sm" type="submit">Create account<?= $emp['approval'] === 'approved' ? ' and email a set-password link' : '' ?></button>
    </form>
    <?php endif; ?>
  </section>
</div>
<?php if (Auth::can('settings', $user)): ?>
<form method="post" class="danger-zone" data-confirm="Delete this person for good, with their account, documents and messages?">
  <?= csrf_field() ?><input type="hidden" name="action" value="delete">
  <button class="btn btn-sm btn-danger" type="submit"><?= icon('trash') ?>Delete person</button>
</form>
<?php endif; ?>
<?php endif; ?>
