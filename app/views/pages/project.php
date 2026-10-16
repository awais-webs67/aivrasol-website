<?php /** @var array $project @var array $more */ ?>
<section class="page-hero">
  <div class="container page-hero-inner">
    <div>
      <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><li><a href="<?= e(url('portfolio')) ?>">Portfolio</a></li><li aria-current="page"><?= e($project['title']) ?></li></ol>
      <p class="project-meta"><?= e($project['category']) ?><?= $project['industry'] !== '' ? ' · ' . e($project['industry']) : '' ?></p>
      <h1 class="display-1"><?= e($project['title']) ?></h1>
      <span class="bar"></span>
      <p class="lead"><?= e($project['summary']) ?></p>
      <?php if ($project['url'] !== '' && preg_match('#^https://#i', $project['url'])): ?>
      <a class="btn btn-outline" href="<?= e($project['url']) ?>" target="_blank" rel="noopener">Visit live project <?= icon('external') ?></a>
      <?php endif; ?>
    </div>
    <div class="case-cover"><?= partial('showcase', ['project' => $project, 'eager' => true]) ?></div>
  </div>
</section>

<section class="section">
  <div class="container case-body">
    <div class="prose">
      <h2>The challenge</h2>
      <div class="rich"><?= Seo::markdown($project['challenge']) ?></div>
      <h2>Our solution</h2>
      <div class="rich"><?= Seo::markdown($project['solution']) ?></div>
      <?php if (lines($project['results'])): ?>
      <h2>The results</h2>
      <ul class="check-list"><?php foreach (lines($project['results']) as $r): ?><li><?= icon('check') ?><span><?= e($r) ?></span></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
    <dl class="case-meta">
      <div><dt>Service</dt><dd><?= e($project['category']) ?></dd></div>
      <?php if ($project['industry'] !== ''): ?><div><dt>Industry</dt><dd><?= e($project['industry']) ?></dd></div><?php endif; ?>
      <?php if ($project['location'] !== ''): ?><div><dt>Location</dt><dd><?= e($project['location']) ?></dd></div><?php endif; ?>
      <?php if (csv_list($project['tech'])): ?><div><dt>Technology</dt><dd><ul class="chips"><?php foreach (csv_list($project['tech']) as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul></dd></div><?php endif; ?>
      <div><a class="btn btn-block" href="<?= e(url('contact')) ?>" data-modal-open="consult-modal">Start a similar project</a></div>
    </dl>
  </div>
</section>

<?php if ($more): ?>
<section class="section section-soft">
  <div class="container">
    <div class="section-head"><h2 class="display-3">More projects</h2><span class="bar"></span></div>
    <div class="project-grid">
      <?php foreach (array_slice($more, 0, 3) as $p): ?><?= partial('project-card', ['project' => $p]) ?><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
