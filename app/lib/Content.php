<?php
declare(strict_types=1);

/** Read-only queries used by the public pages. Results are cached per request. */
final class Content
{
    private static array $cache = [];

    private static function remember(string $key, callable $fn)
    {
        return self::$cache[$key] ??= $fn();
    }

    public static function offerings(string $type): array
    {
        return self::remember("off_$type", fn () => Database::all(
            'SELECT * FROM offerings WHERE type = ? AND is_active = 1 ORDER BY sort_order, id',
            [$type]
        ));
    }

    public static function offering(string $type, string $slug): ?array
    {
        foreach (self::offerings($type) as $row) {
            if ($row['slug'] === $slug) {
                return $row;
            }
        }
        return null;
    }

    public static function projects(?int $limit = null): array
    {
        $rows = self::remember('projects', fn () => Database::all(
            'SELECT * FROM projects WHERE is_active = 1 ORDER BY is_featured DESC, sort_order, id'
        ));
        return $limit ? array_slice($rows, 0, $limit) : $rows;
    }

    public static function project(string $slug): ?array
    {
        foreach (self::projects() as $row) {
            if ($row['slug'] === $slug) {
                return $row;
            }
        }
        return null;
    }

    public static function testimonials(): array
    {
        return self::remember('testimonials', fn () => Database::all(
            'SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order, id'
        ));
    }

    /** Active plans. $filter: null (all), 'project', 'hire' or 'home'. */
    public static function plans(?string $filter = null): array
    {
        $rows = self::remember('plans', fn () => Database::all(
            'SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order, id'
        ));
        if ($filter === null) {
            return $rows;
        }
        return array_values(array_filter($rows, fn ($p) => $filter === 'home' ? (int) $p['show_on_home'] === 1 : $p['category'] === $filter));
    }

    /** Dropdown options for a plan question. "[hire-roles]" becomes the list of live hire pages. */
    public static function planOptions(array $plan, string $q): array
    {
        $out = [];
        foreach (lines($plan[$q . '_options']) as $line) {
            if ($line === '[hire-roles]') {
                array_push($out, ...array_column(self::offerings('hire'), 'short_title'));
            } else {
                $out[] = $line;
            }
        }
        $out = array_values(array_unique($out));
        if (!array_filter($out, 'is_other_option')) {
            $out[] = 'Other';
        }
        return $out;
    }

    public static function plan(int $id): ?array
    {
        foreach (self::plans() as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }
        return null;
    }

    public static function jobs(): array
    {
        return self::remember('jobs', fn () => Database::all(
            'SELECT * FROM jobs WHERE is_active = 1 ORDER BY sort_order, created_at DESC'
        ));
    }

    public static function job(string $slug): ?array
    {
        foreach (self::jobs() as $row) {
            if ($row['slug'] === $slug) {
                return $row;
            }
        }
        return null;
    }

    /** Live blog posts, newest first. Posts with a future date stay hidden. */
    public static function posts(): array
    {
        return self::remember('posts', fn () => Database::all(
            'SELECT id, title, slug, category, excerpt, image, author_name, author_role, published_at, updated_at, body FROM posts WHERE is_active = 1 AND published_at IS NOT NULL AND published_at <= UTC_DATE() ORDER BY published_at DESC, id DESC'
        ));
    }

    public static function post(string $slug): ?array
    {
        if (!self::posts()) {
            return null;
        }
        $row = Database::one('SELECT * FROM posts WHERE slug = ? AND is_active = 1 AND published_at IS NOT NULL AND published_at <= UTC_DATE()', [$slug]);
        return $row ?: null;
    }

    public static function faqs(string $scope): array
    {
        return self::remember("faq_$scope", fn () => Database::all(
            'SELECT question, answer FROM faqs WHERE scope = ? AND is_active = 1 ORDER BY sort_order, id',
            [$scope]
        ));
    }

    /** Live offices, head office first. Each row also gets a map link. */
    public static function offices(): array
    {
        return self::remember('offices', function () {
            try {
                $rows = Database::all('SELECT * FROM offices WHERE is_active = 1 ORDER BY is_head_office DESC, sort_order, id');
            } catch (Throwable $e) {
                return [];
            }
            foreach ($rows as &$r) {
                $place = trim(implode(', ', array_filter([$r['address'], $r['city'], $r['country']])));
                $r['map'] = $r['map_url'] !== '' && preg_match('#^https://#i', $r['map_url'])
                    ? $r['map_url']
                    : ($r['address'] !== '' || $r['city'] !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($place) : '');
            }
            return $rows;
        });
    }

    public static function stats(): array
    {
        $stats = [];
        foreach ([
            'stat_projects' => 'Projects delivered',
            'stat_clients' => 'Happy clients',
            'stat_team' => 'Team members',
            'stat_years' => 'Years of experience',
        ] as $key => $label) {
            if (setting($key) !== '') {
                $stats[] = ['value' => setting($key), 'label' => $label];
            }
        }
        return $stats;
    }

    public static function socials(): array
    {
        $out = [];
        foreach (['linkedin' => 'LinkedIn', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'x' => 'X', 'github' => 'GitHub'] as $key => $label) {
            $link = setting('social_' . $key);
            if ($link !== '' && preg_match('#^https://#i', $link)) {
                $out[] = ['icon' => $key, 'label' => $label, 'url' => $link];
            }
        }
        return $out;
    }
}
