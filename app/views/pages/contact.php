<?php
$phone = setting('phone');
$whatsapp = setting('whatsapp');
$offices = Content::offices();
?>
<section class="page-hero">
  <div class="container page-hero-inner single">
    <div>
      <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><li aria-current="page">Contact us</li></ol>
      <h1 class="display-1">We are the right team for your project</h1>
      <span class="bar"></span>
      <p class="lead">Whether it is a company website, an online store, a mobile app or a complex platform, we are happy to answer your questions. Call, chat or send us a message.</p>
    </div>
  </div>
</section>

<section class="section-tight" aria-labelledby="ways-title">
  <h2 class="sr-only" id="ways-title">Ways to reach us</h2>
  <div class="container cards-4">
    <article class="info-card"><span class="mega-icon"><?= icon('mail') ?></span><h3>Email</h3><a href="mailto:<?= e(setting('email_info')) ?>"><?= e(setting('email_info')) ?></a></article>
    <?php if ($phone !== ''): ?>
    <article class="info-card"><span class="mega-icon"><?= icon('call') ?></span><h3>Phone</h3><a href="<?= e(phone_href($phone)) ?>"><?= e($phone) ?></a></article>
    <?php endif; ?>
    <?php if ($whatsapp !== ''): ?>
    <article class="info-card"><span class="mega-icon"><?= icon('whatsapp') ?></span><h3>WhatsApp</h3><a href="<?= e(whatsapp_href($whatsapp)) ?>" target="_blank" rel="noopener"><?= e($whatsapp) ?></a></article>
    <?php endif; ?>
    <article class="info-card"><span class="mega-icon"><?= icon('briefcase') ?></span><h3>Careers</h3><a href="mailto:<?= e(setting('email_hr')) ?>"><?= e(setting('email_hr')) ?></a></article>
    <?php if (setting('office_hours') !== ''): ?>
    <article class="info-card"><span class="mega-icon"><?= icon('clock') ?></span><h3>Office hours</h3><p><?= e(setting('office_hours')) ?></p></article>
    <?php endif; ?>
    <?php foreach ($offices as $o): ?>
    <article class="info-card"><span class="mega-icon"><?= icon('pin') ?></span><h3><?= e($o['title']) ?></h3><p><?= e($o['text']) ?></p>
      <a class="link-arrow small" href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode($o['text'])) ?>" target="_blank" rel="noopener">Open in Maps <?= icon('external') ?></a>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<?= partial('contact-split') ?>
