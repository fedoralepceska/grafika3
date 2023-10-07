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
// Define your ENUM values here
const NOT_STARTED_YET = 'Not started yet';
const IN_PROGRESS = 'In progress';
const COMPLETED = 'Completed';
}
