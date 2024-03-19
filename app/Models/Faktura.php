<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faktura extends Model
{
    protected $table = 'faktura';
    protected $fillable = ['isInvoiced', 'comment', 'created_by'];

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
}
