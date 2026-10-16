<?php
declare(strict_types=1);

/**
 * Handles every public form. Order of checks: CSRF, bot check, rate limit, validation, save, notify.
 * Works with JavaScript (JSON replies) and without it (redirect back with a message).
 */

function form_back(): string
{
    $back = (string) ($_POST['_back'] ?? '/');
    // Only allow local paths, never another site.
    if (!preg_match('#^/[A-Za-z0-9/_\-]*(\#[a-z0-9-]+)?$#', $back) || str_starts_with($back, '//')) {
        $back = '/';
    }
    return $back;
}

function form_fail(string $message, array $errors = [], int $code = 422): never
{
    if (wants_json()) {
        json_response(['ok' => false, 'message' => $message, 'errors' => $errors], $code);
    }
    $_SESSION['_old'] = array_map(fn ($v) => is_string($v) ? mb_substr($v, 0, 2000) : '', $_POST);
    unset($_SESSION['_old']['_csrf'], $_SESSION['_old']['_ts'], $_SESSION['_old']['website']);
    flash('form_error', $message . ($errors ? ' ' . implode(' ', $errors) : ''));
    redirect(url(form_back()));
}

function form_ok(string $message): never
{
    clear_old();
    if (wants_json()) {
        json_response(['ok' => true, 'message' => $message]);
    }
    flash('form_success', $message);
    redirect(url(form_back()));
}

function form_guard(string $bucket, int $max, int $window): void
{
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        form_fail('Your session expired. Please refresh the page and try again.', [], 419);
    }
    if (!Security::looksHuman($_POST)) {
        // Pretend success so bots learn nothing.
        form_ok('Thank you. We will be in touch soon.');
    }
    if (!Security::rateLimit('form_' . $bucket, $max, $window)) {
        form_fail('You have sent several requests in a short time. Please wait a few minutes and try again.', [], 429);
    }
}

