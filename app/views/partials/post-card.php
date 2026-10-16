<?php /** @var array $post */ $tone = 'tone-' . (crc32($post['category']) % 4 + 1); ?>
<a class="post-card" href="<?= e(url('blog/' . $post['slug'])) ?>">
  <div class="post-cover">
    <?php if ($post['image'] !== ''): ?>
    <img src="<?= e(asset($post['image'])) ?>" alt="" width="1200" height="630" loading="lazy" decoding="async">
    <?php else: ?>
    <span class="post-cover-art <?= $tone ?>"><span><?= e($post['category']) ?></span></span>
    <?php endif; ?>
  </div>
  <div class="post-body">
    <span class="project-meta"><?= e($post['category']) ?> · <time datetime="<?= e($post['published_at']) ?>"><?= e(date('j M Y', strtotime($post['published_at']))) ?></time> · <?= Seo::readingMinutes($post['body']) ?> min read</span>
    <h3><?= e($post['title']) ?></h3>
    <p><?= e(excerpt($post['excerpt'], 150)) ?></p>
    <span class="link-arrow">Read article <?= icon('arrow-right') ?></span>
  </div>
</a>
