<?php
/** @var array $projects */
$categories = array_values(array_unique(array_column($projects, 'category')));
?>
<section class="page-hero">
  <div class="container page-hero-inner single">
    <div>
      <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><li aria-current="page">Portfolio</li></ol>
      <h1 class="display-1">Discover our successful projects</h1>
      <span class="bar"></span>
      <p class="lead">A selection of web apps, mobile apps and online stores we have designed and built. Each case study shows the problem, what we built and the result.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (!$projects): ?>
    <div class="empty"><h3>Case studies are coming soon</h3><p>Ask us for examples that match your project.</p><a class="btn" href="<?= e(url('contact')) ?>">Contact us</a></div>
    <?php else: ?>
    <?php if (count($categories) > 1): ?>
    <div class="filters" role="group" aria-label="Filter projects" data-filters hidden>
      <button class="filter" type="button" aria-pressed="true" data-filter="*">All</button>
      <?php foreach ($categories as $c): ?><button class="filter" type="button" aria-pressed="false" data-filter="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="project-grid" data-filter-grid>
      <?php foreach ($projects as $project): ?><?= partial('project-card', ['project' => $project]) ?><?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?= partial('cta', ['ctaTitle' => 'Want results like these?']) ?>
