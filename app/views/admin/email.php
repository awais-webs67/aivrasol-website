<?php
/** @var array $errors @var ?array $posted @var string $openBox @var array $log @var array $user */
$leadList = Mailer::addressList(setting('notify_emails'));
$hrList = Mailer::addressList(setting('notify_emails_hr'));
$cfgMail = config('mail', []);
$gv = fn (string $k) => $posted !== null && ($posted['action'] ?? '') === 'general' ? (string) ($posted[$k] ?? '') : setting($k);
?>
<section class="card">
  <h2>Mailboxes at a glance</h2>
  <p class="small muted">Each part of the business sends from its own mailbox. A mailbox without its own server sends through the website mailbox. Replies go to the reply-to address.</p>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Mailbox</th><th>Sends from</th><th>Through</th><th>Replies go to</th></tr></thead>
    <tbody>
    <?php foreach (Mailer::BOXES as $key => $b): $c = Mailer::settings($key); $own = Mailer::hasOwn($key); ?>
    <tr>
      <td><a href="#box-<?= e($key) ?>"><?= e($b['label']) ?></a><br><span class="status <?= $own ? 'status-won' : '' ?>"><?= $key === 'main' ? ($own ? 'Settings on this page' : 'From app/config.php') : ($own ? 'Own mailbox' : 'Uses website mailbox') ?></span></td>
      <td class="small"><?= e($c['from_name']) ?><br><?= e($c['from_email'] ?: 'Not set') ?></td>
      <td class="small"><?= $c['host'] !== '' ? e($c['host'] . ':' . $c['port']) : 'PHP mail()' ?></td>
      <td class="small"><?= e(($c['reply_to'] ?? '') ?: 'Same as sender') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>

<?php foreach (Mailer::BOXES as $key => $b):
    $p = $b['prefix'];
    $sk = fn (string $n) => $key === 'main' ? (['from_email' => 'mail_from_email', 'from_name' => 'mail_from_name', 'reply_to' => 'mail_reply_to'][$n] ?? $n) : $p . $n;
    $mine = $posted !== null && ($posted['box'] ?? '') === $key && ($posted['action'] ?? '') === 'box_save';
    $v = fn (string $n) => $mine ? (string) ($posted[$n] ?? '') : setting($sk($n));
    $own = $mine ? ($key === 'main' ? ($posted['mail_source'] ?? '') === 'custom' : !empty($posted['own'])) : Mailer::hasOwn($key);
    $err = fn (string $n) => $errors["$key.$n"] ?? null;
    $open = $openBox === $key || $mine;
    $hasPass = setting($sk('smtp_password')) !== '';
