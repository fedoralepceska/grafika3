<?php

namespace App\Enums;

enum BillType: int
{
    case FISCAL_BILL = 1;
    case INVOICE = 2;
    case OTHER = 3;

    public function label(): string
    {
        return match($this) {
            self::FISCAL_BILL => 'фискална сметка',
            self::INVOICE => 'фактура',
            self::OTHER => 'останато',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'id' => $case->value,
            'name' => $case->label()
        ], self::cases());
    }
} 