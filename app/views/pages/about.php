<?php
/** @var array $stats */
$values = [
    ['chat', 'Clear communication', 'Weekly demos, honest updates and one point of contact. You always know where your project stands.'],
    ['shield', 'Quality and security', 'Code reviews, automated tests and security checks are part of every release, not an extra.'],
    ['rocket', 'Business first', 'We build what moves your business forward and say so when something is not worth building.'],
    ['users', 'Long-term partners', 'Most clients stay with us after launch. We support, improve and scale what we build.'],
    ['layers', 'Modern technology', 'We pick proven, well-supported tools so your product is easy to maintain and hire for.'],
    ['check', 'You own everything', 'Source code, designs and accounts belong to you. No lock-in, ever.'],
];
?>
<section class="page-hero">
  <div class="container page-hero-inner">
    <div>
      <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><li aria-current="page">About us</li></ol>
      <h1 class="display-1">We build software that helps businesses grow</h1>
      <span class="bar"></span>
      <p class="lead">AivraSol is a software development company. We design and build web apps, mobile apps, cloud systems and AI tools for startups, small businesses and enterprises around the world.</p>
      <div class="btn-group">
        <a class="btn" href="<?= e(url('contact')) ?>" data-modal-open="consult-modal">Work with us</a>
        <a class="btn btn-outline" href="<?= e(url('careers')) ?>">Join the team</a>
      </div>
    </div>
    <div class="about-photo"><?php $photo = site_photo(); ?><img src="<?= e($photo['src']) ?>" alt="The AivraSol office reception" width="<?= $photo['w'] ?>" height="<?= $photo['h'] ?>"></div>
  </div>
</section>

<?php if ($stats): ?>
<section class="stats" aria-label="AivraSol in numbers">
  <div class="container stats-grid">
    <?php foreach ($stats as $st): ?><div><span class="stat-value"><?= e($st['value']) ?></span><span class="stat-label"><?= e($st['label']) ?></span></div><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container split">
    <div>
      <h2 class="display-2">Our story</h2>
      <span class="bar"></span>
    </div>
    <div class="prose">
      <p class="lead">We started AivraSol with a simple idea: building software should feel clear and calm, not confusing and risky.</p>
      <p>Too many businesses pay for projects that run late, go over budget or become impossible to change. We work differently. Every project starts with a clear scope and a fixed quote. You see progress every week. And when the product goes live, we stay to support it and help it grow.</p>
      <p>Today our team of developers, designers and cloud engineers works with clients across many industries, from retail and events to healthcare and logistics.</p>
    </div>
  </div>
</section>

<?php $offices = Content::offices(); if ($offices): ?>
<section class="section" aria-labelledby="where-title">
  <div class="container">
    <div class="section-head center">
      <h2 class="display-2" id="where-title">Where we work</h2>
      <span class="bar center"></span>
      <p class="lead">Local teams in <?= e(implode(', ', array_slice(array_column($offices, 'country'), 0, -1)) . (count($offices) > 1 ? ' and ' : '') . end($offices)['country']) ?>, working together for clients around the world.</p>
    </div>
    <?= partial('offices', ['offices' => $offices]) ?>
  </div>
</section>
<?php endif; ?>

<section class="section section-soft">
  <div class="container">
    <div class="section-head center">
      <h2 class="display-2">What we believe in</h2>
      <span class="bar center"></span>
    </div>
    <div class="tiles-3">
      <?php foreach ($values as [$ic, $title, $text]): ?>
      <article class="tile"><span class="mega-icon"><?= icon($ic) ?></span><h3><?= e($title) ?></h3><p><?= e($text) ?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?= partial('cta', ['ctaTitle' => 'Let us build something great together']) ?>
