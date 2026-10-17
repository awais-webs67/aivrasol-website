<?php
/** @var array $errors @var ?array $posted @var array $effective @var array $log @var array $user */
$v = fn (string $k) => $posted !== null ? (string) ($posted[$k] ?? '') : setting($k);
$source = $posted !== null ? (($posted['mail_source'] ?? '') === 'custom' ? 'custom' : 'config') : (setting('mail_source') === 'custom' ? 'custom' : 'config');
$leadList = Mailer::addressList(setting('notify_emails'));
$hrList = Mailer::addressList(setting('notify_emails_hr'));
$cfgMail = config('mail', []);
$hasSavedPassword = setting('smtp_password') !== '';
?>
<section class="card">
  <h2>Where emails go right now</h2>
  <dl class="dl">
    <dt>New enquiries</dt><dd><?= $leadList ? e(implode(', ', $leadList)) : '<span class="status status-new">No address set</span>' ?></dd>
    <dt>Job applications</dt><dd><?= $hrList ? e(implode(', ', $hrList)) : '<span class="status status-new">No address set</span>' ?></dd>
    <dt>Sent from</dt><dd><?= e($effective['from_email'] ?: 'Not set') ?></dd>
    <dt>Sent through</dt><dd><?= $effective['host'] !== '' ? e($effective['host'] . ':' . $effective['port']) : 'PHP mail() (no SMTP server set)' ?> · settings from <strong><?= $effective['source'] === 'admin' ? 'this page' : 'app/config.php' ?></strong></dd>
    <dt>Automatic replies</dt><dd><?= setting('autoreply_enabled', '1') === '1' ? 'On' : 'Off' ?></dd>
  </dl>
</section>

<?php if ($errors): ?><p class="form-status is-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>

