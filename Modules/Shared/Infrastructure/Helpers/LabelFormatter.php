<?php

namespace Modules\Shared\Infrastructure\Helpers;

class LabelFormatter
{
    public static function toReadable(string $input): string
    {
        if (!$input) {
            return $input;
        }

        $text = str_replace(['_', '-'], ' ', $input);
        $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);

        return ucwords($text);
    }
}
