<?php
declare(strict_types=1);

/**
 * Email campaigns with safe sending: hourly and daily limits, a sending window, random gaps between emails,
 * one-click unsubscribe, automatic suppression of bounces, and a pause after repeated errors.
 */
final class Campaigns
{
    public const KINDS = ['cold' => 'Cold outreach', 'follow_up' => 'Follow-up', 'newsletter' => 'Newsletter', 'announcement' => 'Announcement'];
    public const CONTACT_STATUSES = ['subscribed' => 'Subscribed', 'unsubscribed' => 'Unsubscribed', 'bounced' => 'Bounced', 'complained' => 'Complained'];
    public const STATUSES = ['draft' => 'Draft', 'sending' => 'Sending', 'paused' => 'Paused', 'done' => 'Finished', 'cancelled' => 'Cancelled'];
    public const TIMEZONES = ['Asia/Karachi', 'Asia/Dubai', 'Europe/London', 'Australia/Sydney', 'America/New_York', 'America/Los_Angeles', 'UTC'];

    public static function suppressionHash(string $email): string
    {
        return hash_hmac('sha256', 'suppress' . strtolower(trim($email)), (string) config('app_key'));
    }

    public static function isSuppressed(string $email): bool
    {
        return (bool) Database::value('SELECT COUNT(*) FROM email_suppressions WHERE email_hash = ?', [self::suppressionHash($email)]);
    }

    /** Remembers an opt-out or bounce for good, even if the contact is later deleted. */
    public static function suppress(string $email, string $reason): void
    {
        Database::run('INSERT IGNORE INTO email_suppressions (email_hash, reason) VALUES (?, ?)', [self::suppressionHash($email), $reason]);
    }

    public static function limits(): array
    {
        return [
            'per_hour' => max(1, min(200, (int) setting('camp_per_hour', '30'))),
            'per_day' => max(1, min(2000, (int) setting('camp_per_day', '150'))),
            'gap' => max(5, min(600, (int) setting('camp_gap_seconds', '45'))),
            'start' => max(0, min(23, (int) setting('camp_window_start', '9'))),
            'end' => max(1, min(24, (int) setting('camp_window_end', '18'))),
            'weekdays' => setting('camp_weekdays_only', '1') === '1',
            'tz' => in_array(setting('camp_timezone', 'Asia/Karachi'), self::TIMEZONES, true) ? setting('camp_timezone', 'Asia/Karachi') : 'UTC',
            'rest_days' => max(0, min(30, (int) setting('camp_rest_days', '2'))),
        ];
    }

