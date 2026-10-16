<?php /** @var array $job */ ?>
<section class="page-hero">
  <div class="container page-hero-inner single">
    <div>
      <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><li><a href="<?= e(url('careers')) ?>">Careers</a></li><li aria-current="page"><?= e($job['title']) ?></li></ol>
      <h1 class="display-1"><?= e($job['title']) ?></h1>
      <span class="bar"></span>
      <ul class="job-meta"><?php foreach (array_filter([$job['department'], $job['job_type'], $job['location'], $job['experience']]) as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
      <p class="lead"><?= e($job['summary']) ?></p>
      <a class="btn" href="#apply">Apply now</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split">
    <div class="prose">
      <?php if (lines($job['responsibilities'])): ?>
      <h2>What you will do</h2>
      <ul class="check-list"><?php foreach (lines($job['responsibilities']) as $r): ?><li><?= icon('check') ?><span><?= e($r) ?></span></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
    <div class="prose">
      <?php if (lines($job['requirements'])): ?>
      <h2>What you bring</h2>
      <ul class="check-list"><?php foreach (lines($job['requirements']) as $r): ?><li><?= icon('check') ?><span><?= e($r) ?></span></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section section-soft" id="apply" aria-labelledby="apply-title">
  <div class="container container-narrow">
    <div class="section-head center">
      <h2 class="display-2" id="apply-title">Apply for this role</h2>
      <span class="bar center"></span>
    </div>
    <?= partial('apply-form', ['job' => $job]) ?>
  </div>
</section>
