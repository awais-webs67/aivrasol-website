<?php
declare(strict_types=1);

/** Validates uploads by real content type, gives them random names and stores them safely. */
final class Upload
{
    private const CV_TYPES = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    private const IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** Returns [storedName, originalName] or throws with a visitor-friendly message. */
    public static function cv(array $file): array
    {
        self::assertUploaded($file, 5 * 1024 * 1024, '5 MB');
        $mime = self::mime($file['tmp_name']);
        $ext = self::CV_TYPES[$mime] ?? null;
        // Some servers report DOCX as a zip archive; accept it only with a .docx name.
        if ($ext === null && $mime === 'application/zip' && preg_match('/\.docx$/i', (string) $file['name'])) {
            $ext = 'docx';
        }
        if ($ext === null) {
            throw new InvalidArgumentException('Please upload your CV as a PDF, DOC or DOCX file.');
        }
        $dir = STORAGE_PATH . '/cv';
        if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
            throw new RuntimeException('CV folder is not writable.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            throw new RuntimeException('Could not save the uploaded CV.');
        }
        @chmod($dir . '/' . $name, 0640);
        $original = mb_substr(preg_replace('/[^\w.\- ]+/u', '_', basename((string) $file['name'])) ?? 'cv', 0, 150);
        return [$name, $original];
    }

    /**
     * Stores an image outside the web root (staff photos, signatures, stamps), re-encoded so no hidden
     * payload survives. PNG keeps transparency for signatures. $ratio crops to width/height first, e.g. 0.8 for 4:5.
     * Returns "folder/name.ext" relative to storage/private.
     */
    public static function privateImage(array $file, string $folder, int $maxWidth, string $format = 'png', ?float $ratio = null): string
    {
        self::assertUploaded($file, 4 * 1024 * 1024, '4 MB');
        $mime = self::mime($file['tmp_name']);
        if (!isset(self::IMAGE_TYPES[$mime])) {
            throw new InvalidArgumentException('Please upload a JPG, PNG or WebP image.');
        }
        $info = @getimagesize($file['tmp_name']);
        if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 40_000_000) {
            throw new InvalidArgumentException('That image could not be read or is too large.');
        }
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png' => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        };
        if (!$src) {
            throw new InvalidArgumentException('That image could not be read.');
        }
        [$w, $h] = [imagesx($src), imagesy($src)];
        [$sx, $sy, $sw, $sh] = [0, 0, $w, $h];
        if ($ratio !== null) {
            if ($w / $h > $ratio) {
                $sw = (int) round($h * $ratio);
                $sx = (int) (($w - $sw) / 2);
            } else {
                $sh = (int) round($w / $ratio);
                $sy = (int) max(0, ($h - $sh) / 3);
            }
        }
        $scale = min(1, $maxWidth / $sw);
        $nw = max(1, (int) round($sw * $scale));
        $nh = max(1, (int) round($sh * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        if ($format === 'png') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
        } else {
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        }
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $nw, $nh, $sw, $sh);
        $folder = preg_replace('/[^a-z0-9-]/', '', $folder) ?: 'misc';
        $dir = STORAGE_PATH . '/private/' . $folder;
        if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
            throw new RuntimeException('Private folder is not writable.');
        }
        $name = bin2hex(random_bytes(12)) . ($format === 'png' ? '.png' : '.jpg');
        $ok = $format === 'png' ? imagepng($dst, $dir . '/' . $name, 6) : imagejpeg($dst, $dir . '/' . $name, 86);
        imagedestroy($src);
        imagedestroy($dst);
        if (!$ok) {
            throw new RuntimeException('Could not save the image.');
        }
        @chmod($dir . '/' . $name, 0640);
        return $folder . '/' . $name;
    }

    /** Absolute path of a private image, or null when the stored name is empty or unsafe. */
    public static function privatePath(string $stored): ?string
    {
        if (!preg_match('#^[a-z0-9-]+/[a-f0-9]{24}\.(png|jpg)$#', $stored)) {
            return null;
        }
        $path = STORAGE_PATH . '/private/' . $stored;
        return is_file($path) ? $path : null;
    }

    public static function deletePrivate(string $stored): void
    {
        if ($path = self::privatePath($stored)) {
            @unlink($path);
        }
    }

    /**
     * Re-encodes an image to WebP (which strips hidden payloads and metadata),
     * resizes it to a maximum width and stores it under public/uploads/{folder}.
     * Returns the public path relative to /public.
     */
    public static function image(array $file, string $folder, int $maxWidth = 1600): string
    {
        self::assertUploaded($file, 4 * 1024 * 1024, '4 MB');
        $mime = self::mime($file['tmp_name']);
        if (!isset(self::IMAGE_TYPES[$mime])) {
            throw new InvalidArgumentException('Please upload a JPG, PNG or WebP image.');
        }
        $info = @getimagesize($file['tmp_name']);
        if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 40_000_000) {
            throw new InvalidArgumentException('That image could not be read or is too large.');
        }
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png' => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        };
        if (!$src) {
            throw new InvalidArgumentException('That image could not be read.');
        }
        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min(1, $maxWidth / $w);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $folder = preg_replace('/[^a-z0-9-]/', '', $folder) ?: 'misc';
        $dir = PUBLIC_PATH . '/uploads/' . $folder;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('Upload folder is not writable.');
        }
        $name = bin2hex(random_bytes(12)) . '.webp';
        if (!imagewebp($dst, $dir . '/' . $name, 82)) {
            throw new RuntimeException('Could not save the image.');
        }
        imagedestroy($src);
        imagedestroy($dst);
        return 'uploads/' . $folder . '/' . $name;
    }

    private static function assertUploaded(array $file, int $maxBytes, string $maxLabel): void
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('Please choose a file to upload.');
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || ($file['size'] ?? 0) > $maxBytes) {
            throw new InvalidArgumentException("The file is too large. The limit is $maxLabel.");
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            throw new InvalidArgumentException('The upload failed. Please try again.');
        }
    }

    private static function mime(string $path): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return (string) $finfo->file($path);
    }
}
