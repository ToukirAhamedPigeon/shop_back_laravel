<?php

namespace Modules\Shared\Infrastructure\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileHelper
{
    /**
     * Save an uploaded file to the storage
     *
     * @param UploadedFile|null $file The uploaded file
     * @param string $subFolder Subfolder within uploads directory (default: 'users')
     * @param string $disk Storage disk (default: 'public')
     * @return string|null The relative path to the saved file, or null if failed
     * @throws \Exception If file validation fails
     */
    public static function saveFile(?UploadedFile $file, string $subFolder = 'users', string $disk = 'public'): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        // Validate file size (5MB max)
        $maxSize = 5 * 1024 * 1024; // 5MB in bytes
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
        $path = $file->storeAs("uploads/{$subFolder}", $fileName, $disk);

        // Return the relative path (with leading slash for web access)
        return $path ? "/storage/{$path}" : null;
    }

    /**
     * Save file synchronously (async version kept for interface compatibility)
     */
    public static function saveFileAsync(?UploadedFile $file, string $subFolder = 'users', string $disk = 'public'): ?string
    {
        return self::saveFile($file, $subFolder, $disk);
    }

    /**
     * Delete a file from storage
     *
     * @param string|null $filePath The file path (can be with or without /storage/ prefix)
     * @param string $disk Storage disk (default: 'public')
     */
    public static function deleteFile(?string $filePath, string $disk = 'public'): void
    {
        if (empty($filePath)) {
            return;
        }

        // Remove /storage/ prefix if present to get the storage path
        $relativePath = str_replace('/storage/', '', $filePath);

        if (Storage::disk($disk)->exists($relativePath)) {
            Storage::disk($disk)->delete($relativePath);
        }
    }

    /**
     * Delete file synchronously (async version kept for interface compatibility)
     */
    public static function deleteFileAsync(?string $filePath, string $disk = 'public'): void
    {
        self::deleteFile($filePath, $disk);
    }

    /**
     * Get the full URL for a file
     */
    public static function getFileUrl(?string $filePath): ?string
    {
        if (empty($filePath)) {
            return null;
        }

        return asset($filePath);
    }

    /**
     * Check if file exists
     */
    public static function fileExists(?string $filePath, string $disk = 'public'): bool
    {
        if (empty($filePath)) {
            return false;
        }

        $relativePath = str_replace('/storage/', '', $filePath);
        return Storage::disk($disk)->exists($relativePath);
    }
}
