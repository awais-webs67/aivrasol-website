<?php
declare(strict_types=1);

require APP_PATH . '/admin/handlers.php';
require APP_PATH . '/admin/staff.php';

/** @var Router $router */
$router->any('/admin/login', fn () => admin_login());
$router->post('/admin/logout', fn () => admin_logout());
$router->any('/admin/setup', fn () => admin_setup());
$router->any('/admin/forgot', fn () => admin_forgot());
$router->any('/admin/reset/{token}', fn (string $token) => admin_reset($token));
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
$router->any('/admin/profile', fn () => admin_profile());
$router->get('/admin/account', fn () => redirect(url('admin/profile')));

$router->get('/admin/employees', fn () => admin_employees());
$router->any('/admin/employees/new', fn () => admin_employee_edit());
$router->any('/admin/employees/{id}', fn (string $id) => admin_employee_edit($id));
$router->post('/admin/employees/{id}/login', fn (string $id) => admin_employee_login($id));
$router->get('/admin/employees/{id}/photo', fn (string $id) => admin_employee_photo($id));
$router->get('/admin/employees/{id}/card', fn (string $id) => admin_employee_card($id));
$router->any('/admin/employees/{id}/letter', fn (string $id) => admin_employee_letter($id));
$router->get('/admin/letters/{id}', fn (string $id) => admin_document_pdf($id));
$router->post('/admin/letters/{id}/revoke', fn (string $id) => admin_document_revoke($id));
$router->any('/admin/documents', fn () => admin_documents_settings());
$router->get('/admin/documents/image/{which}', fn (string $which) => admin_documents_image($which));
$router->get('/admin/documents/sample/{kind}', fn (string $kind) => admin_documents_sample($kind));
