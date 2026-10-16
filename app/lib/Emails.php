<?php
declare(strict_types=1);

/** Builds the website's emails: alerts for the team and automatic replies for visitors. */
final class Emails
{
    public const TYPES = [
        'contact' => 'Contact form',
        'consultation' => 'Free consultation',
        'quote' => 'Quote request',
        'hire' => 'Hire a developer',
        'application' => 'Job application',
    ];

    /** Alert for the team about a new enquiry or application. Returns [subject, text, html]. */
    public static function alert(string $type, array $fields, ?string $message, string $adminPath, string $replyName, string $replyEmail): array
    {
        $label = self::TYPES[$type] ?? 'Website form';
        $subject = "New {$label}: {$replyName}";
        $rows = '';
        $text = "New {$label} on the " . setting('site_name', 'AivraSol') . " website\n\n";
        foreach ($fields as $k => $v) {
            if ((string) $v === '') {
                continue;
            }
            $val = e((string) $v);
            if ($k === 'Email') {
                $val = '<a href="mailto:' . e((string) $v) . '" style="color:#B8380F;">' . $val . '</a>';
            }
            $rows .= '<tr><td style="padding:9px 12px 9px 0;border-bottom:1px solid #E1E3E5;color:#5F666B;font-size:14px;width:150px;vertical-align:top;">' . e((string) $k)
                . '</td><td style="padding:9px 0;border-bottom:1px solid #E1E3E5;color:#191F24;font-size:14px;">' . $val . '</td></tr>';
            $text .= "$k: $v\n";
        }
        $msgHtml = '';
        if ($message !== null && trim($message) !== '') {
            $msgHtml = '<p style="margin:22px 0 6px;font-weight:bold;color:#191F24;">Message</p><div style="padding:14px 16px;background:#F6F7F8;border-left:3px solid #FF5B35;color:#191F24;">' . nl2br(e($message)) . '</div>';
            $text .= "\nMessage:\n$message\n";
        }
        $adminUrl = absolute_url($adminPath);
        $text .= "\nOpen in admin: $adminUrl\nReply directly to this email to answer {$replyName}.\n";
        $content = '<p style="margin:0 0 4px;font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#B8380F;font-weight:bold;">' . e($label) . '</p>'
            . '<h1 style="margin:0 0 18px;font-size:22px;line-height:30px;color:#191F24;">New enquiry from ' . e($replyName) . '</h1>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $rows . '</table>'
            . $msgHtml
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0 8px;"><tr>'
            . '<td style="background:#FF5B35;border-radius:4px;"><a href="mailto:' . e($replyEmail) . '" style="display:inline-block;padding:12px 20px;color:#FFFFFF;font-weight:bold;text-decoration:none;font-size:14px;">Reply to ' . e(self::firstName($replyName)) . '</a></td>'
            . '<td style="width:10px;"></td>'
            . '<td style="border:1px solid #FF5B35;border-radius:4px;"><a href="' . e($adminUrl) . '" style="display:inline-block;padding:11px 20px;color:#B8380F;font-weight:bold;text-decoration:none;font-size:14px;">Open in admin</a></td>'
            . '</tr></table>'
            . '<p style="margin:16px 0 0;font-size:13px;color:#5F666B;">Tip: just press Reply in your inbox. Your answer goes straight to ' . e($replyEmail) . '.</p>';
        $html = render('emails/layout', ['content' => $content, 'preheader' => "$label from $replyName"]);
        return [$subject, $text, $html];
    }

    /** Automatic reply to the visitor, or null when turned off. Returns [subject, text, html]. */
    public static function autoReply(string $type, string $name, array $summary): ?array
    {
        if (setting('autoreply_enabled', '1') !== '1') {
            return null;
        }
        $vars = ['{name}' => self::firstName($name), '{site_name}' => setting('site_name', 'AivraSol')];
        $subject = strtr(setting("autoreply_{$type}_subject", 'We received your message, {name}'), $vars);
        $body = strtr(setting("autoreply_{$type}_body", 'Thank you for contacting {site_name}. We will reply within one business day.'), $vars);

        $html = '<h1 style="margin:0 0 18px;font-size:22px;line-height:30px;color:#191F24;">Hi ' . e($vars['{name}']) . ',</h1>' . self::paragraphs($body);
        $text = "Hi {$vars['{name}']},\n\n$body\n";
        $summary = array_filter($summary, fn ($v) => (string) $v !== '');
        if ($summary) {
            $html .= '<p style="margin:22px 0 8px;font-weight:bold;color:#191F24;">Your request</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F6F7F8;border-radius:6px;">';
            $text .= "\nYour request:\n";
            foreach ($summary as $k => $v) {
                $html .= '<tr><td style="padding:9px 14px;color:#5F666B;font-size:14px;width:150px;">' . e(ucfirst((string) $k)) . '</td><td style="padding:9px 14px;color:#191F24;font-size:14px;">' . e((string) $v) . '</td></tr>';
                $text .= "$k: $v\n";
            }
            $html .= '</table>';
        }
        $html .= '<p style="margin:24px 0 4px;">Kind regards,<br><strong>The ' . e($vars['{site_name}']) . ' team</strong></p>'
            . '<p style="margin:18px 0 0;font-size:13px;color:#5F666B;">You can reply to this email if you want to add anything.</p>';
        $text .= "\nKind regards,\nThe {$vars['{site_name}']} team\n";
        return [$subject, $text, render('emails/layout', ['content' => $html, 'preheader' => mb_substr(strtok($body, "\n") ?: '', 0, 120)])];
    }

