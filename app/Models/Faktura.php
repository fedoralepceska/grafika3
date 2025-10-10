<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faktura extends Model
{
    protected $table = 'faktura';
    protected $fillable = ['isInvoiced', 'comment', 'created_by', 'merge_groups', 'payment_deadline_override'];
    protected $casts = [
        'merge_groups' => 'array',
    ];

    /**
     * Get the invoices associated with the faktura.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tradeItems()
    {
        return $this->hasMany(FakturaTradeItem::class);
    }
}
