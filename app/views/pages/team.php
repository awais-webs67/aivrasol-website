<?php /** @var array $members */ $site = setting('site_name', 'AivraSol'); ?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><li aria-current="page">Team</li></ol>
    <h1 class="display-1">Meet the team</h1>
    <span class="bar"></span>
    <p class="lead">The people who design, build and support your software. Open a profile to see their experience, skills and recent work.</p>
  </div>
</section>
<section class="section">
  <div class="container">
    <div class="team-grid">
      <?php foreach ($members as $m): ?>
      <a class="team-card" href="<?= e(url('team/' . $m['slug'])) ?>">
        <span class="team-photo"><?php if (!empty($m['photo'])): ?><img src="<?= e(url($m['photo'])) ?>" alt="" width="240" height="240" loading="lazy" decoding="async"><?php else: ?><span aria-hidden="true"><?= e(Team::initials($m['name'])) ?></span><?php endif; ?></span>
        <span class="team-name"><?= e($m['name']) ?></span>
        <span class="team-role"><?= e($m['headline'] ?? '') ?></span>
        <?php if (!empty($m['skills'])): ?><span class="team-skills"><?= e(implode(' · ', array_slice($m['skills'], 0, 3))) ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="section section-soft">
  <div class="container center-block">
    <h2 class="display-2">Want this team on your project?</h2>
    <span class="bar center"></span>
    <p>Tell us what you need. We will match the right people and send a free, no-obligation quote.</p>
    <div class="btn-group center"><a class="btn" href="<?= e(url('contact')) ?>">Talk to us</a><a class="btn btn-outline" href="<?= e(url('careers')) ?>">Join the team</a></div>
  </div>
</section>
