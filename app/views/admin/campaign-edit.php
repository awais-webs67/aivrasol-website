<?php
$f = fn ($k) => e((string) ($values[$k] ?? ''));
$err = fn ($k) => isset($errors[$k]) ? '<span class="field-error">' . e($errors[$k]) . '</span>' : '';
?>
<p><a class="link-arrow small" href="<?= e(url('admin/campaigns')) ?>">← Back to campaigns</a></p>
<?php if ($errors): ?><p class="form-status is-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>
<?php if ($c): ?>
<section class="card">
  <div class="card-head"><h2><?= e(Campaigns::STATUSES[$c['status']] ?? $c['status']) ?></h2>
    <div class="btn-group">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="test"><button class="btn btn-sm btn-outline" type="submit"><?= icon('mail') ?>Send me a test</button></form>
      <?php if ($c['status'] === 'draft'): ?><form method="post" data-confirm="Start sending to <?= (int) $audience ?> contacts? Emails go out slowly within your limits."><?= csrf_field() ?><input type="hidden" name="action" value="start"><button class="btn btn-sm" type="submit">Start campaign</button></form><?php endif; ?>
      <?php if ($c['status'] === 'sending'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="pause"><button class="btn btn-sm btn-outline" type="submit">Pause</button></form><?php endif; ?>
      <?php if ($c['status'] === 'paused'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="resume"><button class="btn btn-sm" type="submit">Resume</button></form><?php endif; ?>
      <?php if (in_array($c['status'], ['sending', 'paused'], true)): ?><form method="post" data-confirm="Cancel this campaign? Nothing more will be sent."><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><button class="btn btn-sm btn-danger" type="submit">Cancel</button></form><?php endif; ?>
    </div>
  </div>
  <?php if ($stats && $stats['total'] > 0): $done = $stats['sent'] + $stats['failed'] + $stats['skipped']; ?>
  <div class="progress progress-lg"><span class="w-<?= (int) (round($done / max(1, $stats['total']) * 20) * 5) ?>"></span></div>
  <p class="small">Sent <strong><?= (int) $stats['sent'] ?></strong> of <?= (int) $stats['total'] ?> · waiting <?= (int) $stats['queued'] ?> · failed <?= (int) $stats['failed'] ?> · skipped <?= (int) $stats['skipped'] ?> · unsubscribed after this campaign <?= (int) $stats['unsubscribed'] ?></p>
  <?php else: ?>
  <p class="small muted">Audience now: <strong><?= (int) $audience ?></strong> subscribed contact<?= $audience === 1 ? '' : 's' ?><?= $values['audience_tag'] !== '' ? ' tagged "' . e($values['audience_tag']) . '"' : '' ?>.</p>
  <?php endif; ?>
  <?php if ($c['status'] === 'sending'): ?><p class="small muted" data-campaign-tick="<?= e(url('admin/campaigns/tick')) ?>">While this page is open it also sends now and then, within your limits.</p><?php endif; ?>
  <?php if (in_array($c['status'], ['sending', 'paused'], true)): ?><?= partial('campaign-status', ['c' => $c]) ?><?php endif; ?>
</section>
<?php endif; ?>
<?php if (!$c && $templates): ?>
<section class="card">
  <h2>Start from a template</h2>
  <div class="btn-group"><?php foreach ($templates as $t): ?><a class="btn btn-sm btn-outline" href="<?= e(url('admin/campaigns/new?template=' . $t['id'])) ?>"><?= e($t['name']) ?></a><?php endforeach; ?></div>
</section>
<?php endif; ?>
<div class="admin-grid">
  <form method="post" class="card form-grid">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <?php if (!$editable): ?><p class="form-status is-error">Pause the campaign to edit it.</p><?php endif; ?>
    <fieldset class="form-grid"<?= $editable ? '' : ' disabled' ?>>
      <label class="field"><span>Campaign name (only you see this)</span><input class="input" name="name" value="<?= $f('name') ?>" maxlength="120" required><?= $err('name') ?></label>
      <label class="field"><span>Audience</span><select class="select" name="audience_tag"><option value="">Every subscribed contact</option><?php foreach ($tags as $t => $n): ?><option value="<?= e($t) ?>"<?= $values['audience_tag'] === $t ? ' selected' : '' ?>>Tag: <?= e($t) ?> (<?= (int) $n ?>)</option><?php endforeach; ?></select><?= $err('audience_tag') ?></label>
      <label class="field"><span>Subject line</span><input class="input" name="subject" value="<?= $f('subject') ?>" maxlength="200" required><?= $err('subject') ?></label>
      <label class="field"><span>Email text</span><textarea class="textarea editor" name="body" maxlength="20000" required><?= $f('body') ?></textarea><span class="small muted">Placeholders: {first_name}, {last_name}, {company}, {website}, {country}, {sender_name}, {site_name}, {site_url}, {booking_url}. {{Hi|Hello}} picks one at random. The unsubscribe line and your company address are added for you.</span><?= $err('body') ?></label>
      <label class="field"><span>Layout</span><select class="select" name="layout"><option value="plain"<?= $values['layout'] === 'plain' ? ' selected' : '' ?>>Plain, like a personal email (best for cold outreach)</option><option value="branded"<?= $values['layout'] === 'branded' ? ' selected' : '' ?>>Branded with logo (newsletters and announcements)</option></select></label>
      <label class="field"><span>Sender name</span><input class="input" name="from_name" value="<?= $f('from_name') ?>" maxlength="80" placeholder="e.g. Awais from AivraSol"></label>
      <label class="field"><span>Reply-to email (optional)</span><input class="input" type="email" name="reply_to" value="<?= $f('reply_to') ?>" maxlength="190"><?= $err('reply_to') ?></label>
      <button class="btn" type="submit">Save campaign</button>
    </fieldset>
  </form>
  <section class="card">
    <h2>Preview</h2>
    <?php if ($preview): ?>
    <dl class="dl mb"><dt>To</dt><dd><?= e($preview['to']) ?></dd><dt>Subject</dt><dd><?= e($preview['subject']) ?></dd></dl>
    <pre class="mail-preview"><?= e($preview['text']) ?></pre>
    <p class="small muted">Random parts change for each person. Use "Send me a test" to see the real email.</p>
    <?php else: ?>
    <p class="muted">Save the campaign to see a preview with a real contact.</p>
    <?php endif; ?>
    <?php if ($recent): ?>
    <h3 class="mt">Latest sends</h3>
    <div class="table-wrap"><table class="table"><tbody><?php foreach ($recent as $r): ?><tr><td class="small"><?= e($r['email']) ?><?= $r['error'] !== '' ? '<br><span class="muted">' . e($r['error']) . '</span>' : '' ?></td><td class="small"><?= e(ucfirst($r['status'])) ?></td></tr><?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  </section>
</div>
<?php if ($c && $c['status'] !== 'sending'): ?>
<form method="post" class="danger-zone" data-confirm="Delete this campaign and its send history?">
  <?= csrf_field() ?><input type="hidden" name="action" value="delete">
  <button class="btn btn-sm btn-danger" type="submit"><?= icon('trash') ?>Delete campaign</button>
</form>
<?php endif; ?>
