<?php
declare(strict_types=1);

/**
 * Sends branded HTML emails (with a plain-text copy) through SMTP or PHP mail().
 * Sending settings come from the admin panel when "mail_source" is "custom", otherwise from app/config.php.
 * Every attempt is written to the email_log table. Leads are always saved before any email is sent.
 */
final class Mailer
{
    private static array $queue = [];
    private static bool $flushRegistered = false;

    /**
     * Mailboxes. Each part of the business can send from its own address and server, so for example HR mail
     * comes from hr@ and cold email never touches the main domain. A mailbox without its own server uses the
     * website mailbox. Keys are stored as {prefix}from_email, {prefix}smtp_host and so on.
     */
    public const BOXES = [
        'main' => ['label' => 'Website and enquiries', 'prefix' => '', 'about' => 'Alerts for new enquiries, automatic replies to visitors, the system check and anything not listed below. The other mailboxes use this one until you give them their own.'],
        'hr' => ['label' => 'HR and team', 'prefix' => 'hr_', 'about' => 'Replies to job applicants, staff invitations and password links, notices and messages to employees, certificates and team page emails.'],
        'clients' => ['label' => 'Clients', 'prefix' => 'cl_', 'about' => 'Client portal invitations, password links, project updates and messages to clients.'],
        'campaign' => ['label' => 'Email campaigns (cold email)', 'prefix' => 'camp_', 'about' => 'Cold email and newsletters. Best on a separate domain or mailbox, so spam reports can never hurt your main address.'],
    ];

    /** Where alerts for each mailbox land (enquiries, job applications, client messages). Campaigns have none. */
    public const ALERT_KEYS = ['main' => 'notify_emails', 'hr' => 'notify_emails_hr', 'clients' => 'cl_notify'];

    /** Alert recipients of a mailbox, with sensible fallbacks. */
    public static function alertList(string $box): array
    {
        $key = self::ALERT_KEYS[$box] ?? null;
        if ($key === null) {
            return [];
        }
        $list = self::addressList(setting($key));
        if (!$list && $box !== 'main') {
            $list = self::addressList(setting($box === 'hr' ? 'email_hr' : 'email_info'));
        }
        return $list ?: self::addressList(setting('email_info'));
    }

    /** Setting key holding the result of the last test for a mailbox. */
    public static function testKey(string $box): string
    {
        return ($box === 'main' ? 'mail_' : self::BOXES[$box]['prefix']) . 'last_test';
    }

