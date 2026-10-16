<form class="filters-bar" method="get" action="<?= e(url('admin/leads')) ?>">
  <label class="sr-only" for="lf-q">Search</label>
  <input class="input" id="lf-q" type="search" name="q" placeholder="Search name, email or company" value="<?= e($filters['q']) ?>">
  <label class="sr-only" for="lf-status">Status</label>
  <select class="select" id="lf-status" name="status"><option value="">All (except spam)</option><?php foreach (LEAD_STATUSES as $k => $v): ?><option value="<?= e($k) ?>"<?= $filters['status'] === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
  <label class="sr-only" for="lf-type">Type</label>
  <select class="select" id="lf-type" name="type"><option value="">All types</option><?php foreach (LEAD_TYPES as $k => $v): ?><option value="<?= e($k) ?>"<?= $filters['type'] === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
  <button class="btn btn-sm" type="submit">Filter</button>
  <a class="btn btn-sm btn-outline" href="<?= e(url('admin/leads/export?' . http_build_query(array_filter($filters)))) ?>"><?= icon('download') ?>Export CSV</a>
</form>
<p class="small muted"><?= $total ?> lead<?= $total === 1 ? '' : 's' ?> found</p>
<div class="card">
<?php if (!$rows): ?>
<p class="muted">No leads match these filters.</p>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Name</th><th>Company</th><th>Type</th><th>Status</th><th>Received</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><a href="<?= e(url('admin/leads/' . $r['id'])) ?>"><?= e($r['name']) ?></a><br><span class="small muted"><?= e($r['email']) ?></span></td>
    <td><?= e($r['company']) ?></td>
    <td><?= e(LEAD_TYPES[$r['type']]) ?></td>
    <td><span class="status status-<?= e($r['status']) ?>"><?= e(LEAD_STATUSES[$r['status']]) ?></span></td>
    <td class="nowrap small"><?= e(date('j M Y, H:i', strtotime($r['created_at'] . ' UTC'))) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
</div>
<?php $pages = (int) ceil($total / $per); if ($pages > 1): ?>
<nav class="pager" aria-label="Pages">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
  <a href="<?= e(url('admin/leads?' . http_build_query(array_filter($filters + ['page' => $i])))) ?>"<?= $i === $page ? ' aria-current="page"' : '' ?>><?= $i ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>