    /** Simple branded message with an optional button. Returns [text, html]. */
    public static function notice(string $title, string $body, ?string $button = null, ?string $link = null, string $footnote = ''): array
    {
        $html = '<h1 style="margin:0 0 16px;font-size:22px;line-height:30px;color:#191F24;">' . e($title) . '</h1>' . self::paragraphs($body);
        $text = "$title\n\n$body\n";
        if ($button !== null && $link !== null) {
            $html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 18px;"><tr><td style="background:#E32B00;border-radius:4px;">'
                . '<a href="' . e($link) . '" style="display:inline-block;padding:13px 24px;color:#FFFFFF;font-weight:bold;text-decoration:none;font-size:15px;">' . e($button) . '</a></td></tr></table>'
                . '<p style="margin:0 0 16px;font-size:13px;color:#5F666B;">If the button does not work, copy this link into your browser:<br><span style="word-break:break-all;color:#B8380F;">' . e($link) . '</span></p>';
            $text .= "\n$button: $link\n";
        }
        if ($footnote !== '') {
            $html .= '<p style="margin:18px 0 0;font-size:13px;color:#5F666B;">' . e($footnote) . '</p>';
            $text .= "\n$footnote\n";
        }
        return [$text, render('emails/layout', ['content' => $html, 'preheader' => $title])];
    }

    public static function test(): array
    {
        $html = '<h1 style="margin:0 0 14px;font-size:22px;color:#191F24;">Your email settings work</h1>'
            . self::paragraphs("This is a test email from the " . setting('site_name', 'AivraSol') . " admin panel.\n\nIf you can read this, website enquiries and automatic replies will be delivered.");
        return ['Test email from your website', "Your email settings work.\n\nWebsite enquiries and automatic replies will be delivered.", render('emails/layout', ['content' => $html, 'preheader' => 'Your email settings work'])];
    }

    /** Visitor names go into emails, so keep them short and free of links. */
    public static function safeName(string $name): string
    {
        $name = preg_replace('#(https?://|www\.)\S*#i', '', $name) ?? '';
        $name = trim(preg_replace('/[^\p{L}\p{M}\s.\'\-]/u', '', $name) ?? '');
        return mb_substr($name !== '' ? $name : 'there', 0, 60);
    }

    private static function firstName(string $name): string
    {
        $first = strtok(self::safeName($name), ' ');
        return $first !== false && $first !== '' ? $first : 'there';
    }

    /** Plain text to email HTML: blank lines make paragraphs, lines starting with "- " make a list. */
    private static function paragraphs(string $text): string
    {
        $out = '';
        foreach (preg_split('/\n\s*\n/', trim($text)) ?: [] as $block) {
            $lines = array_map('trim', explode("\n", $block));
            $items = array_filter($lines, fn ($l) => str_starts_with($l, '- '));
            if ($items && count($items) >= count($lines) - 1) {
                $lead = array_values(array_filter($lines, fn ($l) => !str_starts_with($l, '- ')));
                if ($lead) {
                    $out .= '<p style="margin:0 0 8px;">' . e($lead[0]) . '</p>';
                }
                $out .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;">';
                foreach ($items as $it) {
                    $out .= '<tr><td style="padding:3px 10px 3px 0;vertical-align:top;color:#FF5B35;font-weight:bold;">■</td><td style="padding:3px 0;">' . e(substr($it, 2)) . '</td></tr>';
                }
                $out .= '</table>';
            } else {
                $out .= '<p style="margin:0 0 16px;">' . nl2br(e($block)) . '</p>';
            }
        }
        return $out;
    }
}
