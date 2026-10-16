<?php /** @var array $project */ ?>
<a class="project-card" href="<?= e(url('portfolio/' . $project['slug'])) ?>" data-category="<?= e($project['category']) ?>">
  <div class="project-cover"><?= partial('showcase', ['project' => $project]) ?></div>
  <div class="project-body">
    <span class="project-meta"><?= e($project['category']) ?><?= $project['industry'] !== '' ? ' · ' . e($project['industry']) : '' ?></span>
    <h3><?= e($project['title']) ?></h3>
    <p><?= e(excerpt($project['summary'], 130)) ?></p>
    <span class="link-arrow">View case study <?= icon('arrow-right') ?></span>
  </div>
</a>
