<?php
declare(strict_types=1);

function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** Escape text for safe HTML output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    return rtrim((string) config('base_url', ''), '/');
}

/** Site-relative URL. Keeps links working when the site runs in a sub-folder. */
function url(string $path = ''): string
{
    $prefix = rtrim((string) parse_url(base_url(), PHP_URL_PATH), '/');
    return $prefix . '/' . ltrim($path, '/');
}

function absolute_url(string $path = ''): string
{
    $origin = preg_replace('#^(https?://[^/]+).*$#', '$1', base_url());
    return $origin . url($path);
}

/** Asset URL with a version stamp so browsers can cache files for a year. */
function asset(string $path): string
{
    $file = PUBLIC_PATH . '/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return url($path) . '?v=' . $version;
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (Database::all('SELECT setting_key, setting_value FROM settings') as $row) {
                $cache[$row['setting_key']] = (string) $row['setting_value'];
            }
        } catch (Throwable $e) {
            error_log('Settings load failed: ' . $e->getMessage());
        }
    }
    $value = $cache[$key] ?? '';
    return $value !== '' ? $value : $default;
}

/** Split a multi-line text field into trimmed, non-empty lines. */
function lines(?string $text): array
{
    $out = [];
    foreach (preg_split('/\R/', (string) $text) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

/** Split "Title | Description" lines into pairs. */
function pairs(?string $text): array
{
    $out = [];
    foreach (lines($text) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        $out[] = ['title' => $parts[0], 'text' => $parts[1] ?? ''];
    }
    return $out;
}

function csv_list(?string $text): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $text)), 'strlen'));
}

function render(string $template, array $data = []): string
{
    $file = VIEW_PATH . '/' . $template . '.php';
    if (!is_file($file)) {
        throw new RuntimeException('View not found: ' . $template);
    }
    extract($data, EXTR_SKIP);
    ob_start();
    try {
        include $file;
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }
    return (string) ob_get_clean();
}

function view(string $template, array $data = [], ?string $layout = 'layouts/main'): string
{
    $content = render($template, $data);
    if ($layout === null) {
        return $content;
    }
    return render($layout, $data + ['content' => $content]);
}

function partial(string $template, array $data = []): string
{
    return render('partials/' . $template, $data);
}

function redirect(string $to, int $code = 303): never
{
    header('Location: ' . $to, true, $code);
    exit;
}

function request_path(): string
{
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $prefix = rtrim((string) parse_url(base_url(), PHP_URL_PATH), '/');
    if ($prefix !== '' && str_starts_with($path, $prefix)) {
        $path = substr($path, strlen($prefix));
    }
    $path = '/' . trim($path, '/');
    return $path;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['_old'][$key] ?? $default);
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function abort(int $code): never
{
    http_response_code($code);
    echo view('pages/error', [
        'code' => $code,
        'pageTitle' => $code === 404 ? 'Page not found' : 'Error',
        'metaRobots' => 'noindex',
    ]);
    exit;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

function excerpt(string $text, int $length = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    return mb_strlen($text) > $length ? rtrim(mb_substr($text, 0, $length - 1)) . '…' : $text;
}

/** Split a "Line one|Line two" heading into HTML with a line break. */
function heading_lines(string $text): string
{
    return implode('<br>', array_map('e', array_map('trim', explode('|', $text))));
}

function phone_href(string $phone): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
}

function whatsapp_href(string $phone): string
{
    return 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone);
}

function is_active_path(string $path): bool
{
    $current = request_path();
    return $path === '/' ? $current === '/' : str_starts_with($current, $path);
}

