<?php
$urls = ['', 'services', 'hire', 'portfolio', 'pricing', 'careers', 'about', 'contact', 'privacy-policy', 'terms', 'cookie-policy'];
foreach ($services as $s) { $urls[] = 'services/' . $s['slug']; }
foreach ($hires as $h) { $urls[] = 'hire/' . $h['slug']; }
foreach ($projects as $p) { $urls[] = 'portfolio/' . $p['slug']; }
foreach ($jobs as $j) { $urls[] = 'careers/' . $j['slug']; }
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $u): ?>
  <url><loc><?= e(absolute_url($u)) ?></loc></url>
<?php endforeach; ?>
</urlset>
