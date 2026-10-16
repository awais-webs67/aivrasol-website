<?php
/** @var string $type @var array $items */
$isHire = $type === 'hire';
?>
<section class="page-hero">
  <div class="container page-hero-inner single">
    <div>
      <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><li aria-current="page"><?= $isHire ? 'Hire developers' : 'Services' ?></li></ol>
      <h1 class="display-1"><?= $isHire ? 'Hire skilled developers' : 'Our services' ?></h1>
      <span class="bar"></span>
      <p class="lead"><?= $isHire
          ? 'Add pre-vetted engineers to your team in days. Full-time, part-time or hourly, with no long-term contract and a free replacement if the fit is not right.'
          : 'Everything you need to plan, build and grow a digital product, delivered by one team that cares about quality and clear communication.' ?></p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="offer-grid">
      <?php foreach ($items as $o): ?>
      <a class="offer-card" href="<?= e(url(($isHire ? 'hire/' : 'services/') . $o['slug'])) ?>">
        <span class="mega-icon"><?= icon($o['icon']) ?></span>
        <h2><?= e($o['title']) ?></h2>
        <p><?= e($o['summary']) ?></p>
        <span class="link-arrow">Learn more<span class="sr-only"> about <?= e($o['title']) ?></span> <?= icon('arrow-right') ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?= partial('cta') ?>
