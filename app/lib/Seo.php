<?php
declare(strict_types=1);

/** Search engine helpers: indexing switch, structured data, IndexNow pings and safe Markdown for blog posts. */
final class Seo
{
    private static array $pending = [];

    /** False while the site is being built or tested, so search engines stay away. */
    public static function indexingOn(): bool
    {
        return setting('search_indexing', '1') === '1';
    }

    /** Key for IndexNow (Bing, Yandex, Seznam, Naver). Stable for the site, served at /indexnow-key.txt. */
    public static function indexNowKey(): string
    {
        return substr(hash('sha256', 'indexnow' . config('app_key')), 0, 32);
    }

    /** Tell IndexNow search engines a page changed. Sent after the response so admins never wait. */
    public static function ping(array $urls): void
    {
        if (!self::indexingOn() || config('env') !== 'production' || !Security::isHttps()) {
            return;
        }
        if (!self::$pending) {
            register_shutdown_function([self::class, 'flush']);
        }
        self::$pending = array_values(array_unique(array_merge(self::$pending, $urls)));
    }

    public static function flush(): void
    {
        if (!self::$pending) {
            return;
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        $body = json_encode([
            'host' => parse_url(base_url(), PHP_URL_HOST),
            'key' => self::indexNowKey(),
            'keyLocation' => absolute_url('indexnow-key.txt'),
            'urlList' => self::$pending,
        ], JSON_UNESCAPED_SLASHES);
        self::$pending = [];
        $ctx = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json; charset=utf-8\r\n",
            'content' => $body,
            'timeout' => 4,
            'ignore_errors' => true,
        ]]);
        @file_get_contents('https://api.indexnow.org/indexnow', false, $ctx);
    }

    /** BreadcrumbList data from [label => path] pairs. The home page is added first. */
    public static function breadcrumbs(array $items): array
    {
        $list = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => absolute_url()]];
        $i = 1;
        foreach ($items as $name => $path) {
            $list[] = ['@type' => 'ListItem', 'position' => ++$i, 'name' => (string) $name, 'item' => absolute_url($path)];
        }
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
    }

    public static function jsonLd(array $data): string
    {
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
    }

    /** Organization id used to link every schema block to the company. */
    public static function orgId(): string
    {
        return absolute_url() . '#organization';
    }

    /**
     * A small, safe Markdown subset for blog posts. All text is escaped first, so no HTML can be injected.
     * Supports: ## and ### headings, paragraphs, - and 1. lists, > quotes, **bold**, *italic*, `code`,
     * fenced code blocks, [links](https://...) and ![images](/uploads/...).
     */
    public static function markdown(string $text): string
    {
        $text = str_replace("\r\n", "\n", trim($text));
        $out = [];
        $code = null;
        $para = [];
        $list = null;
        $items = [];
        // A single Enter keeps a line break, a blank line starts a new paragraph.
        $flushPara = function () use (&$para, &$out): void {
            if ($para) {
                $out[] = '<p>' . implode('<br>', array_map([self::class, 'inline'], $para)) . '</p>';
                $para = [];
            }
        };
        $boxes = 0;
        $flushList = function () use (&$list, &$items, &$out): void {
            if ($list) {
                $out[] = "<$list>" . implode('', array_map(fn ($i) => '<li>' . self::inline($i) . '</li>', $items)) . "</$list>";
                $list = null;
                $items = [];
            }
        };
        foreach (explode("\n", $text) as $line) {
            if ($code !== null) {
                if (rtrim($line) === '```') {
                    $out[] = '<pre><code>' . e(implode("\n", $code)) . '</code></pre>';
                    $code = null;
                } else {
                    $code[] = $line;
                }
                continue;
            }
            $t = trim($line);
            if (preg_match('/^:::\s*(justify|center|right)$/i', $t, $mm)) {
                $flushPara();
                $flushList();
                $out[] = '<div class="text-' . strtolower($mm[1]) . '">';
                $boxes++;
                continue;
            }
            if (preg_match('/^(-{3,}|\*{3,})$/', $t)) {
                $flushPara();
                $flushList();
                $out[] = '<hr>';
                continue;
            }
            if ($t === ':::' && $boxes > 0) {
                $flushPara();
                $flushList();
                $out[] = '</div>';
                $boxes--;
                continue;
            }
            if (str_starts_with($t, '```')) {
                $flushPara();
                $flushList();
                $code = [];
            } elseif ($t === '') {
                $flushPara();
                $flushList();
            } elseif (preg_match('/^(#{2,4})\s+(.+)$/', $t, $m)) {
                $flushPara();
                $flushList();
                $level = strlen($m[1]);
                $out[] = "<h$level id=\"" . e(slugify($m[2])) . "\">" . self::inline($m[2]) . "</h$level>";
            } elseif (preg_match('/^[-*]\s+(.+)$/', $t, $m) || preg_match('/^\d+[.)]\s+(.+)$/', $t, $m)) {
                $flushPara();
                $type = preg_match('/^\d/', $t) ? 'ol' : 'ul';
                if ($list !== $type) {
                    $flushList();
                    $list = $type;
                }
                $items[] = $m[1];
            } elseif (str_starts_with($t, '>')) {
                $flushPara();
                $flushList();
                $out[] = '<blockquote><p>' . self::inline(ltrim(substr($t, 1))) . '</p></blockquote>';
            } else {
                $flushList();
                $para[] = $t;
            }
        }
        if ($code !== null) {
            $out[] = '<pre><code>' . e(implode("\n", $code)) . '</code></pre>';
        }
        $flushPara();
        $flushList();
        $out = array_merge($out, array_fill(0, $boxes, '</div>'));
        return implode("\n", $out);
    }

    /** Headings of a post for the table of contents: [[id, text], ...]. */
    public static function headings(string $text): array
    {
        preg_match_all('/^##\s+(.+)$/m', $text, $m);
        return array_map(fn ($h) => [slugify(trim($h)), trim(preg_replace('/[*`]/', '', $h) ?? '')], $m[1]);
    }

    public static function plainText(string $markdown): string
    {
        $t = preg_replace(['/```.*?```/s', '/!\[[^\]]*\]\([^)]*\)/', '/\[([^\]]+)\]\([^)]*\)/', '/^:::.*$/m', '/[#*`>]/'], ['', '', '$1', '', ''], $markdown) ?? '';
        return trim(preg_replace('/\s+/', ' ', $t) ?? '');
    }

    public static function readingMinutes(string $markdown): int
    {
        return max(1, (int) ceil(str_word_count(self::plainText($markdown)) / 220));
    }

    /**
     * Inline formatting. Code, images and links are cut out into placeholders first, built as complete HTML,
     * and put back only at the end, so no later rule can ever run inside an attribute.
     */
    public static function inline(string $s): string
    {
        $s = str_replace("\x00", '', $s);
        $parts = [];
        $hold = function (string $html) use (&$parts): string {
            $parts[] = $html;
            return "\x00" . (count($parts) - 1) . "\x00";
        };
        $safeUrl = fn (string $u) => !preg_match('/[\[\]"\'<>\s\\`]/', $u);
        $s = preg_replace_callback('/`([^`]+)`/', fn ($m) => $hold('<code>' . e($m[1]) . '</code>'), $s) ?? $s;
        $s = preg_replace_callback('/!\[([^\]\x00]*)\]\(([^)\s\x00]+)\)/', function ($m) use ($hold, $safeUrl) {
            $src = $m[2];
            if (!$safeUrl($src) || !preg_match('#^(https://|/uploads/|uploads/)#', $src)) {
                return '';
            }
            $src = str_starts_with($src, 'https://') ? $src : url(ltrim($src, '/'));
            return $hold('<img src="' . e($src) . '" alt="' . e($m[1]) . '" loading="lazy" decoding="async">');
        }, $s) ?? $s;
        $s = preg_replace_callback('/\[([^\]\x00]+)\]\(([^)\s\x00]+)\)/', function ($m) use ($hold, $safeUrl) {
            $href = $m[2];
            $text = e($m[1]);
            if (!$safeUrl($href)) {
                return $text;
            }
            if (preg_match('~^/[a-z0-9/#_-]*$~i', $href)) {
                return $hold('<a href="' . e(url(ltrim($href, '/'))) . '">' . $text . '</a>');
            }
            if (!preg_match('#^(https?://|mailto:)#i', $href)) {
                return $text;
            }
            $own = str_starts_with($href, base_url());
            return $hold('<a href="' . e($href) . '"' . ($own ? '' : ' rel="noopener" target="_blank"') . '>' . $text . '</a>');
        }, $s) ?? $s;
        // Escape everything that is still plain text (placeholders contain only \x00 and digits, so they survive).
        $s = implode('', array_map(fn ($chunk) => preg_match('/^\x00\d+\x00$/', $chunk) ? $chunk : e($chunk), preg_split('/(\x00\d+\x00)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE) ?: []));
        $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?!\w)/', '<em>$1</em>', $s) ?? $s;
        return preg_replace_callback('/\x00(\d+)\x00/', fn ($m) => $parts[(int) $m[1]] ?? '', $s) ?? $s;
    }
}

/** Countries the company serves, from the offices list, for schema.org areaServed. */
function seo_area_served(): array
{
    $countries = array_values(array_unique(array_filter(array_column(Content::offices(), 'country'))));
    return array_map(fn ($c) => ['@type' => 'Country', 'name' => $c], $countries);
}

function service_schema(array $item, string $path): array
{
    return array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $item['title'],
        'serviceType' => $item['short_title'],
        'description' => $item['summary'],
        'url' => absolute_url($path),
        'provider' => ['@id' => Seo::orgId()],
        'areaServed' => seo_area_served() ?: null,
    ]);
}

