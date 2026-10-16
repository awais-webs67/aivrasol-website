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

    $fields = ['Name' => $data['name'], 'Email' => $data['email'], 'Phone' => $data['phone'] ?? '', 'Company' => $data['company'] ?? ''];
    foreach ($details as $k => $v) {
        $fields[ucfirst(str_replace('_', ' ', (string) $k))] = $v;
    }
    $fields['Sent from page'] = form_back();
    $summary = array_map(fn ($v) => preg_replace('/^(other|something else):.*$/is', '$1 (as you described)', (string) $v), $details);
    send_form_emails($type, setting('notify_emails', setting('email_info')), $fields, $data['message'] ?? null, 'admin/leads/' . $id, $data['name'], $data['email'], $summary);
    return $id;
}

/**
 * Queues the team alert and the visitor's automatic reply. Both are sent after the visitor gets their answer.
 * The auto-reply never repeats the visitor's own message, so the form cannot be used to send spam to other people.
 */
function send_form_emails(string $type, string $recipients, array $fields, ?string $message, string $adminPath, string $name, string $email, array $summary): void
{
    $to = Mailer::addressList($recipients);
    if ($to) {
        [$subject, $text, $html] = Emails::alert($type, $fields, $message, $adminPath, Emails::safeName($name), $email);
        Mailer::queue($to, $subject, $text, $html, $email, $type . '_alert');
    }
    // At most 3 automatic replies per address per day.
    if (Security::rateLimit('autoreply', 3, 86400, hash_hmac('sha256', strtolower($email), (string) config('app_key')))) {
        $reply = Emails::autoReply($type, $name, ['Request' => Emails::TYPES[$type] ?? ''] + $summary);
        if ($reply) {
            [$subject, $text, $html] = $reply;
            Mailer::queue($email, $subject, $text, $html, setting('email_info') ?: null, $type . '_autoreply');
        }
    }
}

/**
 * When a dropdown answer is "Other", the visitor must type what they mean in the extra box.
 * The typed text is added to the answer, for example "Other: Shopify store migration".
 */
function apply_other(array &$data, array &$errors, string $field): void
{
    if (($data[$field] ?? '') === '' || !is_other_option($data[$field])) {
        return;
    }
    $raw = $_POST[$field . '_other'] ?? '';
    $text = is_string($raw) ? trim(preg_replace('/\s+/', ' ', str_replace("\0", '', $raw)) ?? '') : '';
    if ($text === '') {
        $errors[$field . '_other'] = 'Please tell us what you need.';
    } elseif (mb_strlen($text) > 150) {
        $errors[$field . '_other'] = 'Please keep this under 150 characters.';
    } elseif (preg_match('#https?://|www\.#i', $text)) {
        $errors[$field . '_other'] = 'Please describe it without links.';
    } else {
        $data[$field] .= ': ' . $text;
    }
}

function handle_consultation(): never
{
    form_guard('consultation', 15, 600);
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
    form_guard('contact', 15, 600);
    $interestOptions = array_merge(
        array_column(Content::offerings('service'), 'short_title'),
        array_column(Content::offerings('hire'), 'short_title'),
        ['General question', 'Other']
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
    $errors = [];
    apply_other($data, $errors, 'interest');
    if ($errors) {
        form_fail('Please check the highlighted fields.', $errors);
    }
    $isHire = in_array($data['interest'], array_column(Content::offerings('hire'), 'short_title'), true);
    save_lead($isHire ? 'hire' : 'contact', $data, $data['interest'] !== '' ? ['interest' => $data['interest']] : []);
    form_ok('Thank you for your message. Our team will reply within one business day.');
}

function handle_quote(): never
{
    form_guard('quote', 15, 600);
    $plan = Content::plan((int) ($_POST['plan_id'] ?? 0));
    if (!$plan) {
        form_fail('Please choose a plan.', ['plan_id' => 'Please choose a plan.']);
    }
    $v = (new Validator($_POST))
        ->options('q1', Content::planOptions($plan, 'q1'))
        ->options('q2', Content::planOptions($plan, 'q2'))
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
    $errors = [];
    apply_other($data, $errors, 'q1');
    apply_other($data, $errors, 'q2');
    if ($errors) {
        form_fail('Please check the highlighted fields.', $errors);
    }
    save_lead('quote', $data, [
        'plan' => $plan['title'],
        rtrim($plan['q1_label'], '?') => $data['q1'],
        rtrim($plan['q2_label'], '?') => $data['q2'],
    ]);
    form_ok('Thank you. We will send your free quote within one business day.');
}

function handle_application(): never
{
    form_guard('apply', 6, 3600);
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
    $position = $job['title'] ?? 'General application';
    send_form_emails('application', setting('notify_emails_hr', setting('email_hr', setting('email_info'))), [
        'Name' => $data['name'], 'Email' => $data['email'], 'Phone' => $data['phone'], 'Position' => $position,
        'Profile' => $data['profile_url'], 'CV file' => $original,
    ], $data['message'] ?: null, 'admin/applications/' . $id, $data['name'], $data['email'], ['Position' => $position]);
    form_ok('Thank you for applying. Our HR team will review your CV and get back to you.');
}