/** Inline SVG icon. Icons are static markup, never user input. */
function icon(string $name, string $class = 'icon'): string
{
    static $icons = [
        'monitor' => '<rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/><circle cx="12" cy="10" r="2.2"/>',
        'phone' => '<rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18.5h2"/>',
        'pen' => '<path d="M4 20l4-1 10.5-10.5a2.1 2.1 0 0 0-3-3L5 16l-1 4z"/><path d="M14 7l3 3"/>',
        'cloud' => '<path d="M7 18a4.5 4.5 0 0 1-.6-8.96A6 6 0 0 1 18 10.5 3.75 3.75 0 0 1 17.5 18z"/>',
        'cpu' => '<rect x="6" y="6" width="12" height="12" rx="1.5"/><rect x="9.5" y="9.5" width="5" height="5"/><path d="M9 2.5v3.5M15 2.5v3.5M9 18v3.5M15 18v3.5M2.5 9h3.5M2.5 15h3.5M18 9h3.5M18 15h3.5"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L21 21"/>',
        'code' => '<path d="M8 7l-5 5 5 5M16 7l5 5-5 5M13.5 4l-3 16"/>',
        'box' => '<path d="M12 2.5l8.5 4.75v9.5L12 21.5l-8.5-4.75v-9.5z"/><path d="M3.5 7.25L12 12l8.5-4.75M12 12v9.5"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.2-5.5 6.5-5.5s5.9 1.9 6.5 5.5"/><circle cx="17" cy="9" r="2.5"/><path d="M17.5 14.5c2.2.3 3.6 1.9 4 4.5"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c.8-4.2 4-6.5 8-6.5s7.2 2.3 8 6.5"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-up' => '<path d="M12 19V5M6 11l6-6 6 6"/>',
        'chevron-down' => '<path d="M6 9l6 6 6-6"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'check' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="M3.5 6l8.5 7 8.5-7"/>',
        'call' => '<path d="M5 3.5h3.5l1.8 4.5-2.3 1.5a11 11 0 0 0 6.5 6.5l1.5-2.3 4.5 1.8V19a2 2 0 0 1-2 2A16.5 16.5 0 0 1 3 5.5a2 2 0 0 1 2-2z"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'cookie' => '<path d="M12 3a9 9 0 1 0 9 9 3 3 0 0 1-3.5-3A3 3 0 0 1 14 5.5 3 3 0 0 1 12 3z"/><circle cx="8.5" cy="10" r="1"/><circle cx="12" cy="15.5" r="1"/><circle cx="15.5" cy="12.5" r=".8"/><circle cx="8" cy="15" r=".7"/>',
        'quote' => '<path d="M10 7H6.5A2.5 2.5 0 0 0 4 9.5V12h4v5H4M20 7h-3.5A2.5 2.5 0 0 0 14 9.5V12h4v5h-4"/>',
        'star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="1.5"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M3 13h18"/>',
        'shield' => '<path d="M12 3l8 3v6c0 4.5-3.4 8.2-8 9-4.6-.8-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
        'rocket' => '<path d="M14 4c3 0 6 3 6 6l-7.5 7.5-6-6z"/><path d="M8 13l-3.5.5L3 17l4 4 3.5-1.5.5-3.5M15 9.5h.01"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 9.5h8M8 12.5h5"/>',
        'layers' => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
        'whatsapp' => '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.7z"/><path d="M9 8.5c0 3.5 3 6.5 6.5 6.5l1-1.5-2-1-1 .8a4.5 4.5 0 0 1-2.3-2.3l.8-1-1-2z"/>',
        'linkedin' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>',
        'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>',
        'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><circle cx="12" cy="12" r="4"/><path d="M17 7v.01"/>',
        'x' => '<path d="M4 4l16 16M20 4L4 20"/>',
        'github' => '<path d="M9 19c-4 1.3-4-2-6-2.5M15 21v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.3 4.3 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12 12 0 0 0-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.3 4.3 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/>',
        'download' => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'logout' => '<path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/>',
        'home' => '<path d="M4 11l8-7 8 7v9h-5v-6H9v6H4z"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M21.5 12h-3M5.5 12h-3M18.7 5.3l-2.1 2.1M7.4 16.6l-2.1 2.1M18.7 18.7l-2.1-2.1M7.4 7.4L5.3 5.3"/>',
        'inbox' => '<path d="M3 13l3-8h12l3 8v6H3z"/><path d="M3 13h5l1.5 2.5h5L16 13h5"/>',
        'file' => '<path d="M6 3h8l5 5v13H6z"/><path d="M14 3v5h5"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'eye' => '<path d="M2.5 12S6 5 12 5s9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z"/><circle cx="12" cy="12" r="3"/>',
    ];
    $paths = $icons[$name] ?? $icons['code'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
}
