<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientYearEndEntry extends Model
{
    protected $fillable = [
        'fiscal_year',
        'client_id',
        'client_card_statement_id',
        'initial_balance',
        'total_output_invoices',
        'total_trade_invoices',
        'total_statement_expenses',
        'total_incoming_invoices',
        'total_statement_income',
        'calculated_balance',
        'adjusted_balance',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'initial_balance' => 'decimal:2',
        'total_output_invoices' => 'decimal:2',
        'total_trade_invoices' => 'decimal:2',
        'total_statement_expenses' => 'decimal:2',
        'total_incoming_invoices' => 'decimal:2',
        'total_statement_income' => 'decimal:2',
        'calculated_balance' => 'decimal:2',
        'adjusted_balance' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function clientCardStatement()
    {
        return $this->belongsTo(ClientCardStatement::class);
    }

    /**
     * Get the final balance (adjusted if set, otherwise calculated)
     */
    public function getFinalBalanceAttribute(): float
    {
        return $this->adjusted_balance !== null 
            ? (float) $this->adjusted_balance 
            : (float) $this->calculated_balance;
    }

    /**
     * Get balance direction: positive = we owe them, negative = they owe us
     */
    public function getBalanceDirectionAttribute(): string
    {
        return $this->final_balance >= 0 ? 'we_owe' : 'they_owe';
    }

    /**
     * Check if balance has been manually adjusted
     */
    public function getIsAdjustedAttribute(): bool
    {
        return $this->adjusted_balance !== null;
    }

    /**
     * Scope to filter by fiscal year
     */
    public function scopeForFiscalYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }
}