    private static function sentSince(int $seconds): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM campaign_recipients WHERE status IN ('sent', 'failed') AND sent_at > UTC_TIMESTAMP() - INTERVAL ? SECOND", [$seconds]);
    }

    /** How many emails may go out right now, and why not when the answer is zero. */
    public static function allowance(): array
    {
        $l = self::limits();
        $now = new DateTime('now', new DateTimeZone($l['tz']));
        $hour = (int) $now->format('G');
        if ($l['weekdays'] && (int) $now->format('N') >= 6) {
            return [0, 'Outside sending days (weekdays only, ' . $l['tz'] . ' time).'];
        }
        if ($hour < $l['start'] || $hour >= $l['end']) {
            return [0, sprintf('Outside the sending window (%02d:00 to %02d:00, %s time).', $l['start'], $l['end'], $l['tz'])];
        }
        $hourLeft = $l['per_hour'] - self::sentSince(3600);
        $dayLeft = $l['per_day'] - self::sentSince(86400);
        if ($dayLeft <= 0) {
            return [0, 'Daily limit reached (' . $l['per_day'] . ' in 24 hours).'];
        }
        if ($hourLeft <= 0) {
            return [0, 'Hourly limit reached (' . $l['per_hour'] . ' per hour).'];
        }
        $last = Database::value("SELECT MAX(sent_at) FROM campaign_recipients WHERE status IN ('sent', 'failed')");
        $since = $last ? time() - strtotime($last . ' UTC') : PHP_INT_MAX;
        if ($since < $l['gap']) {
            return [0, 'Waiting ' . ($l['gap'] - $since) . ' seconds between emails.'];
        }
        return [min($hourLeft, $dayLeft), ''];
    }

    /** {{Hi|Hello}} picks one option at random. */
    public static function spin(string $text): string
    {
        return preg_replace_callback('/\{\{([^{}]+)\}\}/', function ($m) {
            $opts = explode('|', $m[1]);
            return trim($opts[array_rand($opts)]);
        }, $text) ?? $text;
    }

    public static function personalize(string $text, array $c, array $campaign): string
    {
        return strtr(self::spin($text), [
            '{first_name}' => $c['first_name'] !== '' ? $c['first_name'] : 'there',
            '{last_name}' => $c['last_name'],
            '{company}' => $c['company'] !== '' ? $c['company'] : 'your company',
            '{website}' => $c['website'],
            '{country}' => $c['country'],
            '{sender_name}' => $campaign['from_name'] !== '' ? $campaign['from_name'] : setting('site_name', 'AivraSol'),
            '{site_name}' => setting('site_name', 'AivraSol'),
            '{site_url}' => rtrim(absolute_url(), '/'),
            '{booking_url}' => setting('booking_url') !== '' ? setting('booking_url') : absolute_url('contact'),
        ]);
    }

    public static function unsubscribeUrl(array $c): string
    {
        return absolute_url('unsubscribe/' . $c['token']);
    }

    /** Postal address line required by anti-spam laws (CAN-SPAM, PECR). */
    private static function postal(): string
    {
        $o = Content::offices()[0] ?? null;
        $addr = $o ? trim(implode(', ', array_filter([$o['address'], $o['city'], $o['country']]))) : '';
        return setting('site_name', 'AivraSol') . ($addr !== '' ? ', ' . $addr : '');
    }

    /** [subject, text, html|null, headers] for one contact. */
    public static function build(array $campaign, array $c): array
    {
        $subject = self::personalize($campaign['subject'], $c, $campaign);
        $body = self::personalize($campaign['body'], $c, $campaign);
        $unsub = self::unsubscribeUrl($c);
        $text = $body . "\n\n--\n" . self::postal() . "\nDon't want these emails? Unsubscribe: $unsub\n";
        $paras = '';
        foreach (preg_split('/\n\s*\n/', trim($body)) ?: [] as $block) {
            $paras .= '<p style="margin:0 0 14px;">' . nl2br(e($block)) . '</p>';
        }
        $paras = preg_replace_callback('#(?<!["\'])(https?://[^\s<]+)#', fn ($m) => '<a href="' . $m[1] . '" style="color:#B8380F;">' . $m[1] . '</a>', $paras) ?? $paras;
        $foot = '<p style="margin:22px 0 0;font-size:12px;line-height:18px;color:#8A9196;">' . e(self::postal()) . '<br>Don\'t want these emails? <a href="' . e($unsub) . '" style="color:#8A9196;">Unsubscribe</a></p>';
        if ($campaign['layout'] === 'branded') {
            $html = render('emails/layout', ['content' => $paras . $foot, 'preheader' => mb_substr(strtok($body, "\n") ?: '', 0, 110)]);
        } else {
            // Plain layout: looks like a normal personal email, which is what cold outreach should be.
            $html = '<!doctype html><html><body style="margin:0;padding:16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:21px;color:#222;">' . $paras . $foot . '</body></html>';
        }
        $headers = [
            'List-Unsubscribe' => '<' . $unsub . '>, <mailto:' . (setting('camp_reply_to') ?: setting('email_info')) . '?subject=unsubscribe>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ];
        if ($campaign['layout'] === 'branded') {
            $headers['Precedence'] = 'bulk';
        }
        return [$subject, $text, $html, $headers];
    }

    /** Adds every matching subscribed contact to the campaign queue. Returns how many were added. */
    public static function queue(array $campaign): int
    {
        $tag = $campaign['audience_tag'];
        $sql = "INSERT IGNORE INTO campaign_recipients (campaign_id, contact_id) SELECT ?, id FROM contacts WHERE status = 'subscribed'";
        $params = [$campaign['id']];
        if ($tag !== '') {
            $sql .= " AND FIND_IN_SET(?, tags) > 0";
            $params[] = $tag;
        }
        return Database::run($sql, $params)->rowCount();
    }

    public static function audience(string $tag): int
    {
        return (int) ($tag === ''
            ? Database::value("SELECT COUNT(*) FROM contacts WHERE status = 'subscribed'")
            : Database::value("SELECT COUNT(*) FROM contacts WHERE status = 'subscribed' AND FIND_IN_SET(?, tags) > 0", [$tag]));
    }

    public static function tags(): array
    {
        $all = [];
        foreach (Database::all("SELECT tags FROM contacts WHERE tags <> ''") as $r) {
            foreach (explode(',', $r['tags']) as $t) {
                if ($t !== '') {
                    $all[$t] = ($all[$t] ?? 0) + 1;
                }
            }
        }
        ksort($all);
        return $all;
    }

    public static function cleanTags(string $raw): string
    {
        $tags = [];
        foreach (preg_split('/[,;]+/', strtolower($raw)) ?: [] as $t) {
            $t = trim(preg_replace('/[^a-z0-9 _-]/', '', $t) ?? '');
            if ($t !== '') {
                $tags[$t] = $t;
            }
        }
        return mb_substr(implode(',', $tags), 0, 255);
    }

    /**
     * Sends queued emails while limits allow. $sleep is true from the command line (cron), where waiting
     * between emails is fine; web requests send at most $max without waiting.
     * Returns a short report.
     */
    public static function run(int $max, bool $sleep): string
    {
        if ((int) Database::value("SELECT GET_LOCK('avs_campaign_run', 0)") !== 1) {
            return 'Another sending run is in progress.';
        }
        try {
            $sent = 0;
            $failed = 0;
            $note = '';
            $deadline = time() + ($sleep ? 240 : 20);
            while ($sent + $failed < $max && time() < $deadline) {
                [$allowed, $why] = self::allowance();
                if ($allowed <= 0) {
                    if ($sleep && str_starts_with($why, 'Waiting')) {
                        sleep(min(60, (int) filter_var($why, FILTER_SANITIZE_NUMBER_INT) + random_int(1, 15)));
                        continue;
                    }
                    $note = $why;
                    break;
                }
                $next = Database::one("SELECT r.id AS rid, r.campaign_id, c.* FROM campaign_recipients r
                    JOIN campaigns k ON k.id = r.campaign_id AND k.status = 'sending'
                    JOIN contacts c ON c.id = r.contact_id
                    WHERE r.status = 'queued' ORDER BY k.started_at, r.id LIMIT 1");
                if (!$next) {
                    $note = 'Nothing waiting to send.';
                    break;
                }
                $campaign = Database::one('SELECT * FROM campaigns WHERE id = ?', [$next['campaign_id']]);
                $rest = self::limits()['rest_days'];
                if ($next['status'] !== 'subscribed' || self::isSuppressed($next['email'])) {
                    Database::run("UPDATE campaign_recipients SET status = 'skipped', error = ? WHERE id = ?", ['Contact is ' . $next['status'], $next['rid']]);
                    continue;
                }
                if ($rest > 0 && $next['last_sent_at'] && strtotime($next['last_sent_at'] . ' UTC') > time() - $rest * 86400) {
                    Database::run("UPDATE campaign_recipients SET status = 'skipped', error = ? WHERE id = ?", ["Emailed in the last $rest days", $next['rid']]);
                    continue;
                }
                [$subject, $text, $html, $headers] = self::build($campaign, $next);
                $replyTo = $campaign['reply_to'] !== '' ? $campaign['reply_to'] : (setting('camp_reply_to') ?: null);
                $err = Mailer::send($next['email'], $subject, $text, $html, $replyTo, 'campaign', ['profile' => 'campaign', 'from_name' => $campaign['from_name'], 'headers' => $headers]);
                if ($err === null) {
                    $sent++;
                    Database::run("UPDATE campaign_recipients SET status = 'sent', sent_at = UTC_TIMESTAMP() WHERE id = ?", [$next['rid']]);
                    Database::run('UPDATE contacts SET last_sent_at = UTC_TIMESTAMP() WHERE id = ?', [$next['id']]);
                } else {
                    $failed++;
                    Database::run("UPDATE campaign_recipients SET status = 'failed', error = ?, sent_at = UTC_TIMESTAMP() WHERE id = ?", [mb_substr($err, 0, 255), $next['rid']]);
                    if (preg_match('/\b55[0-4]\b|5\.1\.\d|user unknown|does not exist|no such user|mailbox unavailable|invalid recipient/i', $err)) {
                        Database::run("UPDATE contacts SET status = 'bounced' WHERE id = ?", [$next['id']]);
                        self::suppress($next['email'], 'bounced');
                    }
                    // Several failures in a row usually means the mailbox is blocked or misconfigured: stop and protect it.
                    $recent = Database::all("SELECT status FROM campaign_recipients WHERE status IN ('sent', 'failed') ORDER BY sent_at DESC, id DESC LIMIT 5");
                    if (count($recent) === 5 && !in_array('sent', array_column($recent, 'status'), true)) {
                        Database::run("UPDATE campaigns SET status = 'paused' WHERE status = 'sending'");
                        self::note('Paused all campaigns after 5 failed emails in a row. Last error: ' . mb_substr($err, 0, 200));
                        $note = 'Paused after repeated errors.';
                        break;
                    }
                }
                if (!Database::value("SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = ? AND status = 'queued'", [$campaign['id']])) {
                    Database::run("UPDATE campaigns SET status = 'done', finished_at = UTC_TIMESTAMP() WHERE id = ? AND status = 'sending'", [$campaign['id']]);
                }
                if ($sleep && $sent + $failed < $max) {
                    $l = self::limits();
                    sleep($l['gap'] + random_int(0, (int) ceil($l['gap'] / 2)));
                }
            }
            $report = "Sent $sent, failed $failed." . ($note !== '' ? " $note" : '');
            Database::run("INSERT INTO settings (setting_key, setting_value) VALUES ('camp_last_run', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", [gmdate('Y-m-d H:i:s') . ' UTC: ' . $report]);
            return $report;
        } finally {
            Database::value("SELECT RELEASE_LOCK('avs_campaign_run')");
        }
    }

    public static function note(string $text): void
    {
        Database::run("INSERT INTO settings (setting_key, setting_value) VALUES ('camp_alert', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", [gmdate('Y-m-d H:i') . ' UTC: ' . $text]);
    }

    /** Secret for the web cron address, for hosts without a cron job feature. */
    public static function cronToken(): string
    {
        return substr(hash_hmac('sha256', 'cron', (string) config('app_key')), 0, 32);
    }

    public static function stats(int $campaignId): array
    {
        $rows = Database::all('SELECT status, COUNT(*) AS n FROM campaign_recipients WHERE campaign_id = ? GROUP BY status', [$campaignId]);
        $s = array_column($rows, 'n', 'status') + ['queued' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        $s['total'] = array_sum(array_map('intval', $s));
        $s['unsubscribed'] = (int) Database::value("SELECT COUNT(*) FROM campaign_recipients r JOIN contacts c ON c.id = r.contact_id WHERE r.campaign_id = ? AND r.status = 'sent' AND c.status = 'unsubscribed'", [$campaignId]);
        return $s;
    }
}
