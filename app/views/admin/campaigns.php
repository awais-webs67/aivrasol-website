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
<p class="small muted">Right now: <?= $allowed > 0 ? 'ready to send.' : e($why) ?> Background sender: <?= $cronOk ? 'running (last run ' . (int) floor($cronAge / 60) . ' min ago).' : 'not set up yet. Click "How this works" for the 2-minute setup. Until then, emails send only while a campaign page is open.' ?><?php if (setting('camp_last_run') !== ''): ?> Last run: <?= e(setting('camp_last_run')) ?><?php endif; ?></p>
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
      <label class="field span-2"><span>Default reply-to email</span><input class="input" type="email" name="camp_reply_to" value="<?= e(setting('camp_reply_to')) ?>" placeholder="<?= e(setting('email_sales') ?: setting('email_info')) ?>"></label>
      <p class="span-2 small muted"><strong>Separate campaign mailbox (recommended for cold email).</strong> Leave the host empty to use the main mailbox from Email settings.</p>
      <label class="field"><span>From email</span><input class="input" type="email" name="camp_from_email" value="<?= e(setting('camp_from_email')) ?>" placeholder="awais@aivrasol.co"></label>
      <label class="field"><span>From name</span><input class="input" name="camp_from_name" value="<?= e(setting('camp_from_name')) ?>" maxlength="80"></label>
      <label class="field"><span>SMTP host</span><input class="input" name="camp_smtp_host" value="<?= e(setting('camp_smtp_host')) ?>" placeholder="mail.aivrasol.co"></label>
      <label class="field"><span>Port</span><input class="input" type="number" name="camp_smtp_port" value="<?= e(setting('camp_smtp_port', '465')) ?>"></label>
      <label class="field"><span>Encryption</span><select class="select" name="camp_smtp_encryption"><?php foreach (['ssl' => 'SSL (port 465)', 'tls' => 'TLS (port 587)', 'none' => 'None'] as $k => $v): ?><option value="<?= $k ?>"<?= setting('camp_smtp_encryption', 'ssl') === $k ? ' selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
      <label class="field"><span>Username</span><input class="input" name="camp_smtp_username" value="<?= e(setting('camp_smtp_username')) ?>" autocomplete="off"></label>
      <label class="field span-2"><span>Password <?= setting('camp_smtp_password') !== '' ? '(saved, leave empty to keep)' : '' ?></span><input class="input" type="password" name="camp_smtp_password" autocomplete="new-password"></label>
      <div class="span-2"><button class="btn btn-sm" type="submit">Save sending settings</button></div>
    </form>
  </section>
  <?php endif; ?>
</div>
