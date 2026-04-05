<?php

namespace Modules\Shared\Infrastructure\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class FileHelper
{
    private static ?RemoteFileHelper $remoteHelper = null;

    // Default resize settings
    public static array $defaultResizeOptions = [
        'enabled' => true,
        'maxWidth' => 1000,
        'maxHeight' => 1000,
        'resizeMode' => 'max' // max, stretch, pad
    ];

    /**
     * Initialize remote helper
     */
    public static function initialize(): void
    {
        if (!self::$remoteHelper) {
            try {
                self::$remoteHelper = app(RemoteFileHelper::class);
            } catch (\Exception $e) {
                Log::warning('RemoteFileHelper not available', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Save file (automatically chooses local or remote based on config)
     */
    public static function saveFile(?UploadedFile $file, string $subFolder = 'users', ?array $resizeOptions = null): ?string
    {
        self::initialize();

        if (self::$remoteHelper) {
            return self::$remoteHelper->saveFile($file, $subFolder, $resizeOptions);
        }

        // Fallback to local
        return self::saveFileLocal($file, $subFolder, $resizeOptions);
    }

    /**
     * Delete file (automatically chooses local or remote based on config)
     */
    public static function deleteFile(?string $filePath, string $disk = 'public'): void
    {
        self::initialize();

        if (self::$remoteHelper) {
            self::$remoteHelper->deleteFile($filePath);
        } else {
            self::deleteFileLocal($filePath, $disk);
        }
    }

    /**
     * Save file locally (fallback)
     */
    public static function saveFileLocal(?UploadedFile $file, string $subFolder = 'users', ?array $resizeOptions = null): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        // Validate file size (5MB max)
        $maxSize = 5 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size must be less than 5MB');
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'webp'];

        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($mimeType, $allowedTypes) || !in_array($extension, $allowedExtensions)) {
            throw new \Exception('Only JPG, PNG, WEBP images are allowed');
        }

        // Generate unique filename
        $fileName = Str::uuid()->toString() . '.' . $extension;
        $options = $resizeOptions ?? self::$defaultResizeOptions;

        // Process and save image
        $imagePath = "uploads/{$subFolder}/{$fileName}";
        $fullPath = Storage::disk('public')->path($imagePath);

        // Create directory if not exists
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        if ($options['enabled'] && str_starts_with($mimeType, 'image/')) {
            $image = Image::read($file->getRealPath());
            $width = $image->width();
            $height = $image->height();

            // Resize if needed
            if ($width > $options['maxWidth'] || $height > $options['maxHeight']) {
                $ratio = min($options['maxWidth'] / $width, $options['maxHeight'] / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
                $image->scale($newWidth, $newHeight);
            }

            // Save based on format
            $quality = 90;
            if ($extension === 'png') {
                $image->encodeByExtension('png', quality: $quality)->save($fullPath);
            } elseif (in_array($extension, ['jpg', 'jpeg'])) {
                $image->encodeByExtension('jpg', quality: $quality)->save($fullPath);
            } elseif ($extension === 'webp') {
                $image->encodeByExtension('webp', quality: $quality)->save($fullPath);
            } else {
                $image->encodeByExtension('png', quality: $quality)->save($fullPath);
            }
        } else {
            // Save original file
            $file->storeAs("uploads/{$subFolder}", $fileName, 'public');
        }

        return "/storage/{$imagePath}";
    }

    /**
     * Delete file locally
     */
    public static function deleteFileLocal(?string $filePath, string $disk = 'public'): void
    {
        if (empty($filePath)) {
            return;
        }

        $relativePath = str_replace('/storage/', '', $filePath);
        if (Storage::disk($disk)->exists($relativePath)) {
            Storage::disk($disk)->delete($relativePath);
        }
    }

    /**
     * Resize an existing image
     */
    public static function resizeImage(string $imagePath, int $width, int $height): void
    {
        $relativePath = str_replace('/storage/', '', $imagePath);
        $fullPath = Storage::disk('public')->path($relativePath);

        if (file_exists($fullPath)) {
            try {
                $img = Image::read($fullPath);
                $img->scale($width, $height);
                $img->save($fullPath);
            } catch (\Exception $e) {
                Log::warning('Failed to resize image: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get file URL
     */
    public static function getFileUrl(?string $filePath): ?string
    {
        if (empty($filePath)) {
            return null;
        }
        return asset($filePath);
    }

    /**
     * Set remote helper instance
     */
    public static function setRemoteHelper(RemoteFileHelper $remoteHelper): void
    {
        self::$remoteHelper = $remoteHelper;
    }
}
