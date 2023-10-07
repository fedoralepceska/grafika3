<?php

namespace App\Enums;

use Illuminate\Validation\Rules\Enum;

class MachineCut extends Enum
{
    public static function values(): array
    {
        $values = [];

        for ($i = 1; $i <= 2; $i++) {
            $values["MACHINE_CUT_$i"] = "Machine cut $i";
        }

        return $values;
    }
}
