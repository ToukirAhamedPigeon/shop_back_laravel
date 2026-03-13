<?php

namespace Modules\Shared\Infrastructure\Helpers;

class LabelFormatter
{
    /**
     * Convert various naming conventions to human-readable format
     * Handles:
     * - snake_case → Snake Case
     * - kebab-case → Kebab Case
     * - camelCase → Camel Case
     * - PascalCase → Pascal Case
     * - Mixed formats and acronyms
     *
     * @param string $input The string to format
     * @return string Human-readable string
     */
    public static function toReadable(string $input): string
    {
        if (empty(trim($input))) {
            return $input;
        }

        // Step 1: Normalize separators - replace hyphens and underscores with spaces
        $text = str_replace(['_', '-'], ' ', $input);

        // Step 2: Add spaces before capital letters in camelCase/PascalCase
        // This handles: "camelCase" → "camel Case", "PascalCase" → "Pascal Case"
        $text = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $text);

        // Also handle consecutive capitals (acronyms) with following lowercase
        // This handles: "XMLHttpRequest" → "XML Http Request"
        $text = preg_replace('/([A-Z]{2,})([A-Z][a-z])/', '$1 $2', $text);

        // Step 3: Split into words
        $words = explode(' ', $text);

        // Step 4: Process each word
        $formattedWords = array_map(function ($word) {
            // Trim any remaining whitespace
            $word = trim($word);
            if (empty($word)) {
                return '';
            }

            // Check if it's an acronym (all uppercase)
            if (strtoupper($word) === $word && strlen($word) > 1) {
                // Preserve acronyms fully uppercase (like API, ID, HTTP)
                return $word;
            }

            // For regular words: capitalize first letter, lowercase the rest
            // But preserve original case for the rest of the word (don't force lowercase)
            // This handles cases like "iPhone" where internal capitals should stay
            if (strlen($word) > 1) {
                return ucfirst($word);
            }

            return ucfirst(strtolower($word));
        }, array_filter($words));

        // Step 5: Join words and clean up multiple spaces
        return preg_replace('/\s+/', ' ', implode(' ', $formattedWords));
    }

    /**
     * Alternative implementation with more control over acronym handling
     */
    public static function toReadableAdvanced(string $input, array $acronyms = []): string
    {
        if (empty(trim($input))) {
            return $input;
        }

        // Default common acronyms
        $defaultAcronyms = ['API', 'ID', 'URL', 'HTTP', 'HTTPS', 'FTP', 'SSH', 'SQL', 'HTML', 'CSS', 'JS', 'PHP', 'JSON', 'XML'];
        $acronyms = array_merge($defaultAcronyms, $acronyms);

        // Create a temporary placeholder for acronyms to protect them
        $placeholders = [];
        $processed = $input;

        foreach ($acronyms as $index => $acronym) {
            $placeholder = "{{ACRONYM_{$index}}}";
            $placeholders[$placeholder] = $acronym;

            // Replace the acronym in various forms
            $processed = str_ireplace($acronym, $placeholder, $processed);
            $processed = str_ireplace(strtolower($acronym), $placeholder, $processed);
            $processed = str_ireplace(ucfirst(strtolower($acronym)), $placeholder, $processed);
        }

        // Normalize separators
        $processed = str_replace(['_', '-'], ' ', $processed);

        // Add spaces before capitals
        $processed = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $processed);

        // Split into words
        $words = explode(' ', $processed);

        // Process each word
        $formattedWords = array_map(function ($word) use ($placeholders) {
            $word = trim($word);

            // Restore acronyms
            foreach ($placeholders as $placeholder => $acronym) {
                if (strpos($word, $placeholder) !== false) {
                    return str_replace($placeholder, $acronym, $word);
                }
            }

            // Capitalize first letter, lowercase rest for regular words
            return ucfirst(strtolower($word));
        }, array_filter($words));

        return implode(' ', $formattedWords);
    }

    /**
     * Convert string to snake_case
     */
    public static function toSnakeCase(string $input): string
    {
        $text = self::toReadable($input);
        return strtolower(str_replace(' ', '_', $text));
    }

    /**
     * Convert string to kebab-case
     */
    public static function toKebabCase(string $input): string
    {
        $text = self::toReadable($input);
        return strtolower(str_replace(' ', '-', $text));
    }

    /**
     * Convert string to camelCase
     */
    public static function toCamelCase(string $input): string
    {
        $text = self::toReadable($input);
        $words = explode(' ', strtolower($text));

        $camel = $words[0];
        for ($i = 1; $i < count($words); $i++) {
            $camel .= ucfirst($words[$i]);
        }

        return $camel;
    }

    /**
     * Convert string to PascalCase
     */
    public static function toPascalCase(string $input): string
    {
        $text = self::toReadable($input);
        $words = explode(' ', strtolower($text));

        return implode('', array_map('ucfirst', $words));
    }
}
