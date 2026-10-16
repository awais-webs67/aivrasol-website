<?php
$nav = [
    'admin' => ['home', 'Dashboard'],
    'admin/leads' => ['inbox', 'Leads'],
    'admin/applications' => ['briefcase', 'Applications'],
];
$contentNav = [];
foreach (admin_resources() as $k => $r) { $contentNav['admin/content/' . $k] = $r['label']; }
$current = trim(request_path(), '/');
$isOn = fn (string $p) => $p === 'admin' ? $current === 'admin' : str_starts_with($current, $p);
$newLeads = (int) Database::value("SELECT COUNT(*) FROM leads WHERE status = 'new'");
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($pageTitle ?? 'Admin') ?> | <?= e(setting('site_name', 'AivraSol')) ?> admin</title>
<link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
</head>
<body class="admin">
<a class="skip-link" href="#admin-main">Skip to content</a>
<div class="admin-shell">
  <aside class="admin-side" id="admin-side">
    <a class="admin-brand" href="<?= e(url('admin')) ?>"><img src="<?= e(asset('assets/img/logo-white.svg')) ?>" alt="<?= e(setting('site_name', 'AivraSol')) ?>" width="130" height="25"><span>Admin</span></a>
    <nav aria-label="Admin">
      <ul>
        <?php foreach ($nav as $path => [$ic, $label]): ?>
        <li><a href="<?= e(url($path)) ?>"<?= $isOn($path) ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span><?php if ($path === 'admin/leads' && $newLeads): ?><b class="pill"><?= $newLeads ?></b><?php endif; ?></a></li>
        <?php endforeach; ?>
      </ul>
      <p class="admin-side-label">Website content</p>
      <ul>
        <?php foreach ($contentNav as $path => $label): ?>
        <li><a href="<?= e(url($path)) ?>"<?= $isOn($path) ? ' aria-current="page"' : '' ?>><?= icon('file') ?><span><?= e($label) ?></span></a></li>
        <?php endforeach; ?>
      </ul>
      <p class="admin-side-label">System</p>
      <ul>
        <li><a href="<?= e(url('admin/settings')) ?>"<?= $isOn('admin/settings') ? ' aria-current="page"' : '' ?>><?= icon('settings') ?><span>Settings</span></a></li>
        <?php if (($user['role'] ?? '') === 'admin'): ?>
        <li><a href="<?= e(url('admin/email')) ?>"<?= $isOn('admin/email') ? ' aria-current="page"' : '' ?>><?= icon('mail') ?><span>Email</span></a></li>
        <?php endif; ?>
        <?php if (($user['role'] ?? '') === 'admin'): ?>
        <li><a href="<?= e(url('admin/users')) ?>"<?= $isOn('admin/users') ? ' aria-current="page"' : '' ?>><?= icon('users') ?><span>Admin users</span></a></li>
        <?php endif; ?>
        <li><a href="<?= e(url('admin/account')) ?>"<?= $isOn('admin/account') ? ' aria-current="page"' : '' ?>><?= icon('user') ?><span>My account</span></a></li>
        <li><a href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('external') ?><span>View website</span></a></li>
      </ul>
    </nav>
  </aside>
  <div class="admin-main">
    <header class="admin-top">
      <button class="admin-menu" type="button" aria-controls="admin-side" aria-expanded="false" data-admin-menu><?= icon('menu') ?><span class="sr-only">Menu</span></button>
      <h1><?= e($pageTitle ?? 'Admin') ?></h1>
      <form method="post" action="<?= e(url('admin/logout')) ?>" class="admin-logout">
        <?= csrf_field() ?>
        <span class="small muted"><?= e($user['name'] ?? '') ?></span>
        <button class="btn btn-sm btn-outline" type="submit"><?= icon('logout') ?>Sign out</button>
      </form>
    </header>
    <main id="admin-main" class="admin-content" tabindex="-1">
      <?php if ($m = flash('success')): ?><p class="form-status is-success" role="status"><?= e($m) ?></p><?php endif; ?>
      <?php if ($m = flash('error')): ?><p class="form-status is-error" role="alert"><?= e($m) ?></p><?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</div>
</body>
</html>
