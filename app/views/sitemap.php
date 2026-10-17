<?php
$urls = [];
$add = function (string $path, ?string $mod = null, string $freq = 'monthly', string $prio = '0.6', array $images = []) use (&$urls): void {
    $urls[] = [absolute_url($path), $mod ? gmdate('Y-m-d', strtotime($mod . ' UTC')) : null, $freq, $prio, array_values(array_filter($images))];
};
$img = fn (?string $p) => $p && !str_ends_with($p, '.svg') ? absolute_url($p) : null;
$add('', null, 'weekly', '1.0');
foreach (['services' => '0.9', 'hire' => '0.9', 'pricing' => '0.8', 'portfolio' => '0.7', 'blog' => '0.8', 'about' => '0.6', 'careers' => '0.6', 'contact' => '0.7'] as $p => $prio) {
    $add($p, $p === 'blog' && $posts ? $posts[0]['updated_at'] : null, $p === 'blog' || $p === 'careers' ? 'weekly' : 'monthly', $prio);
}
foreach ($services as $s) { $add('services/' . $s['slug'], $s['updated_at'] ?? null, 'monthly', '0.9'); }
foreach ($hires as $h) { $add('hire/' . $h['slug'], $h['updated_at'] ?? null, 'monthly', '0.8'); }
foreach ($projects as $p) { $add('portfolio/' . $p['slug'], $p['updated_at'] ?? null, 'yearly', '0.5', [$img($p['image'] ?? null), $img($p['image_mobile'] ?? null)]); }
foreach ($jobs as $j) { $add('careers/' . $j['slug'], $j['updated_at'] ?? null, 'weekly', '0.5'); }
foreach ($posts as $p) { $add('blog/' . $p['slug'], $p['updated_at'], 'monthly', '0.7', [$img($p['image'] ?? null)]); }
if ($members) {
    if (setting('team_list', '1') === '1') { $add('team', null, 'monthly', '0.5'); }
    foreach ($members as $m) { $add('team/' . $m['slug'], $m['updated_at'], 'monthly', '0.5', [$img($m['photo'] ?? null)]); }
}
foreach (['privacy-policy', 'terms', 'cookie-policy'] as $p) { $add($p, null, 'yearly', '0.2'); }
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach ($urls as [$loc, $mod, $freq, $prio, $images]): ?>
  <url><loc><?= e($loc) ?></loc><?php if ($mod): ?><lastmod><?= $mod ?></lastmod><?php endif; ?><changefreq><?= $freq ?></changefreq><priority><?= $prio ?></priority><?php foreach ($images as $im): ?><image:image><image:loc><?= e($im) ?></image:loc></image:image><?php endforeach; ?></url>
<?php endforeach; ?>
</urlset>
