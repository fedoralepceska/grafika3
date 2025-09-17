<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndividualOrder extends Model
{
	use HasFactory;

	protected $fillable = [
		'invoice_id',
		'client_id',
		'total_amount',
		'paid_status',
		'notes',
	];

	protected $casts = [
		'total_amount' => 'decimal:2',
	];

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
	}

	public function client()
	{
		return $this->belongsTo(Client::class);
	}
}
