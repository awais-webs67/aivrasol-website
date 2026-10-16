<?php /** @var array $offices */ ?>
<div class="offices">
  <?php foreach ($offices as $o): ?>
  <article class="office-card">
    <div class="office-head">
      <span class="mega-icon"><?= icon('pin') ?></span>
      <div>
        <h3><?= e($o['country']) ?></h3>
        <?php if ($o['city'] !== '' || $o['is_head_office']): ?><p class="office-city"><?= e($o['city']) ?><?php if ($o['is_head_office']): ?> <span class="office-tag">Head office</span><?php endif; ?></p><?php endif; ?>
      </div>
    </div>
    <ul class="contact-lines">
      <?php if ($o['address'] !== ''): ?><li><?= icon('pin') ?><span><?= nl2br(e($o['address'])) ?></span></li><?php else: ?><li><?= icon('chat') ?><span>Serving clients across <?= e($o['country']) ?></span></li><?php endif; ?>
    </ul>
    <?php $wa = (string) ($o['whatsapp'] ?? ''); if ($o['phone'] !== '' || $wa !== '' || $o['email'] !== '' || $o['map'] !== ''): ?>
    <div class="office-actions">
      <?php if ($o['phone'] !== ''): ?><a class="oa oa-call" href="<?= e(phone_href($o['phone'])) ?>" title="Call <?= e($o['phone']) ?>" aria-label="Call the <?= e($o['country']) ?> office on <?= e($o['phone']) ?>"><?= icon('call') ?><span>Call</span></a><?php endif; ?>
      <?php if ($wa !== ''): ?><a class="oa oa-wa" href="<?= e(whatsapp_href($wa, setting('whatsapp_message'))) ?>" target="_blank" rel="noopener" title="WhatsApp <?= e($wa) ?>" aria-label="WhatsApp the <?= e($o['country']) ?> office on <?= e($wa) ?>"><?= icon('whatsapp') ?><span>WhatsApp</span></a><?php endif; ?>
      <?php if ($o['email'] !== ''): ?><a class="oa oa-mail" href="mailto:<?= e($o['email']) ?>" title="<?= e($o['email']) ?>" aria-label="Email the <?= e($o['country']) ?> office at <?= e($o['email']) ?>"><?= icon('mail') ?><span>Email</span></a><?php endif; ?>
      <?php if ($o['map'] !== ''): ?><a class="oa oa-map" href="<?= e($o['map']) ?>" target="_blank" rel="noopener" title="Open in Google Maps" aria-label="Open the <?= e($o['country']) ?> office in Google Maps"><?= icon('pin') ?><span>Map</span></a><?php endif; ?>
    </div>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>
</div>
