<?php /** @var array $post @var array $more */ $toc = Seo::headings($post['body']); $tone = 'tone-' . (crc32($post['category']) % 4 + 1); ?>
<article>
  <header class="page-hero post-hero">
    <div class="container post-head">
      <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><li><a href="<?= e(url('blog')) ?>">Blog</a></li><li aria-current="page"><?= e($post['title']) ?></li></ol>
      <p class="project-meta"><a href="<?= e(url('blog') . '?category=' . rawurlencode($post['category'])) ?>"><?= e($post['category']) ?></a></p>
      <h1 class="display-1"><?= e($post['title']) ?></h1>
      <span class="bar"></span>
      <p class="lead"><?= e($post['excerpt']) ?></p>
      <p class="post-byline">
        <?php if ($post['author_name'] !== ''): ?><strong><?= e($post['author_name']) ?></strong><?= $post['author_role'] !== '' ? ', ' . e($post['author_role']) : '' ?> · <?php endif; ?>
        <time datetime="<?= e($post['published_at']) ?>"><?= e(date('j F Y', strtotime($post['published_at']))) ?></time> · <?= Seo::readingMinutes($post['body']) ?> min read
      </p>
    </div>
  </header>
  <div class="container post-layout">
    <div class="post-main">
      <div class="post-cover post-cover-lg">
        <?php if ($post['image'] !== ''): ?>
        <img src="<?= e(asset($post['image'])) ?>" alt="<?= e($post['title']) ?>" width="1200" height="630" fetchpriority="high">
        <?php else: ?>
        <span class="post-cover-art <?= $tone ?>"><span><?= e($post['category']) ?></span></span>
        <?php endif; ?>
      </div>
      <div class="prose post-prose"><?= Seo::markdown($post['body']) ?></div>
    </div>
    <aside class="post-side">
      <?php if (count($toc) > 1): ?>
      <nav class="toc" aria-label="On this page">
        <p class="toc-title">On this page</p>
        <ol><?php foreach ($toc as [$id, $text]): ?><li><a href="#<?= e($id) ?>"><?= e($text) ?></a></li><?php endforeach; ?></ol>
      </nav>
      <?php endif; ?>
      <div class="side-cta">
        <p class="toc-title">Need help with this?</p>
        <p>Talk to our team. Free advice, no obligation.</p>
        <a class="btn btn-block" href="<?= e(url('contact')) ?>" data-modal-open="consult-modal">Book a free consultation</a>
      </div>
    </aside>
  </div>
</article>

<?php if ($more): ?>
<section class="section section-soft" aria-labelledby="more-title">
  <div class="container">
    <div class="section-head"><h2 class="display-2" id="more-title">Keep reading</h2><span class="bar"></span></div>
    <div class="post-grid">
      <?php foreach ($more as $m): ?><?= partial('post-card', ['post' => $m]) ?><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?= partial('cta', ['ctaTitle' => 'Ready to start your project?']) ?>
