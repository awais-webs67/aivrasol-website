<?php /** @var string $content @var ?array $client */ $site = setting('site_name', 'AivraSol'); $bare = !empty($bare); ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($pageTitle ?? 'Client portal') ?> | <?= e($site) ?> client portal</title>
<link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/portal.css')) ?>">
<script src="<?= e(asset('assets/js/portal.js')) ?>" defer></script>
</head>
<body class="portal<?= $bare ? ' portal-bare' : '' ?>">
<a class="skip-link" href="#portal-main">Skip to content</a>
<?php if ($bare): ?>
<main class="portal-auth" id="portal-main">
  <a href="<?= e(url('/')) ?>"><img class="portal-auth-logo" src="<?= e(asset('assets/img/logo.svg')) ?>" alt="<?= e($site) ?>" width="160" height="31"></a>
  <?= $content ?>
</main>
<?php else: ?>
<header class="portal-top">
  <div class="container portal-top-inner">
    <a class="portal-brand" href="<?= e(url('portal')) ?>"><img src="<?= e(asset('assets/img/logo.svg')) ?>" alt="<?= e($site) ?>" width="130" height="25"><span>Client portal</span></a>
    <nav class="portal-nav" aria-label="Portal">
      <a href="<?= e(url('portal')) ?>"<?= in_array(trim(request_path(), '/'), ['portal'], true) || str_starts_with(trim(request_path(), '/'), 'portal/projects') ? ' aria-current="page"' : '' ?>>Projects</a>
      <a href="<?= e(url('portal/account')) ?>"<?= trim(request_path(), '/') === 'portal/account' ? ' aria-current="page"' : '' ?>>Account</a>
      <form method="post" action="<?= e(url('portal/logout')) ?>"><?= csrf_field() ?><button type="submit">Sign out</button></form>
    </nav>
  </div>
</header>
<main class="portal-main" id="portal-main" tabindex="-1">
  <div class="container">
    <?php if ($m = flash('success')): ?><p class="form-status is-success" role="status"><?= e($m) ?></p><?php endif; ?>
    <?php if ($m = flash('error')): ?><p class="form-status is-error" role="alert"><?= e($m) ?></p><?php endif; ?>
    <?= $content ?>
  </div>
</main>
<footer class="portal-foot"><div class="container small muted">Need help? Email <a href="mailto:<?= e(setting('email_info')) ?>"><?= e(setting('email_info')) ?></a><?php if (setting('whatsapp') !== ''): ?> or <a href="<?= e(whatsapp_href(setting('whatsapp'))) ?>" target="_blank" rel="noopener">WhatsApp us</a><?php endif; ?>.</div></footer>
<?php endif; ?>
</body>
</html>
