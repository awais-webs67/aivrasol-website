<?php
/** @var string $content */
$siteName = setting('site_name', 'AivraSol');
$pageTitle = $pageTitle ?? $siteName;
$metaDescription = $metaDescription ?? setting('meta_description');
$canonical = absolute_url(isset($canonicalPath) ? $canonicalPath : (request_path() === '/' ? '' : ltrim(request_path(), '/')));
$ogImage = absolute_url(!empty($ogImage) ? $ogImage : 'assets/img/og-image.jpg');
$robots = Seo::indexingOn() ? ($metaRobots ?? 'index, follow, max-image-preview:large, max-snippet:-1') : 'noindex, nofollow';
$gaId = setting('ga_measurement_id');
$adsId = setting('google_ads_id');
$pixelId = setting('meta_pixel_id');
$org = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    '@id' => Seo::orgId(),
    'name' => $siteName,
    'url' => absolute_url(),
    'logo' => absolute_url('assets/img/apple-touch-icon.png'),
    'image' => absolute_url('assets/img/og-image.jpg'),
    'description' => setting('company_summary') !== '' ? setting('company_summary') : setting('meta_description'),
    'slogan' => setting('site_tagline'),
    'email' => setting('email_info'),
    'telephone' => setting('phone') !== '' ? setting('phone') : null,
    'foundingDate' => setting('company_founded') !== '' ? setting('company_founded') : null,
    'sameAs' => array_column(Content::socials(), 'url') ?: null,
    'areaServed' => seo_area_served() ?: null,
    'knowsAbout' => array_column(Content::offerings('service'), 'short_title') ?: null,
]);
$locations = [];
foreach (Content::offices() as $o) {
    $locations[] = array_filter(['@type' => 'PostalAddress', 'addressCountry' => $o['country'], 'addressLocality' => $o['city'], 'streetAddress' => $o['address']]);
}
if ($locations) {
    $org['address'] = $locations;
}
$contacts = [];
foreach (['email_sales' => 'sales', 'email_info' => 'customer support', 'email_hr' => 'human resources'] as $k => $type) {
    if (setting($k) !== '') {
        $contacts[] = ['@type' => 'ContactPoint', 'contactType' => $type, 'email' => setting($k), 'availableLanguage' => ['English']];
    }
}
if ($contacts) {
    $org['contactPoint'] = $contacts;
}
$ld = [$org];
if (!empty($isHome)) {
    $ld[] = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $siteName, 'url' => absolute_url(), 'publisher' => ['@id' => Seo::orgId()], 'inLanguage' => 'en'];
}
if (!empty($crumbs)) {
    $ld[] = Seo::breadcrumbs($crumbs);
}
foreach ($schema ?? [] as $extra) {
    $ld[] = $extra;
}
$bodyData = '';
foreach (['ga' => $gaId, 'gads' => $adsId, 'gads-lead' => setting('google_ads_lead_label'), 'pixel' => $pixelId] as $k => $v) {
    if ($v !== '') {
        $bodyData .= ' data-' . $k . '="' . e($v) . '"';
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta name="robots" content="<?= e($robots) ?>">
<?php if (setting('google_site_verification') !== ''): ?><meta name="google-site-verification" content="<?= e(setting('google_site_verification')) ?>">
<?php endif; ?>
<?php if (setting('bing_site_verification') !== ''): ?><meta name="msvalidate.01" content="<?= e(setting('bing_site_verification')) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="theme-color" content="#FF5B35">
<meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
<meta property="og:locale" content="en_US">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta property="og:image:alt" content="<?= e($pageTitle) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($metaDescription) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php if (!empty($post)): ?><meta property="article:published_time" content="<?= e($post['published_at']) ?>">
<meta property="article:modified_time" content="<?= e(gmdate('c', strtotime($post['updated_at'] . ' UTC'))) ?>">
<meta property="article:section" content="<?= e($post['category']) ?>">
<?php endif; ?>
<link rel="alternate" type="application/rss+xml" title="<?= e($siteName) ?> blog" href="<?= e(absolute_url('blog/feed.xml')) ?>">
<link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="icon" href="<?= e(asset('assets/img/favicon-32.png')) ?>" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="<?= e(asset('assets/img/apple-touch-icon.png')) ?>">
<link rel="manifest" href="<?= e(url('site.webmanifest')) ?>">
<link rel="preload" href="<?= e(url('assets/fonts/bebas-neue.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(url('assets/fonts/inter-var.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<?php if (!empty($isHome)): $photo = site_photo(); ?>
<link rel="preload" as="image" href="<?= e($photo['src']) ?>"<?php if ($photo['srcset'] !== ''): ?> imagesrcset="<?= e($photo['srcset']) ?>" imagesizes="(min-width: 1024px) 500px, 92vw"<?php endif; ?>>
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
<?php foreach ($ld as $block): ?><?= Seo::jsonLd($block) ?>
<?php endforeach; ?>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</head>
<body<?= $bodyData ?>>
<a class="skip-link" href="#main">Skip to content</a>
<?= partial('header') ?>
<?php if ($msg = flash('form_success')): ?>
<div class="flash is-success" role="status"><div class="container"><?= e($msg) ?></div></div>
<?php endif; ?>
<?php if ($msg = flash('form_error')): ?>
<div class="flash is-error" role="alert"><div class="container"><?= e($msg) ?></div></div>
<?php endif; ?>
<main id="main" tabindex="-1">
<?= $content ?>
</main>
<?= partial('footer') ?>
<?= partial('modals') ?>
<?= partial('cookie') ?>
<button class="fab fab-cookie" type="button" data-cookie-open aria-label="Cookie settings"><?= icon('cookie') ?></button>
<?php $wa = setting('whatsapp'); $waOn = $wa !== '' && setting('whatsapp_button', '1') === '1'; ?>
<?php if ($waOn): ?><a class="fab fab-wa" href="<?= e(whatsapp_href($wa, setting('whatsapp_message'))) ?>" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp"><span class="fab-wa-label"><strong>Need help?</strong> Chat with us</span><span class="fab-wa-btn"><?= icon('whatsapp') ?></span></a><?php endif; ?>
<a class="fab fab-top<?= $waOn ? ' above-wa' : '' ?>" href="#main" data-to-top aria-label="Back to top"><?= icon('arrow-up') ?></a>
<div class="toast" role="status" aria-live="polite" data-toast></div>
</body>
</html>
