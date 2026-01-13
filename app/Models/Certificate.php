<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Certificate extends Model
{
    protected $table = 'certificate';

    protected $fillable = [
        'date', 'bank', 'bankAccount', 'created_by', 'id_per_bank', 'fiscal_year', 'archived', 'archived_at'
    ];

    protected $casts = [
        'archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    /**
     * Generate the next id_per_bank for the given bank and current fiscal year
     */
    public static function generateIdPerBank(string $bank): array
    {
        $year = now()->year;

        return DB::transaction(function () use ($bank, $year) {
            $lastStatement = self::where('bank', $bank)
                ->where('fiscal_year', $year)
                ->orderBy('id_per_bank', 'desc')
                ->lockForUpdate()
                ->first();

            return [
                'id_per_bank' => ($lastStatement?->id_per_bank ?? 0) + 1,
                'fiscal_year' => $year,
            ];
        });
    }

    /**
     * Get formatted statement number with year (e.g., "5/2026")
     */
    public function getFullStatementNumberAttribute(): string
    {
        return "{$this->id_per_bank}/{$this->fiscal_year}";
    }

    /**
     * Scope for non-archived statements
     */
    public function scopeActive($query)
    {
        return $query->where('archived', false);
    }

    /**
     * Scope for specific fiscal year
     */
    public function scopeForFiscalYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
