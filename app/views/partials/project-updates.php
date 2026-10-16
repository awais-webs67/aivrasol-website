<?php /** @var array $updates */ ?>
<?php if (!$updates): ?>
<p class="muted">No updates yet. Progress reports from the team will appear here.</p>
<?php else: ?>
<ol class="timeline">
  <?php foreach ($updates as $u): ?>
  <li>
    <p class="timeline-meta"><time datetime="<?= e(gmdate('c', strtotime($u['created_at'] . ' UTC'))) ?>"><?= e(date('j M Y', strtotime($u['created_at'] . ' UTC'))) ?></time><?= !empty($u['name']) ? ' · ' . e($u['name']) : '' ?><?= $u['progress'] !== null ? ' · ' . (int) $u['progress'] . '% complete' : '' ?></p>
    <h3><?= e($u['title']) ?></h3>
    <div class="timeline-body"><?= nl2br(e($u['body'])) ?></div>
  </li>
  <?php endforeach; ?>
</ol>
<?php endif; ?>
