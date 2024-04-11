<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCardStatement extends Model
{
    use HasFactory;

    protected $table = 'client_card_statement';

    protected $fillable=[
        'name',
        'functionInfo',
        'phone',
        'fax',
        'mobile_phone',
        'edb',
        'account',
        'bank',
        'initial_statement',
        'initial_cash',
        'credit_limit',
        'payment_deadline',
        'client_id'
    ];

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function updateFields(array $data)
    {
        $this->update($data);
    }
}
