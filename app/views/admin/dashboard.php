<p class="role-note muted">Signed in as <strong><?= e($user['name']) ?></strong>, <?= e(Auth::roleLabel($user['role'])) ?>.</p>
<?php if ($counts): ?>
<div class="stat-cards">
  <?php foreach ($counts as [$label, $n, $path]): ?>
  <a class="stat-card" href="<?= e(url($path)) ?>"><span><?= e($label) ?></span><strong><?= (int) $n ?></strong></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="admin-grid">
  <?php if ($checklist):
    $count = fn ($st) => count(array_filter($checklist, fn ($i) => $i[0] === $st));
    $done = $count('done'); $total = count($checklist); $later = $count('later');
  ?>
  <section class="card span-all">
    <div class="card-head"><h2>Launch checklist</h2><span class="small muted"><?= $done ?> of <?= $total ?> done</span></div>
    <div class="cl-bar" aria-hidden="true"><span class="w-<?= (int) (round($done / max(1, $total) * 20) * 5) ?>"></span></div>
    <ul class="checklist">
      <?php foreach ($checklist as [$state, $text, $hint, $link]): if ($state !== 'todo') continue; ?>
      <li><?= icon('close') ?><span><?php if ($link): ?><a href="<?= e($link) ?>"><?= e($text) ?></a><?php else: ?><strong><?= e($text) ?></strong><?php endif; ?><span class="cl-hint"><?= e($hint) ?></span></span></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($later): ?>
    <p class="cl-head">On the live server</p>
    <ul class="checklist">
      <?php foreach ($checklist as [$state, $text, $hint, $link]): if ($state !== 'later') continue; ?>
      <li class="is-later"><?= icon('clock') ?><span><?= e($text) ?><span class="cl-hint"><?= e($hint) ?></span></span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <?php if ($done): ?>
    <details class="cl-done"><summary>Done (<?= $done ?>)</summary>
      <ul class="checklist">
        <?php foreach ($checklist as [$state, $text]): if ($state !== 'done') continue; ?>
        <li class="is-done"><?= icon('check') ?><span><?= e($text) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </details>
    <?php endif; ?>
    <?php if ($done === $total): ?><p class="form-status is-success">Everything is done. The site is ready.</p><?php endif; ?>
  </section>
  <?php endif; ?>
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
</div>
