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

    /** Effective sending settings and where they came from. */
    public static function settings(string $profile = 'main'): array
    {
        // Campaigns can use their own mailbox (for example on a second domain) so cold email never affects the main domain.
        if ($profile === 'campaign' && setting('camp_smtp_host') !== '') {
            return [
                'source' => 'campaign',
                'from_email' => setting('camp_from_email'),
                'from_name' => setting('camp_from_name', setting('site_name', 'AivraSol')),
                'host' => setting('camp_smtp_host'),
                'port' => (int) setting('camp_smtp_port', '465'),
                'encryption' => setting('camp_smtp_encryption', 'ssl') === 'none' ? '' : setting('camp_smtp_encryption', 'ssl'),
                'username' => setting('camp_smtp_username'),
                'password' => Secret::decrypt(setting('camp_smtp_password')),
            ];
        }
        if (setting('mail_source') === 'custom') {
            return [
                'source' => 'admin',
                'from_email' => setting('mail_from_email', (string) config('mail.from_email', '')),
                'from_name' => setting('mail_from_name', setting('site_name', 'AivraSol')),
                'host' => setting('smtp_host'),
                'port' => (int) setting('smtp_port', '465'),
                'encryption' => setting('smtp_encryption', 'ssl') === 'none' ? '' : setting('smtp_encryption', 'ssl'),
                'username' => setting('smtp_username'),
                'password' => Secret::decrypt(setting('smtp_password')),
            ];
        }
        return [
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
        }
        ignore_user_abort(true);
        @set_time_limit(120);
        foreach (self::$queue as $m) {
            self::send(...$m);
        }
        self::$queue = [];
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
        $cfg = self::settings($options['profile'] ?? 'main');
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
            if ($cfg['username'] !== '') {
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
