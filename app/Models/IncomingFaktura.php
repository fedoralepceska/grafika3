<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingFaktura extends Model
{
    protected $table = 'incoming_faktura';

    protected $fillable = [
        'warehouse',
        'cost_type',
        'billing_type',
        'description',
        'comment',
        'amount',
        'tax',
        'client_id',
        'date',
        'due_date',
        'incoming_number',
        'faktura_counter',
        'tax_a_amount',
        'tax_b_amount',
        'tax_c_amount',
        'tax_d_amount',
    ];

    
    protected $casts = [
        'amount' => 'float',
        'tax' => 'float',
        'cost_type' => 'integer',
        'billing_type' => 'integer',
        'client_id' => 'integer',
        'date' => 'date',
        'due_date' => 'date',
        'faktura_counter' => 'integer',
        'tax_a_amount' => 'float',
        'tax_b_amount' => 'float',
        'tax_c_amount' => 'float',
        'tax_d_amount' => 'float',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public static function getNextFakturaCounter()
    {
        // Get all used counters sorted (across all billing types).
        $usedCounters = self::whereNotNull('faktura_counter')
                           ->orderBy('faktura_counter')
                           ->pluck('faktura_counter')
                           ->toArray();

        if (empty($usedCounters)) {
            return 1;
        }

        // Find first gap in sequence
        $prev = 0;
        foreach ($usedCounters as $counter) {
            if ($counter > $prev + 1) {
                // Found a gap
                return $prev + 1;
            }
            $prev = $counter;
        }

        // No gaps found, return next number
        return $prev + 1;
    }
}
