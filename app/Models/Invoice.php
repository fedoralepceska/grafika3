<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
        'client_id',
        'contact_id',
        'invoice_title',
        'comment',
        'status',
        'created_by',
        'perfect',
        'onHold',
        'ripFirst',
        'revisedArt',
        'revisedArtComplete',
        'rush',
        'additionalArt'
    ];



    protected $enumCasts = [
        'status' => InvoiceStatus::class,
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];

    public function jobs(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->BelongsToMany(Job::class);
    }

    public function client_id(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function historyLogs()
    {
        return $this->hasMany(HistoryLog::class);
    }

    public function user() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client() {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
