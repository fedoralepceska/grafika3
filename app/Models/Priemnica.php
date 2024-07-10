<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Priemnica extends Model
{
    use HasFactory;

    protected $table = 'priemnica';

    protected $fillable = [
        'warehouse',
        'client_id',
        'article_id',
        'quantity',
        'comment',
    ];

    public function client() {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'priemnica_id'); // Using the bridge table (optional)
    }
}
