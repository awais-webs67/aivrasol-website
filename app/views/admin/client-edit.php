<?php /** @var ?array $client @var array $values @var array $errors @var array $projects */
$f = fn ($k) => e((string) ($values[$k] ?? ''));
$err = fn ($k) => isset($errors[$k]) ? '<span class="field-error">' . e($errors[$k]) . '</span>' : '';
?>
<p><a class="link-arrow small" href="<?= e(url('admin/clients')) ?>">← Back to clients</a></p>
<?php if ($errors): ?><p class="form-status is-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>
<div class="admin-grid">
  <form method="post" class="card form-grid cols-2">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <?php if (!empty($values['lead_id']) && !$client): ?><input type="hidden" name="lead_id" value="<?= (int) $values['lead_id'] ?>"><p class="span-2 small muted">Filled in from lead #<?= (int) $values['lead_id'] ?>. The lead is marked as won when you save.</p><?php endif; ?>
    <h2 class="span-2"><?= $client ? 'Client details' : 'New client' ?></h2>
    <label class="field"><span>Contact name *</span><input class="input" name="contact_name" value="<?= $f('contact_name') ?>" maxlength="100" required><?= $err('contact_name') ?></label>
    <label class="field"><span>Company</span><input class="input" name="company_name" value="<?= $f('company_name') ?>" maxlength="120"><?= $err('company_name') ?></label>
    <label class="field"><span>Email (portal sign-in) *</span><input class="input" type="email" name="email" value="<?= $f('email') ?>" maxlength="190" required><?= $err('email') ?></label>
    <label class="field"><span>Phone</span><input class="input" name="phone" value="<?= $f('phone') ?>" maxlength="40"></label>
    <label class="field"><span>Country</span><input class="input" name="country" value="<?= $f('country') ?>" maxlength="60"></label>
    <label class="field span-2"><span>Private notes (never shown to the client)</span><textarea class="textarea" name="notes" maxlength="3000"><?= $f('notes') ?></textarea></label>
    <?php if (!$client): ?><label class="check span-2"><input type="checkbox" name="invite" value="1" checked> <span>Email a portal invitation now (they choose their own password; the link works for 7 days)</span></label><?php endif; ?>
    <div class="span-2 btn-group"><button class="btn" type="submit"><?= $client ? 'Save client' : 'Add client' ?></button></div>
  </form>
  <?php if ($client): ?>
  <section class="card">
    <h2>Portal access</h2>
    <dl class="dl mb">
      <dt>Status</dt><dd><?= $client['is_active'] ? 'On' : 'Off' ?><?= $client['must_change_password'] ? ' (temporary password)' : '' ?></dd>
      <dt>Last sign in</dt><dd><?= $client['last_login_at'] ? e(date('j M Y, H:i', strtotime($client['last_login_at'] . ' UTC'))) : 'Never' ?></dd>
      <dt>Portal address</dt><dd><?= e(absolute_url('portal')) ?></dd>
    </dl>
    <div class="form-grid">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="link"><button class="btn btn-sm btn-outline btn-block" type="submit"><?= icon('mail') ?><?= $client['last_login_at'] ? 'Email a password reset link' : 'Email the invitation again' ?></button></form>
      <form method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="action" value="password">
        <label class="field"><span>Or set a temporary password</span><input class="input" type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password" required></label>
        <button class="btn btn-sm btn-outline" type="submit">Set temporary password</button>
      </form>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><button class="btn btn-sm btn-block <?= $client['is_active'] ? 'btn-danger' : 'btn-outline' ?>" type="submit"><?= $client['is_active'] ? 'Turn portal access off' : 'Turn portal access on' ?></button></form>
    </div>
  </section>
  <?php endif; ?>
</div>
<?php if ($client): ?>
<section class="card">
  <div class="card-head"><h2>Projects</h2><?php if (Auth::can('projects', $user)): ?><a class="btn btn-sm" href="<?= e(url('admin/projects/new?client=' . $client['id'])) ?>"><?= icon('plus') ?>New project</a><?php endif; ?></div>
  <?php if (!$projects): ?><p class="muted">No projects yet.</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Project</th><th>Status</th><th>Progress</th><th>Budget</th></tr></thead>
    <tbody><?php foreach ($projects as $pr): ?><tr>
      <td><?php if (Auth::can('projects', $user)): ?><a href="<?= e(url('admin/projects/' . $pr['id'])) ?>"><?= e($pr['title']) ?></a><?php else: ?><?= e($pr['title']) ?><?php endif; ?></td>
      <td><?= e(Portal::STATUSES[$pr['status']] ?? $pr['status']) ?></td><td><?= (int) $pr['progress'] ?>%</td><td class="nowrap"><?= e(Portal::money($pr['budget'], $pr['currency'])) ?></td>
    </tr><?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php if (Auth::can('settings', $user)): ?>
<form method="post" class="danger-zone" data-confirm="Delete this client, all their projects, messages and payments for good?">
  <?= csrf_field() ?><input type="hidden" name="action" value="delete">
  <button class="btn btn-sm btn-danger" type="submit"><?= icon('trash') ?>Delete client</button>
</form>
<?php endif; ?>
<?php endif; ?>
