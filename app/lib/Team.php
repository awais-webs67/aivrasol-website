<?php
declare(strict_types=1);

/**
 * Personal portfolio pages for team members at /team/{slug}.
 * Each member edits a draft. The public page shows the "live" copy, which is replaced when the draft is
 * published (straight away, or after an admin approves it when review is turned on).
 */
final class Team
{
    public const LINKS = ['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'website' => 'Website', 'behance' => 'Behance', 'dribbble' => 'Dribbble', 'x' => 'X (Twitter)', 'youtube' => 'YouTube'];

    /** Repeating sections: key => [label, max rows, [field => [label, max length]]]. */
    public const LISTS = [
        'experience' => ['Experience', 15, ['role' => ['Job title', 100], 'company' => ['Company', 100], 'start' => ['From', 20], 'end' => ['To', 20], 'text' => ['What you did', 600]]],
        'work' => ['Featured work', 12, ['title' => ['Project name', 100], 'url' => ['Link (optional)', 255], 'text' => ['Short description', 400]]],
        'education' => ['Education', 8, ['degree' => ['Degree or course', 120], 'school' => ['School or university', 120], 'years' => ['Years', 20]]],
        'certs' => ['Certifications', 15, ['name' => ['Certificate', 120], 'issuer' => ['Issued by', 100], 'year' => ['Year', 10], 'url' => ['Link (optional)', 255]]],
    ];

    private const RESERVED = ['admin', 'portal', 'new', 'edit', 'preview', 'team', 'about', 'contact', 'api', 'login', 'www', 'aivrasol', 'staff', 'hr', 'ceo', 'support', 'help', 'jobs', 'careers'];

    /** Profile row for a user, created on first use. */
    public static function forUser(int $userId): array
    {
        $row = Database::one('SELECT * FROM team_profiles WHERE user_id = ?', [$userId]);
        if (!$row) {
            Database::insert('team_profiles', ['user_id' => $userId]);
            $row = Database::one('SELECT * FROM team_profiles WHERE user_id = ?', [$userId]);
        }
        return $row;
    }

    public static function decode(?string $json): array
    {
        $d = $json ? json_decode($json, true) : null;
        return is_array($d) ? $d : [];
    }

    /** A filled-in starting draft from the user account and their employee record. */
    public static function starter(array $user): array
    {
        $emp = Database::one('SELECT e.designation, o.city, o.country FROM employees e LEFT JOIN offices o ON o.id = e.office_id WHERE e.user_id = ?', [$user['id']]);
        return [
            'slug' => slugify((string) $user['name']),
            'name' => (string) $user['name'],
            'headline' => (string) ($emp['designation'] ?? ''),
            'location' => $emp ? trim(implode(', ', array_filter([(string) $emp['city'], (string) $emp['country']]))) : '',
            'open' => true,
        ];
    }

    /** Reads the posted form into a clean draft. Returns [draft, errors]. */
    public static function fromPost(array $post, array $old, int $profileId): array
    {
        $errors = [];
        $s = fn (string $k, int $max) => mb_substr(trim(str_replace(["\r", "\0"], '', (string) ($post[$k] ?? ''))), 0, $max);
        $d = [
            'slug' => strtolower($s('slug', 60)),
            'name' => $s('name', 80),
            'headline' => $s('headline', 120),
            'location' => $s('location', 80),
            'about' => mb_substr(str_replace(["\r\n", "\0"], ["\n", ''], (string) ($post['about'] ?? '')), 0, 5000),
            'email' => strtolower($s('email', 190)),
            'open' => !empty($post['open']),
            'photo' => (string) ($old['photo'] ?? ''),
        ];
        if ($d['name'] === '') {
            $errors[] = 'Please enter your name.';
        }
        if ($d['headline'] === '') {
            $errors[] = 'Please enter your job title, for example "Senior Laravel Developer".';
        }
        if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $d['slug']) || strlen($d['slug']) < 3) {
            $errors[] = 'Page address: use 3 or more lowercase letters, numbers and dashes, for example ali-ahmed.';
        } elseif (in_array($d['slug'], self::RESERVED, true)) {
            $errors[] = 'That page address is reserved. Please choose another.';
        } elseif (Database::value('SELECT COUNT(*) FROM team_profiles WHERE id <> ? AND (slug = ? OR JSON_UNQUOTE(JSON_EXTRACT(draft, "$.slug")) = ?)', [$profileId, $d['slug'], $d['slug']])) {
            $errors[] = 'Someone else already uses that page address. Try adding your surname or role.';
        }
        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'The public email address does not look right.';
        }
        $skills = [];
        foreach (preg_split('/[,\n]/', (string) ($post['skills'] ?? '')) as $sk) {
            $sk = mb_substr(trim($sk), 0, 40);
            if ($sk !== '' && !in_array(mb_strtolower($sk), array_map('mb_strtolower', $skills), true)) {
                $skills[] = $sk;
            }
        }
        $d['skills'] = array_slice($skills, 0, 30);
        $d['links'] = [];
        foreach (self::LINKS as $k => $label) {
            $u = trim((string) ($post['links'][$k] ?? ''));
            if ($u === '') {
                continue;
            }
            if (!preg_match('#^https://[^\s"<>]+$#i', $u) || strlen($u) > 255) {
                $errors[] = "$label link must start with https://";
                continue;
            }
            $d['links'][$k] = $u;
        }
        foreach (self::LISTS as $key => [$label, $max, $fields]) {
            $rows = [];
            foreach ((array) ($post[$key] ?? []) as $r) {
                if (!is_array($r)) {
                    continue;
                }
                $row = [];
                foreach ($fields as $f => [, $len]) {
                    $row[$f] = mb_substr(trim(str_replace(["\r", "\0"], '', (string) ($r[$f] ?? ''))), 0, $len);
                }
                if (implode('', $row) === '') {
                    continue;
                }
                if (isset($row['url']) && $row['url'] !== '' && !preg_match('#^https://[^\s"<>]+$#i', $row['url'])) {
                    $errors[] = "$label: links must start with https://";
                    $row['url'] = '';
                }
                $rows[] = $row;
            }
            if (count($rows) > $max) {
                $errors[] = "$label: up to $max entries.";
            }
            $d[$key] = array_slice($rows, 0, $max);
        }
        return [$d, $errors];
    }

    /** Short state for lists and badges. */
    public static function state(array $row): array
    {
        if (!(int) $row['allowed']) {
            return ['off', 'Not allowed'];
        }
        if ((int) $row['hidden']) {
            return ['hidden', 'Hidden by admin'];
        }
        if ((int) $row['pending']) {
            return ['pending', $row['live'] ? 'Live, changes waiting for review' : 'Waiting for review'];
        }
        if ($row['live']) {
            return ['live', $row['draft'] !== null && self::decode($row['draft']) != self::decode($row['live']) ? 'Live, unpublished changes' : 'Live'];
        }
        return ['draft', $row['draft'] ? 'Draft' : 'Not started'];
    }

    /** Copies the draft to the public page. */
    public static function publish(int $id): void
    {
        $row = Database::one('SELECT draft FROM team_profiles WHERE id = ?', [$id]);
        $d = self::decode($row['draft'] ?? null);
        Database::run('UPDATE team_profiles SET live = draft, slug = ?, pending = 0, admin_note = "", published_at = NOW() WHERE id = ?', [$d['slug'] ?? null, $id]);
        if (!empty($d['slug'])) {
            // Tell search engines (IndexNow) the page and the team list changed.
            Seo::ping([absolute_url('team/' . $d['slug']), absolute_url('team')]);
        }
    }

    public static function approvalOn(): bool
    {
        return setting('team_approval', '1') === '1';
    }

    /** Public members: allowed, published, not hidden, account active. */
    public static function members(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $rows = Database::all('SELECT t.slug, t.live, t.published_at, t.updated_at FROM team_profiles t JOIN users u ON u.id = t.user_id
            WHERE t.allowed = 1 AND t.hidden = 0 AND t.live IS NOT NULL AND t.slug IS NOT NULL AND u.is_active = 1 ORDER BY t.published_at');
        return $cache = array_map(fn ($r) => ['slug' => $r['slug'], 'updated_at' => $r['updated_at']] + self::decode($r['live']), $rows);
    }

    public static function member(string $slug): ?array
    {
        foreach (self::members() as $m) {
            if ($m['slug'] === $slug) {
                return $m;
            }
        }
        return null;
    }

    /** A live team member with this exact name, for linking blog authors to their profile. */
    public static function byName(string $name): ?array
    {
        $name = mb_strtolower(trim($name));
        foreach (self::members() as $m) {
            if (mb_strtolower(trim((string) $m['name'])) === $name) {
                return $m;
            }
        }
        return null;
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        return mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . (count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : ''));
    }

    /** schema.org Person data for search engines and AI assistants. */
    public static function schema(array $m): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'url' => absolute_url('team/' . $m['slug']),
            'mainEntity' => array_filter([
                '@type' => 'Person',
                'name' => $m['name'],
                'jobTitle' => $m['headline'] ?? '',
                'description' => Seo::plainText((string) ($m['about'] ?? '')) ?: null,
                'image' => !empty($m['photo']) ? absolute_url($m['photo']) : null,
                'address' => !empty($m['location']) ? ['@type' => 'PostalAddress', 'addressLocality' => $m['location']] : null,
                'worksFor' => ['@id' => Seo::orgId()],
                'knowsAbout' => $m['skills'] ?? [] ?: null,
                'sameAs' => array_values($m['links'] ?? []) ?: null,
                'url' => absolute_url('team/' . $m['slug']),
            ]),
        ]);
    }
}
