<?php
/** @var array $services @var array $projects @var array $plans @var array $testimonials @var array $faqs @var array $stats */
?>
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <h1 class="display-1"><?= heading_lines(setting('hero_heading', 'Smart software|for growing businesses')) ?></h1>
      <p class="eyebrow"><?= e(setting('site_name', 'AivraSol')) ?></p>
      <p class="lead"><?= e(setting('hero_text')) ?></p>
      <div class="btn-group">
        <a class="btn" href="<?= e(url('contact')) ?>" data-modal-open="consult-modal">Get in touch</a>
        <a class="btn btn-outline" href="<?= e(url('portfolio')) ?>">See our work</a>
      </div>
      <ul class="hero-points">
        <li><?= icon('check') ?>Free consultation</li>
        <li><?= icon('check') ?>Fixed, clear quotes</li>
        <li><?= icon('check') ?>You own the code</li>
      </ul>
    </div>
    <div class="hero-media">
      <svg class="hero-arrow" viewBox="0 0 160 70" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M158 22c-20 2-38 10-52 22-10 8-24 14-30 6-6-7 4-20 14-16 10 5 4 22-12 28-18 6-40 4-62 0"/>
        <path d="M22 52l-16 9 16 7"/>
      </svg>
      <div class="hero-frame">
        <?php $photo = site_photo(); ?>
        <img src="<?= e($photo['src']) ?>"<?php if ($photo['srcset'] !== ''): ?> srcset="<?= e($photo['srcset']) ?>" sizes="(min-width: 1024px) 500px, 92vw"<?php endif; ?> width="<?= $photo['w'] ?>" height="<?= $photo['h'] ?>" alt="The <?= e(setting('site_name', 'AivraSol')) ?> office" fetchpriority="high">
      </div>
      <div class="hero-badge">
        <span class="mega-icon"><?= icon('rocket') ?></span>
        <span><strong>From idea to launch</strong>One team for design, code and growth</span>
      </div>
    </div>
  </div>
</section>

