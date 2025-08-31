<?php

namespace App\Enums;

use Illuminate\Validation\Rules\Enum;

class Material extends Enum
{
    public static function values(): array
    {
        $values = [];

        for ($i = 1; $i <= 29; $i++) {
            $values["MATERIAL_$i"] = "Material $i";
        }

        return $values;
    }
}
