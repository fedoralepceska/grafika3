<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCardStatement extends Model
{
    use HasFactory;

    protected $fillable=[
        'name',
        'function',
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
