<?php

namespace Modules\Shared\Infrastructure\Helpers;

use Closure;

class PredicateBuilder
{
    /**
     * Create a true predicate
     */
    public static function true(): Closure
    {
        return function ($item) {
            return true;
        };
    }

    /**
     * Create a false predicate
     */
    public static function false(): Closure
    {
        return function ($item) {
            return false;
        };
    }

    /**
     * Combine two predicates with OR
     */
    public static function or(Closure $predicate1, Closure $predicate2): Closure
    {
        return function ($item) use ($predicate1, $predicate2) {
            return $predicate1($item) || $predicate2($item);
        };
    }

    /**
     * Combine two predicates with AND
     */
    public static function and(Closure $predicate1, Closure $predicate2): Closure
    {
        return function ($item) use ($predicate1, $predicate2) {
            return $predicate1($item) && $predicate2($item);
        };
    }

    /**
     * Create a predicate that checks if a property equals a value
     */
    public static function equals(string $property, $value): Closure
    {
        return function ($item) use ($property, $value) {
            if (is_array($item)) {
                return ($item[$property] ?? null) === $value;
            } elseif (is_object($item)) {
                return ($item->$property ?? null) === $value;
            }
            return false;
        };
    }

    /**
     * Create a predicate that checks if a property contains a value (for strings/arrays)
     */
    public static function contains(string $property, $value): Closure
    {
        return function ($item) use ($property, $value) {
            $prop = null;

            if (is_array($item)) {
                $prop = $item[$property] ?? null;
            } elseif (is_object($item)) {
                $prop = $item->$property ?? null;
            }

            if (is_string($prop)) {
                return str_contains($prop, $value);
            } elseif (is_array($prop)) {
                return in_array($value, $prop);
            }

            return false;
        };
    }

    /**
     * Create a predicate that checks if a property is in an array of values
     */
    public static function in(string $property, array $values): Closure
    {
        return function ($item) use ($property, $values) {
            if (is_array($item)) {
                return isset($item[$property]) && in_array($item[$property], $values);
            } elseif (is_object($item)) {
                return isset($item->$property) && in_array($item->$property, $values);
            }
            return false;
        };
    }

    /**
     * Create a predicate that checks if a property is between two values
     */
    public static function between(string $property, $min, $max, bool $inclusive = true): Closure
    {
        return function ($item) use ($property, $min, $max, $inclusive) {
            $value = null;

            if (is_array($item)) {
                $value = $item[$property] ?? null;
            } elseif (is_object($item)) {
                $value = $item->$property ?? null;
            }

            if ($value === null) {
                return false;
            }

            if ($inclusive) {
                return $value >= $min && $value <= $max;
            }

            return $value > $min && $value < $max;
        };
    }

    /**
     * Negate a predicate
     */
    public static function not(Closure $predicate): Closure
    {
        return function ($item) use ($predicate) {
            return !$predicate($item);
        };
    }

    /**
     * Chain multiple predicates with AND
     */
    public static function all(Closure ...$predicates): Closure
    {
        return function ($item) use ($predicates) {
            foreach ($predicates as $predicate) {
                if (!$predicate($item)) {
                    return false;
                }
            }
            return true;
        };
    }

    /**
     * Chain multiple predicates with OR
     */
    public static function any(Closure ...$predicates): Closure
    {
        return function ($item) use ($predicates) {
            foreach ($predicates as $predicate) {
                if ($predicate($item)) {
                    return true;
                }
            }
            return false;
        };
    }

    /**
     * Create a predicate from a where array (for query builders)
     * Example: ['name' => 'John', 'age' => ['>', 18]]
     */
    public static function fromArray(array $conditions): Closure
    {
        return function ($item) use ($conditions) {
            foreach ($conditions as $key => $condition) {
                if (is_array($condition) && count($condition) === 2) {
                    // Operator syntax: ['age', '>', 18] or ['age' => ['>', 18]]
                    $operator = $condition[0];
                    $value = $condition[1];

                    $itemValue = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);

                    switch ($operator) {
                        case '>':
                            if (!($itemValue > $value)) return false;
                            break;
                        case '>=':
                            if (!($itemValue >= $value)) return false;
                            break;
                        case '<':
                            if (!($itemValue < $value)) return false;
                            break;
                        case '<=':
                            if (!($itemValue <= $value)) return false;
                            break;
                        case '!=':
                        case '<>':
                            if (!($itemValue != $value)) return false;
                            break;
                        case 'like':
                            if (!str_contains($itemValue, $value)) return false;
                            break;
                    }
                } else {
                    // Equality check: ['name' => 'John']
                    $itemValue = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
                    if ($itemValue != $condition) return false;
                }
            }
            return true;
        };
    }
}
