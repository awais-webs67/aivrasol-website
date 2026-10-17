<?php
$cronAge = setting('cron_last_seen') !== '' ? time() - strtotime(setting('cron_last_seen') . ' UTC') : null;
$cronOk = $cronAge !== null && $cronAge < 900;
?>
<?php if (setting('camp_alert') !== ''): ?><p class="form-status is-error" role="alert"><?= e(setting('camp_alert')) ?> Fix the email settings, then resume the campaign.</p><?php endif; ?>
<div class="stat-cards">
  <div class="stat-card"><span>Sent in last 24 hours</span><strong><?= $sent24 ?></strong></div>
  <div class="stat-card"><span>Waiting to send</span><strong><?= $queued ?></strong></div>
  <div class="stat-card"><span>Daily limit</span><strong><?= $limits['per_day'] ?></strong></div>
  <div class="stat-card"><span>Hourly limit</span><strong><?= $limits['per_hour'] ?></strong></div>
</div>
<?= partial('campaign-status') ?>
<div class="list-head">
  <h2 class="sr-only">Campaigns</h2>
  <div class="btn-group">
    <a class="btn btn-sm" href="<?= e(url('admin/campaigns/new')) ?>"><?= icon('plus') ?>New campaign</a>
    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/content/email-templates')) ?>"><?= icon('file') ?>Email templates</a>
    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/contacts')) ?>"><?= icon('users') ?>Contacts</a>
  </div>
</div>
<div class="card">
<?php if (!$rows): ?>
<p class="muted">No campaigns yet. Start from one of the ready-made templates.</p>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Campaign</th><th>Status</th><th>Sent</th><th>Created</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><a href="<?= e(url('admin/campaigns/' . $r['id'])) ?>"><?= e($r['name']) ?></a><br><span class="small muted"><?= e($r['subject']) ?></span></td>
    <td><span class="status status-<?= ['sending' => 'contacted', 'done' => 'won', 'paused' => 'qualified', 'cancelled' => 'lost'][$r['status']] ?? 'new' ?>"><?= e(Campaigns::STATUSES[$r['status']] ?? $r['status']) ?></span></td>
    <td class="nowrap"><?= (int) $r['sent'] ?> / <?= (int) $r['total'] ?></td>
    <td class="small nowrap"><?= e(date('j M Y', strtotime($r['created_at'] . ' UTC'))) ?><?= $r['author'] ? '<br>' . e($r['author']) : '' ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
</div>

<div class="admin-grid">
  <?php if (Auth::can('settings', $user)): $l = $limits; ?>
  <section class="card span-all">
    <h2>Sending settings</h2>
    <form method="post" class="form-grid cols-2">
      <?= csrf_field() ?><input type="hidden" name="action" value="limits">
      <label class="field"><span>Emails per hour</span><input class="input" type="number" name="camp_per_hour" min="1" max="200" value="<?= $l['per_hour'] ?>"></label>
      <label class="field"><span>Emails per day</span><input class="input" type="number" name="camp_per_day" min="1" max="2000" value="<?= $l['per_day'] ?>"></label>
      <label class="field"><span>Seconds between emails (at least)</span><input class="input" type="number" name="camp_gap_seconds" min="5" max="600" value="<?= $l['gap'] ?>"></label>
      <label class="field"><span>Days before the same person can be emailed again</span><input class="input" type="number" name="camp_rest_days" min="0" max="30" value="<?= $l['rest_days'] ?>"></label>
      <label class="field"><span>Send from (hour)</span><input class="input" type="number" name="camp_window_start" min="0" max="23" value="<?= $l['start'] ?>"></label>
      <label class="field"><span>Send until (hour)</span><input class="input" type="number" name="camp_window_end" min="1" max="24" value="<?= $l['end'] ?>"></label>
      <label class="field"><span>Time zone</span><select class="select" name="camp_timezone"><?php foreach (Campaigns::TIMEZONES as $tz): ?><option value="<?= e($tz) ?>"<?= $l['tz'] === $tz ? ' selected' : '' ?>><?= e($tz) ?></option><?php endforeach; ?></select></label>
      <label class="check"><input type="checkbox" name="camp_weekdays_only" value="1"<?= $l['weekdays'] ? ' checked' : '' ?>> <span>Weekdays only</span></label>
      <?php $cm = Mailer::settings('campaign'); ?>
      <div class="span-2 camp-box">
        <p><strong>Campaign mailbox:</strong> <?= Mailer::hasOwn('campaign') ? 'its own mailbox' : 'uses the website mailbox' ?>, from <?= e($cm['from_email'] ?: 'not set') ?><?= ($cm['reply_to'] ?? '') !== '' ? ', replies to ' . e($cm['reply_to']) : '' ?>.</p>
        <p class="small muted">Sender, reply-to and mail server for campaigns are set in Email settings, with a test button. A separate mailbox is recommended for cold email.</p>
        <?php if (Auth::can('settings')): ?><a class="btn btn-sm btn-outline" href="<?= e(url('admin/email?box=campaign#box-campaign')) ?>">Campaign mailbox settings</a><?php endif; ?>
      </div>
      <div class="span-2"><button class="btn btn-sm" type="submit">Save sending settings</button></div>
    </form>
  </section>
  <?php endif; ?>
</div>