function save_lead(string $type, array $data, array $details = []): int
{
    $id = Database::insert('leads', [
        'type' => $type,
        'name' => $data['name'],
        'email' => strtolower($data['email']),
        'phone' => $data['phone'] ?? '',
        'company' => $data['company'] ?? '',
        'message' => $data['message'] ?? null,
        'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
        'source_page' => mb_substr(form_back(), 0, 255),
        'ip_hash' => Security::ipHash(),
        'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);

    $labels = ['contact' => 'Contact form', 'consultation' => 'Free consultation', 'quote' => 'Quote request', 'hire' => 'Hire a developer'];
    $body = "New {$labels[$type]} on the AivraSol website\n\n";
    foreach (['name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'company' => 'Company'] as $k => $label) {
        if (!empty($data[$k])) {
            $body .= "$label: {$data[$k]}\n";
        }
    }
    foreach ($details as $k => $v) {
        $body .= ucfirst(str_replace('_', ' ', (string) $k)) . ": $v\n";
    }
    if (!empty($data['message'])) {
        $body .= "\nMessage:\n{$data['message']}\n";
    }
    $body .= "\nPage: " . form_back() . "\nView in admin: " . absolute_url('admin/leads/' . $id) . "\n";
    Mailer::send(setting('notify_email', setting('email_info')), "New lead: {$labels[$type]} from {$data['name']}", $body, $data['email']);
    return $id;
}

function handle_consultation(): never
{
    form_guard('consultation', 8, 600);
    $v = (new Validator($_POST))
        ->check('name', 'Name', 'required|max:100')
        ->check('email', 'Email', 'required|email|max:190')
        ->check('message', 'Project idea', 'max:2000|nolinks');
    if ($v->fails()) {
        form_fail('Please check the highlighted fields.', $v->errors());
    }
    save_lead('consultation', $v->clean());
    form_ok('Thank you. An expert will contact you within one business day.');
}

function handle_contact(): never
{
    form_guard('contact', 8, 600);
    $interestOptions = array_merge(
        array_column(Content::offerings('service'), 'short_title'),
        array_column(Content::offerings('hire'), 'short_title'),
        ['General question']
    );
    $v = (new Validator($_POST))
        ->options('interest', $interestOptions)
        ->check('company', 'Company', 'max:120')
        ->check('name', 'Name', 'required|max:100')
        ->check('email', 'Email', 'required|email|max:190')
        ->check('phone', 'Phone number', 'phone|max:40')
        ->check('interest', 'Interest', 'oneof')
        ->check('message', 'Message', 'required|min:10|max:5000|nolinks')
        ->check('terms', 'the privacy policy', 'accepted');
    if ($v->fails()) {
        form_fail('Please check the highlighted fields.', $v->errors());
    }
    $data = $v->clean();
    $isHire = in_array($data['interest'], array_column(Content::offerings('hire'), 'short_title'), true);
    save_lead($isHire ? 'hire' : 'contact', $data, $data['interest'] !== '' ? ['interest' => $data['interest']] : []);
    form_ok('Thank you for your message. Our team will reply within one business day.');
}

function handle_quote(): never
{
    form_guard('quote', 8, 600);
    $plan = Content::plan((int) ($_POST['plan_id'] ?? 0));
    if (!$plan) {
        form_fail('Please choose a plan.', ['plan_id' => 'Please choose a plan.']);
    }
    $v = (new Validator($_POST))
        ->options('q1', lines($plan['q1_options']))
        ->options('q2', lines($plan['q2_options']))
        ->check('q1', $plan['q1_label'], 'required|oneof')
        ->check('q2', $plan['q2_label'], 'required|oneof')
        ->check('name', 'Name', 'required|max:100')
        ->check('email', 'Email', 'required|email|max:190')
        ->check('phone', 'Phone number', 'phone|max:40')
        ->check('company', 'Company', 'max:120')
        ->check('message', 'Project details', 'max:3000|nolinks');
    if ($v->fails()) {
        form_fail('Please check the highlighted fields.', $v->errors());
    }
    $data = $v->clean();
    save_lead('quote', $data, [
        'plan' => $plan['title'],
        rtrim($plan['q1_label'], '?') => $data['q1'],
        rtrim($plan['q2_label'], '?') => $data['q2'],
    ]);
    form_ok('Thank you. We will send your free quote within one business day.');
}

function handle_application(): never
{
    form_guard('apply', 3, 3600);
    $job = null;
    $jobSlug = (string) ($_POST['job'] ?? '');
    if ($jobSlug !== '' && $jobSlug !== 'general') {
        $job = Content::job($jobSlug);
        if (!$job) {
            form_fail('That job is no longer open. You can still send a general application.');
        }
    }
    $v = (new Validator($_POST))
        ->check('name', 'Full name', 'required|max:100')
        ->check('email', 'Email', 'required|email|max:190')
        ->check('phone', 'Phone number', 'required|phone|max:40')
        ->check('profile_url', 'Portfolio or LinkedIn link', 'url|max:255')
        ->check('message', 'Cover note', 'max:3000|nolinks')
        ->check('terms', 'the privacy policy', 'accepted');
    if ($v->fails()) {
        form_fail('Please check the highlighted fields.', $v->errors());
    }
    try {
        [$stored, $original] = Upload::cv($_FILES['cv'] ?? []);
    } catch (InvalidArgumentException $e) {
        form_fail($e->getMessage(), ['cv' => $e->getMessage()]);
    }
    $data = $v->clean();
    $id = Database::insert('applications', [
        'job_id' => $job['id'] ?? null,
        'position' => $job['title'] ?? 'General application',
        'name' => $data['name'],
        'email' => strtolower($data['email']),
        'phone' => $data['phone'],
        'profile_url' => $data['profile_url'],
        'message' => $data['message'] ?: null,
        'cv_file' => $stored,
        'cv_original' => $original,
        'ip_hash' => Security::ipHash(),
    ]);
    Mailer::send(
        setting('email_hr', setting('email_info')),
        'New job application: ' . ($job['title'] ?? 'General application') . ' from ' . $data['name'],
        "Name: {$data['name']}\nEmail: {$data['email']}\nPhone: {$data['phone']}\nPosition: " . ($job['title'] ?? 'General application')
            . "\n\nView and download the CV in admin: " . absolute_url('admin/applications/' . $id) . "\n",
        $data['email']
    );
    form_ok('Thank you for applying. Our HR team will review your CV and get back to you.');
}
