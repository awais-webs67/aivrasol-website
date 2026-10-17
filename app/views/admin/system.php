<?php $labels = ['ok' => 'OK', 'warn' => 'Check', 'fail' => 'Fix']; ?>
<section class="card">
  <div class="card-head"><h2>Test everything</h2></div>
  <p class="muted">Checks the server, database, folders, settings, email, PDFs, background jobs, backups and security in one go. Each run is saved below.</p>
  <form method="post" class="inline-form">
    <?= csrf_field() ?>
    <label class="check"><input type="checkbox" name="email" value="1"> <span>Also send a real test email to <?= e($user['email']) ?></span></label>
    <button class="btn btn-sm" type="submit"><?= icon('check') ?>Run system check</button>
  </form>
</section>
<?php if ($run): ?>
<section class="card">
  <div class="card-head"><h2>Results</h2><span class="small muted"><?= e(date('j M Y, H:i', strtotime($run['created_at'] . ' UTC'))) ?> UTC<?= $run['name'] ? ' · ' . e($run['name']) : '' ?></span></div>
  <div class="check-summary">
    <span class="sc sc-ok"><?= (int) $run['passed'] ?> OK</span>
    <span class="sc sc-warn"><?= (int) $run['warnings'] ?> to check</span>
    <span class="sc sc-fail"><?= (int) $run['failed'] ?> to fix</span>
  </div>
  <?php $group = null; foreach ($results as [$g, $name, $status, $detail, $fix]): ?>
  <?php if ($g !== $group): $group = $g; ?><h3 class="check-group"><?= e($g) ?></h3><?php endif; ?>
  <div class="check-row is-<?= e($status) ?>">
    <span class="sc sc-<?= e($status) ?>"><?= e($labels[$status] ?? $status) ?></span>
    <div><strong><?= e($name) ?></strong><br><span class="small"><?= e($detail) ?></span><?php if ($status !== 'ok' && $fix !== ''): ?><br><span class="small muted">How to fix: <?= e($fix) ?></span><?php endif; ?></div>
  </div>
  <?php endforeach; ?>
</section>
<?php endif; ?>
<?php if ($runs): ?>
<section class="card">
  <h2>Check log</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>When (UTC)</th><th>By</th><th>Result</th></tr></thead>
    <tbody><?php foreach ($runs as $r): ?><tr<?= $run && (int) $r['id'] === (int) $run['id'] ? ' class="is-current"' : '' ?>>
      <td class="small nowrap"><a href="<?= e(url('admin/system') . '?run=' . $r['id']) ?>"><?= e(date('j M Y, H:i', strtotime($r['created_at'] . ' UTC'))) ?></a></td>
      <td class="small"><?= e((string) $r['name']) ?></td>
      <td class="small nowrap"><span class="sc sc-ok"><?= (int) $r['passed'] ?></span> <span class="sc sc-warn"><?= (int) $r['warnings'] ?></span> <span class="sc sc-fail"><?= (int) $r['failed'] ?></span></td>
    </tr><?php endforeach; ?></tbody>
  </table></div>
  <?= admin_pager($runsTotal, $runsPer, $runsPage, 'runs_page') ?>
</section>
<?php endif; ?>
