<?php

namespace Modules\Shared\Infrastructure\Helpers;

class FilePathHelper
{
    public static function apiUploadsPath(string ...$paths): string
    {
        $base = base_path('public/uploads');

        if (!is_dir($base)) {
            $base = public_path('uploads');
        }

        return realpath($base . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $paths))
            ?: $base . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $paths);
    }
}