<section class="section" id="services" aria-labelledby="services-title">
  <div class="container">
    <div class="section-head center">
      <h2 class="display-2" id="services-title">Turning ideas into digital products</h2>
      <span class="bar center"></span>
      <p class="lead">From strategy and UX design to web apps, mobile apps, cloud and AI, we help you plan, build and grow your digital business with one reliable team.</p>
    </div>
    <div class="svc" data-tabs>
      <div class="svc-tiles" role="tablist" aria-label="Our services">
        <?php foreach ($services as $i => $s): ?>
        <a class="svc-tile" href="<?= e(url('services/' . $s['slug'])) ?>" role="tab" id="tab-<?= e($s['slug']) ?>" aria-controls="panel-<?= e($s['slug']) ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" tabindex="<?= $i === 0 ? '0' : '-1' ?>">
          <?= duo($s['icon']) ?><span><?= e($s['short_title']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php foreach ($services as $i => $s): ?>
      <div class="svc-panel" role="tabpanel" id="panel-<?= e($s['slug']) ?>" aria-labelledby="tab-<?= e($s['slug']) ?>"<?= $i === 0 ? '' : ' hidden' ?>>
        <h3><?= e($s['title']) ?></h3>
        <p class="lead"><?= e($s['summary']) ?></p>
        <ul class="check-list cols-2">
          <?php foreach (array_slice(pairs($s['features']), 0, 4) as $f): ?>
          <li><?= icon('check') ?><span><?= e($f['title']) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <ul class="chips" aria-label="Technologies">
          <?php foreach (csv_list($s['tech']) as $t): ?><li><?= e($t) ?></li><?php endforeach; ?>
        </ul>
        <a class="btn btn-block" href="<?= e(url('services/' . $s['slug'])) ?>">Learn more<span class="sr-only"> about <?= e($s['title']) ?></span></a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($stats && home_section('stats')): ?>
<section class="stats" aria-label="AivraSol in numbers">
  <div class="container stats-grid">
    <?php foreach ($stats as $st): ?>
    <div><span class="stat-value"><?= e($st['value']) ?></span><span class="stat-label"><?= e($st['label']) ?></span></div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($projects && home_section('portfolio')): ?>
<section class="section section-soft" aria-labelledby="work-title">
  <div class="container work-slider" data-slider>
    <div>
      <h2 class="display-2" id="work-title">Discover our successful projects</h2>
      <span class="bar"></span>
      <p>Businesses from many industries trust us with their products. Here are a few examples of what we have built.</p>
      <a class="all-link" href="<?= e(url('portfolio')) ?>">All projects</a>
      <div class="slider-controls">
        <button class="slider-btn prev" type="button" data-slide="prev" aria-label="Previous project"><?= icon('arrow-left') ?></button>
        <button class="slider-btn next" type="button" data-slide="next" aria-label="Next project"><?= icon('arrow-right') ?></button>
      </div>
    </div>
    <div class="slider-track" data-track tabindex="0" aria-label="Projects">
      <?php foreach ($projects as $project): ?>
      <?= partial('project-card', ['project' => $project]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (home_section('process')): ?>
<section class="section" aria-labelledby="process-title">
  <div class="container">
    <div class="section-head center">
      <h2 class="display-2" id="process-title">How we work</h2>
      <span class="bar center"></span>
      <p class="lead">A simple, open process so you always know what is happening and what comes next.</p>
    </div>
    <ol class="steps">
      <li class="step"><span class="step-num">01</span><h3>Discover</h3><p>We learn your goals, users and budget, then agree a clear scope and a fixed quote.</p></li>
      <li class="step"><span class="step-num">02</span><h3>Design</h3><p>Wireframes and clickable prototypes so you can see and test the product before we code.</p></li>
      <li class="step"><span class="step-num">03</span><h3>Build</h3><p>Short sprints with weekly demos, automated tests and security checks on every release.</p></li>
      <li class="step"><span class="step-num">04</span><h3>Launch & grow</h3><p>We launch, monitor and keep improving with support plans that fit your stage.</p></li>
    </ol>
  </div>
</section>
<?php endif; ?>

<?php if ($plans && home_section('pricing')): ?>
<section class="section section-soft" id="pricing" aria-labelledby="pricing-title">
  <div class="container">
    <div class="section-head center">
      <h2 class="display-2" id="pricing-title">Our pricing</h2>
      <span class="bar center"></span>
      <p class="lead">Get a free, fixed quote with no hidden fees. Pick how you want to work with us and tell us what you need.</p>
    </div>
    <?= partial('plans', ['plans' => $plans]) ?>
  </div>
</section>
<?php endif; ?>

<?php if ($testimonials && home_section('reviews')): ?>
<section class="section" aria-labelledby="reviews-title">
  <div class="container reviews" data-slider>
    <div>
      <h2 class="display-2" id="reviews-title">Reviews that speak volumes</h2>
      <span class="bar"></span>
      <p>We help businesses turn ideas into healthy, growing products. Here is what our clients say about working with us.</p>
      <div class="slider-controls">
        <button class="slider-btn prev" type="button" data-slide="prev" aria-label="Previous review"><?= icon('arrow-left') ?></button>
        <button class="slider-btn next" type="button" data-slide="next" aria-label="Next review"><?= icon('arrow-right') ?></button>
      </div>
    </div>
    <div class="slider-track" data-track tabindex="0" aria-label="Client reviews">
      <?php foreach ($testimonials as $t): ?>
      <figure class="review-card">
        <div class="review-stars" role="img" aria-label="<?= (int) $t['rating'] ?> out of 5 stars"><?= str_repeat(icon('star'), max(1, min(5, (int) $t['rating']))) ?></div>
        <blockquote>“<?= e($t['quote']) ?>”</blockquote>
        <figcaption class="review-person">
          <span class="avatar"><?php if ($t['avatar'] !== ''): ?><img src="<?= e(url($t['avatar'])) ?>" alt="" width="46" height="46" loading="lazy"><?php else: ?><?= e(mb_strtoupper(mb_substr($t['name'], 0, 1))) ?><?php endif; ?></span>
          <span><strong><?= e($t['name']) ?></strong><span><?= e(trim($t['role'] . ($t['company'] !== '' ? ', ' . $t['company'] : ''), ', ')) ?></span></span>
        </figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (home_section('faq')): ?><?= partial('faq', ['faqs' => $faqs]) ?><?php endif; ?>

<section class="section section-grey question" id="contact-form" aria-labelledby="question-title">
  <div class="container">
    <div class="section-head center">
      <h2 class="display-2" id="question-title">Do you have any questions?</h2>
      <span class="bar center"></span>
      <p class="lead">Share your details and our consultation team will reply within one business day.</p>
    </div>
    <?= partial('contact-form', ['idPrefix' => 'home']) ?>
  </div>
</section>
