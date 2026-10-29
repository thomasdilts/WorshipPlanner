<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Normalize uploaded profile photos to JPEG, max 100px on the longest edge.
 */
class UserAvatarProcessor
{
    public const MAX_EDGE = 100;

    public function __construct(
        protected string $disk = 'public',
    ) {}

    /**
     * Convert the stored upload to a square-ish JPEG ≤ 100px high/wide.
     * Returns the (possibly new) storage path.
     */
    public function normalizeToJpeg(string $path): string
    {
        $disk = Storage::disk($this->disk);

        if (! $disk->exists($path)) {
            return $path;
        }

        $absolute = $disk->path($path);
        $image = $this->loadImage($absolute);

        if ($image === false) {
            throw new RuntimeException('Unable to read uploaded profile picture.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width < 1 || $height < 1) {
            imagedestroy($image);

            throw new RuntimeException('Invalid profile picture dimensions.');
        }

        $scale = min(1, self::MAX_EDGE / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        // Cap height at 100 even if width was the long edge after weird crops.
        if ($targetHeight > self::MAX_EDGE) {
            $ratio = self::MAX_EDGE / $targetHeight;
            $targetHeight = self::MAX_EDGE;
            $targetWidth = max(1, (int) round($targetWidth * $ratio));
        }

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($resized, 255, 255, 255);
        imagefilledrectangle($resized, 0, 0, $targetWidth, $targetHeight, $white);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($image);

        $directory = trim(Str::beforeLast($path, '/'), '/');
        $newPath = ($directory !== '' ? $directory.'/' : '').Str::uuid()->toString().'.jpg';

        ob_start();
        imagejpeg($resized, null, 85);
        $binary = ob_get_clean();
        imagedestroy($resized);

        if ($binary === false || $binary === '') {
            throw new RuntimeException('Unable to encode profile picture as JPEG.');
        }

        $disk->put($newPath, $binary);

        if ($newPath !== $path) {
            $disk->delete($path);
        }

        return $newPath;
    }

    /**
     * @return \GdImage|false
     */
    protected function loadImage(string $absolute): mixed
    {
        $info = @getimagesize($absolute);

        if ($info === false) {
            return false;
        }

        return match ($info[2] ?? null) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absolute),
            IMAGETYPE_PNG => @imagecreatefrompng($absolute),
            IMAGETYPE_GIF => @imagecreatefromgif($absolute),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp')
                ? @imagecreatefromwebp($absolute)
                : false,
            default => false,
        };
    }
}
