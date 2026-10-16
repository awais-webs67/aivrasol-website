<?php
/** @var string $content */
$siteName = setting('site_name', 'AivraSol');
$pageTitle = $pageTitle ?? $siteName;
$metaDescription = $metaDescription ?? setting('meta_description');
$canonical = absolute_url(request_path() === '/' ? '' : ltrim(request_path(), '/'));
$ogImage = absolute_url($ogImage ?? 'assets/img/og-image.jpg');
$gaId = setting('ga_measurement_id');
$org = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $siteName,
    'url' => absolute_url(),
    'logo' => absolute_url('assets/img/logo.svg'),
    'email' => setting('email_info'),
    'sameAs' => array_column(Content::socials(), 'url'),
];
if (setting('phone') !== '') {
    $org['telephone'] = setting('phone');
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta name="robots" content="<?= e($metaRobots ?? 'index, follow') ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="theme-color" content="#FF5B35">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
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
<script type="application/ld+json"><?= json_encode($org, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</head>
<body<?= $gaId !== '' ? ' data-ga="' . e($gaId) . '"' : '' ?>>
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
<a class="fab fab-top" href="#main" data-to-top aria-label="Back to top"><?= icon('arrow-up') ?></a>
<div class="toast" role="status" aria-live="polite" data-toast></div>
</body>
</html>
