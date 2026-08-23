<?php

namespace App\Services;

use App\Models\User;
use App\Support\MediaPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProfileImageService
{
    private const THUMBNAIL_SIZE = 192;

    public function replace(User $user, UploadedFile $image, string $disk = 'supabase'): User
    {
        $oldImage = $user->image;
        $oldThumbnail = $user->profile_thumbnail;
        $originalPath = $image->store('profile', $disk);

        if (!$originalPath) {
            throw new RuntimeException('The profile image could not be stored.');
        }

        $thumbnailPath = null;

        try {
            $thumbnailPath = 'profile/thumbnails/'.Str::uuid().'.webp';
            $stored = Storage::disk($disk)->put(
                $thumbnailPath,
                $this->makeSquareWebp($image),
                ['visibility' => 'public']
            );

            if (!$stored) {
                throw new RuntimeException('The profile thumbnail could not be stored.');
            }
        } catch (Throwable $exception) {
            report($exception);
            $thumbnailPath = null;
        }

        try {
            $user->forceFill([
                'image' => MediaPath::storeForDisk($originalPath, $disk),
                'profile_thumbnail' => $thumbnailPath
                    ? MediaPath::storeForDisk($thumbnailPath, $disk)
                    : null,
            ])->save();
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete(array_values(array_filter([$originalPath, $thumbnailPath])));
            throw $exception;
        }

        MediaPath::delete($oldImage);
        MediaPath::delete($oldThumbnail);

        return $user->refresh();
    }

    private function makeSquareWebp(UploadedFile $image): string
    {
        $contents = file_get_contents($image->getRealPath());
        $source = $contents === false ? false : imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('The uploaded profile image could not be decoded.');
        }

        try {
            $source = $this->orientJpeg($source, $image);
            $width = imagesx($source);
            $height = imagesy($source);
            $cropSize = min($width, $height);
            $sourceX = (int) floor(($width - $cropSize) / 2);
            $sourceY = (int) floor(($height - $cropSize) / 2);
            $thumbnail = imagecreatetruecolor(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);

            if ($thumbnail === false) {
                throw new RuntimeException('The profile thumbnail canvas could not be created.');
            }

            try {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
                imagefill($thumbnail, 0, 0, $transparent);
                imagecopyresampled(
                    $thumbnail,
                    $source,
                    0,
                    0,
                    $sourceX,
                    $sourceY,
                    self::THUMBNAIL_SIZE,
                    self::THUMBNAIL_SIZE,
                    $cropSize,
                    $cropSize
                );

                ob_start();
                $encoded = imagewebp($thumbnail, null, 82);
                $webp = ob_get_clean();

                if (!$encoded || !is_string($webp) || $webp === '') {
                    throw new RuntimeException('The profile thumbnail could not be encoded.');
                }

                return $webp;
            } finally {
                imagedestroy($thumbnail);
            }
        } finally {
            imagedestroy($source);
        }
    }

    private function orientJpeg(\GdImage $source, UploadedFile $image): \GdImage
    {
        if (!function_exists('exif_read_data') || $image->getMimeType() !== 'image/jpeg') {
            return $source;
        }

        $orientation = @exif_read_data($image->getRealPath())['Orientation'] ?? 1;
        $angle = match ((int) $orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $source;
        }

        $rotated = imagerotate($source, $angle, 0);
        if ($rotated === false) {
            return $source;
        }

        imagedestroy($source);

        return $rotated;
    }
}
