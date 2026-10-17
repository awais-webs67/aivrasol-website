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
    'admin/employees' => ['users', 'People', 'employees'],
    'admin/messages' => ['mail', 'Messages', 'employees'],
    'admin/issuance' => ['award', 'Issuance', 'employees'],
    'admin/team-pages' => ['user', 'Team pages', 'users'],
    'admin/documents' => ['file', 'Document design', 'settings'],
], fn ($n) => $can($n[2]));
$system = array_filter([
    'admin/settings' => ['settings', 'Settings', 'settings'],
    'admin/email' => ['mail', 'Email and mailboxes', 'settings'],
    'admin/backups' => ['download', 'Backups', 'settings'],
    'admin/system' => ['check', 'System check', 'settings'],
], fn ($n) => $can($n[2]));
$mine = array_filter([
    'admin/profile' => ['user', 'My profile', 'profile'],
    'admin/inbox' => ['inbox', 'My messages', 'profile'],
], fn ($n) => $can($n[2]));
$myPage = $can('profile') ? Database::one('SELECT allowed FROM team_profiles WHERE user_id = ?', [$user['id']]) : null;
if ($myPage && (int) $myPage['allowed']) {
    $mine['admin/portfolio'] = ['layers', 'My portfolio page', 'profile'];
}
require_once APP_PATH . '/admin/messages.php';
$meEmp = $can('profile') ? Database::one('SELECT id FROM employees WHERE user_id = ?', [$user['id']]) : null;
$pills = [
    'admin/employees' => $can('users') ? (int) Database::value("SELECT COUNT(*) FROM employees WHERE approval = 'pending'") : 0,
    'admin/messages' => $can('employees') ? msg_unread() : 0,
    'admin/inbox' => $meEmp ? msg_unread((int) $meEmp['id']) : 0,
];
$current = trim(request_path(), '/');
$isOn = fn (string $p) => $p === 'admin' ? $current === 'admin' : str_starts_with($current, $p);
$unreadChat = $can('projects') ? (int) Database::value("SELECT COUNT(DISTINCT project_id) FROM project_messages WHERE sender_type = 'client' AND read_by_staff = 0") : 0;
$teamReview = $can('users') ? (int) Database::value('SELECT COUNT(*) FROM team_profiles WHERE pending = 1 AND allowed = 1') : 0;
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
    <div class="admin-side-top">
      <a class="admin-brand" href="<?= e(Auth::home($user)) ?>"><img src="<?= e(asset('assets/img/logo-white.svg')) ?>" alt="<?= e(setting('site_name', 'AivraSol')) ?>" width="130" height="25"><span>Admin</span></a>
      <button class="admin-side-close" type="button" data-admin-close aria-label="Close menu"><?= icon('close') ?></button>
    </div>
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
        <li><a href="<?= e(url($path)) ?>"<?= $isOn($path) ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span><?php if ($path === 'admin/team-pages' && $teamReview): ?><b class="pill"><?= $teamReview ?></b><?php elseif (!empty($pills[$path])): ?><b class="pill"><?= $pills[$path] ?></b><?php endif; ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <?php if ($system): ?>
      <p class="admin-side-label">System</p>
      <ul>
        <?php foreach ($system as $path => [$ic, $label]): ?>
        <li><a href="<?= e(url($path)) ?>"<?= $isOn($path) ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <p class="admin-side-label">Me</p>
      <ul>
        <?php foreach ($mine as $path => [$ic, $label]): ?>
        <li><a href="<?= e(url($path)) ?>"<?= $isOn($path) ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span><?php if (!empty($pills[$path])): ?><b class="pill"><?= $pills[$path] ?></b><?php endif; ?></a></li>
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
      <?php if (!empty($guide)): ?>
      <details class="guide">
        <summary class="btn btn-sm btn-outline guide-toggle"><?= icon('help') ?><span>How this works</span></summary>
        <div class="guide-body">
          <h2><?= e($guide['title']) ?></h2>
          <?php if (!empty($guide['intro'])): ?><p class="guide-intro"><?= e($guide['intro']) ?></p><?php endif; ?>
          <ol class="guide-steps">
            <?php foreach ($guide['sections'] as $sec): ?>
            <li>
              <h3><?= e($sec['h']) ?></h3>
              <?php if (!empty($sec['p'])): ?><p><?= e($sec['p']) ?></p><?php endif; ?>
              <?php if (!empty($sec['list'])): ?><ul><?php foreach ($sec['list'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul><?php endif; ?>
              <?php foreach ($sec['code'] ?? [] as $code): ?><code class="code-line"><?= e($code) ?></code><?php endforeach; ?>
            </li>
            <?php endforeach; ?>
          </ol>
        </div>
      </details>
      <?php endif; ?>
      <?php if ($m = flash('success')): ?><p class="form-status is-success" role="status"><?= e($m) ?></p><?php endif; ?>
      <?php if ($m = flash('error')): ?><p class="form-status is-error" role="alert"><?= e($m) ?></p><?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</div>
</body>
</html>