?>
<details class="card mbox" id="box-<?= e($key) ?>"<?= $open ? ' open' : '' ?>>
  <summary class="mbox-head">
    <span><strong><?= e($b['label']) ?></strong><span class="small muted"><?= e($b['about']) ?></span></span>
    <span class="status <?= Mailer::hasOwn($key) ? 'status-won' : '' ?>"><?= $key === 'main' ? (Mailer::hasOwn($key) ? 'Configured here' : 'app/config.php') : (Mailer::hasOwn($key) ? 'Own mailbox' : 'Uses website mailbox') ?></span>
  </summary>
  <form method="post" class="form-grid cols-2 mbox-form" autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="action" value="box_save"><input type="hidden" name="box" value="<?= e($key) ?>">
    <?php if ($key === 'main'): ?>
    <div class="span-2 radio-cards">
      <label class="radio-card"><input type="radio" name="mail_source" value="config"<?= !$own ? ' checked' : '' ?>>
        <span><strong>Use app/config.php</strong><span class="small muted">Currently: <?= ($cfgMail['host'] ?? '') !== '' ? e($cfgMail['host'] . ':' . ($cfgMail['port'] ?? '')) : 'no SMTP server' ?>, from <?= e(($cfgMail['from_email'] ?? '') ?: 'not set') ?></span></span></label>
      <label class="radio-card"><input type="radio" name="mail_source" value="custom"<?= $own ? ' checked' : '' ?>>
        <span><strong>Use the settings below</strong><span class="small muted">Change the mail server here without editing files</span></span></label>
    </div>
    <?php else: ?>
    <label class="check span-2"><input type="checkbox" name="own" value="1"<?= $own ? ' checked' : '' ?>> <span>Use its own mailbox<br><span class="small muted">Untick to send through the website mailbox. The sender name and reply-to below still apply.</span></span></label>
    <?php endif; ?>
    <div class="field"><label for="m-<?= $key ?>-from"><span>Send from (email)</span></label><input class="input" id="m-<?= $key ?>-from" type="email" name="from_email" value="<?= e($v('from_email')) ?>" placeholder="<?= e(['main' => 'no-reply@', 'hr' => 'hr@', 'clients' => 'projects@', 'campaign' => 'hello@'][$key]) ?>yourdomain.com"<?= $err('from_email') ? ' aria-invalid="true"' : '' ?>><?php if ($err('from_email')): ?><span class="field-error"><?= e($err('from_email')) ?></span><?php endif; ?></div>
    <div class="field"><label for="m-<?= $key ?>-name"><span>Sender name</span></label><input class="input" id="m-<?= $key ?>-name" name="from_name" value="<?= e($v('from_name')) ?>" maxlength="80" placeholder="<?= e(setting('site_name', 'AivraSol') . ['main' => '', 'hr' => ' HR', 'clients' => ' Projects', 'campaign' => ''][$key]) ?>"></div>
    <div class="field span-2"><label for="m-<?= $key ?>-reply"><span>Replies go to (reply-to)</span></label><input class="input" id="m-<?= $key ?>-reply" type="email" name="reply_to" value="<?= e($v('reply_to')) ?>" placeholder="<?= e(['main' => 'Leave empty to use the sender', 'hr' => setting('email_hr', 'hr@yourdomain.com'), 'clients' => setting('email_info', 'info@yourdomain.com'), 'campaign' => 'you@yourdomain.com'][$key]) ?>"<?= $err('reply_to') ? ' aria-invalid="true"' : '' ?>><span class="small muted">When someone presses Reply, their answer goes here.</span><?php if ($err('reply_to')): ?><span class="field-error"><?= e($err('reply_to')) ?></span><?php endif; ?></div>
    <div class="field"><label for="m-<?= $key ?>-host"><span>SMTP server</span></label><input class="input" id="m-<?= $key ?>-host" name="smtp_host" value="<?= e($v('smtp_host')) ?>" placeholder="mail.yourdomain.com"<?= $err('smtp_host') ? ' aria-invalid="true"' : '' ?>><span class="small muted">cPanel: Email Accounts, Connect Devices.</span><?php if ($err('smtp_host')): ?><span class="field-error"><?= e($err('smtp_host')) ?></span><?php endif; ?></div>
    <div class="field"><label for="m-<?= $key ?>-port"><span>Port</span></label><input class="input" id="m-<?= $key ?>-port" type="number" name="smtp_port" value="<?= e($v('smtp_port') ?: '465') ?>" min="1" max="65535"></div>
    <div class="field"><label for="m-<?= $key ?>-enc"><span>Encryption</span></label>
      <select class="select" id="m-<?= $key ?>-enc" name="smtp_encryption"><?php foreach (['ssl' => 'SSL (port 465)', 'tls' => 'TLS (port 587)', 'none' => 'None (not recommended)'] as $ek => $el): ?><option value="<?= e($ek) ?>"<?= ($v('smtp_encryption') ?: 'ssl') === $ek ? ' selected' : '' ?>><?= e($el) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="m-<?= $key ?>-user"><span>Username</span></label><input class="input" id="m-<?= $key ?>-user" name="smtp_username" value="<?= e($v('smtp_username')) ?>" autocomplete="off" placeholder="Usually the full email address"></div>
    <div class="field span-2"><label for="m-<?= $key ?>-pass"><span>Password</span></label><input class="input" id="m-<?= $key ?>-pass" type="password" name="smtp_password" autocomplete="new-password" placeholder="<?= $hasPass ? 'Saved. Leave empty to keep it' : 'Mailbox password' ?>"><span class="small muted">Stored encrypted.</span><?php if ($hasPass): ?><label class="check"><input type="checkbox" name="smtp_password_clear" value="1"> <span>Remove the saved password</span></label><?php endif; ?></div>
    <div class="span-2"><button class="btn" type="submit">Save <?= e(strtolower($b['label'])) ?> mailbox</button></div>
  </form>
  <div class="mbox-tools">
    <form method="post" class="filters-bar">
      <?= csrf_field() ?><input type="hidden" name="action" value="box_test"><input type="hidden" name="box" value="<?= e($key) ?>">
      <label class="sr-only" for="m-<?= $key ?>-test">Send a test to</label>
      <input class="input" id="m-<?= $key ?>-test" type="email" name="test_to" value="<?= e($user['email'] ?? '') ?>" required>
      <button class="btn btn-sm btn-outline" type="submit"><?= icon('mail') ?>Send test</button>
    </form>
    <form method="post" data-confirm="<?= e($key === 'main' ? 'Reset the website mailbox? Emails will use the settings in app/config.php again.' : 'Reset this mailbox? Its settings are cleared and it sends through the website mailbox.') ?>">
      <?= csrf_field() ?><input type="hidden" name="action" value="box_reset"><input type="hidden" name="box" value="<?= e($key) ?>">
      <button class="btn btn-sm btn-outline" type="submit"><?= icon('close') ?>Reset to <?= $key === 'main' ? 'app/config.php' : 'website mailbox' ?></button>
    </form>
  </div>
