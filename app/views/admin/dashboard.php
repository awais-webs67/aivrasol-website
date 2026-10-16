<div class="stat-cards">
  <a class="stat-card" href="<?= e(url('admin/leads?status=new')) ?>"><span>New leads</span><strong><?= $counts['new_leads'] ?></strong></a>
  <a class="stat-card" href="<?= e(url('admin/leads')) ?>"><span>Leads in last 30 days</span><strong><?= $counts['leads_30'] ?></strong></a>
  <a class="stat-card" href="<?= e(url('admin/applications?status=new')) ?>"><span>New applications</span><strong><?= $counts['new_apps'] ?></strong></a>
  <a class="stat-card" href="<?= e(url('admin/content/jobs')) ?>"><span>Open jobs</span><strong><?= $counts['open_jobs'] ?></strong></a>
</div>

<div class="admin-grid">
  <section class="card">
    <div class="card-head"><h2>Latest leads</h2><a class="link-arrow small" href="<?= e(url('admin/leads')) ?>">All leads <?= icon('arrow-right') ?></a></div>
    <?php if (!$recent): ?>
    <p class="muted">No leads yet. New enquiries from the website will appear here.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Name</th><th>Type</th><th>Status</th><th>Received</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $r): ?>
      <tr><td><a href="<?= e(url('admin/leads/' . $r['id'])) ?>"><?= e($r['name']) ?></a><br><span class="small muted"><?= e($r['email']) ?></span></td><td><?= e(LEAD_TYPES[$r['type']]) ?></td><td><span class="status status-<?= e($r['status']) ?>"><?= e(LEAD_STATUSES[$r['status']]) ?></span></td><td class="nowrap small"><?= e(date('j M Y, H:i', strtotime($r['created_at'] . ' UTC'))) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </section>
  <section class="card">
    <div class="card-head"><h2>Launch checklist</h2></div>
    <ul class="checklist">
      <?php foreach ($checklist as [$done, $text, $link]): ?>
      <li class="<?= $done ? 'is-done' : '' ?>"><?= icon($done ? 'check' : 'close') ?><span><?php if ($link && !$done): ?><a href="<?= e($link) ?>"><?= e($text) ?></a><?php else: ?><?= e($text) ?><?php endif; ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>
