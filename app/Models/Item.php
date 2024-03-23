<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'item';

    protected $fillable = [
        'client_id', 'certificate_id', 'income', 'expense', 'code', 'reference_to', 'comment'
    ];

    public function certificate()
    {
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
