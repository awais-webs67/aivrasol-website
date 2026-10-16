<div class="list-head">
  <form class="filters-bar" method="get" action="<?= e(url('admin/projects')) ?>">
    <label class="sr-only" for="pf-status">Status</label>
    <select class="select" id="pf-status" name="status"><option value="">All statuses</option><?php foreach (Portal::STATUSES as $k => $v): ?><option value="<?= e($k) ?>"<?= $status === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
    <button class="btn btn-sm" type="submit">Filter</button>
  </form>
  <a class="btn btn-sm" href="<?= e(url('admin/projects/new')) ?>"><?= icon('plus') ?>New project</a>
</div>
<div class="card">
<?php if (!$rows): ?>
<p class="muted">No client projects yet. Add a client first, then create their project.</p>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Project</th><th>Status</th><th>Progress</th><th>Paid / budget</th><th>Manager</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><a href="<?= e(url('admin/projects/' . $r['id'])) ?>"><?= e($r['title']) ?></a><?php if ((int) $r['unread'] > 0): ?> <b class="pill"><?= (int) $r['unread'] ?> new</b><?php endif; ?><br><span class="small muted"><?= e($r['company_name'] !== '' ? $r['company_name'] : $r['contact_name']) ?></span></td>
    <td><?= e(Portal::STATUSES[$r['status']] ?? $r['status']) ?><?= $r['due_date'] ? '<br><span class="small muted">Due ' . e(date('j M Y', strtotime($r['due_date']))) . '</span>' : '' ?></td>
    <td><?= (int) $r['progress'] ?>%</td>
    <td class="nowrap small"><?= e(Portal::money($r['paid'], $r['currency'])) ?> / <?= e(Portal::money($r['budget'], $r['currency'])) ?></td>
    <td class="small"><?= e((string) $r['manager']) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
</div>
