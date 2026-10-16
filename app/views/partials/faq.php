<?php
/** @var array $faqs */
if (!$faqs) {
    return;
}
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn ($f) => [
        '@type' => 'Question',
        'name' => $f['question'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => Seo::plainText($f['answer'])],
    ], $faqs),
];
?>
<section class="section" aria-labelledby="faq-title">
  <div class="container">
    <div class="section-head center">
      <h2 class="display-2" id="faq-title"><?= e($faqTitle ?? 'Frequently asked questions') ?></h2>
      <span class="bar center"></span>
    </div>
    <div class="faq">
      <?php foreach ($faqs as $i => $f): ?>
      <details<?= $i === 0 ? ' open' : '' ?>>
        <summary><?= e($f['question']) ?></summary>
        <div class="rich"><?= Seo::markdown($f['answer']) ?></div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</section>
