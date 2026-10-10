<?php

namespace App\Services\Helpers;

use BackedEnum;

final class EnumToString
{
    public function convert(BackedEnum|string|null $input): string
    {
        if ($input === null) {
            return 'Unknown';
        }

        $value = $input instanceof BackedEnum
            ? $input->value
            : $input;

        return str($value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
