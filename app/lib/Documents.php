<?php
declare(strict_types=1);

require_once APP_PATH . '/lib/Pdf.php';

/** Staff records helpers and the branded PDF documents: ID card and experience letter. */
final class Documents
{
    public const STATUSES = ['active' => 'Active', 'on_leave' => 'On leave', 'resigned' => 'Resigned', 'terminated' => 'Left (terminated)'];
    public const TYPES = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote'];
    public const PRONOUNS = ['he' => 'He / him', 'she' => 'She / her', 'they' => 'They / them'];
    public const BLOOD = ['', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public const DEFAULT_LETTER = "This is to certify that {name}{id_part} worked with {company} as {designation}{department_part} from {join_date} to {end_date}, a period of {duration}.\n\nDuring this time, {name} handled {his} responsibilities with dedication, honesty and professionalism, and worked well with colleagues and clients alike.\n\n{name} left the company on good terms. We thank {him} for {his} contribution and wish {him} every success in the future.\n\nThis letter is issued on request for whatever purpose it may serve.";

    public const DEFAULT_CARD_BACK = 'This card is the property of {company} and must be shown on request. If found, please return it to any {company} office or email {email}.';

    /** Random lowercase token for verification links. Hard to guess, so staff lists cannot be scraped. */
    public static function token(): string
    {
        $chars = 'abcdefghjkmnpqrstuvwxyz23456789';
        $t = '';
        for ($i = 0; $i < 16; $i++) {
            $t .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $t;
    }

    public static function codePrefix(): string
    {
        $p = strtoupper(preg_replace('/[^A-Za-z]/', '', setting('doc_code_prefix', 'AVS')) ?? '');
        return $p !== '' ? substr($p, 0, 5) : 'AVS';
    }

    public static function nextRef(string $kind): string
    {
        $year = gmdate('Y');
        $prefix = self::codePrefix() . '-' . $kind . '-' . $year . '-';
        $last = (string) Database::value('SELECT ref_no FROM employee_documents WHERE ref_no LIKE ? ORDER BY id DESC LIMIT 1', [$prefix . '%']);
        $n = $last !== '' ? (int) substr($last, strlen($prefix)) + 1 : 1;
        return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    public static function longDate(?string $date): string
    {
        return $date ? date('j F Y', strtotime($date)) : '';
    }

    /** "2 years and 3 months" between two dates. */
    public static function duration(string $from, string $to): string
    {
        $d = (new DateTime($from))->diff((new DateTime($to))->modify('+1 day'));
        $parts = [];
        if ($d->y) $parts[] = $d->y . ' year' . ($d->y > 1 ? 's' : '');
        if ($d->m) $parts[] = $d->m . ' month' . ($d->m > 1 ? 's' : '');
        if (!$parts) $parts[] = max(1, $d->d) . ' day' . ($d->d > 1 ? 's' : '');
        return implode(' and ', $parts);
    }

    public static function pronouns(string $p): array
    {
        return match ($p) {
            'he' => ['he' => 'he', 'him' => 'him', 'his' => 'his'],
            'she' => ['he' => 'she', 'him' => 'her', 'his' => 'her'],
            default => ['he' => 'they', 'him' => 'them', 'his' => 'their'],
        };
    }

    public static function nationalId(array $emp): string
    {
        return $emp['national_id'] !== '' ? Secret::decrypt($emp['national_id']) : '';
    }

    /** Placeholder values for letter templates. */
    public static function placeholders(array $emp, string $endDate, string $details = ''): array
    {
        $p = self::pronouns($emp['pronoun']);
        $nid = self::nationalId($emp);
        return [
            '{name}' => $emp['full_name'],
            '{father_name}' => $emp['father_name'],
            '{national_id}' => $nid,
            '{id_part}' => $nid !== '' ? " (ID number $nid)" : '',
            '{employee_code}' => $emp['employee_code'],
            '{designation}' => $emp['designation'],
            '{department}' => $emp['department'],
            '{department_part}' => $emp['department'] !== '' ? ' in the ' . $emp['department'] . ' team' : '',
            '{employment_type}' => strtolower($emp['employment_type']),
            '{join_date}' => self::longDate($emp['join_date']),
            '{end_date}' => self::longDate($endDate),
            '{duration}' => self::duration($emp['join_date'], $endDate),
            '{company}' => setting('site_name', 'AivraSol'),
            '{details}' => $details,
            '{he}' => $p['he'], '{He}' => ucfirst($p['he']),
            '{him}' => $p['him'], '{his}' => $p['his'], '{His}' => ucfirst($p['his']),
        ];
    }

    private static function resource(string $name): string
    {
        return APP_PATH . '/resources/pdf/' . $name;
    }

    private static function signatory(): array
    {
        return [
            'name' => setting('doc_signatory_name'),
            'title' => setting('doc_signatory_title'),
            'signature' => Upload::privatePath(setting('doc_signature')),
            'stamp' => Upload::privatePath(setting('doc_stamp')),
        ];
    }

    private static function headOffice(): ?array
    {
        return Content::offices()[0] ?? null;
    }

    /* ---------- ID card: CR80 size (54 x 85.6 mm), front and back ---------- */

    public static function idCard(array $emp): Pdf
    {
        $W = 54.0;
        $H = 85.6;
        $pdf = new Pdf('P', 'mm', [$W, $H]);
        $pdf->SetTitle('ID card ' . $emp['full_name'], true);
        $site = setting('site_name', 'AivraSol');
        $verify = absolute_url('verify/' . $emp['verify_token']);

        // Front
        $pdf->AddPage();
        $pdf->fill(Pdf::SLATE)->Rect(0, 0, $W, 24, 'F');
        $pdf->fill(Pdf::ORANGE)->Rect(0, 24, $W, 1.2, 'F');
        $pdf->Image(self::resource('logo-white.png'), ($W - 26) / 2, 5.2, 26);
        $pdf->font('Inter', 'B', 4.6, [255, 214, 201])->textAt(0, 10.6, 'EMPLOYEE IDENTITY CARD', $W, 'C', 3);
        $px = ($W - 25) / 2;
        $py = 15;
        $pdf->fill(Pdf::WHITE)->roundRect($px - 1, $py - 1, 27, 33.25, 1.6);
        $photo = Upload::privatePath($emp['photo']);
        if ($photo) {
            $pdf->Image($photo, $px, $py, 25, 31.25);
        } else {
            $pdf->fill(Pdf::SOFT)->Rect($px, $py, 25, 31.25, 'F');
            $initials = implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($emp['full_name'])) ?: [], 0, 2)));
            $pdf->font('Bebas', '', 30, Pdf::LINE)->textAt($px, $py + 10, mb_strtoupper($initials), 25, 'C', 12);
        }
        $pdf->SetTextColor(...Pdf::INK);
        $pdf->fitText(3, 50.4, $W - 6, mb_strtoupper($emp['full_name']), 'Bebas', '', 17, 10, 'C', 7);
        $pdf->SetTextColor(...Pdf::ORANGE_DARK);
        $pdf->fitText(3, 57.3, $W - 6, $emp['designation'], 'Inter', 'B', 7.4, 5, 'C', 4);
        if ($emp['department'] !== '') {
            $pdf->SetTextColor(...Pdf::BODY);
            $pdf->fitText(3, 61.1, $W - 6, $emp['department'], 'Inter', '', 6.2, 4.5, 'C', 3.5);
        }
        $pdf->stroke(Pdf::LINE, 0.2)->Line(5, 66.2, $W - 5, 66.2);
        $pdf->font('Inter', '', 4.8, Pdf::BODY)->textAt(5, 68.4, 'EMPLOYEE ID', 0, 'L', 2.6);
        $pdf->font('Inter', 'B', 7.6, Pdf::INK)->textAt(5, 71, $emp['employee_code'], 0, 'L', 3.6);
        $pdf->font('Inter', '', 4.8, Pdf::BODY)->textAt(5, 75.4, 'JOINED', 0, 'L', 2.6);
        $pdf->font('Inter', 'B', 6.4, Pdf::INK)->textAt(5, 78, date('M Y', strtotime($emp['join_date'])), 0, 'L', 3.2);
        $pdf->fill(Pdf::WHITE)->Rect(36, 67.2, 14, 14, 'F');
        $pdf->qr($verify, 36.5, 67.7, 13);
        $pdf->fill(Pdf::ORANGE)->Rect(0, $H - 2.2, $W, 2.2, 'F');

