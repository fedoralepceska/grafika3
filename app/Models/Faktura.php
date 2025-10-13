<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faktura extends Model
{
    protected $table = 'faktura';
    protected $fillable = [
        'isInvoiced', 
        'comment', 
        'created_by', 
        'merge_groups', 
        'payment_deadline_override',
        'is_split_invoice',
        'split_group_identifier',
        'parent_order_id'
    ];
    protected $casts = [
        'merge_groups' => 'array',
        'is_split_invoice' => 'boolean',
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

    /**
     * Get the parent order for split invoices
     */
    public function parentOrder()
    {
        return $this->belongsTo(Invoice::class, 'parent_order_id');
    }

    /**
     * Get jobs directly assigned to this faktura
     */
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}
