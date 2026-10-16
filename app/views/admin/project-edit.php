<?php /** @var ?array $p @var array $values @var array $errors @var array $clients @var array $managers */
$f = fn ($k) => e((string) ($values[$k] ?? ''));
$err = fn ($k) => isset($errors[$k]) ? '<span class="field-error">' . e($errors[$k]) . '</span>' : '';
?>
<p><a class="link-arrow small" href="<?= e(url('admin/projects')) ?>">← Back to projects</a></p>
<?php if ($errors): ?><p class="form-status is-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>
<?php if (!$clients): ?><p class="form-status is-error" role="alert">Add a client first. <a href="<?= e(url('admin/clients/new')) ?>">Add a client</a></p><?php endif; ?>
<?php if ($p): ?>
<section class="card">
  <div class="card-head"><h2><?= e($p['company_name'] !== '' ? $p['company_name'] : $p['contact_name']) ?></h2><span class="pstatus pstatus-<?= e($p['status']) ?>"><?= e(Portal::STATUSES[$p['status']]) ?></span></div>
  <div class="progress progress-lg"><span class="w-<?= (int) (round($p['progress'] / 5) * 5) ?>"></span></div>
  <p class="small"><strong><?= (int) $p['progress'] ?>% complete</strong> · Client view: <a href="<?= e(url('admin/clients/' . $p['client_id'])) ?>"><?= e($p['client_email']) ?></a><?= $p['client_active'] ? '' : ' (portal access is off)' ?></p>
</section>
<?php endif; ?>
<details class="card"<?= !$p || $errors ? ' open' : '' ?>>
  <summary class="card-summary"><h2><?= $p ? 'Project details' : 'New project' ?></h2></summary>
  <form method="post" class="form-grid cols-2">
    <?= csrf_field() ?>
    <label class="field"><span>Client *</span><select class="select" name="client_id" required><option value="">Choose a client</option><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) $values['client_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['company_name'] !== '' ? $c['company_name'] . ' (' . $c['contact_name'] . ')' : $c['contact_name']) ?></option><?php endforeach; ?></select><?= $err('client_id') ?></label>
    <label class="field"><span>Project title *</span><input class="input" name="title" value="<?= $f('title') ?>" maxlength="150" required><?= $err('title') ?></label>
    <label class="field span-2"><span>Summary (the client sees this)</span><textarea class="textarea" name="summary" maxlength="3000"><?= $f('summary') ?></textarea><?= $err('summary') ?></label>
    <label class="field"><span>Status</span><select class="select" name="status"><?php foreach (Portal::STATUSES as $k => $v): ?><option value="<?= e($k) ?>"<?= $values['status'] === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
    <label class="field"><span>Progress (%)</span><input class="input" type="number" name="progress" min="0" max="100" value="<?= (int) $values['progress'] ?>"></label>
    <label class="field"><span>Currency</span><select class="select" name="currency"><?php foreach (array_keys(Portal::CURRENCIES) as $cur): ?><option value="<?= e($cur) ?>"<?= $values['currency'] === $cur ? ' selected' : '' ?>><?= e($cur) ?></option><?php endforeach; ?></select></label>
    <label class="field"><span>Agreed budget</span><input class="input" name="budget" inputmode="decimal" value="<?= $f('budget') ?>" placeholder="e.g. 12000"><?= $err('budget') ?></label>
    <label class="field"><span>Start date</span><input class="input" type="date" name="start_date" value="<?= $f('start_date') ?>"><?= $err('start_date') ?></label>
    <label class="field"><span>Target date</span><input class="input" type="date" name="due_date" value="<?= $f('due_date') ?>"><?= $err('due_date') ?></label>
    <label class="field"><span>Project manager</span><select class="select" name="manager_id"><option value="">None</option><?php foreach ($managers as $m): ?><option value="<?= (int) $m['id'] ?>"<?= (int) ($values['manager_id'] ?? 0) === (int) $m['id'] ? ' selected' : '' ?>><?= e($m['name']) ?></option><?php endforeach; ?></select><span class="small muted">Gets an email when the client sends a message.</span></label>
    <div class="span-2 btn-group"><button class="btn" type="submit">Save project</button></div>
  </form>
</details>
<?php if ($p): ?>
<div class="portal-grid wide-left">
  <section class="card" id="chat">
    <h2>Messages with the client</h2>
    <?= partial('project-chat', ['endpoint' => url('admin/projects/' . $p['id'] . '/messages'), 'placeholder' => 'Reply to the client. They get an email (at most one every 15 minutes).']) ?>
  </section>
  <section class="card" id="updates">
    <h2>Post an update</h2>
    <form method="post" action="<?= e(url('admin/projects/' . $p['id'] . '/updates')) ?>" class="form-grid">
      <?= csrf_field() ?>
      <label class="field"><span>Title</span><input class="input" name="title" maxlength="150" required placeholder="e.g. Design approved, development started"></label>
      <label class="field"><span>What happened</span><textarea class="textarea" name="body" maxlength="5000" required></textarea></label>
      <label class="field"><span>New progress % (optional)</span><input class="input" type="number" name="progress" min="0" max="100" placeholder="<?= (int) $p['progress'] ?>"></label>
      <label class="check"><input type="checkbox" name="notify" value="1" checked> <span>Email the client about this update</span></label>
      <button class="btn btn-sm" type="submit">Post update</button>
    </form>
    <h3 class="mt">Timeline</h3>
    <?= partial('project-updates', ['updates' => $updates]) ?>
  </section>
</div>
<section class="card" id="money">
  <h2>Budget and payments</h2>
  <?= partial('project-money', ['p' => $p, 'totals' => $totals, 'entries' => $entries, 'staff' => Auth::can('finance', $user)]) ?>
  <?php if (Auth::can('finance', $user)): ?>
  <form method="post" action="<?= e(url('admin/projects/' . $p['id'] . '/entries')) ?>" class="form-grid cols-2 entry-form">
    <?= csrf_field() ?>
    <h3 class="span-2">Add an invoice or payment</h3>
    <label class="field"><span>Type</span><select class="select" name="type"><?php foreach (Portal::ENTRY_TYPES as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?></select></label>
    <label class="field"><span>Amount (<?= e($p['currency']) ?>)</span><input class="input" name="amount" inputmode="decimal" required placeholder="e.g. 2500"></label>
    <label class="field"><span>Date</span><input class="input" type="date" name="entry_date" value="<?= e(gmdate('Y-m-d')) ?>" required></label>
    <label class="field"><span>Invoice due date (invoices only)</span><input class="input" type="date" name="due_date"></label>
    <label class="field"><span>Description</span><input class="input" name="description" maxlength="200" placeholder="e.g. Milestone 2: design"></label>
    <label class="field"><span>Reference</span><input class="input" name="reference" maxlength="80" placeholder="Invoice no. or transaction ID"></label>
    <label class="field"><span>Payment method (payments and refunds)</span><select class="select" name="method"><option value="">Not set</option><?php foreach (Portal::METHODS as $m): ?><option value="<?= e($m) ?>"><?= e($m) ?></option><?php endforeach; ?></select></label>
    <div class="span-2"><button class="btn btn-sm" type="submit">Add entry</button></div>
  </form>
  <?php endif; ?>
</section>
<?php if (Auth::can('settings', $user)): ?>
<form method="post" class="danger-zone" data-confirm="Delete this project with all messages, updates and payments for good?">
  <?= csrf_field() ?><input type="hidden" name="action" value="delete">
  <button class="btn btn-sm btn-danger" type="submit"><?= icon('trash') ?>Delete project</button>
</form>
<?php endif; ?>
<?php endif; ?>
