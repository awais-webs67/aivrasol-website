<?php /** @var array $rows @var string $show @var int $unread */ ?>
<div class="list-head">
  <div class="tabs-inline">
    <a class="<?= $show === '' ? 'is-on' : '' ?>" href="<?= e(url('admin/messages')) ?>">All conversations</a>
    <a class="<?= $show === 'unread' ? 'is-on' : '' ?>" href="<?= e(url('admin/messages?show=unread')) ?>">New replies<?= $unread ? ' <b class="pill">' . $unread . '</b>' : '' ?></a>
  </div>
  <div class="btn-group">
    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/messages/new?group=all')) ?>"><?= icon('users') ?>Notice to everyone</a>
    <a class="btn btn-sm" href="<?= e(url('admin/messages/new')) ?>"><?= icon('plus') ?>New message</a>
  </div>
</div>
<div class="card">
<?php if (!$rows): ?>
<p class="muted"><?= $show ? 'No new replies.' : 'No messages yet. Write to one person from their page in People, tick several people in the list, or send a notice to everyone.' ?></p>
<?php else: ?>
<ul class="thread-list">
  <?php foreach ($rows as $r): ?>
  <li class="<?= $r['unread'] ? 'is-unread' : '' ?>">
    <a href="<?= e(url('admin/messages/' . $r['id'])) ?>">
      <span class="person-pic"><?= e(Team::initials($r['full_name'])) ?></span>
      <span class="tl-main">
        <strong><?= e($r['full_name']) ?></strong> <span class="small muted"><?= e($r['designation']) ?></span><br>
        <span class="tl-subject"><?php if ($r['kind'] === 'notice'): ?><span class="status status-qualified">Notice</span> <?php endif; ?><?= e($r['subject']) ?></span>
      </span>
      <span class="tl-meta small">
        <?= e(date('j M, H:i', strtotime($r['last_at'] . ' UTC'))) ?><br>
        <?php if ($r['unread']): ?><b class="pill"><?= (int) $r['unread'] ?> new</b><?php elseif ($r['unseen']): ?><span class="muted">Not read yet</span><?php else: ?><span class="muted"><?= (int) $r['replies'] ? (int) $r['replies'] . ' ' . ((int) $r['replies'] === 1 ? 'reply' : 'replies') : 'Read' ?></span><?php endif; ?>
      </span>
    </a>
  </li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
<?= admin_pager($total, $per, $page) ?>
</div>
