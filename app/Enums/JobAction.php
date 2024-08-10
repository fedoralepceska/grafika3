<?php
// app/Enums/JobAction.php

namespace App\Enums;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobAction extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status', 'quantity'];
}
