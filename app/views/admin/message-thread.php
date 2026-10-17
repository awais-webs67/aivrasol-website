<?php /** @var array $root @var array $items @var array $person @var bool $mine @var array $errors */ ?>
<p><a class="link-arrow small" href="<?= e(url($mine ? 'admin/inbox' : 'admin/messages')) ?>">← Back to <?= $mine ? 'my messages' : 'messages' ?></a></p>
<section class="card thread">
  <div class="thread-head">
    <div>
      <?php if ($root['kind'] === 'notice'): ?><span class="status status-qualified">Notice</span><?php endif; ?>
      <h2><?= e($root['subject']) ?></h2>
      <?php if (!$mine): ?><p class="small muted">Conversation with <a href="<?= e(url('admin/employees/' . $person['id'])) ?>"><?= e($person['full_name']) ?></a>, <?= e($person['designation']) ?><?= $person['user_id'] ? '' : ' (no portal account, email only)' ?></p><?php endif; ?>
    </div>
  </div>
  <ol class="msgs">
    <?php foreach ($items as $i => $m): $fromMe = $mine ? $m['direction'] === 'in' : $m['direction'] === 'out'; ?>
    <li class="msg <?= $fromMe ? 'is-me' : 'is-them' ?>"<?= $i === count($items) - 1 ? ' id="last"' : '' ?>>
      <div class="msg-meta small"><strong><?= e($m['direction'] === 'in' ? $person['full_name'] : ($m['sender'] ?? 'HR')) ?></strong> · <?= e(date('j M Y, H:i', strtotime($m['created_at'] . ' UTC'))) ?><?php if ($m['direction'] === 'out' && !$mine): ?> · <?= $m['read_at'] ? 'read' : 'not read yet' ?><?= $m['emailed'] ? ', emailed' : '' ?><?php endif; ?></div>
      <div class="msg-body"><?= nl2br(e($m['body'])) ?></div>
    </li>
    <?php endforeach; ?>
  </ol>
  <?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
  <form method="post" class="form-grid reply-box">
    <?= csrf_field() ?>
    <label class="field"><span><?= $mine ? 'Your reply' : 'Reply to ' . e(strtok($person['full_name'], ' ')) ?></span><textarea class="textarea" name="body" rows="4" maxlength="8000" required></textarea></label>
    <div class="btn-group"><button class="btn" type="submit"><?= icon('mail') ?>Send reply</button><span class="small muted"><?= $mine ? 'HR gets your reply here and by email.' : 'Saved here and emailed from the HR mailbox.' ?></span></div>
  </form>
</section>
