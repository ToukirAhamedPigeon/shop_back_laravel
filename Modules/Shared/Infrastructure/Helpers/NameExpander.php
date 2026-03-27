<?php

namespace Modules\Shared\Infrastructure\Helpers;

class NameExpander
{
    // Define the expansion patterns
    private static array $expansionPatterns = [
        'CRUD' => ['Create', 'Read', 'Update', 'Delete'],
        'RU' => ['Read', 'Update'],
        'CRUDR' => ['Create', 'Read', 'Update', 'Delete', 'Restore'],
        'CRUDT' => ['Create', 'Read', 'Update', 'Delete', 'Trash'],
        'CRUDTR' => ['Create', 'Read', 'Update', 'Delete', 'Trash', 'Restore'],
        'C' => ['Create'],
        'R' => ['Read'],
        'U' => ['Update'],
        'D' => ['Delete'],
        'T' => ['Trash'],
        'RESTORE' => ['Restore'],
    ];

    /**
     * Expands shorthand notation like "CRUDR-admin-roles" to full permission names
     * Example: "CRUDR-admin-roles" -> ["create-admin-roles", "read-admin-roles", "update-admin-roles", "delete-admin-roles", "restore-admin-roles"]
     */
    public static function expandNames(string $input): array
    {
        if (empty(trim($input))) {
            return [];
        }

        $result = [];

        // Split by '=' first to handle multiple names
        $parts = array_map('trim', explode('=', $input));
        $parts = array_filter($parts, fn($p) => !empty($p));

        foreach ($parts as $part) {
            $expanded = self::expandSingleName($part);
            $result = array_merge($result, $expanded);
        }

        return array_values(array_unique($result));
    }

    private static function expandSingleName(string $name): array
    {
        $result = [];

        // Look for shorthand pattern at the beginning of the name
        foreach (self::$expansionPatterns as $pattern => $operations) {
            if (str_starts_with(strtoupper($name), $pattern . '-')) {
                $suffix = substr($name, strlen($pattern) + 1); // +1 for the dash
                foreach ($operations as $operation) {
                    $expandedName = strtolower($operation) . '-' . $suffix;
                    $result[] = $expandedName;
                }
                return $result;
            }
        }

        // No pattern found, return the original name
        $result[] = strtolower($name);
        return $result;
    }

    /**
     * Expands a list of names (used for permissions in role creation)
     */
    public static function expandPermissionNames(array $permissionNames): array
    {
        $expanded = [];
        foreach ($permissionNames as $name) {
            $expanded = array_merge($expanded, self::expandNames($name));
        }
        return array_values(array_unique($expanded));
    }
}
