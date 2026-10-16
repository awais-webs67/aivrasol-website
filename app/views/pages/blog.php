<?php /** @var array $posts @var array $categories @var string $category */ ?>
<section class="page-hero">
  <div class="container page-hero-inner single">
    <div>
      <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><li aria-current="page">Blog</li></ol>
      <h1 class="display-1">Insights and guides</h1>
      <span class="bar"></span>
      <p class="lead">Practical advice on software costs, hiring developers, choosing technology and growing your business online. Written by our team, in plain English.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (count($categories) > 1): ?>
    <nav class="filters" aria-label="Blog categories">
      <a class="filter" href="<?= e(url('blog')) ?>"<?= $category === '' ? ' aria-current="page"' : '' ?>>All</a>
      <?php foreach ($categories as $c): ?><a class="filter" href="<?= e(url('blog') . '?category=' . rawurlencode($c)) ?>"<?= $category === $c ? ' aria-current="page"' : '' ?>><?= e($c) ?></a><?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php if (!$posts): ?>
    <div class="empty"><h3>New articles are on the way</h3><p>In the meantime, tell us about your project and get free advice.</p><a class="btn" href="<?= e(url('contact')) ?>">Contact us</a></div>
    <?php else: ?>
    <div class="post-grid">
      <?php foreach ($posts as $post): ?><?= partial('post-card', ['post' => $post]) ?><?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?= partial('cta', ['ctaTitle' => 'Have a project in mind?']) ?>
