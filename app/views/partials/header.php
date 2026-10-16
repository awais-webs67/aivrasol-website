<?php
$services = Content::offerings('service');
$hires = Content::offerings('hire');
$simple = ['/about' => 'About', '/portfolio' => 'Portfolio', '/blog' => 'Blog', '/careers' => 'Careers', '/pricing' => 'Pricing'];
?>
<header class="site-header" data-header>
  <div class="container header-inner">
    <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e(setting('site_name', 'AivraSol')) ?> home">
      <img src="<?= e(asset('assets/img/logo.svg')) ?>" alt="<?= e(setting('site_name', 'AivraSol')) ?>" width="160" height="31">
    </a>
    <button class="nav-toggle" type="button" aria-controls="main-nav" aria-expanded="false" data-nav-toggle>
      <span class="sr-only">Menu</span>
      <?= icon('menu', 'icon icon-open') ?><?= icon('close', 'icon icon-close') ?>
    </button>
    <nav class="main-nav" id="main-nav" aria-label="Main">
      <ul class="nav-list">
        <li class="nav-item">
          <button class="nav-link<?= is_active_path('/services') ? ' is-active' : '' ?>" type="button" aria-expanded="false" aria-controls="mega-services" data-dropdown>Services <?= icon('chevron-down') ?></button>
          <div class="mega" id="mega-services">
            <div class="mega-grid">
              <?php foreach ($services as $s): ?>
              <a class="mega-link" href="<?= e(url('services/' . $s['slug'])) ?>">
                <span class="mega-icon"><?= duo($s['icon']) ?></span>
                <span class="mega-title"><?= e($s['title']) ?></span>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
        </li>
        <li class="nav-item">
          <button class="nav-link<?= is_active_path('/hire') ? ' is-active' : '' ?>" type="button" aria-expanded="false" aria-controls="mega-hire" data-dropdown>Hire Developers <?= icon('chevron-down') ?></button>
          <div class="mega" id="mega-hire">
            <div class="mega-grid">
              <?php foreach ($hires as $h): ?>
              <a class="mega-link" href="<?= e(url('hire/' . $h['slug'])) ?>">
                <span class="mega-icon"><?= duo($h['icon']) ?></span>
                <span class="mega-title"><?= e($h['title']) ?></span>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
        </li>
        <?php foreach ($simple as $path => $label): ?>
        <li class="nav-item"><a class="nav-link<?= is_active_path($path) ? ' is-active' : '' ?>" href="<?= e(url($path)) ?>"<?= is_active_path($path) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-cta"><a class="btn" href="<?= e(url('contact')) ?>">Contact us</a></div>
    </nav>
  </div>
</header>
