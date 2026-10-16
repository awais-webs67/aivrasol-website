<?php /** @var ?array $contact @var bool $done */ $site = setting('site_name', 'AivraSol'); ?>
<section class="section">
  <div class="container verify-wrap">
    <?php if (!$contact): ?>
    <h1 class="display-2">Link not found</h1>
    <p class="lead">This unsubscribe link is not valid. If you keep getting emails from us, reply to one of them with "unsubscribe" and we will remove you by hand.</p>
    <?php elseif ($done): ?>
    <div class="verify-badge is-ok"><?= icon('check') ?><span>Unsubscribed</span></div>
    <h1 class="display-2">You will not get these emails again</h1>
    <p class="lead"><?= e($contact['email']) ?> has been removed from <?= e($site) ?> email campaigns. Sorry for the bother.</p>
    <?php else: ?>
    <h1 class="display-2">Unsubscribe from <?= e($site) ?> emails?</h1>
    <p class="lead">We will stop sending campaign emails to <strong><?= e($contact['email']) ?></strong>.</p>
    <form method="post"><button class="btn" type="submit">Yes, unsubscribe me</button></form>
    <?php endif; ?>
  </div>
</section>
