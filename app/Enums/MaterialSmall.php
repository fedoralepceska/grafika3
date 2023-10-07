<?php

// app/Enums/JobAction.php

namespace App\Enums;

use Illuminate\Validation\Rules\Enum;

/**
 * @method static self ACTION_1()
 * @method static self ACTION_2()
 * @method static self ACTION_3()
 * @method static self ACTION_4()
 * @method static self ACTION_5()
 * @method static self ACTION_6()
 * @method static self ACTION_7()
 * @method static self ACTION_8()
 * @method static self ACTION_9()
 * @method static self ACTION_10()
 */
class MaterialSmall extends Enum
{
    protected static function values(): array
    {
        $values = [];

        for ($i = 1; $i <= 34; $i++) {
            $values["MATERIAL_SMALL_$i"] = "Material small $i";
        }

        return $values;
    }
}
