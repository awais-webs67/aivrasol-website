<?php
/** @var array $jobs @var array $faqs */
$benefits = [
    ['briefcase', 'Competitive salary', 'Fair pay reviewed every year, plus performance bonuses.'],
    ['clock', 'Flexible hours', 'Flexible start times and hybrid options for many roles.'],
    ['shield', 'Health and wellbeing', 'Medical support, wellness days and a healthy workplace.'],
    ['rocket', 'Learning budget', 'Courses, certifications and conferences paid by us.'],
    ['users', 'Mentorship', 'Work next to senior engineers and grow your skills fast.'],
    ['star', 'Recognition', 'We celebrate good work with rewards and real responsibility.'],
];
?>
<section class="page-hero">
  <div class="container page-hero-inner">
    <div>
      <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><li aria-current="page">Careers</li></ol>
      <h1 class="display-1">We're looking for you!</h1>
      <span class="bar"></span>
      <p class="lead">AivraSol is growing and we are always looking for curious developers, designers and marketers who want to build software that matters. Jobs and internships are listed below.</p>
      <a class="btn" href="#jobs">See open roles</a>
    </div>
    <div class="about-photo"><img src="<?= e(url('assets/img/hero-office.webp')) ?>" alt="Inside the AivraSol office" width="703" height="771" loading="lazy"></div>
  </div>
</section>

<section class="section" id="jobs" aria-labelledby="jobs-title">
  <div class="container">
    <div class="section-head">
      <h2 class="display-2" id="jobs-title">Our job offers</h2>
      <span class="bar"></span>
      <p class="lead">Do you want to build the internet of tomorrow with a motivated team? Are you ready to take responsibility and grow with us? We would love to hear from you.</p>
    </div>
    <?php if ($jobs): ?>
    <div class="job-list">
      <?php foreach ($jobs as $job): ?>
      <article class="job-card">
        <div>
          <h3><?= e($job['title']) ?></h3>
          <ul class="job-meta">
            <?php foreach (array_filter([$job['department'], $job['job_type'], $job['location'], $job['experience']]) as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
          </ul>
        </div>
        <a class="btn btn-outline" href="<?= e(url('careers/' . $job['slug'])) ?>">View and apply</a>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty">
      <h3>There are no open roles right now</h3>
      <p>We still want to meet great people. Send a general application below and we will contact you when a matching role opens.</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section-soft" aria-labelledby="offer-title">
  <div class="container">
    <div class="section-head center">
      <h2 class="display-2" id="offer-title">What we offer</h2>
      <span class="bar center"></span>
    </div>
    <div class="tiles-3">
      <?php foreach ($benefits as [$ic, $title, $text]): ?>
      <article class="tile"><span class="mega-icon"><?= icon($ic) ?></span><h3><?= e($title) ?></h3><p><?= e($text) ?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="apply" aria-labelledby="apply-title">
  <div class="container container-narrow">
    <div class="section-head center">
      <h2 class="display-2" id="apply-title">Send a general application</h2>
      <span class="bar center"></span>
      <p class="lead">Upload your CV and tell us a little about yourself.</p>
    </div>
    <?= partial('apply-form') ?>
  </div>
</section>

<?= partial('faq', ['faqs' => $faqs, 'faqTitle' => 'Careers questions']) ?>
