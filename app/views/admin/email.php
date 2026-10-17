<?php
/** @var array $errors @var ?array $posted @var string $openBox @var array $logData @var array $user */
$cfgMail = config('mail', []);
$gv = fn (string $k) => $posted !== null && ($posted['action'] ?? '') === 'general' ? (string) ($posted[$k] ?? '') : setting($k);
?>
<section class="card mbox-intro">
  <h2>Your mailboxes</h2>
  <p class="muted">Each part of the business has its own mailbox. Open one to set where its emails are <strong>sent from</strong> and where <strong>replies and alerts arrive</strong>. The sentence under each name always shows what is saved. Click <strong>Send test</strong> after every change.</p>
</section>


<?php
$flowText = [
    'main' => 'Website emails go out from {from} through {server}. When visitors reply, it reaches {reply}. New enquiry alerts go to {alerts}.',
    'hr' => 'HR emails to applicants and the team go out from {from} through {server}. Their replies reach {reply}. Job applications and replies from employees go to {alerts}.',
    'clients' => 'Client portal emails go out from {from} through {server}. Client replies reach {reply}. New client messages (on projects without a manager) go to {alerts}.',
    'campaign' => 'Campaign emails go out from {from} through {server}. When someone replies to a campaign, it reaches {reply}.',
];
$mainCfg = Mailer::settings('main');
foreach (Mailer::BOXES as $key => $b):
    $p = $b['prefix'];
    $sk = fn (string $n) => $key === 'main' ? (['from_email' => 'mail_from_email', 'from_name' => 'mail_from_name', 'reply_to' => 'mail_reply_to'][$n] ?? $n) : $p . $n;
    $mine = $posted !== null && ($posted['box'] ?? '') === $key && ($posted['action'] ?? '') === 'box_save';
    $v = fn (string $n) => $mine ? (string) ($posted[$n] ?? '') : setting($sk($n));
    $own = $mine ? ($key === 'main' ? ($posted['mail_source'] ?? '') === 'custom' : !empty($posted['own'])) : Mailer::hasOwn($key);
    $err = fn (string $n) => $errors["$key.$n"] ?? null;
    $open = $openBox === $key || $mine;
    $hasPass = setting($sk('smtp_password')) !== '';
    $alertKey = Mailer::ALERT_KEYS[$key] ?? null;
    $alertsVal = $mine ? (string) ($posted['alerts'] ?? '') : ($alertKey ? setting($alertKey) : '');
    $cfg = Mailer::settings($key);
    $saved = [
        '{from}' => trim($cfg['from_name'] . ' <' . ($cfg['from_email'] ?: 'not set') . '>'),
        '{server}' => $cfg['host'] !== '' ? $cfg['host'] . ' (' . ($cfg['encryption'] !== '' ? strtoupper($cfg['encryption']) : 'no encryption') . ', port ' . $cfg['port'] . ')' : 'PHP mail() on this server',
        '{reply}' => ($cfg['reply_to'] ?? '') !== '' ? $cfg['reply_to'] : ($cfg['from_email'] ?: 'the sender'),
        '{alerts}' => implode(', ', Mailer::alertList($key)) ?: 'nobody yet',
    ];
    $test = json_decode(setting(Mailer::testKey($key)) ?: 'null', true);
