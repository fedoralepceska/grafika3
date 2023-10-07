<?php

namespace App\Enums;

use Illuminate\Validation\Rules\Enum;

class MachinePrint extends Enum
{
    public static function values(): array
    {
        $values = [];

        for ($i = 1; $i <= 10; $i++) {
            $values["MACHINE_PRINT_$i"] = "Machine print $i";
        }

        return $values;
    }
}
