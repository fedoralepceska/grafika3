<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $table = 'certificate';

    protected $fillable = [
        'date', 'bank', 'bankAccount', 'bank_certificate_id'
    ];

    public function bankCertificate()
    {
        return $this->belongsTo(BankCertificate::class, 'bank_certificate_id');
    }
}
