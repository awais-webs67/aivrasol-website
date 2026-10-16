<?php
declare(strict_types=1);

require APP_PATH . '/admin/handlers.php';
require APP_PATH . '/admin/staff.php';
require APP_PATH . '/admin/clients.php';
require APP_PATH . '/admin/campaigns.php';
require APP_PATH . '/admin/system.php';

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

$router->get('/admin/clients', fn () => admin_clients());
$router->any('/admin/clients/new', fn () => admin_client_edit());
$router->any('/admin/clients/{id}', fn (string $id) => admin_client_edit($id));
$router->get('/admin/projects', fn () => admin_projects());
$router->any('/admin/projects/new', fn () => admin_project_edit());
$router->any('/admin/projects/{id}', fn (string $id) => admin_project_edit($id));
$router->post('/admin/projects/{id}/updates', fn (string $id) => admin_project_update_post($id));
$router->post('/admin/projects/{id}/updates/{uid}/delete', fn (string $id, string $uid) => admin_project_update_delete($id, $uid));
$router->post('/admin/projects/{id}/entries', fn (string $id) => admin_project_entry($id));
$router->post('/admin/projects/{id}/entries/{eid}/delete', fn (string $id, string $eid) => admin_project_entry_delete($id, $eid));
$router->any('/admin/projects/{id}/messages', fn (string $id) => admin_project_messages($id));
$router->get('/admin/project-files/{id}', fn (string $id) => admin_project_file($id));

$router->any('/admin/contacts', fn () => admin_contacts());
$router->get('/admin/campaigns', fn () => admin_campaigns());
$router->post('/admin/campaigns', fn () => admin_campaigns());
$router->post('/admin/campaigns/tick', fn () => admin_campaign_tick());
$router->any('/admin/campaigns/new', fn () => admin_campaign_edit());
$router->any('/admin/campaigns/{id}', fn (string $id) => admin_campaign_edit($id));

$router->any('/admin/backups', fn () => admin_backups());
$router->get('/admin/backups/download', fn () => admin_backup_download((string) ($_GET['name'] ?? '')));
$router->any('/admin/system', fn () => admin_system());
$router->post('/admin/preview', function () {
    $user = Auth::user();
    if (!$user || !Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        json_response(['ok' => false], 403);
    }
    $text = mb_substr(str_replace("\r\n", "\n", (string) ($_POST['text'] ?? '')), 0, 60000);
    json_response(['ok' => true, 'html' => Seo::markdown($text)]);
});
