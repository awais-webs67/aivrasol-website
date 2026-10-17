<?php
/** @var array $m @var bool $preview */
$first = strtok((string) $m['name'], ' ') ?: $m['name'];
$links = $m['links'] ?? [];
$site = setting('site_name', 'AivraSol');
$has = fn (string $k) => !empty($m[$k]);
?>
<?php if (!empty($preview)): ?>
<div class="preview-bar" role="status"><div class="container">Preview of your draft. This is how the page will look. It is not public until it is published.</div></div>
<?php endif; ?>
<section class="page-hero member-hero">
  <div class="container member-head">
    <div class="member-photo">
      <?php if ($has('photo')): ?><img src="<?= e(url($m['photo'])) ?>" alt="<?= e($m['name']) ?>" width="320" height="320" fetchpriority="high"><?php else: ?><span aria-hidden="true"><?= e(Team::initials($m['name'])) ?></span><?php endif; ?>
    </div>
    <div class="member-intro">
      <?php if (empty($preview)): ?>
      <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><?php if (setting('team_list', '1') === '1'): ?><li><a href="<?= e(url('team')) ?>">Team</a></li><?php endif; ?><li aria-current="page"><?= e($m['name']) ?></li></ol>
      <?php endif; ?>
      <p class="project-meta"><?= e($site) ?> team<?= $has('location') ? ' · ' . e($m['location']) : '' ?></p>
      <h1 class="display-1"><?= e($m['name']) ?></h1>
      <span class="bar"></span>
      <p class="lead"><?= e($m['headline'] ?? '') ?></p>
      <div class="member-actions">
        <?php if (!empty($m['open'])): ?><a class="btn" href="<?= e(url('contact')) ?>">Work with <?= e($first) ?></a><?php endif; ?>
        <?php if ($has('email')): ?><a class="btn btn-outline" href="mailto:<?= e($m['email']) ?>"><?= icon('mail') ?>Email</a><?php endif; ?>
      </div>
      <?php if ($links): ?>
      <ul class="member-links" aria-label="<?= e($first) ?> online">
        <?php foreach ($links as $k => $u): ?>
        <li><a class="s-<?= e($k) ?>" href="<?= e($u) ?>" target="_blank" rel="noopener me" title="<?= e(Team::LINKS[$k] ?? $k) ?>" aria-label="<?= e(Team::LINKS[$k] ?? $k) ?>"><?= icon($k === 'website' ? 'globe' : $k) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container member-body">
    <div class="member-main">
      <?php if (trim((string) ($m['about'] ?? '')) !== ''): ?>
      <div class="member-block">
        <h2 class="member-h">About</h2>
        <div class="rich prose"><?= Seo::markdown((string) $m['about']) ?></div>
      </div>
      <?php endif; ?>

      <?php if ($has('experience')): ?>
      <div class="member-block">
        <h2 class="member-h">Experience</h2>
        <ol class="timeline">
          <?php foreach ($m['experience'] as $x): ?>
          <li>
            <p class="tl-when"><?= e(trim($x['start'] . ($x['start'] !== '' && $x['end'] !== '' ? ' to ' : '') . $x['end'])) ?></p>
            <h3><?= e($x['role']) ?><?php if ($x['company'] !== ''): ?> <span class="muted">at <?= e($x['company']) ?></span><?php endif; ?></h3>
            <?php if ($x['text'] !== ''): ?><p><?= nl2br(e($x['text'])) ?></p><?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ol>
      </div>
      <?php endif; ?>

      <?php if ($has('work')): ?>
      <div class="member-block">
        <h2 class="member-h">Featured work</h2>
        <div class="work-grid">
          <?php foreach ($m['work'] as $w): ?>
          <article class="work-card">
            <h3><?= e($w['title']) ?></h3>
            <?php if ($w['text'] !== ''): ?><p><?= nl2br(e($w['text'])) ?></p><?php endif; ?>
            <?php if ($w['url'] !== ''): ?><a class="link-arrow" href="<?= e($w['url']) ?>" target="_blank" rel="noopener nofollow">View project <?= icon('arrow-right') ?></a><?php endif; ?>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($has('education')): ?>
      <div class="member-block">
        <h2 class="member-h">Education</h2>
        <ul class="plain-list">
          <?php foreach ($m['education'] as $x): ?>
          <li><strong><?= e($x['degree']) ?></strong><?php if ($x['school'] !== ''): ?><br><span><?= e($x['school']) ?></span><?php endif; ?><?php if ($x['years'] !== ''): ?> <span class="muted">· <?= e($x['years']) ?></span><?php endif; ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($has('certs')): ?>
      <div class="member-block">
        <h2 class="member-h">Certifications</h2>
        <ul class="plain-list">
          <?php foreach ($m['certs'] as $x): ?>
          <li><strong><?php if ($x['url'] !== ''): ?><a href="<?= e($x['url']) ?>" target="_blank" rel="noopener nofollow"><?= e($x['name']) ?></a><?php else: ?><?= e($x['name']) ?><?php endif; ?></strong><?php if ($x['issuer'] !== '' || $x['year'] !== ''): ?><br><span class="muted"><?= e(trim($x['issuer'] . ($x['issuer'] !== '' && $x['year'] !== '' ? ', ' : '') . $x['year'])) ?></span><?php endif; ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>

    <aside class="member-side">
      <?php if ($has('skills')): ?>
      <div class="side-card">
        <h2 class="member-h">Skills</h2>
        <ul class="chips"><?php foreach ($m['skills'] as $sk): ?><li><?= e($sk) ?></li><?php endforeach; ?></ul>
      </div>
      <?php endif; ?>
      <div class="side-card side-cta">
        <h2 class="member-h">Hire <?= e($first) ?> through <?= e($site) ?></h2>
        <p><?= e($first) ?> works with the <?= e($site) ?> team. Tell us about your project and we will reply within one business day.</p>
        <a class="btn" href="<?= e(url('contact')) ?>">Start a project</a>
        <a class="link-arrow" href="<?= e(url('pricing')) ?>">See plans and pricing <?= icon('arrow-right') ?></a>
      </div>
    </aside>
  </div>
</section>
