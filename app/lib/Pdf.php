<?php
declare(strict_types=1);

require_once APP_PATH . '/vendor/fpdf/fpdf.php';
require_once APP_PATH . '/vendor/qrcode/qrcode.php';

/** FPDF with the brand fonts, colours and a few drawing helpers. All text methods take UTF-8. */
class Pdf extends FPDF
{
    public const ORANGE = [255, 91, 53];
    public const ORANGE_DARK = [184, 56, 15];
    public const INK = [25, 31, 36];
    public const SLATE = [51, 63, 72];
    public const BODY = [95, 102, 107];
    public const LINE = [225, 227, 229];
    public const SOFT = [246, 247, 248];
    public const WHITE = [255, 255, 255];

    public function __construct(string $orientation = 'P', string $unit = 'mm', string|array $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
        $this->AddFont('Inter', '', 'inter.json');
        $this->AddFont('Inter', 'B', 'interb.json');
        $this->AddFont('Inter', 'I', 'interi.json');
        $this->AddFont('Bebas', '', 'bebas.json');
        $this->SetAutoPageBreak(false);
        $this->SetMargins(0, 0, 0);
        $this->SetCreator(setting('site_name', 'AivraSol'), true);
        $this->SetAuthor(setting('site_name', 'AivraSol'), true);
    }

    /** UTF-8 to the Windows-1252 encoding the fonts use. Unknown characters become close matches. */
    public static function enc(string $s): string
    {
        $s = strtr($s, ['’' => "'", '‘' => "'", '“' => '"', '”' => '"', '–' => '-', '—' => '-']);
        $out = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
        return $out === false ? (string) mb_convert_encoding($s, 'Windows-1252', 'UTF-8') : $out;
    }

    public function font(string $family, string $style = '', float $size = 10, array $color = self::INK): static
    {
        $this->SetFont($family, $style, $size);
        $this->SetTextColor(...$color);
        return $this;
    }

    public function fill(array $rgb): static
    {
        $this->SetFillColor(...$rgb);
        return $this;
    }

    public function stroke(array $rgb, float $width = 0.2): static
    {
        $this->SetDrawColor(...$rgb);
        $this->SetLineWidth($width);
        return $this;
    }

    /** Single line of text at an exact position. align: L, C or R inside width $w. */
    public function textAt(float $x, float $y, string $text, float $w = 0, string $align = 'L', float $h = 5): void
    {
        $this->SetXY($x, $y);
        $this->Cell($w, $h, self::enc($text), 0, 0, $align);
    }

    /** Wrapped paragraph. Returns the y position after the text. */
    public function para(float $x, float $y, float $w, string $text, float $lineH = 5.6, string $align = 'L'): float
    {
        $this->SetXY($x, $y);
        $this->MultiCell($w, $lineH, self::enc($text), 0, $align);
        return $this->GetY();
    }

    /** Shrinks the font until the text fits in one line of the given width. */
    public function fitText(float $x, float $y, float $w, string $text, string $family, string $style, float $size, float $min, string $align = 'L', float $h = 6): void
    {
        $this->SetFont($family, $style, $size);
        while ($size > $min && $this->GetStringWidth(self::enc($text)) > $w) {
            $size -= 0.5;
            $this->SetFont($family, $style, $size);
        }
        $this->textAt($x, $y, $text, $w, $align, $h);
    }

    public function roundRect(float $x, float $y, float $w, float $h, float $r, string $style = 'F'): void
    {
        $k = $this->k;
        $hp = $this->h;
        $op = $style === 'F' ? 'f' : ($style === 'FD' || $style === 'DF' ? 'B' : 'S');
        $arc = 4 / 3 * (M_SQRT2 - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->arc($xc + $r * $arc, $yc - $r, $xc + $r, $yc - $r * $arc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->arc($xc + $r, $yc + $r * $arc, $xc + $r * $arc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->arc($xc - $r * $arc, $yc + $r, $xc - $r, $yc + $r * $arc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->arc($xc - $r, $yc - $r * $arc, $xc - $r * $arc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    private function arc(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x1 * $this->k, ($h - $y1) * $this->k, $x2 * $this->k, ($h - $y2) * $this->k, $x3 * $this->k, ($h - $y3) * $this->k));
    }

    /** Vector QR code: sharp at any print size. */
    public function qr(string $data, float $x, float $y, float $size, array $color = self::INK): void
    {
        $q = QRCode::getMinimumQRCode($data, QR_ERROR_CORRECT_LEVEL_M);
        $n = $q->getModuleCount();
        $m = $size / $n;
        $this->fill($color);
        for ($r = 0; $r < $n; $r++) {
            for ($c = 0; $c < $n; $c++) {
                if ($q->isDark($r, $c)) {
                    // A hair of overlap stops white seams between modules in some viewers.
                    $this->Rect($x + $c * $m, $y + $r * $m, $m + 0.02, $m + 0.02, 'F');
                }
            }
        }
    }

    /** Draws a private image (photo, signature) if it exists. Returns true when drawn. */
    public function imageIf(?string $file, float $x, float $y, float $w = 0, float $h = 0): bool
    {
        if ($file === null || $file === '' || !is_file($file)) {
            return false;
        }
        $this->Image($file, $x, $y, $w, $h);
        return true;
    }

    /** Sends the PDF to the browser. inline shows it, otherwise it downloads. */
    public function send(string $filename, bool $inline = false): never
    {
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        $this->Output($inline ? 'I' : 'D', $filename, true);
        exit;
    }
}
