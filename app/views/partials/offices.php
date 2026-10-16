<?php /** @var array $offices */ ?>
<div class="offices">
  <?php foreach ($offices as $o): ?>
  <article class="office-card">
    <div class="office-head">
      <span class="mega-icon"><?= icon('pin') ?></span>
      <div>
        <h3><?= e($o['country']) ?></h3>
        <?php if ($o['city'] !== ''): ?><p class="office-city"><?= e($o['city']) ?></p><?php endif; ?>
      </div>
      <?php if ($o['is_head_office']): ?><span class="office-tag">Head office</span><?php endif; ?>
    </div>
    <ul class="contact-lines">
      <?php if ($o['address'] !== ''): ?><li><?= icon('pin') ?><span><?= nl2br(e($o['address'])) ?></span></li><?php endif; ?>
      <?php if ($o['phone'] !== ''): ?><li><?= icon('call') ?><a href="<?= e(phone_href($o['phone'])) ?>"><?= e($o['phone']) ?></a></li><?php endif; ?>
      <?php if ($o['email'] !== ''): ?><li><?= icon('mail') ?><a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a></li><?php endif; ?>
      <?php if ($o['address'] === '' && $o['phone'] === '' && $o['email'] === ''): ?><li><?= icon('chat') ?><span>Serving clients across <?= e($o['country']) ?></span></li><?php endif; ?>
    </ul>
    <?php if ($o['map'] !== ''): ?><a class="link-arrow small" href="<?= e($o['map']) ?>" target="_blank" rel="noopener">Open in Google Maps <?= icon('external') ?></a><?php endif; ?>
  </article>
  <?php endforeach; ?>
</div>
