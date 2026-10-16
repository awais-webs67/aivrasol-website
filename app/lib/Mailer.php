<?php
declare(strict_types=1);

/**
 * Sends plain-text notification emails through SMTP (STARTTLS or SSL) or PHP mail().
 * Failures are logged and never shown to visitors; leads are saved before any email is sent.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $body, ?string $replyTo = null): bool
    {
        $to = self::cleanHeader($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $fromEmail = self::cleanHeader((string) config('mail.from_email', 'no-reply@localhost'));
        $fromName = self::cleanHeader((string) config('mail.from_name', 'Website'));
        $subject = self::cleanHeader($subject);
        $replyTo = $replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? self::cleanHeader($replyTo) : null;

        $headers = [
            'Date' => date('r'),
            'From' => sprintf('"%s" <%s>', addcslashes($fromName, '"\\'), $fromEmail),
            'To' => $to,
            'Subject' => '=?UTF-8?B?' . base64_encode($subject) . '?=',
            'Message-ID' => sprintf('<%s@%s>', bin2hex(random_bytes(12)), substr(strrchr($fromEmail, '@') ?: '@localhost', 1)),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => 'base64',
        ];
        if ($replyTo) {
            $headers['Reply-To'] = $replyTo;
        }
        $encodedBody = rtrim(chunk_split(base64_encode($body), 76, "\r\n"));

        try {
            if ((string) config('mail.host', '') === '') {
                $extra = $headers;
                unset($extra['To'], $extra['Subject']);
                $headerLines = implode("\r\n", array_map(fn ($k, $v) => "$k: $v", array_keys($extra), $extra));
                return mail($to, $headers['Subject'], $encodedBody, $headerLines, '-f' . $fromEmail);
            }
            return self::smtp($fromEmail, $to, $headers, $encodedBody);
        } catch (Throwable $e) {
            error_log('Mail failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function smtp(string $from, string $to, array $headers, string $body): bool
    {
        $host = (string) config('mail.host');
        $port = (int) config('mail.port', 587);
        $enc = (string) config('mail.encryption', 'tls');
        $remote = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $sock = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$sock) {
            throw new RuntimeException("SMTP connect failed: $errstr ($errno)");
        }
        stream_set_timeout($sock, 15);
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
                throw new RuntimeException('SMTP error after "' . strtok($command, ' ') . '": ' . trim($reply));
            }
            return $reply;
        };
        $helo = preg_replace('/[^a-z0-9.\-]/i', '', parse_url(base_url(), PHP_URL_HOST) ?: 'localhost');
        $cmd('', [220]);
        $cmd("EHLO $helo", [250]);
        if ($enc === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new RuntimeException('STARTTLS failed');
            }
            $cmd("EHLO $helo", [250]);
        }
        $user = (string) config('mail.username', '');
        if ($user !== '') {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode($user), [334]);
            $cmd(base64_encode((string) config('mail.password', '')), [235]);
        }
        $cmd("MAIL FROM:<$from>", [250]);
        $cmd("RCPT TO:<$to>", [250, 251]);
        $cmd('DATA', [354]);
        $head = implode("\r\n", array_map(fn ($k, $v) => "$k: $v", array_keys($headers), $headers));
        $cmd($head . "\r\n\r\n" . $body . "\r\n.", [250]);
        $cmd('QUIT', [221]);
        fclose($sock);
        return true;
    }

    private static function cleanHeader(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], '', $value));
    }
}
