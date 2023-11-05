<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryLog extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_id', 'action', 'performed_by'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
