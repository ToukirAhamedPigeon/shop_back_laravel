<?php

namespace Modules\Shared\Infrastructure\Helpers;

use Illuminate\Support\Facades\File;

class FilePathHelper
{
    /**
     * Find the actual uploads folder by scanning upward from the base path.
     * Returns a full absolute path combined with any provided relative path segments.
     *
     * @param string ...$paths Relative path segments to append to the uploads path
     * @return string Full absolute path
     */
    public static function getApiUploadsPath(string ...$paths): string
    {
        try {
            $baseDir = base_path();
            $dir = new \SplFileInfo($baseDir);

            while ($dir && $dir->getPathname() !== $dir->getPath()) {
                $currentPath = $dir->getPathname();

                // Try several candidate layouts relative to the current ancestor
                $candidates = [
                    $currentPath . '/public/uploads',
                    $currentPath . '/storage/app/public/uploads',
                    $currentPath . '/uploads',
                    $currentPath . '/public/storage/uploads',
                ];

                // Also try with Modules/Shared structure
                $candidates[] = $currentPath . '/Modules/Shared/Infrastructure/Storage/uploads';
                $candidates[] = $currentPath . '/Modules/Shared/Resources/uploads';

                foreach ($candidates as $candidate) {
                    if (File::isDirectory($candidate)) {
                        $final = $candidate . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $paths);
                        $absolutePath = realpath($final) ?: $final;

                        // Normalize path (remove duplicate segments)
                        $absolutePath = self::normalizePath($absolutePath);

                        return $absolutePath;
                    }
                }

                $dir = $dir->getPathInfo();
            }

            // Fallback paths
            $fallback = base_path('public/uploads');
            $fallbackFull = $fallback . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $paths);

            // Normalize duplicate segments
            $fallbackFull = self::normalizePath($fallbackFull);

            // Create directory if it doesn't exist
            if (!File::isDirectory($fallback)) {
                File::makeDirectory($fallback, 0755, true);
            }

            return $fallbackFull;

        } catch (\Exception $ex) {
            // Last resort: return combined relative path from base directory
            $safe = base_path('public/uploads') . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $paths);
            return self::normalizePath($safe);
        }
    }

    /**
     * Normalize path by removing duplicate directory separators and resolving parent references
     */
    private static function normalizePath(string $path): string
    {
        // Replace duplicate directory separators
        $path = preg_replace('/[\/\\\\]+/', DIRECTORY_SEPARATOR, $path);

        // Resolve parent references (..)
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $result = [];

        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($result);
            } elseif ($part !== '.' && $part !== '') {
                $result[] = $part;
            }
        }

        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $result);
    }

    /**
     * Get the URL path for a stored file
     */
    public static function getFileUrl(string $filePath): string
    {
        // Remove base path and return relative URL
        $basePath = base_path();
        $relativePath = str_replace($basePath, '', $filePath);

        // Convert backslashes to forward slashes for URLs
        return str_replace('\\', '/', $relativePath);
    }

    /**
     * Get the uploads directory for a specific module
     */
    public static function getModuleUploadsPath(string $module, string ...$paths): string
    {
        return self::getApiUploadsPath('modules', $module, ...$paths);
    }
}
