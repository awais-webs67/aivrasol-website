<?php
$can = fn (string $cap) => Auth::can($cap, $user);
$main = array_filter([
    'admin' => ['home', 'Dashboard', 'dashboard'],
    'admin/leads' => ['inbox', 'Leads', 'leads'],
    'admin/applications' => ['briefcase', 'Applications', 'applications'],
], fn ($n) => $can($n[2]));
$contentNav = [];
foreach (admin_resources() as $k => $r) {
    if (($r['nav'] ?? true) && $can('content:' . $k)) {
        $contentNav['admin/content/' . $k] = $r['label'];
    }
}
$work = array_filter([
    'admin/campaigns' => ['mail', 'Email campaigns', 'campaigns'],
    'admin/contacts' => ['users', 'Contacts', 'campaigns'],
    'admin/clients' => ['briefcase', 'Clients', 'clients'],
    'admin/projects' => ['layers', 'Client projects', 'projects'],
], fn ($n) => $can($n[2]));
$people = array_filter([
    'admin/employees' => ['users', 'Employees', 'employees'],
    'admin/documents' => ['file', 'Letters and ID cards', 'settings'],
], fn ($n) => $can($n[2]));
$system = array_filter([
    'admin/settings' => ['settings', 'Settings', 'settings'],
    'admin/email' => ['mail', 'Email', 'settings'],
    'admin/users' => ['users', 'Team and roles', 'users'],
    'admin/profile' => ['user', 'My profile', 'profile'],
], fn ($n) => $can($n[2]));
$current = trim(request_path(), '/');
$isOn = fn (string $p) => $p === 'admin' ? $current === 'admin' : str_starts_with($current, $p);
$unreadChat = $can('projects') ? (int) Database::value("SELECT COUNT(DISTINCT project_id) FROM project_messages WHERE sender_type = 'client' AND read_by_staff = 0") : 0;
$newLeads = $can('leads') ? (int) Database::value("SELECT COUNT(*) FROM leads WHERE status = 'new'") : 0;
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
<?php if (!empty($extraCss)): ?><link rel="stylesheet" href="<?= e(asset($extraCss)) ?>">
<?php endif; ?>
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
<?php if (!empty($extraJs)): ?><script src="<?= e(asset($extraJs)) ?>" defer></script>
<?php endif; ?>
</head>
<body class="admin">
<a class="skip-link" href="#admin-main">Skip to content</a>
<div class="admin-shell">
  <aside class="admin-side" id="admin-side">
    <a class="admin-brand" href="<?= e(Auth::home($user)) ?>"><img src="<?= e(asset('assets/img/logo-white.svg')) ?>" alt="<?= e(setting('site_name', 'AivraSol')) ?>" width="130" height="25"><span>Admin</span></a>
    <nav aria-label="Admin">
      <?php if ($main): ?>
      <ul>
        <?php foreach ($main as $path => [$ic, $label]): ?>
        <li><a href="<?= e(url($path)) ?>"<?= $isOn($path) ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span><?php if ($path === 'admin/leads' && $newLeads): ?><b class="pill"><?= $newLeads ?></b><?php endif; ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <?php if ($contentNav): ?>
      <p class="admin-side-label">Website content</p>
      <ul>
        <?php foreach ($contentNav as $path => $label): ?>
        <li><a href="<?= e(url($path)) ?>"<?= $isOn($path) ? ' aria-current="page"' : '' ?>><?= icon('file') ?><span><?= e($label) ?></span></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <?php if ($work): ?>
      <p class="admin-side-label">Sales and clients</p>
      <ul>
        <?php foreach ($work as $path => [$ic, $label]): ?>
        <li><a href="<?= e(url($path)) ?>"<?= $isOn($path) ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span><?php if ($path === 'admin/projects' && $unreadChat): ?><b class="pill"><?= $unreadChat ?></b><?php endif; ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <?php if ($people): ?>
      <p class="admin-side-label">People</p>
      <ul>
        <?php foreach ($people as $path => [$ic, $label]): ?>
        <li><a href="<?= e(url($path)) ?>"<?= $isOn($path) ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <p class="admin-side-label">System</p>
      <ul>
        <?php foreach ($system as $path => [$ic, $label]): ?>
        <li><a href="<?= e(url($path)) ?>"<?= $isOn($path) ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span></a></li>
        <?php endforeach; ?>
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
