<?php /** @var array $projects */ ?>
<h1 class="portal-title">Hello, <?= e(strtok($client['contact_name'], ' ') ?: $client['contact_name']) ?></h1>
<p class="lead">Here is everything on your projects with <?= e(setting('site_name', 'AivraSol')) ?>.</p>
<?php if (!$projects): ?>
<div class="p-card"><h2>No projects yet</h2><p class="muted">Your project will appear here as soon as our team sets it up.</p></div>
<?php else: ?>
<div class="project-list">
  <?php foreach ($projects as $p): $t = $p['totals']; ?>
  <a class="p-card project-tile" href="<?= e(url('portal/projects/' . $p['id'])) ?>">
    <div class="tile-top">
      <span class="pstatus pstatus-<?= e($p['status']) ?>"><?= e(Portal::STATUSES[$p['status']] ?? $p['status']) ?></span>
      <?php if ((int) $p['unread'] > 0): ?><span class="unread"><?= (int) $p['unread'] ?> new message<?= (int) $p['unread'] > 1 ? 's' : '' ?></span><?php endif; ?>
    </div>
    <h2><?= e($p['title']) ?></h2>
    <div class="progress" aria-label="<?= (int) $p['progress'] ?>% complete"><span class="w-<?= (int) (round($p['progress'] / 5) * 5) ?>"></span></div>
    <p class="small muted"><?= (int) $p['progress'] ?>% complete<?= $p['due_date'] ? ' · target ' . e(date('j M Y', strtotime($p['due_date']))) : '' ?></p>
    <dl class="tile-money">
      <div><dt>Budget</dt><dd><?= e(Portal::money($t['budget'], $p['currency'])) ?></dd></div>
      <div><dt>Paid</dt><dd><?= e(Portal::money($t['paid'], $p['currency'])) ?></dd></div>
      <div><dt>Remaining</dt><dd><?= e(Portal::money($t['remaining'], $p['currency'])) ?></dd></div>
    </dl>
    <span class="link-arrow">Open project <?= icon('arrow-right') ?></span>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
