<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable=[
        'name',
        'address',
        'city'
    ];

    public function contacts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function clientCardStatement(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ClientCardStatement::class);
    }
}