</details>
<?php endforeach; ?>

<?php if (array_filter(array_keys($errors), fn ($k) => !str_contains((string) $k, '.'))): ?><p class="form-status is-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>

<form method="post" class="settings-form">
  <?= csrf_field() ?><input type="hidden" name="action" value="general">
  <fieldset class="card form-grid cols-2">
    <legend>Who receives website emails</legend>
    <div class="field">
      <label for="e-leads"><span>Enquiries (contact, consultation, quote, hire)</span></label>
      <textarea class="textarea" id="e-leads" name="notify_emails" rows="4"<?= isset($errors['notify_emails']) ? ' aria-invalid="true"' : '' ?>><?= e($gv('notify_emails')) ?></textarea>
      <span class="small muted">One address per line. Now: <?= $leadList ? e(implode(', ', $leadList)) : 'none' ?></span>
      <?php if (isset($errors['notify_emails'])): ?><span class="field-error"><?= e($errors['notify_emails']) ?></span><?php endif; ?>
    </div>
    <div class="field">
      <label for="e-hr"><span>Job applications</span></label>
      <textarea class="textarea" id="e-hr" name="notify_emails_hr" rows="4"<?= isset($errors['notify_emails_hr']) ? ' aria-invalid="true"' : '' ?>><?= e($gv('notify_emails_hr')) ?></textarea>
      <span class="small muted">One address per line. Now: <?= $hrList ? e(implode(', ', $hrList)) : 'none' ?></span>
      <?php if (isset($errors['notify_emails_hr'])): ?><span class="field-error"><?= e($errors['notify_emails_hr']) ?></span><?php endif; ?>
    </div>
  </fieldset>

  <fieldset class="card">
    <legend>Automatic replies to visitors</legend>
    <label class="check"><input type="checkbox" name="autoreply_enabled" value="1"<?= ($posted !== null && ($posted['action'] ?? '') === 'general' ? isset($posted['autoreply_enabled']) : setting('autoreply_enabled', '1') === '1') ? ' checked' : '' ?>> <span>Send an automatic, branded reply after every form</span></label>
    <p class="small muted">You can use <code>{name}</code> (the visitor's first name), <code>{site_name}</code> and <code>{ref}</code> (the reference number, for example AIV-00042). Start a line with <code>- </code> to make a bullet list. Job application replies go out from the HR mailbox, the rest from the website mailbox.</p>
    <div class="reply-grid">
      <?php foreach (Emails::TYPES as $t => $label): ?>
      <details class="reply-item">
        <summary><strong><?= e($label) ?></strong><span class="small muted"><?= e(excerpt($gv("autoreply_{$t}_subject"), 70)) ?></span></summary>
        <label class="field"><span>Subject</span><input class="input" type="text" name="autoreply_<?= e($t) ?>_subject" value="<?= e($gv("autoreply_{$t}_subject")) ?>" maxlength="150"></label>
        <label class="field"><span>Message</span><textarea class="textarea" name="autoreply_<?= e($t) ?>_body" rows="8" maxlength="3000"><?= e($gv("autoreply_{$t}_body")) ?></textarea></label>
        <?php if (isset($errors["autoreply_$t"])): ?><span class="field-error"><?= e($errors["autoreply_$t"]) ?></span><?php endif; ?>
      </details>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <div class="sticky-save"><button class="btn" type="submit">Save recipients and replies</button></div>
</form>

<section class="card">
  <h2>Recent emails</h2>
  <?php if (!$log): ?>
  <p class="muted">No emails sent yet.</p>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>When (UTC)</th><th>Mailbox</th><th>Type</th><th>To</th><th>Subject</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($log as $l): ?>
    <tr>
      <td class="small nowrap"><?= e($l['created_at']) ?></td>
      <td class="small"><?= e(Mailer::BOXES[Mailer::boxFor($l['type'])]['label'] ?? '') ?></td>
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
