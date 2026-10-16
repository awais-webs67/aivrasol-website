<p class="role-note muted">Signed in as <strong><?= e($user['name']) ?></strong>, <?= e(Auth::roleLabel($user['role'])) ?>.</p>
<?php if ($counts): ?>
<div class="stat-cards">
  <?php foreach ($counts as [$label, $n, $path]): ?>
  <a class="stat-card" href="<?= e(url($path)) ?>"><span><?= e($label) ?></span><strong><?= (int) $n ?></strong></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="admin-grid">
  <?php if (Auth::can('leads', $user)): ?>
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
  <?php endif; ?>
  <?php if (Auth::can('applications', $user)): ?>
  <section class="card">
    <div class="card-head"><h2>Latest applications</h2><a class="link-arrow small" href="<?= e(url('admin/applications')) ?>">All applications <?= icon('arrow-right') ?></a></div>
    <?php if (!$recentApps): ?>
    <p class="muted">No applications yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Name</th><th>Job</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($recentApps as $a): ?>
      <tr><td><a href="<?= e(url('admin/applications/' . $a['id'])) ?>"><?= e($a['name']) ?></a><br><span class="small muted"><?= e(date('j M Y', strtotime($a['created_at'] . ' UTC'))) ?></span></td><td><?= e($a['position']) ?></td><td><span class="status status-<?= e($a['status']) ?>"><?= e(APP_STATUSES[$a['status']] ?? $a['status']) ?></span></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </section>
  <?php endif; ?>
  <?php if ($checklist): ?>
  <section class="card">
    <div class="card-head"><h2>Launch checklist</h2></div>
    <ul class="checklist">
      <?php foreach ($checklist as [$done, $text, $link]): ?>
      <li class="<?= $done ? 'is-done' : '' ?>"><?= icon($done ? 'check' : 'close') ?><span><?php if ($link && !$done): ?><a href="<?= e($link) ?>"><?= e($text) ?></a><?php else: ?><?= e($text) ?><?php endif; ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
</div>
