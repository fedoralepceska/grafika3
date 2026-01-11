<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalYearClosure extends Model
{
    protected $fillable = [
        'fiscal_year',
        'module',
        'closed_at',
        'closed_by',
        'summary',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'summary' => 'array',
    ];

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public static function isYearClosed(int $year, string $module = 'orders'): bool
    {
        return self::where('fiscal_year', $year)
            ->where('module', $module)
            ->exists();
    }

    public static function getClosureInfo(int $year, string $module = 'orders'): ?self
    {
        return self::where('fiscal_year', $year)
            ->where('module', $module)
            ->with('closedByUser')
            ->first();
    }
}
