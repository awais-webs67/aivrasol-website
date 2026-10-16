<?php
$site = setting('site_name', 'AivraSol');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <title><?= e($site) ?> blog</title>
  <link><?= e(absolute_url('blog')) ?></link>
  <description><?= e('Guides on software development, hiring developers and growing your business, from ' . $site . '.') ?></description>
  <language>en</language>
  <atom:link href="<?= e(absolute_url('blog/feed.xml')) ?>" rel="self" type="application/rss+xml"/>
<?php foreach ($posts as $p): ?>
  <item>
    <title><?= e($p['title']) ?></title>
    <link><?= e(absolute_url('blog/' . $p['slug'])) ?></link>
    <guid isPermaLink="true"><?= e(absolute_url('blog/' . $p['slug'])) ?></guid>
    <pubDate><?= gmdate(DATE_RSS, strtotime($p['published_at'] . ' 09:00 UTC')) ?></pubDate>
    <category><?= e($p['category']) ?></category>
    <description><?= e($p['excerpt']) ?></description>
  </item>
<?php endforeach; ?>
</channel>
</rss>