        // Back
        $pdf->AddPage();
        $pdf->fill(Pdf::ORANGE)->Rect(0, 0, $W, 2.2, 'F');
        $pdf->Image(self::resource('logo.png'), ($W - 24) / 2, 6, 24);
        $rows = array_filter([
            'Blood group' => $emp['blood_group'],
            'Emergency contact' => $emp['emergency_contact'],
            'Phone' => $emp['phone'],
            'Valid until' => date('M Y', strtotime('+' . max(1, (int) setting('doc_card_valid_years', '2')) . ' years')),
        ], fn ($v) => (string) $v !== '');
        $y = 15.5;
        foreach ($rows as $label => $value) {
            $pdf->font('Inter', '', 4.8, Pdf::BODY)->textAt(5, $y, mb_strtoupper($label), 0, 'L', 2.6);
            $pdf->SetTextColor(...Pdf::INK);
            $pdf->fitText(5, $y + 2.5, $W - 10, $value, 'Inter', 'B', 6.6, 4.8, 'L', 3.4);
            $y += 7.2;
        }
        $vars = ['{company}' => $site, '{email}' => setting('email_info')];
        $pdf->font('Inter', '', 5.2, Pdf::BODY);
        $y = $pdf->para(5, max($y + 1, 45), $W - 10, strtr(setting('doc_card_back', self::DEFAULT_CARD_BACK), $vars), 2.9, 'C');
        $sig = self::signatory();
        $sy = 66.5;
        if ($sig['signature']) {
            $pdf->Image($sig['signature'], ($W - 20) / 2, $sy - 8.5, 20, 0);
        }
        $pdf->stroke(Pdf::LINE, 0.2)->Line(14, $sy, $W - 14, $sy);
        $pdf->font('Inter', 'B', 5.4, Pdf::INK)->textAt(0, $sy + 0.8, $sig['name'] !== '' ? $sig['name'] : 'Authorised signature', $W, 'C', 2.8);
        if ($sig['title'] !== '') {
            $pdf->font('Inter', '', 4.8, Pdf::BODY)->textAt(0, $sy + 3.6, $sig['title'], $W, 'C', 2.6);
        }
        $pdf->fill(Pdf::SLATE)->Rect(0, $H - 10, $W, 10, 'F');
        $pdf->font('Inter', 'B', 5.8, Pdf::WHITE)->textAt(0, $H - 8.2, preg_replace('#^https?://#', '', rtrim(base_url(), '/')), $W, 'C', 3);
        $office = self::headOffice();
        if ($office) {
            $pdf->font('Inter', '', 4.6, [207, 211, 214])->textAt(2, $H - 4.8, trim(implode(', ', array_filter([$office['city'], $office['country']]))), $W - 4, 'C', 2.6);
        }
        return $pdf;
    }

    /* ---------- Experience letter: A4 ---------- */

    public static function experienceLetter(array $doc): Pdf
    {
        $data = json_decode($doc['data'], true) ?: [];
        $pdf = new Pdf('P', 'mm', 'A4');
        $pdf->SetTitle($doc['title'] . ' ' . ($data['name'] ?? ''), true);
        $pdf->AddPage();
        $site = setting('site_name', 'AivraSol');
        $L = 22.0;
        $R = 188.0;
        $w = $R - $L;

        // Watermark and header
        $pdf->Image(self::resource('mark-faint.png'), 55, 95, 100);
        $pdf->fill(Pdf::ORANGE)->Rect(0, 0, 210, 3.2, 'F');
        $pdf->Image(self::resource('logo.png'), $L, 15, 44);
        $contact = array_filter([preg_replace('#^https?://#', '', rtrim(base_url(), '/')), setting('email_info'), setting('phone')]);
        $y = 15;
        foreach ($contact as $c) {
            $pdf->font('Inter', '', 8.4, Pdf::BODY)->textAt($L, $y, $c, $w, 'R', 4);
            $y += 4.3;
        }
        $pdf->stroke(Pdf::LINE, 0.3)->Line($L, 33, $R, 33);

        // Reference and date
        $pdf->font('Inter', '', 9, Pdf::BODY)->textAt($L, 39, 'Ref: ' . $doc['ref_no'], 0, 'L', 5);
        $pdf->font('Inter', '', 9, Pdf::BODY)->textAt($L, 39, 'Date: ' . self::longDate($doc['issued_at']), $w, 'R', 5);

        // Title
        $pdf->font('Bebas', '', 30, Pdf::SLATE)->textAt($L, 52, $doc['title'], 0, 'L', 11);
        $pdf->fill(Pdf::ORANGE)->Rect($L, 64, 16, 1.4, 'F');
        $salutation = (string) ($data['salutation'] ?? 'To whom it may concern');
        if ($salutation !== '') {
            $pdf->font('Inter', 'B', 10.5, Pdf::INK)->textAt($L, 73, $salutation, 0, 'L', 6);
        }

        // Body
        $y = 83;
        $pdf->font('Inter', '', 10.5, Pdf::SLATE);
        foreach (preg_split('/\n\s*\n/', trim((string) ($data['body'] ?? ''))) ?: [] as $para) {
            $y = $pdf->para($L, $y, $w, preg_replace('/\s*\n\s*/', ' ', $para) ?? '', 6.1, 'J') + 4.2;
        }

        // Signature block
        $y = max($y + 4, 170);
        $pdf->font('Inter', '', 10, Pdf::SLATE)->textAt($L, $y, 'For ' . $site . ',', 0, 'L', 5);
        $sigY = $y + 7;
        $sigFile = Upload::privatePath((string) ($data['signature'] ?? ''));
        $stampFile = Upload::privatePath((string) ($data['stamp'] ?? ''));
        if ($stampFile) {
            $pdf->Image($stampFile, $L + 42, $sigY - 4, 30);
        }
        if ($sigFile) {
            $pdf->Image($sigFile, $L, $sigY, 46, 0);
        }
        $lineY = $sigY + 20;
        $pdf->stroke(Pdf::INK, 0.3)->Line($L, $lineY, $L + 62, $lineY);
        $pdf->font('Inter', 'B', 10.5, Pdf::INK)->textAt($L, $lineY + 2, (string) ($data['signatory_name'] ?? '') ?: 'Authorised signatory', 0, 'L', 5.5);
        if (!empty($data['signatory_title'])) {
            $pdf->font('Inter', '', 9.5, Pdf::BODY)->textAt($L, $lineY + 7.5, (string) $data['signatory_title'], 0, 'L', 5);
        }

        // Verification box
        $vy = 246;
        $pdf->fill(Pdf::SOFT)->roundRect($L, $vy, $w, 24, 2.5);
        $pdf->fill(Pdf::WHITE)->Rect($L + 3.5, $vy + 3, 18, 18, 'F');
        $verify = absolute_url('verify/' . $doc['verify_token']);
        $pdf->qr($verify, $L + 4, $vy + 3.5, 17);
        $pdf->font('Inter', 'B', 9, Pdf::INK)->textAt($L + 27, $vy + 5, 'Check that this letter is genuine', 0, 'L', 5);
        $pdf->font('Inter', '', 8.4, Pdf::BODY);
        $pdf->para($L + 27, $vy + 10.5, $w - 31, 'Scan the code or open ' . $verify . ' to see the details we hold for reference ' . $doc['ref_no'] . '.', 4.4);

        // Footer
        $pdf->fill(Pdf::SLATE)->Rect(0, 284, 210, 13, 'F');
        $pdf->fill(Pdf::ORANGE)->Rect(0, 284, 210, 0.9, 'F');
        $countries = array_values(array_unique(array_column(Content::offices(), 'country')));
        $office = self::headOffice();
        $addr = $office ? trim(implode(', ', array_filter([$office['address'], $office['city'], $office['country']]))) : '';
        $pdf->font('Inter', 'B', 8.4, Pdf::WHITE)->textAt($L, 286.6, $site . ($countries ? '  |  ' . implode('  ·  ', $countries) : ''), $w, 'C', 4.4);
        if ($addr !== '' || setting('doc_company_reg') !== '') {
            $pdf->font('Inter', '', 7.6, [207, 211, 214])->textAt($L, 291, trim($addr . (setting('doc_company_reg') !== '' ? '   Reg. no. ' . setting('doc_company_reg') : '')), $w, 'C', 4);
        }
        return $pdf;
    }

    /* ---------- Certificate: A4 landscape ---------- */

    public static function certificate(array $doc): Pdf
    {
        $data = json_decode($doc['data'], true) ?: [];
        $pdf = new Pdf('L', 'mm', 'A4');
        $pdf->SetTitle($doc['title'] . ' ' . ($data['name'] ?? ''), true);
        $pdf->AddPage();
        $site = setting('site_name', 'AivraSol');
        $W = 297.0;
        $H = 210.0;

        // Frame: soft background, slate border, thin orange inner line and corner accents
        $pdf->fill([255, 251, 249])->Rect(0, 0, $W, $H, 'F');
        $pdf->fill(Pdf::SLATE)->Rect(0, 0, $W, 9, 'F');
        $pdf->fill(Pdf::SLATE)->Rect(0, $H - 9, $W, 9, 'F');
        $pdf->fill(Pdf::ORANGE)->Rect(0, 9, $W, 1.2, 'F');
        $pdf->fill(Pdf::ORANGE)->Rect(0, $H - 10.2, $W, 1.2, 'F');
        $pdf->stroke(Pdf::ORANGE, 0.5)->Rect(14, 18, $W - 28, $H - 36);
        $pdf->stroke([255, 214, 200], 0.3)->Rect(17, 21, $W - 34, $H - 42);
        // L-shaped orange corners
        foreach ([[14, 18, 14, 18], [$W - 28, 18, $W - 15.6, 18], [14, $H - 19.6, 14, $H - 32], [$W - 28, $H - 19.6, $W - 15.6, $H - 32]] as [$hx, $hy, $vx, $vy]) {
            $pdf->fill(Pdf::ORANGE)->Rect($hx, $hy, 14, 1.6, 'F');
            $pdf->fill(Pdf::ORANGE)->Rect($vx, $vy, 1.6, 14, 'F');
        }
        $pdf->Image(self::resource('mark-faint.png'), $W / 2 - 45, 60, 90);

        // Logo, title and name
        $pdf->Image(self::resource('logo.png'), $W / 2 - 22, 27, 44);
        $pdf->font('Bebas', '', 40, Pdf::SLATE)->textAt(20, 44, (string) $doc['title'], $W - 40, 'C', 15);
        $pdf->fill(Pdf::ORANGE)->Rect($W / 2 - 10, 61, 20, 1.4, 'F');
        $sal = (string) ($data['salutation'] ?? 'This certificate is presented to');
        if ($sal !== '') {
            $pdf->font('Inter', 'I', 11, Pdf::BODY)->textAt(20, 68, $sal, $W - 40, 'C', 6);
        }
        $pdf->font('Bebas', '', 46, Pdf::INK);
        $pdf->fitText(30, 78, $W - 60, (string) ($data['name'] ?? ''), 'Bebas', '', 46, 26, 'C', 18);
        $pdf->stroke([255, 214, 200], 0.4)->Line($W / 2 - 60, 98, $W / 2 + 60, 98);
        $pdf->font('Inter', '', 11.5, Pdf::SLATE);
        $body = trim(preg_replace('/\s*\n\s*/', ' ', (string) ($data['body'] ?? '')) ?? '');
        $pdf->para(48, 104, $W - 96, $body, 6.6, 'C');

        // Signature (left), date and reference (middle), verification (right)
        $baseY = 160;
        $sigFile = Upload::privatePath((string) ($data['signature'] ?? ''));
        $stampFile = Upload::privatePath((string) ($data['stamp'] ?? ''));
        if ($stampFile) {
            $pdf->Image($stampFile, 76, $baseY - 18, 24);
        }
        if ($sigFile) {
            $pdf->Image($sigFile, 34, $baseY - 16, 40, 0);
        }
        $pdf->stroke(Pdf::INK, 0.3)->Line(30, $baseY + 4, 96, $baseY + 4);
        $pdf->font('Inter', 'B', 10, Pdf::INK)->textAt(30, $baseY + 6, (string) ($data['signatory_name'] ?? '') ?: 'Authorised signatory', 66, 'C', 5);
        $pdf->font('Inter', '', 8.8, Pdf::BODY)->textAt(30, $baseY + 11, trim(((string) ($data['signatory_title'] ?? '')) . ', ' . $site, ', '), 66, 'C', 4.5);

        $pdf->font('Inter', 'B', 10, Pdf::INK)->textAt($W / 2 - 40, $baseY + 2, self::longDate($doc['issued_at']), 80, 'C', 5);
        $pdf->font('Inter', '', 8.8, Pdf::BODY)->textAt($W / 2 - 40, $baseY + 7, 'Date of issue', 80, 'C', 4.5);
        $pdf->font('Inter', '', 8.4, Pdf::BODY)->textAt($W / 2 - 40, $baseY + 13, 'Ref: ' . $doc['ref_no'], 80, 'C', 4.5);

        $verify = absolute_url('verify/' . $doc['verify_token']);
        $pdf->fill(Pdf::WHITE)->Rect($W - 62, $baseY - 18, 26, 26, 'F');
        $pdf->qr($verify, $W - 61, $baseY - 17, 24);
        $pdf->font('Inter', '', 7.6, Pdf::BODY)->textAt($W - 80, $baseY + 10, 'Scan to check it is genuine', 62, 'C', 4);
        return $pdf;
    }

    /** Builds the right PDF for any issued document. */
    public static function pdf(array $doc): Pdf
    {
        $data = json_decode($doc['data'], true) ?: [];
        return ($data['kind'] ?? 'letter') === 'certificate' ? self::certificate($doc) : self::experienceLetter($doc);
    }

    /** Snapshot saved with an issued letter, so the PDF stays the same even if settings change later. */
    public static function letterSnapshot(array $emp, string $endDate, string $body, array $extra = []): array
    {
        $sig = [
            'signatory_name' => setting('doc_signatory_name'),
            'signatory_title' => setting('doc_signatory_title'),
            'signature' => setting('doc_signature'),
            'stamp' => setting('doc_stamp'),
        ];
        return $sig + [
            'name' => $emp['full_name'],
            'designation' => $emp['designation'],
            'department' => $emp['department'],
            'join_date' => $emp['join_date'],
            'end_date' => $endDate,
            'body' => $body,
        ] + $extra;
    }
}
