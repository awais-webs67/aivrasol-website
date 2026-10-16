<section class="page-hero">
  <div class="container page-hero-inner single">
    <div>
      <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><li aria-current="page"><?= e($legalTitle) ?></li></ol>
      <h1 class="display-1"><?= e($legalTitle) ?></h1>
      <span class="bar"></span>
      <p class="muted small">Last updated: <?= e(date('j F Y', filemtime(__FILE__))) ?></p>
    </div>
  </div>
</section>
<section class="section"><div class="container"><div class="prose">