?>
<details class="card mbox" id="box-<?= e($key) ?>"<?= $open ? ' open' : '' ?>>
  <summary class="mbox-head">
    <span class="mbox-title">
      <strong><?= e($b['label']) ?></strong>
      <span class="mbox-flow small"><?= e(strtr($flowText[$key], $saved)) ?></span>
    </span>
    <span class="mbox-badges">
      <span class="status <?= Mailer::hasOwn($key) ? 'status-won' : '' ?>"><?= $key === 'main' ? (Mailer::hasOwn($key) ? 'Configured here' : 'From app/config.php') : (Mailer::hasOwn($key) ? 'Own mailbox' : 'Uses website mailbox') ?></span>
      <?php if ($test): ?><span class="status status-<?= $test['ok'] ? 'won' : 'lost' ?>" title="<?= e($test['ok'] ? 'Sent to ' . $test['to'] : $test['error']) ?>"><?= $test['ok'] ? 'Test passed' : 'Test failed' ?> <?= e(date('j M, H:i', strtotime($test['at'] . ' UTC'))) ?></span><?php else: ?><span class="status">Not tested</span><?php endif; ?>
    </span>
  </summary>

  <form method="post" class="mbox-form" autocomplete="off" data-flow-form>
    <?= csrf_field() ?><input type="hidden" name="action" value="box_save"><input type="hidden" name="box" value="<?= e($key) ?>">
    <p class="small muted mbox-about"><?= e($b['about']) ?></p>
    <p class="mbox-live" data-flow="<?= e($flowText[$key]) ?>" data-fallback-from="<?= e($mainCfg['from_email']) ?>" data-fallback-server="<?= e($mainCfg['host'] !== '' ? $mainCfg['host'] . ' (' . strtoupper($mainCfg['encryption'] ?: 'none') . ', port ' . $mainCfg['port'] . ')' : 'PHP mail()') ?>" aria-live="polite"><?= e(strtr($flowText[$key], $saved)) ?></p>

    <fieldset class="mbox-part">
      <legend><span class="mbox-step">1</span> Sending: the mailbox emails go out from</legend>
      <?php if ($key === 'main'): ?>
      <div class="radio-cards">
        <label class="radio-card"><input type="radio" name="mail_source" value="custom" data-own<?= $own ? ' checked' : '' ?>><span><strong>Use the settings below</strong><span class="small muted">Recommended. Change the mail server here without editing files.</span></span></label>
        <label class="radio-card"><input type="radio" name="mail_source" value="config"<?= !$own ? ' checked' : '' ?>><span><strong>Use app/config.php</strong><span class="small muted">Now: <?= ($cfgMail['host'] ?? '') !== '' ? e($cfgMail['host'] . ':' . ($cfgMail['port'] ?? '')) : 'no SMTP server' ?>, from <?= e(($cfgMail['from_email'] ?? '') ?: 'not set') ?></span></span></label>
      </div>
      <?php else: ?>
      <label class="check"><input type="checkbox" name="own" value="1" data-own<?= $own ? ' checked' : '' ?>> <span><strong>Use its own mailbox</strong><br><span class="small muted">Untick to send through the website mailbox (<?= e($mainCfg['from_email'] ?: 'not set yet') ?>). The sender name and reply address below still apply.</span></span></label>
      <?php endif; ?>
      <div class="form-grid cols-2">
        <div class="field"><label for="m-<?= $key ?>-from"><span>Send from (email)</span></label><input class="input" id="m-<?= $key ?>-from" type="email" name="from_email" value="<?= e($v('from_email')) ?>" placeholder="<?= e(['main' => 'no-reply', 'hr' => 'hr', 'clients' => 'projects', 'campaign' => 'hello'][$key]) ?>@yourdomain.com" data-f="from"<?= $err('from_email') ? ' aria-invalid="true"' : '' ?>><?php if ($err('from_email')): ?><span class="field-error"><?= e($err('from_email')) ?></span><?php endif; ?></div>
        <div class="field"><label for="m-<?= $key ?>-name"><span>Sender name</span></label><input class="input" id="m-<?= $key ?>-name" name="from_name" value="<?= e($v('from_name')) ?>" maxlength="80" placeholder="<?= e(setting('site_name', 'AivraSol') . ['main' => '', 'hr' => ' HR', 'clients' => ' Projects', 'campaign' => ''][$key]) ?>" data-f="name"></div>
      </div>
      <div class="mbox-server" data-server>
        <p class="small muted">Mail server. In cPanel: Email Accounts, then "Connect Devices" next to the mailbox.</p>
        <div class="form-grid cols-2">
          <div class="field"><label for="m-<?= $key ?>-host"><span>SMTP server</span></label><input class="input" id="m-<?= $key ?>-host" name="smtp_host" value="<?= e($v('smtp_host')) ?>" placeholder="mail.yourdomain.com" data-f="host"<?= $err('smtp_host') ? ' aria-invalid="true"' : '' ?>><?php if ($err('smtp_host')): ?><span class="field-error"><?= e($err('smtp_host')) ?></span><?php endif; ?></div>
          <div class="field mbox-sec">
            <div><label for="m-<?= $key ?>-enc"><span>Security</span></label>
              <select class="select" id="m-<?= $key ?>-enc" name="smtp_encryption" data-f="enc"><?php foreach (['ssl' => 'SSL (recommended)', 'tls' => 'TLS / STARTTLS', 'none' => 'None (not secure)'] as $ek => $el): ?><option value="<?= e($ek) ?>"<?= ($v('smtp_encryption') ?: 'ssl') === $ek ? ' selected' : '' ?>><?= e($el) ?></option><?php endforeach; ?></select></div>
            <div><label for="m-<?= $key ?>-port"><span>Port</span></label><input class="input" id="m-<?= $key ?>-port" type="number" name="smtp_port" value="<?= e($v('smtp_port') ?: '465') ?>" min="1" max="65535" data-f="port"></div>
          </div>
          <div class="field"><label for="m-<?= $key ?>-user"><span>Username</span></label><input class="input" id="m-<?= $key ?>-user" name="smtp_username" value="<?= e($v('smtp_username')) ?>" autocomplete="off" placeholder="Same as Send from"><span class="small muted">Usually the full email address. Left empty, the Send from address is used.</span></div>
          <div class="field"><label for="m-<?= $key ?>-pass"><span>Password</span><?php if ($hasPass): ?> <span class="status status-won">Saved</span><?php endif; ?></label><input class="input" id="m-<?= $key ?>-pass" type="password" name="smtp_password" autocomplete="new-password" placeholder="<?= $hasPass ? 'Leave empty to keep the saved password' : 'Password of this mailbox' ?>"><span class="small muted">Stored encrypted.</span><?php if ($hasPass): ?><label class="check"><input type="checkbox" name="smtp_password_clear" value="1"> <span>Remove the saved password</span></label><?php endif; ?></div>
        </div>
      </div>
    </fieldset>

    <fieldset class="mbox-part">
      <legend><span class="mbox-step">2</span> Receiving: where answers and alerts arrive</legend>
      <div class="form-grid cols-2">
        <div class="field<?= $alertKey ? '' : ' span-2' ?>"><label for="m-<?= $key ?>-reply"><span>Replies go to</span></label><input class="input" id="m-<?= $key ?>-reply" type="email" name="reply_to" value="<?= e($v('reply_to')) ?>" placeholder="Empty: replies go to the Send from address" data-f="reply"<?= $err('reply_to') ? ' aria-invalid="true"' : '' ?>><span class="small muted">When someone presses Reply on one of these emails, the answer lands here.</span><?php if ($err('reply_to')): ?><span class="field-error"><?= e($err('reply_to')) ?></span><?php endif; ?></div>
        <?php if ($alertKey): ?>
        <div class="field"><label for="m-<?= $key ?>-alerts"><span><?= e(['main' => 'Enquiry alerts go to', 'hr' => 'Applications and employee replies go to', 'clients' => 'New client messages go to'][$key]) ?></span></label><textarea class="textarea" id="m-<?= $key ?>-alerts" name="alerts" rows="2" data-f="alerts"<?= $err('alerts') ? ' aria-invalid="true"' : '' ?>><?= e($alertsVal) ?></textarea><span class="small muted">One or more addresses, one per line. <?= $key === 'clients' ? 'Projects with a manager notify the manager instead.' : '' ?></span><?php if ($err('alerts')): ?><span class="field-error"><?= e($err('alerts')) ?></span><?php endif; ?></div>
        <?php endif; ?>
      </div>
    </fieldset>
    <div class="mbox-save"><button class="btn" type="submit">Save <?= e(strtolower($b['label'])) ?> mailbox</button></div>
  </form>
  <div class="mbox-tools">
    <form method="post" class="filters-bar">
      <?= csrf_field() ?><input type="hidden" name="action" value="box_test"><input type="hidden" name="box" value="<?= e($key) ?>">
      <label class="sr-only" for="m-<?= $key ?>-test">Send a test to</label>
      <input class="input" id="m-<?= $key ?>-test" type="email" name="test_to" value="<?= e($user['email'] ?? '') ?>" required>
      <button class="btn btn-sm btn-outline" type="submit"><?= icon('mail') ?>Send test</button>
    </form>
    <?php if ($test && !$test['ok']): ?><p class="small mbox-error"><?= e($test['error']) ?></p><?php endif; ?>
    <form method="post" data-confirm="<?= e($key === 'main' ? 'Reset the website mailbox? Emails will use the settings in app/config.php again.' : 'Reset this mailbox? Its sending settings are cleared and it sends through the website mailbox.') ?>">
      <?= csrf_field() ?><input type="hidden" name="action" value="box_reset"><input type="hidden" name="box" value="<?= e($key) ?>">
      <button class="btn btn-sm btn-outline" type="submit"><?= icon('close') ?>Reset to <?= $key === 'main' ? 'app/config.php' : 'website mailbox' ?></button>
    </form>
  </div>
