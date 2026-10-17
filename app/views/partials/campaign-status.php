<?php
/** Plain-language sending status: what happens now and why. @var ?array $c (campaign, optional) */
[$allowed, $why] = Campaigns::allowance();
$cronAge = setting('cron_last_seen') !== '' ? time() - strtotime(setting('cron_last_seen') . ' UTC') : null;
$cronOk = $cronAge !== null && $cronAge < 900;
$next = Campaigns::nextWindow();
$queued = (int) Database::value("SELECT COUNT(*) FROM campaign_recipients r JOIN campaigns k ON k.id = r.campaign_id WHERE r.status = 'queued' AND k.status = 'sending'" . (!empty($c) ? ' AND k.id = ' . (int) $c['id'] : ''));
$state = $queued === 0 ? 'idle' : ($allowed > 0 ? 'go' : 'wait');
?>
<section class="send-status is-<?= $state ?>" aria-live="polite">
  <div class="ss-head">
    <span class="ss-dot" aria-hidden="true"></span>
    <strong><?= $state === 'idle' ? 'Nothing waiting to send' : ($state === 'go' ? 'Sending is allowed right now' : 'Waiting: not sending right now') ?></strong>
  </div>
  <ul class="ss-list">
    <?php if ($state !== 'idle'): ?><li><?= $queued ?> email<?= $queued === 1 ? '' : 's' ?> waiting<?= !empty($c) ? ' in this campaign' : '' ?>.</li><?php endif; ?>
    <?php if ($state === 'wait'): ?><li><strong>Why:</strong> <?= e(trim(preg_replace('/\s*Next window:.*$/', '', $why) ?? $why)) ?></li><?php endif; ?>
    <?php if ($state === 'idle' && empty($c)): ?><li>Start a campaign to queue its emails. Draft campaigns send nothing.</li><?php endif; ?>
    <li><strong>Background sender:</strong> <?= $cronOk ? 'running, last check ' . (int) floor($cronAge / 60) . ' min ago. Emails go out on their own.' : (is_local_site() ? 'not available on a test computer (XAMPP has no cron). Emails go out only while a sending campaign page stays open (about one a minute), or when you click a send button.' : 'not set up yet. Until you add the cron job (see How this works), emails go out only while a campaign page is open.') ?></li>
    <?php if ($next !== null && $state !== 'go'): ?><li><strong>Next sending window:</strong> <?= e($next) ?>. Change the days and hours in Sending settings.</li><?php endif; ?>
  </ul>
  <?php if (!empty($c) && $c['status'] === 'sending' && $queued > 0): ?>
  <div class="btn-group">
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="send_now"><button class="btn btn-sm" type="submit"><?= icon('mail') ?>Send 1 now (ignore days and hours)</button></form>
    <?php if ($state === 'go'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="send_batch"><button class="btn btn-sm btn-outline" type="submit">Send next 3 now</button></form><?php endif; ?>
  </div>
  <p class="small muted">Manual sends still keep your hourly and daily limits and the gap between emails.</p>
  <?php endif; ?>
</section>
