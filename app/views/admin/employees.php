<div class="list-head">
  <form class="filters-bar" method="get" action="<?= e(url('admin/employees')) ?>">
    <label class="sr-only" for="ef-q">Search</label>
    <input class="input" id="ef-q" type="search" name="q" placeholder="Search name, ID, job title or email" value="<?= e($q) ?>">
    <label class="sr-only" for="ef-status">Status</label>
    <select class="select" id="ef-status" name="status"><option value="">All statuses</option><?php foreach (Documents::STATUSES as $k => $v): ?><option value="<?= e($k) ?>"<?= $status === $k ? ' selected' : '' ?>><?= e($v) ?> (<?= (int) ($counts[$k] ?? 0) ?>)</option><?php endforeach; ?></select>
    <button class="btn btn-sm" type="submit">Filter</button>
  </form>
  <a class="btn btn-sm" href="<?= e(url('admin/employees/new')) ?>"><?= icon('plus') ?>Add employee</a>
</div>
<div class="card">
<?php if (!$rows): ?>
<p class="muted"><?= $q !== '' || $status !== '' ? 'No employees match these filters.' : 'No employees yet. Add your first team member to create their ID card and documents.' ?></p>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Name</th><th>ID</th><th>Job</th><th>Status</th><th>Login</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><a href="<?= e(url('admin/employees/' . $r['id'])) ?>"><?= e($r['full_name']) ?></a><br><span class="small muted">Joined <?= e(date('j M Y', strtotime($r['join_date']))) ?></span></td>
    <td class="nowrap"><?= e($r['employee_code']) ?></td>
    <td><?= e($r['designation']) ?><?php if ($r['department'] !== ''): ?><br><span class="small muted"><?= e($r['department']) ?></span><?php endif; ?></td>
    <td><span class="status status-<?= $r['status'] === 'active' ? 'won' : ($r['status'] === 'on_leave' ? 'qualified' : 'lost') ?>"><?= e(Documents::STATUSES[$r['status']] ?? $r['status']) ?></span></td>
    <td class="small"><?php if ($r['user_id']): ?><?= e(Auth::roleLabel((string) $r['role'])) ?><?= $r['login_active'] ? '' : ' (off)' ?><?php else: ?><span class="muted">None</span><?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
</div>
