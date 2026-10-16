<form class="filters-bar" method="get" action="<?= e(url('admin/applications')) ?>">
  <label class="sr-only" for="af-status">Status</label>
  <select class="select" id="af-status" name="status"><option value="">All statuses</option><?php foreach (APP_STATUSES as $k => $v): ?><option value="<?= e($k) ?>"<?= $status === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
  <button class="btn btn-sm" type="submit">Filter</button>
</form>
<div class="card">
<?php if (!$rows): ?>
<p class="muted">No applications yet.</p>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Name</th><th>Position</th><th>Status</th><th>Received</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
  <tr><td><a href="<?= e(url('admin/applications/' . $r['id'])) ?>"><?= e($r['name']) ?></a><br><span class="small muted"><?= e($r['email']) ?></span></td><td><?= e($r['position']) ?></td><td><span class="status status-<?= e($r['status']) ?>"><?= e(APP_STATUSES[$r['status']]) ?></span></td><td class="nowrap small"><?= e(date('j M Y', strtotime($r['created_at'] . ' UTC'))) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
</div>