<form method="post" class="settings-form" autocomplete="off">
  <?= csrf_field() ?>
  <fieldset class="card form-grid cols-2">
    <legend>Who receives website emails</legend>
    <div class="field">
      <label for="e-leads"><span>Enquiries (contact, consultation, quote, hire)</span></label>
      <textarea class="textarea" id="e-leads" name="notify_emails" rows="4"<?= isset($errors['notify_emails']) ? ' aria-invalid="true"' : '' ?>><?= e($v('notify_emails')) ?></textarea>
      <span class="small muted">One address per line. Everyone listed gets their own copy.</span>
      <?php if (isset($errors['notify_emails'])): ?><span class="field-error"><?= e($errors['notify_emails']) ?></span><?php endif; ?>
    </div>
    <div class="field">
      <label for="e-hr"><span>Job applications</span></label>
      <textarea class="textarea" id="e-hr" name="notify_emails_hr" rows="4"<?= isset($errors['notify_emails_hr']) ? ' aria-invalid="true"' : '' ?>><?= e($v('notify_emails_hr')) ?></textarea>
      <span class="small muted">One address per line.</span>
      <?php if (isset($errors['notify_emails_hr'])): ?><span class="field-error"><?= e($errors['notify_emails_hr']) ?></span><?php endif; ?>
    </div>
  </fieldset>

  <fieldset class="card form-grid cols-2">
    <legend>How emails are sent</legend>
    <div class="span-2 radio-cards">
      <label class="radio-card"><input type="radio" name="mail_source" value="config"<?= $source === 'config' ? ' checked' : '' ?>>
        <span><strong>Use app/config.php</strong><span class="small muted">Currently: <?= ($cfgMail['host'] ?? '') !== '' ? e($cfgMail['host'] . ':' . ($cfgMail['port'] ?? '')) : 'no SMTP server' ?>, from <?= e(($cfgMail['from_email'] ?? '') ?: 'not set') ?></span></span></label>
      <label class="radio-card"><input type="radio" name="mail_source" value="custom"<?= $source === 'custom' ? ' checked' : '' ?>>
        <span><strong>Use the settings below</strong><span class="small muted">Change the mail server here without editing files</span></span></label>
    </div>
    <div class="field"><label for="e-from"><span>Send from (email)</span></label><input class="input" id="e-from" type="email" name="mail_from_email" value="<?= e($v('mail_from_email')) ?>" placeholder="no-reply@aivrasol.com"<?= isset($errors['mail_from_email']) ? ' aria-invalid="true"' : '' ?>><?php if (isset($errors['mail_from_email'])): ?><span class="field-error"><?= e($errors['mail_from_email']) ?></span><?php endif; ?></div>
    <div class="field"><label for="e-fromname"><span>Send from (name)</span></label><input class="input" id="e-fromname" type="text" name="mail_from_name" value="<?= e($v('mail_from_name')) ?>" maxlength="80"></div>
    <div class="field"><label for="e-host"><span>SMTP server</span></label><input class="input" id="e-host" type="text" name="smtp_host" value="<?= e($v('smtp_host')) ?>" placeholder="mail.aivrasol.com"><span class="small muted">In cPanel: Email Accounts, then Connect Devices.</span></div>
    <div class="field"><label for="e-port"><span>Port</span></label><input class="input" id="e-port" type="number" name="smtp_port" value="<?= e($v('smtp_port') ?: '465') ?>" min="1" max="65535"></div>
    <div class="field"><label for="e-enc"><span>Encryption</span></label>
      <select class="select" id="e-enc" name="smtp_encryption"><?php foreach (['ssl' => 'SSL (port 465)', 'tls' => 'STARTTLS (port 587)', 'none' => 'None (not recommended)'] as $ek => $el): ?><option value="<?= e($ek) ?>"<?= $v('smtp_encryption') === $ek ? ' selected' : '' ?>><?= e($el) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="e-user"><span>Username</span></label><input class="input" id="e-user" type="text" name="smtp_username" value="<?= e($v('smtp_username')) ?>" autocomplete="off" placeholder="no-reply@aivrasol.com"></div>
    <div class="field"><label for="e-pass"><span>Password</span></label><input class="input" id="e-pass" type="password" name="smtp_password" value="" autocomplete="new-password" placeholder="<?= $hasSavedPassword ? 'Saved. Leave empty to keep it' : 'Email account password' ?>">
      <span class="small muted">Stored encrypted.</span>
      <?php if ($hasSavedPassword): ?><label class="check"><input type="checkbox" name="smtp_password_clear" value="1"> <span>Remove the saved password</span></label><?php endif; ?></div>
  </fieldset>

  <fieldset class="card">
    <legend>Automatic replies to visitors</legend>
    <label class="check"><input type="checkbox" name="autoreply_enabled" value="1"<?= ($posted !== null ? isset($posted['autoreply_enabled']) : setting('autoreply_enabled', '1') === '1') ? ' checked' : '' ?>> <span>Send an automatic, branded reply after every form</span></label>
    <p class="small muted">You can use <code>{name}</code> (the visitor's first name), <code>{site_name}</code> and <code>{ref}</code> (the reference number, for example AVS-00042). Start a line with <code>- </code> to make a bullet list. The visitor's own message is never copied into the reply, so the forms cannot be used to send spam.</p>
    <div class="reply-grid">
      <?php foreach (Emails::TYPES as $t => $label): ?>
      <div class="reply-item">
        <h3><?= e($label) ?></h3>
        <label class="field"><span>Subject</span><input class="input" type="text" name="autoreply_<?= e($t) ?>_subject" value="<?= e($v("autoreply_{$t}_subject")) ?>" maxlength="150"></label>
        <label class="field"><span>Message</span><textarea class="textarea" name="autoreply_<?= e($t) ?>_body" rows="7" maxlength="3000"><?= e($v("autoreply_{$t}_body")) ?></textarea></label>
        <?php if (isset($errors["autoreply_$t"])): ?><span class="field-error"><?= e($errors["autoreply_$t"]) ?></span><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <div class="sticky-save"><button class="btn" type="submit">Save email settings</button></div>
</form>

<section class="card">
  <h2>Send a test email</h2>
  <p class="small muted">Save your settings first, then send a test to check that emails arrive.</p>
  <form method="post" class="filters-bar">
    <?= csrf_field() ?><input type="hidden" name="action" value="test">
    <label class="sr-only" for="e-test">Send test to</label>
    <input class="input" id="e-test" type="email" name="test_to" value="<?= e($user['email'] ?? '') ?>" required>
    <button class="btn btn-sm" type="submit"><?= icon('mail') ?>Send test email</button>
  </form>
</section>

<section class="card">
  <h2>Recent emails</h2>
  <?php if (!$log): ?>
  <p class="muted">No emails sent yet.</p>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>When (UTC)</th><th>Type</th><th>To</th><th>Subject</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($log as $l): ?>
    <tr>
      <td class="small nowrap"><?= e($l['created_at']) ?></td>
      <td class="small"><?= e(str_replace('_', ' ', $l['type'])) ?></td>
      <td class="small"><?= e(excerpt($l['recipients'], 60)) ?></td>
      <td class="small"><?= e(excerpt($l['subject'], 60)) ?></td>
      <td><span class="status status-<?= $l['status'] === 'sent' ? 'won' : 'new' ?>"><?= $l['status'] === 'sent' ? 'Sent' : 'Failed' ?></span><?php if ($l['error'] !== ''): ?><br><span class="small muted"><?= e(excerpt($l['error'], 140)) ?></span><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
