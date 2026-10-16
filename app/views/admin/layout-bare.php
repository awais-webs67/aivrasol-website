<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($pageTitle ?? 'Admin') ?> | <?= e(setting('site_name', 'AivraSol')) ?> admin</title>
<link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin-bare">
<main class="auth-box">
  <img class="auth-logo" src="<?= e(asset('assets/img/logo.svg')) ?>" alt="<?= e(setting('site_name', 'AivraSol')) ?>" width="160" height="31">
  <?= $content ?>
</main>
</body>
</html>
