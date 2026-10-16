<?php
declare(strict_types=1);

require APP_PATH . '/admin/handlers.php';

/** @var Router $router */
$router->any('/admin/login', fn () => admin_login());
$router->post('/admin/logout', fn () => admin_logout());
$router->any('/admin/setup', fn () => admin_setup());
$router->get('/admin', fn () => admin_dashboard());

$router->get('/admin/leads', fn () => admin_leads());
$router->get('/admin/leads/export', fn () => admin_leads_export());
$router->any('/admin/leads/{id}', fn (string $id) => admin_lead($id));

$router->get('/admin/applications', fn () => admin_applications());
$router->any('/admin/applications/{id}', fn (string $id) => admin_application($id));
$router->get('/admin/applications/{id}/cv', fn (string $id) => admin_application_cv($id));

$router->get('/admin/content/{key}', fn (string $key) => admin_content_list($key));
$router->any('/admin/content/{key}/new', fn (string $key) => admin_content_edit($key));
$router->any('/admin/content/{key}/{id}', fn (string $key, string $id) => admin_content_edit($key, $id));

$router->any('/admin/settings', fn () => admin_settings());
$router->any('/admin/email', fn () => admin_email());
$router->any('/admin/users', fn () => admin_users());
$router->any('/admin/account', fn () => admin_account());