function article_schema(array $post): array
{
    $img = $post['image'] !== '' ? absolute_url($post['image']) : absolute_url('assets/img/og-image.jpg');
    return [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => mb_substr($post['title'], 0, 110),
        'description' => $post['excerpt'],
        'image' => [$img],
        'datePublished' => $post['published_at'],
        'dateModified' => gmdate('c', strtotime($post['updated_at'] . ' UTC')),
        'mainEntityOfPage' => absolute_url('blog/' . $post['slug']),
        'articleSection' => $post['category'],
        'wordCount' => str_word_count(Seo::plainText($post['body'])),
        'author' => $post['author_name'] !== ''
            ? ['@type' => str_ends_with($post['author_name'], 'Team') ? 'Organization' : 'Person', 'name' => $post['author_name'], 'url' => absolute_url('about')]
            : ['@id' => Seo::orgId()],
        'publisher' => ['@id' => Seo::orgId()],
    ];
}

function job_schema(array $job): array
{
    $types = ['full-time' => 'FULL_TIME', 'part-time' => 'PART_TIME', 'internship' => 'INTERN', 'contract' => 'CONTRACTOR', 'temporary' => 'TEMPORARY'];
    $type = $types[strtolower(trim($job['job_type']))] ?? 'FULL_TIME';
    $desc = '<p>' . e($job['summary']) . '</p>';
    foreach (['Responsibilities' => 'responsibilities', 'Requirements' => 'requirements'] as $label => $col) {
        $items = lines($job[$col]);
        if ($items) {
            $desc .= '<p><strong>' . $label . '</strong></p><ul>' . implode('', array_map(fn ($l) => '<li>' . e($l) . '</li>', $items)) . '</ul>';
        }
    }
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'JobPosting',
        'title' => $job['title'],
        'description' => $desc,
        'datePosted' => gmdate('Y-m-d', strtotime($job['created_at'] . ' UTC')),
        'validThrough' => gmdate('Y-m-d\TH:i:s\Z', max(time(), strtotime($job['updated_at'] . ' UTC')) + 60 * 86400),
        'employmentType' => $type,
        'hiringOrganization' => ['@type' => 'Organization', 'name' => setting('site_name', 'AivraSol'), 'sameAs' => absolute_url(), 'logo' => absolute_url('assets/img/apple-touch-icon.png')],
        'directApply' => true,
    ];
    $loc = trim($job['location']);
    if ($loc === '' || stripos($loc, 'remote') !== false) {
        $data['jobLocationType'] = 'TELECOMMUTE';
        $data['applicantLocationRequirements'] = seo_area_served() ?: ['@type' => 'Country', 'name' => 'Pakistan'];
    } else {
        $parts = array_map('trim', explode(',', $loc));
        $data['jobLocation'] = ['@type' => 'Place', 'address' => array_filter([
            '@type' => 'PostalAddress',
            'addressLocality' => count($parts) > 1 ? $parts[0] : null,
            'addressCountry' => end($parts),
        ])];
    }
    return $data;
}
