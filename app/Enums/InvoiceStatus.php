<?php
namespace App\Enums;

use Illuminate\Validation\Rules\Enum;

/**
* @method static self ACTION_1()
* @method static self ACTION_2()
* @method static self ACTION_3()
*/
class InvoiceStatus extends Enum
{
    public static function values(): array
    {
        $values = [];
        $values["NOT_STARTED_YET"] = 'Not started yet';
        $values["IN_PROGRESS"] = 'In progress';
        $values["COMPLETED"] = 'Completed';

        return $values;
    }
// Define your ENUM values here

}
