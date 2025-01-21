<?php

namespace App\Enums;

enum CostType: int
{
    case MATERIAL = 1;
    case TRADE = 2;
    case FIXED_ASSETS = 3;
    case EXPENSE = 4;
    case OTHER = 5;

    public function label(): string
    {
        return match($this) {
            self::MATERIAL => 'материјално',
            self::TRADE => 'трговија',
            self::FIXED_ASSETS => 'основни средства',
            self::EXPENSE => 'трошок',
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