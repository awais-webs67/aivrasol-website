<?php
/** @var array $item @var string $type @var array $faqs */
$isHire = $type === 'hire';
$features = pairs($item['features']);
$tech = csv_list($item['tech']);
$others = array_values(array_filter(Content::offerings($type), fn ($o) => $o['slug'] !== $item['slug']));
?>
<section class="page-hero">
  <div class="container page-hero-inner">
    <div>
      <ol class="breadcrumb">
        <li><a href="<?= e(url('/')) ?>">Home</a></li>
        <li><a href="<?= e(url($isHire ? 'hire' : 'services')) ?>"><?= $isHire ? 'Hire developers' : 'Services' ?></a></li>
        <li aria-current="page"><?= e($item['short_title']) ?></li>
      </ol>
      <h1 class="display-1"><?= e($item['title']) ?></h1>
      <span class="bar"></span>
      <p class="lead"><?= e($item['hero_text']) ?></p>
      <div class="btn-group">
        <a class="btn" href="#get-in-touch"><?= $isHire ? 'Hire now' : 'Get in touch' ?></a>
        <a class="btn btn-outline" href="<?= e(url('contact')) ?>" data-modal-open="consult-modal">Free consultation</a>
      </div>
    </div>
    <div class="visual" aria-hidden="true">
      <span class="visual-ring"></span><span class="visual-ring inner"></span>
      <span class="visual-core on-dark"><?= duo($item['icon']) ?></span>
      <?php foreach (array_slice($tech, 0, 5) as $i => $t): ?><span class="visual-chip c<?= $i + 1 ?>"><?= e($t) ?></span><?php endforeach; ?>
    </div>
  </div>
</section>

<section class="quote-band">
  <div class="container"><p><span>“</span> <?= e($item['quote']) ?> <span>”</span></p></div>
</section>

<section class="section" aria-labelledby="features-title">
  <div class="container">
    <div class="section-head">
      <h2 class="display-2" id="features-title"><?= $isHire ? 'Why choose ' . e($item['short_title']) . 's from us?' : 'What we offer' ?></h2>
      <span class="bar"></span>
    </div>
    <div class="feature-grid<?= count($features) > 4 ? ' cols-3' : '' ?>">
      <?php foreach ($features as $f): ?>
      <article class="feature">
        <span class="feature-bar"></span>
        <h3><?= e($f['title']) ?></h3>
        <p><?= e($f['text']) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-soft" aria-labelledby="expertise-title">
  <div class="container split">
    <div>
      <h2 class="display-2" id="expertise-title"><?= e($item['expertise_title']) ?></h2>
      <span class="bar"></span>
      <div class="lead rich"><?= Seo::markdown($item['expertise_text']) ?></div>
      <?php if ($tech): ?>
      <h3 class="small muted">Technologies we use</h3>
      <ul class="chips"><?php foreach ($tech as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
    <div class="panel">
      <h3><?= $isHire ? 'How hiring works' : 'What you get' ?></h3>
      <ul class="check-list">
        <?php if ($isHire): ?>
        <li><?= icon('check') ?><span>Share your requirements in a short call.</span></li>
        <li><?= icon('check') ?><span>Interview hand-picked, pre-vetted candidates within days.</span></li>
        <li><?= icon('check') ?><span>Start with a risk-free trial period.</span></li>
        <li><?= icon('check') ?><span>Scale up, scale down or swap developers any month.</span></li>
        <li><?= icon('check') ?><span>Daily updates in your tools: Slack, Jira, GitHub.</span></li>
        <?php else: ?>
        <li><?= icon('check') ?><span>A clear plan, timeline and fixed quote before work starts.</span></li>
        <li><?= icon('check') ?><span>A dedicated project manager and weekly demos.</span></li>
        <li><?= icon('check') ?><span>Clean, documented code that you fully own.</span></li>
        <li><?= icon('check') ?><span>Testing and security checks on every release.</span></li>
        <li><?= icon('check') ?><span>Free bug fixing after launch and flexible support plans.</span></li>
        <?php endif; ?>
      </ul>
      <a class="btn btn-block" href="<?= e(url('contact')) ?>" data-modal-open="consult-modal">Book a free consultation</a>
    </div>
  </div>
</section>

<?php if ($isHire): ?>
<section class="section-tight">
  <div class="container">
    <div class="pricing-strip">
      <div>
        <h2>Full-time, part-time or a whole team</h2>
        <p>See our hiring plans and get a free quote for <?= e($item['short_title']) ?>s within one business day.</p>
      </div>
      <a class="btn" href="<?= e(url('pricing')) ?>?role=<?= e(rawurlencode($item['short_title'])) ?>#hire-plans">See hiring plans</a>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($others): ?>
<section class="section section-soft" aria-labelledby="more-title">
  <div class="container">
    <div class="section-head">
      <h2 class="display-3" id="more-title"><?= $isHire ? 'Hire other experts' : 'Explore more services' ?></h2>
      <span class="bar"></span>
    </div>
    <div class="offer-grid">
      <?php foreach (array_slice($others, 0, 3) as $o): ?>
      <a class="offer-card" href="<?= e(url(($isHire ? 'hire/' : 'services/') . $o['slug'])) ?>">
        <span class="mega-icon"><?= duo($o['icon']) ?></span>
        <h3 class="h4"><?= e($o['title']) ?></h3>
        <p><?= e(excerpt($o['summary'], 120)) ?></p>
        <span class="link-arrow">Learn more<span class="sr-only"> about <?= e($o['title']) ?></span> <?= icon('arrow-right') ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?= partial('faq', ['faqs' => $faqs]) ?>


<?= partial('contact-split', ['interest' => $item['short_title']]) ?>
