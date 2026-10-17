<?php /** @var array $rows */ ?>
<div class="card">
<?php if (!$rows): ?>
<p class="muted">No messages yet. Messages and notices from HR and management appear here and in your email.</p>
<?php else: ?>
<ul class="thread-list">
  <?php foreach ($rows as $r): ?>
  <li class="<?= $r['unread'] ? 'is-unread' : '' ?>">
    <a href="<?= e(url('admin/inbox/' . $r['id'])) ?>">
      <span class="person-pic"><?= icon($r['kind'] === 'notice' ? 'bell' : 'mail') ?></span>
      <span class="tl-main"><span class="tl-subject"><?php if ($r['kind'] === 'notice'): ?><span class="status status-qualified">Notice</span> <?php endif; ?><strong><?= e($r['subject']) ?></strong></span></span>
      <span class="tl-meta small"><?= e(date('j M, H:i', strtotime($r['last_at'] . ' UTC'))) ?><br><?= $r['unread'] ? '<b class="pill">New</b>' : '<span class="muted">' . ((int) $r['replies'] ? (int) $r['replies'] . ' ' . ((int) $r['replies'] === 1 ? 'reply' : 'replies') : 'Read') . '</span>' ?></span>
    </a>
  </li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
<?= admin_pager($total, $per, $page) ?>
</div>