    /** Setting keys that belong to a mailbox (used by Save and Reset). */
    public static function boxKeys(string $box): array
    {
        if ($box === 'main') {
            return ['mail_source', 'mail_from_email', 'mail_from_name', 'mail_reply_to', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password'];
        }
        $p = self::BOXES[$box]['prefix'];
        return array_map(fn ($k) => $p . $k, ['from_email', 'from_name', 'reply_to', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password']);
    }

    /** Which mailbox an email type goes through. */
    public static function boxFor(string $type): string
    {
        if (str_starts_with($type, 'campaign')) {
            return 'campaign';
        }
        if (str_starts_with($type, 'client') || str_starts_with($type, 'portal')) {
            return 'clients';
        }
        if (in_array($type, ['invite', 'password_reset', 'team_page', 'team_review'], true) || str_starts_with($type, 'application') || str_starts_with($type, 'staff') || str_starts_with($type, 'hr')) {
            return 'hr';
        }
        return 'main';
    }

    /** True when a mailbox has its own server instead of borrowing the website mailbox. */
    public static function hasOwn(string $box): bool
    {
        if ($box === 'main') {
            return setting('mail_source') === 'custom';
        }
        $p = self::BOXES[$box]['prefix'] ?? '';
        return $p !== '' && setting($p . 'smtp_host') !== '' && filter_var(setting($p . 'from_email'), FILTER_VALIDATE_EMAIL);
    }

    /** Effective sending settings and where they came from. */
    public static function settings(string $profile = 'main'): array
    {
        $box = isset(self::BOXES[$profile]) ? $profile : 'main';
        $p = self::BOXES[$box]['prefix'];
        $defaultReply = ['main' => setting('mail_reply_to'), 'hr' => setting('email_hr'), 'clients' => setting('email_info'), 'campaign' => ''][$box];
        $replyTo = $box === 'main' ? setting('mail_reply_to') : (setting($p . 'reply_to') ?: $defaultReply);
        if ($box !== 'main' && self::hasOwn($box)) {
            return [
                'source' => $box,
                'box' => $box,
                'from_email' => setting($p . 'from_email'),
                'from_name' => setting($p . 'from_name', setting('site_name', 'AivraSol')),
                'reply_to' => $replyTo,
                'host' => setting($p . 'smtp_host'),
                'port' => (int) setting($p . 'smtp_port', '465'),
                'encryption' => setting($p . 'smtp_encryption', 'ssl') === 'none' ? '' : setting($p . 'smtp_encryption', 'ssl'),
                'username' => setting($p . 'smtp_username'),
                'password' => Secret::decrypt(setting($p . 'smtp_password')),
            ];
        }
        if (setting('mail_source') === 'custom') {
            $cfg = [
                'source' => 'admin',
                'from_email' => setting('mail_from_email', (string) config('mail.from_email', '')),
                'from_name' => setting('mail_from_name', setting('site_name', 'AivraSol')),
                'host' => setting('smtp_host'),
                'port' => (int) setting('smtp_port', '465'),
                'encryption' => setting('smtp_encryption', 'ssl') === 'none' ? '' : setting('smtp_encryption', 'ssl'),
                'username' => setting('smtp_username'),
                'password' => Secret::decrypt(setting('smtp_password')),
            ];
        } else {
            $cfg = [
                'source' => 'config',
                'from_email' => (string) config('mail.from_email', ''),
                'from_name' => (string) config('mail.from_name', 'AivraSol'),
                'host' => (string) config('mail.host', ''),
                'port' => (int) config('mail.port', 587),
                'encryption' => (string) config('mail.encryption', 'tls'),
                'username' => (string) config('mail.username', ''),
                'password' => (string) config('mail.password', ''),
            ];
        }
        // A mailbox without its own server borrows the website one, but keeps its own sender name and reply address.
        if ($box !== 'main' && setting($p . 'from_name') !== '') {
            $cfg['from_name'] = setting($p . 'from_name');
        }
        return $cfg + ['box' => $box, 'reply_to' => $replyTo];
    }

    /** Valid email addresses from a list typed one per line or comma separated. */
    public static function addressList(string $text): array
    {
        $out = [];
        foreach (preg_split('/[\s,;]+/', $text) ?: [] as $a) {
            $a = strtolower(trim($a));
            if ($a !== '' && filter_var($a, FILTER_VALIDATE_EMAIL)) {
                $out[$a] = $a;
            }
        }
        return array_values($out);
    }

    /** Send after the visitor has their answer, so forms stay fast. */
    public static function queue(array|string $to, string $subject, string $text, ?string $html = null, ?string $replyTo = null, string $type = 'general'): void
    {
        self::$queue[] = [$to, $subject, $text, $html, $replyTo, $type];
        if (!self::$flushRegistered) {
            self::$flushRegistered = true;
            register_shutdown_function([self::class, 'flush']);
        }
    }

    public static function flush(): void
    {
        if (!self::$queue) {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            self::finishResponse();
        }
        ignore_user_abort(true);
        @set_time_limit(120);
        foreach (self::$queue as $m) {
            self::send(...$m);
        }
        self::$queue = [];
    }

    /**
     * Ends the visitor's request before slow work starts on servers without PHP-FPM (Apache mod_php, XAMPP):
     * the page tells the browser its exact length and to close the connection, so the browser shows the
     * result straight away while the emails are sent in the background.
     */
    private static function finishResponse(): void
    {
        if (headers_sent()) {
            @flush();
            return;
        }
        $body = '';
        while (ob_get_level() > 0) {
            $body = (string) ob_get_clean() . $body;
        }
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', '0');
        header('Content-Encoding: none');
        header('Content-Length: ' . strlen($body));
        header('Connection: close');
        echo $body;
        @flush();
    }

    /** Sends one message (to one or more people, each receiving their own copy). Returns null on success or the error text. */
    /**
     * $options: 'profile' => 'campaign' to use the campaign mailbox, 'from_name' to override the sender name,
     * 'headers' => extra headers such as List-Unsubscribe.
     */
    public static function send(array|string $to, string $subject, string $text, ?string $html = null, ?string $replyTo = null, string $type = 'general', array $options = []): ?string
    {
        $recipients = is_array($to) ? $to : [$to];
        $recipients = array_values(array_filter(array_map(fn ($a) => self::clean((string) $a), $recipients), fn ($a) => filter_var($a, FILTER_VALIDATE_EMAIL)));
        $subject = self::clean($subject);
        if (!$recipients) {
            return self::log($type, '', $subject, 'No valid recipient address.');
        }
        $cfg = self::settings($options['profile'] ?? self::boxFor($type));
        if ($replyTo === null && ($cfg['reply_to'] ?? '') !== '') {
            $replyTo = $cfg['reply_to'];
        }
        $fromEmail = self::clean($cfg['from_email']);
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return self::log($type, implode(', ', $recipients), $subject, 'The "from" email address is not set. Add it in Admin, Email settings, or in app/config.php.');
        }
        $fromName = self::clean((string) ($options['from_name'] ?? '') !== '' ? (string) $options['from_name'] : $cfg['from_name']);
        $extraHeaders = [];
        foreach ((array) ($options['headers'] ?? []) as $k => $v) {
            if (preg_match('/^[A-Za-z][A-Za-z0-9-]{1,40}$/', (string) $k)) {
                $extraHeaders[$k] = self::clean((string) $v);
            }
        }
        $replyTo = $replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? self::clean($replyTo) : null;

        $errors = [];
        foreach ($recipients as $rcpt) {
            [$headers, $body] = self::build($rcpt, $fromEmail, $fromName, $subject, $text, $html, $replyTo);
            $headers = array_merge($headers, $extraHeaders);
            try {
                if ($cfg['host'] === '') {
                    $extra = $headers;
                    unset($extra['To'], $extra['Subject']);
                    $lines = implode("\r\n", array_map(fn ($k, $v) => "$k: $v", array_keys($extra), $extra));
                    if (!@mail($rcpt, $headers['Subject'], $body, $lines, '-f' . $fromEmail)) {
                        throw new RuntimeException('PHP mail() could not send. Set up SMTP in Admin, Email settings.');
                    }
                } else {
                    self::smtp($cfg, $fromEmail, $rcpt, $headers, $body);
                }
            } catch (Throwable $e) {
                $errors[] = $rcpt . ': ' . $e->getMessage();
                error_log('Mail failed: ' . $e->getMessage());
            }
        }
        return self::log($type, implode(', ', $recipients), $subject, $errors ? implode(' | ', $errors) : null);
    }

    private static function build(string $to, string $fromEmail, string $fromName, string $subject, string $text, ?string $html, ?string $replyTo): array
    {
        $domain = substr(strrchr($fromEmail, '@') ?: '@localhost', 1);
        $headers = [
            'Date' => date('r'),
            'From' => sprintf('"%s" <%s>', addcslashes($fromName, '"\\'), $fromEmail),
            'To' => $to,
            'Subject' => '=?UTF-8?B?' . base64_encode($subject) . '?=',
            'Message-ID' => sprintf('<%s@%s>', bin2hex(random_bytes(12)), $domain),
            'MIME-Version' => '1.0',
            'X-Mailer' => 'AivraSol Website',
        ];
        if ($replyTo) {
            $headers['Reply-To'] = $replyTo;
        }
        $enc = fn (string $s) => rtrim(chunk_split(base64_encode($s), 76, "\r\n"));
        if ($html === null) {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
            $headers['Content-Transfer-Encoding'] = 'base64';
            return [$headers, $enc($text)];
        }
        $boundary = 'b' . bin2hex(random_bytes(12));
        $headers['Content-Type'] = 'multipart/alternative; boundary="' . $boundary . '"';
        $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $enc($text)
            . "\r\n--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $enc($html)
            . "\r\n--$boundary--";
        return [$headers, $body];
    }

    private static function smtp(array $cfg, string $from, string $to, array $headers, string $body): void
    {
        $enc = $cfg['encryption'];
        $port = (int) $cfg['port'];
        // The port decides the kind of encryption, so a mismatch in the settings is fixed here instead of hanging.
        if ($port === 465) {
            $enc = 'ssl';
        } elseif ($enc === 'ssl' && in_array($port, [25, 587, 2525], true)) {
            $enc = 'tls';
        }
        $remote = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $cfg['host'] . ':' . $cfg['port'];
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $sock = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$sock) {
            $why = strtolower($errstr . ' ' . $errno);
            if (preg_match('/timed out|failed to respond|did not properly respond|10060|110\b/', $why)) {
                $hint = is_local_site()
                    ? ' The mail server did not answer. On a test computer this usually means your internet provider, router, antivirus or Windows Firewall blocks email ports. It normally works once the site runs on your hosting. To test from here, try port 587 with TLS, or another network such as a mobile hotspot.'
                    : ' The mail server did not answer. Try port 587 with TLS, or ask your host whether outgoing SMTP is allowed from the server.';
            } elseif (preg_match('/certificate|ssl|crypto/', $why)) {
                $hint = ' The secure connection failed. Use the server name from cPanel (Email Accounts, Connect Devices, "Secure SSL/TLS Settings"), which matches the server certificate.';
            } elseif (preg_match('/refused|10061|111\b/', $why)) {
                $hint = ' The server refused the connection on this port. Check the port: 465 for SSL, 587 for TLS.';
            } elseif (preg_match('/getaddrinfo|name or service|no such host|11001/', $why)) {
                $hint = ' The server name could not be found. Check the SMTP server spelling.';
            } else {
                $hint = ' Check the host, port and encryption.';
            }
            throw new RuntimeException("Could not connect to {$cfg['host']}:{$cfg['port']} ($errstr).$hint");
        }
        stream_set_timeout($sock, 30);
        $read = function () use ($sock): string {
            $data = '';
            while (($line = fgets($sock, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };
        $cmd = function (string $command, array $expect) use ($sock, $read): string {
            if ($command !== '') {
                fwrite($sock, $command . "\r\n");
            }
            $reply = $read();
            if (!in_array((int) substr($reply, 0, 3), $expect, true)) {
                $what = strtok($command, ' ') ?: 'connect';
                if ($what === 'AUTH' || str_starts_with((string) $reply, '535')) {
                    throw new RuntimeException('The mail server rejected the username or password.');
                }
                if (trim($reply) === '') {
                    throw new RuntimeException('The mail server accepted the connection but did not reply' . ($what === 'connect' ? ' with a greeting' : ' to ' . $what) . '. Check the port and encryption pair (465 with SSL, or 587 with TLS). If they are right, the server may be slowing down or blocking your IP address: wait 15 minutes, or ask your host to check the firewall (CSF/cPHulk) for your IP.');
                }
                throw new RuntimeException('Mail server error after ' . $what . ': ' . trim(mb_substr($reply, 0, 200)));
            }
            return $reply;
        };
        $helo = preg_replace('/[^a-z0-9.\-]/i', '', (string) (parse_url(base_url(), PHP_URL_HOST) ?: 'localhost'));
        try {
            $cmd('', [220]);
            $cmd("EHLO $helo", [250]);
            if ($enc === 'tls') {
                $cmd('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new RuntimeException('Secure connection (STARTTLS) failed.');
                }
                $cmd("EHLO $helo", [250]);
            }
            if ($cfg['username'] !== '' && $cfg['password'] !== '') {
                $cmd('AUTH LOGIN', [334]);
                $cmd(base64_encode($cfg['username']), [334]);
                $cmd(base64_encode($cfg['password']), [235]);
            }
            $cmd("MAIL FROM:<$from>", [250]);
            $cmd("RCPT TO:<$to>", [250, 251]);
            $cmd('DATA', [354]);
            $head = implode("\r\n", array_map(fn ($k, $v) => "$k: $v", array_keys($headers), $headers));
            // Dot-stuffing: a line that starts with "." must be doubled.
            $cmd($head . "\r\n\r\n" . preg_replace('/^\./m', '..', $body) . "\r\n.", [250]);
            $cmd('QUIT', [221]);
        } finally {
            fclose($sock);
        }
    }

    private static function log(string $type, string $to, string $subject, ?string $error): ?string
    {
        try {
            Database::insert('email_log', [
                'type' => mb_substr($type, 0, 40),
                'recipients' => mb_substr($to, 0, 500),
                'subject' => mb_substr($subject, 0, 255),
                'status' => $error === null ? 'sent' : 'failed',
                'error' => mb_substr((string) $error, 0, 500),
            ]);
            if (random_int(1, 50) === 1) {
                Database::run('DELETE FROM email_log WHERE created_at < UTC_TIMESTAMP() - INTERVAL 90 DAY');
            }
        } catch (Throwable $e) {
            error_log('Email log failed: ' . $e->getMessage());
        }
        return $error;
    }

    private static function clean(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], '', $value));
    }
}

/** Encrypts small secrets (like the SMTP password) with the app_key before they are stored in the database. */
final class Secret
{
    /**
     * New values use AES-256-GCM from OpenSSL, which every PHP host has (XAMPP does not turn sodium on by default).
     * Values saved earlier with sodium ("enc:") still open when sodium is available.
     */
    private static function key(): string
    {
        return hash('sha256', 'secret|' . config('app_key'), true);
    }

    public static function encrypt(string $plain): string
    {
        if ($plain === '') {
            return '';
        }
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($cipher === false) {
            throw new RuntimeException('Encryption is not available on this server (OpenSSL is missing).');
        }
        return 'enc2:' . base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $stored): string
    {
        if (str_starts_with($stored, 'enc2:')) {
            $raw = base64_decode(substr($stored, 5), true);
            if ($raw === false || strlen($raw) < 29) {
                return '';
            }
            $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
            return $plain === false ? '' : $plain;
        }
        if (str_starts_with($stored, 'enc:') && function_exists('sodium_crypto_secretbox_open')) {
            $raw = base64_decode(substr($stored, 4), true);
            $n = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
            if ($raw === false || strlen($raw) <= $n) {
                return '';
            }
            $key = sodium_crypto_generichash((string) config('app_key'), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
            $plain = sodium_crypto_secretbox_open(substr($raw, $n), substr($raw, 0, $n), $key);
            return $plain === false ? '' : $plain;
        }
        return '';
    }
}
