<?php
/** @var array $rows @var array $f @var array $stats @var array $roleCounts @var array $audit @var array $user */
$filtered = array_filter($f) !== [];
$accClass = ['active' => 'won', 'invited' => 'qualified', 'disabled' => 'lost', 'none' => ''];
?>
<div class="people-stats">
  <a class="stat-card" href="<?= e(url('admin/employees')) ?>"><span class="stat-num"><?= (int) $stats['total'] ?></span><span>People</span></a>
  <a class="stat-card" href="<?= e(url('admin/employees?status=active')) ?>"><span class="stat-num"><?= (int) $stats['active'] ?></span><span>Working now</span></a>
  <a class="stat-card" href="<?= e(url('admin/employees?account=active')) ?>"><span class="stat-num"><?= (int) $stats['logins'] ?></span><span>Accounts on</span></a>
  <a class="stat-card<?= (int) $stats['pending'] ? ' is-alert' : '' ?>" href="<?= e(url('admin/employees?approval=pending')) ?>"><span class="stat-num"><?= (int) $stats['pending'] ?></span><span>Waiting for approval</span></a>
</div>

<div class="list-head">
  <form class="filters-bar people-filters" method="get" action="<?= e(url('admin/employees')) ?>">
    <label class="sr-only" for="pf-q">Search</label>
    <input class="input" id="pf-q" type="search" name="q" placeholder="Search name, email, ID or job" value="<?= e($f['q']) ?>">
    <label class="sr-only" for="pf-role">Role</label>
    <select class="select" id="pf-role" name="role">
      <option value="">All roles</option>
      <?php foreach (Auth::ROLES as $k => $r): ?><option value="<?= e($k) ?>"<?= $f['role'] === $k ? ' selected' : '' ?>><?= e($r['label']) ?> (<?= (int) ($roleCounts[$k] ?? 0) ?>)</option><?php endforeach; ?>
      <option value="none"<?= $f['role'] === 'none' ? ' selected' : '' ?>>No login</option>
    </select>
    <label class="sr-only" for="pf-acc">Account</label>
    <select class="select" id="pf-acc" name="account"><option value="">Any account status</option><?php foreach (PEOPLE_ACCOUNTS as $k => $v): ?><option value="<?= e($k) ?>"<?= $f['account'] === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
    <label class="sr-only" for="pf-status">Employment</label>
    <select class="select" id="pf-status" name="status"><option value="">Any employment status</option><?php foreach (Documents::STATUSES as $k => $v): ?><option value="<?= e($k) ?>"<?= $f['status'] === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
    <?php if ($f['approval'] !== ''): ?><input type="hidden" name="approval" value="<?= e($f['approval']) ?>"><?php endif; ?>
    <button class="btn btn-sm" type="submit">Filter</button>
    <?php if ($filtered): ?><a class="btn btn-sm btn-outline" href="<?= e(url('admin/employees')) ?>">Clear</a><?php endif; ?>
  </form>
  <a class="btn btn-sm" href="<?= e(url('admin/employees/new')) ?>"><?= icon('plus') ?>Add person</a>
</div>

<form method="get" action="<?= e(url('admin/messages/new')) ?>" class="card" id="people-list">
<?php if (!$rows): ?>
<p class="muted"><?= $filtered ? 'Nobody matches these filters.' : 'No people yet. Add your first team member to create their account and ID card.' ?></p>
<?php else: ?>
<div class="card-head">
  <p class="small muted"><?= count($rows) ?> <?= count($rows) === 1 ? 'person' : 'people' ?><?= $filtered ? ' match' : '' ?>. Tick people to message them.</p>
  <?php if (Auth::can('employees', $user)): ?><button class="btn btn-sm btn-outline" type="submit"><?= icon('mail') ?>Message selected</button><?php endif; ?>
</div>
<div class="table-wrap"><table class="table people-table">
  <thead><tr><th class="pt-check"><input type="checkbox" aria-label="Select everyone in this list" data-check-all></th><th>Person</th><th>Job</th><th>Role</th><th>Account</th><th>Employment</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): $acc = people_account($r); ?>
  <tr<?= $r['approval'] === 'pending' ? ' class="is-pending"' : '' ?>>
    <td class="pt-check"><input type="checkbox" name="to[]" value="<?= (int) $r['id'] ?>" aria-label="Select <?= e($r['full_name']) ?>"></td>
    <td>
      <a class="person" href="<?= e(url('admin/employees/' . $r['id'])) ?>">
        <span class="person-pic"><?php if (Upload::privatePath($r['photo'])): ?><img src="<?= e(url('admin/employees/' . $r['id'] . '/photo')) ?>" alt="" width="40" height="40" loading="lazy"><?php else: ?><?= e(Team::initials($r['full_name'])) ?><?php endif; ?></span>
        <span><strong><?= e($r['full_name']) ?></strong><br><span class="small muted"><?= e($r['login_email'] ?: $r['employee_code']) ?></span></span>
      </a>
      <?php if ($r['approval'] === 'pending'): ?><span class="status status-qualified">Waiting for approval</span><?php elseif ($r['approval'] === 'rejected'): ?><span class="status status-lost">Not approved</span><?php endif; ?>
    </td>
    <td><?= e($r['designation']) ?><br><span class="small muted"><?= e($r['employee_code']) ?><?= $r['department'] !== '' ? ' · ' . e($r['department']) : '' ?></span></td>
    <td class="small"><?= $r['user_id'] ? e(Auth::roleLabel((string) $r['role'])) : '<span class="muted">No login</span>' ?></td>
    <td><span class="status status-<?= e($accClass[$acc]) ?>"><?= e(['active' => 'Active', 'invited' => 'Invited', 'disabled' => 'Disabled', 'none' => 'None'][$acc]) ?></span><?php if ($acc === 'active'): ?><br><span class="small muted">last in <?= e(date('j M', strtotime($r['last_login_at'] . ' UTC'))) ?></span><?php endif; ?></td>
    <td><span class="status status-<?= $r['status'] === 'active' ? 'won' : ($r['status'] === 'on_leave' ? 'qualified' : 'lost') ?>"><?= e(Documents::STATUSES[$r['status']] ?? $r['status']) ?></span></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
</form>

<details class="card">
  <summary class="card-summary is-plain"><h2>What each role can do</h2></summary>
  <dl class="dl roles-dl">
    <?php foreach (Auth::ROLES as $k => $r): ?><dt><?= e($r['label']) ?></dt><dd><?= e($r['about']) ?></dd><?php endforeach; ?>
  </dl>
  <p class="small muted">HR can add people with the Employee role. An admin approves them before their account and ID card turn on. Only admins can change roles.</p>
</details>

<?php if ($audit): ?>
<details class="card">
  <summary class="card-summary is-plain"><h2>Recent account activity</h2></summary>
  <ul class="audit">
    <?php foreach ($audit as $a): ?><li><span class="small muted"><?= e(date('j M, H:i', strtotime($a['created_at'] . ' UTC'))) ?></span> <?= e($a['name'] ?? 'System') ?>: <?= e(str_replace('_', ' ', $a['action'])) ?><?= $a['target'] ? ' · ' . e($a['target']) : '' ?></li><?php endforeach; ?>
  </ul>
</details>
<?php endif; ?>
