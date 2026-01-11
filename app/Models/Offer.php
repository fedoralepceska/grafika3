<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'client_id',
        'validity_days',
        'production_start_date',
        'production_end_date',
        'price1',
        'price2',
        'price3',
        'status',
        'decline_reason',
        'contact_id',
        'production_time',
        'created_by',
        'offer_number',
        'fiscal_year',
    ];

    protected $casts = [
        'production_start_date' => 'date',
        'production_end_date' => 'date',
        'validity_days' => 'integer',
        'price1' => 'decimal:2',
        'price2' => 'decimal:2',
        'price3' => 'decimal:2',
    ];

    /**
     * Generate the next offer number for the current fiscal year
     */
    public static function generateOfferNumber(): array
    {
        $year = now()->year;

        return DB::transaction(function () use ($year) {
            $lastOffer = self::where('fiscal_year', $year)
                ->orderBy('offer_number', 'desc')
                ->lockForUpdate()
                ->first();

            return [
                'offer_number' => ($lastOffer?->offer_number ?? 0) + 1,
                'fiscal_year' => $year,
            ];
        });
    }

    /**
     * Get formatted offer number for display (e.g., #42)
     */
    public function getFormattedOfferNumberAttribute(): string
    {
        return "#{$this->offer_number}";
    }

    /**
     * Get formatted offer number with year (e.g., #42/2025)
     */
    public function getFullOfferNumberAttribute(): string
    {
        return "#{$this->offer_number}/{$this->fiscal_year}";
    }

    public function catalogItems()
    {
        return $this->belongsToMany(CatalogItem::class, 'catalog_item_offer')
                    ->withPivot('quantity', 'description','custom_price')
                    ->withTimestamps()
                    ->withTrashed(); // Include soft deleted catalog items
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    public function isDeclined()
    {
        return $this->status === 'declined';
    }
}
