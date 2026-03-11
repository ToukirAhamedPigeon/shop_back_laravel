<?php

namespace Modules\Shared\Infrastructure\Helpers;

class LabelFormatter
{
    /**
     * Convert camelCase, snake_case, kebab-case to human-readable format
     * e.g., read-admin-dashboard → Read Admin Dashboard
     */
    public static function toReadable(string $input): string
    {
        if (!$input) {
            return $input;
        }

        // Replace underscores and hyphens with space
        $text = str_replace(['_', '-'], ' ', $input);

        // Add space before camelCase capitals
        $text = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $text);

        // Capitalize first letter of each word, lowercase the rest
        $words = array_map(function ($w) {
            // Preserve acronyms fully uppercase (like API, ID)
            if (strtoupper($w) === $w) return $w;
            return ucfirst(strtolower($w));
        }, explode(' ', $text));

        return implode(' ', $words);
    }
}
