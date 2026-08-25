<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Simpan gambar tetamu dalam dua bentuk:
 *  - asal, bait-demi-bait seperti yang dihantar telefon (untuk muat turun pengantin)
 *  - thumbnail JPEG bersaiz web (untuk galeri, supaya laju atas data mudah alih)
 *
 * Thumbnail diputar mengikut EXIF kerana GD membuang metadata itu; fail asal
 * dibiarkan utuh kerana penonton gambar menghormati EXIF dengan sendirinya.
 */
class PhotoStorage
{
    public const THUMB_MAX_EDGE = 1200;

    public const THUMB_QUALITY = 82;

    private const SUPPORTED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function isSupported(string $mime): bool
    {
        return isset(self::SUPPORTED[$mime]);
    }

    public function supportedMimes(): array
    {
        return array_keys(self::SUPPORTED);
    }

    /**
     * @return array{original_path:string,thumb_path:string,mime:string,bytes:int,width:?int,height:?int}
     */
    public function store(UploadedFile $file): array
    {
        $mime = (string) $file->getMimeType();

        if (! $this->isSupported($mime)) {
            throw new RuntimeException('Format gambar tidak disokong: ' . $mime);
        }

        $disk = Storage::disk('public');
        $folder = now()->format('Y-m-d');
        $slug = Str::ulid()->toBase32();
        $ext = self::SUPPORTED[$mime];

        $originalPath = "photos/{$folder}/{$slug}.{$ext}";
        $thumbPath = "photos/{$folder}/{$slug}_thumb.jpg";

        $bytes = (int) $file->getSize();
        $disk->putFileAs("photos/{$folder}", $file, "{$slug}.{$ext}");

        $absoluteOriginal = $disk->path($originalPath);
        [$width, $height] = $this->makeThumbnail($absoluteOriginal, $disk->path($thumbPath), $mime);

        return [
            'original_path' => $originalPath,
            'thumb_path' => $thumbPath,
            'mime' => $mime,
            'bytes' => $bytes,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Hasilkan thumbnail dan pulangkan dimensi gambar asal selepas putaran EXIF.
     *
     * @return array{0:?int,1:?int}
     */
    private function makeThumbnail(string $sourcePath, string $destinationPath, string $mime): array
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            'image/gif' => @imagecreatefromgif($sourcePath),
            default => false,
        };

        if ($image === false) {
            throw new RuntimeException('Gambar tidak dapat dibaca.');
        }

        try {
            $image = $this->applyExifOrientation($image, $sourcePath, $mime);

            $width = imagesx($image);
            $height = imagesy($image);
            $longestEdge = max($width, $height);

            $scale = $longestEdge > self::THUMB_MAX_EDGE ? self::THUMB_MAX_EDGE / $longestEdge : 1.0;
            $thumbWidth = max(1, (int) round($width * $scale));
            $thumbHeight = max(1, (int) round($height * $scale));

            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);

            // Ratakan ketelusan atas putih supaya PNG/WebP tidak jadi hitam dalam JPEG.
            $white = imagecolorallocate($thumb, 255, 255, 255);
            imagefilledrectangle($thumb, 0, 0, $thumbWidth, $thumbHeight, $white);
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

            $this->ensureDirectory($destinationPath);
            imagejpeg($thumb, $destinationPath, self::THUMB_QUALITY);
            imagedestroy($thumb);

            return [$width, $height];
        } finally {
            if ($image instanceof \GdImage) {
                imagedestroy($image);
            }
        }
    }

    private function applyExifOrientation(\GdImage $image, string $path, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 0);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated === null || $rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    private function ensureDirectory(string $filePath): void
    {
        $directory = dirname($filePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }
}
