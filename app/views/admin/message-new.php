<?php /** @var array $people @var string $group @var array $values @var array $errors @var array $roleCounts */ ?>
<p><a class="link-arrow small" href="<?= e(url('admin/messages')) ?>">← Back to messages</a></p>
<?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<section class="card">
  <h2>Who receives it</h2>
  <form method="get" class="filters-bar">
    <label class="sr-only" for="mg">Group</label>
    <select class="select" id="mg" name="group">
      <option value="">Choose a group...</option>
      <option value="all"<?= $group === 'all' ? ' selected' : '' ?>>Everyone working now</option>
      <?php foreach (Auth::ROLES as $k => $r): ?><option value="<?= e($k) ?>"<?= $group === $k ? ' selected' : '' ?>><?= e($r['label']) ?> (<?= (int) ($roleCounts[$k] ?? 0) ?>)</option><?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline" type="submit">Use this group</button>
    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/employees')) ?>">Pick people from People</a>
  </form>
  <?php if ($people): ?>
  <ul class="chips recipients"><?php foreach ($people as $p): ?><li><?= e($p['full_name']) ?><?= msg_address($p) === '' ? ' <span class="muted">(no email)</span>' : '' ?></li><?php endforeach; ?></ul>
  <p class="small muted"><?= count($people) ?> <?= count($people) === 1 ? 'person' : 'people' ?>. Everyone gets their own private copy.</p>
  <?php else: ?>
  <p class="muted">Nobody chosen yet. Choose a group above, or tick people in People and click "Message selected".</p>
  <?php endif; ?>
</section>
<?php if ($people): ?>
<form method="post" class="card form-grid">
  <?= csrf_field() ?>
  <?php foreach ($people as $p): ?><input type="hidden" name="to[]" value="<?= (int) $p['id'] ?>"><?php endforeach; ?>
  <h2>Message</h2>
  <div class="radio-cards">
    <?php foreach (MESSAGE_KINDS as $k => $label): ?>
    <label class="radio-card"><input type="radio" name="kind" value="<?= e($k) ?>"<?= $values['kind'] === $k ? ' checked' : '' ?>><span><strong><?= e($label) ?></strong><span class="small muted"><?= $k === 'notice' ? 'Policy, holiday or office news. Marked as a notice.' : 'A personal note or request. They can reply.' ?></span></span></label>
    <?php endforeach; ?>
  </div>
  <label class="field"><span>Subject *</span><input class="input" name="subject" value="<?= e($values['subject']) ?>" maxlength="150" required></label>
  <label class="field"><span>Message *</span><textarea class="textarea" name="body" rows="9" maxlength="8000" required><?= e($values['body']) ?></textarea><span class="small muted">"Dear (first name)," is added at the top for each person. The email asks them to reply from the portal so answers are saved here.</span></label>
  <label class="check"><input type="checkbox" name="email" value="1"<?= $values['email'] ? ' checked' : '' ?>> <span>Also send it by email (from the HR mailbox)</span></label>
  <div class="btn-group"><button class="btn" type="submit"><?= icon('mail') ?>Send to <?= count($people) ?> <?= count($people) === 1 ? 'person' : 'people' ?></button></div>
</form>
<?php endif; ?>
