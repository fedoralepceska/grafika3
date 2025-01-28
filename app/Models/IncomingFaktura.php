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
        'incoming_number',
        'faktura_counter'
    ];

    
    protected $casts = [
        'amount' => 'float',
        'tax' => 'float',
        'cost_type' => 'integer',
        'billing_type' => 'integer',
        'client_id' => 'integer',
        'date' => 'date',
        'faktura_counter' => 'integer'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public static function getNextFakturaCounter()
    {
        // Get all used counters sorted
        $usedCounters = self::where('billing_type', 2)
                           ->whereNotNull('faktura_counter')
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