</details>
<?php endforeach; ?>

<?php if (array_filter(array_keys($errors), fn ($k) => !str_contains((string) $k, '.'))): ?><p class="form-status is-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>

<form method="post" class="settings-form">
  <?= csrf_field() ?><input type="hidden" name="action" value="general">
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
  <div class="sticky-save"><button class="btn" type="submit">Save automatic replies</button></div>
</form>

<section class="card" id="email-log">
  <div class="card-head"><h2>Sent emails</h2></div>
  <form class="filters-bar" method="get" action="<?= e(url('admin/email')) ?>#email-log">
    <label class="sr-only" for="lg-q">Search</label>
    <input class="input" id="lg-q" type="search" name="log_q" value="<?= e($logData['f']['q']) ?>" placeholder="Search address or subject">
    <label class="sr-only" for="lg-box">Mailbox</label>
    <select class="select" id="lg-box" name="log_box"><option value="">All mailboxes</option><?php foreach (Mailer::BOXES as $k => $bx): ?><option value="<?= e($k) ?>"<?= $logData['f']['box'] === $k ? ' selected' : '' ?>><?= e($bx['label']) ?></option><?php endforeach; ?></select>
    <label class="sr-only" for="lg-st">Status</label>
    <select class="select" id="lg-st" name="log_status"><option value="">Sent and failed</option><option value="sent"<?= $logData['f']['status'] === 'sent' ? ' selected' : '' ?>>Sent</option><option value="failed"<?= $logData['f']['status'] === 'failed' ? ' selected' : '' ?>>Failed</option></select>
    <button class="btn btn-sm" type="submit">Filter</button>
  </form>
  <?php if (!$logData['rows']): ?>
  <p class="muted"><?= array_filter($logData['f']) ? 'No emails match these filters.' : 'No emails sent yet.' ?></p>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>When (UTC)</th><th>Mailbox</th><th>Type</th><th>To</th><th>Subject</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($logData['rows'] as $l): ?>
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
  <?= admin_pager($logData['total'], $logData['per'], $logData['page'], 'log_page', 'email-log') ?>
</section>
