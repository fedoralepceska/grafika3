<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Faktura extends Model
{
    protected $table = 'faktura';
    protected $fillable = [
        'isInvoiced', 
        'comment', 
        'created_by', 
        'merge_groups', 
        'payment_deadline_override',
        'is_split_invoice',
        'split_group_identifier',
        'parent_order_id',
        'faktura_overrides',
        'client_id',
        'faktura_number',
        'fiscal_year',
    ];
    protected $casts = [
        'merge_groups' => 'array',
        'is_split_invoice' => 'boolean',
        'faktura_overrides' => 'array',
    ];

    /**
     * Boot method to auto-generate faktura_number on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($faktura) {
            if (empty($faktura->faktura_number)) {
                $faktura->generateFakturaNumber();
            }
        });
    }

    /**
     * Generate the next sequential faktura number for the current fiscal year
     */
    public function generateFakturaNumber(): void
    {
        $fiscalYear = $this->fiscal_year ?? (int) date('Y');
        $this->fiscal_year = $fiscalYear;

        $maxNumber = DB::table('faktura')
            ->where('fiscal_year', $fiscalYear)
            ->max('faktura_number') ?? 0;

        $this->faktura_number = $maxNumber + 1;
    }

    /**
     * Get the formatted faktura number (e.g., "5/2026")
     */
    public function getFormattedFakturaNumberAttribute(): string
    {
        return $this->faktura_number . '/' . $this->fiscal_year;
    }

    /**
     * Scope to filter by fiscal year
     */
    public function scopeForFiscalYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

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

    public function tradeItems()
    {
        return $this->hasMany(FakturaTradeItem::class);
    }

    /**
     * Get the parent order for split invoices
     */
    public function parentOrder()
    {
        return $this->belongsTo(Invoice::class, 'parent_order_id');
    }

    /**
     * Get jobs directly assigned to this faktura
     */
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    /**
     * Get the additional services associated with the faktura.
     */
    public function additionalServices()
    {
        return $this->hasMany(AdditionalService::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get override for a specific order title
     */
    public function getOrderTitleOverride($orderId)
    {
        $overrides = $this->faktura_overrides ?? [];
        return $overrides['order_titles'][$orderId] ?? null;
    }

    /**
     * Get override for a specific job name
     */
    public function getJobNameOverride($jobId)
    {
        $overrides = $this->faktura_overrides ?? [];
        return $overrides['job_names'][$jobId] ?? null;
    }

    /**
     * Get override for a specific job quantity
     */
    public function getJobQuantityOverride($jobId)
    {
        $overrides = $this->faktura_overrides ?? [];
        return $overrides['job_quantities'][$jobId] ?? null;
    }

    /**
     * Set override for order title
     */
    public function setOrderTitleOverride($orderId, $title)
    {
        $overrides = $this->faktura_overrides ?? [];
        if (!isset($overrides['order_titles'])) {
            $overrides['order_titles'] = [];
        }
        $overrides['order_titles'][$orderId] = $title;
        $this->faktura_overrides = $overrides;
    }

    /**
     * Set override for job name
     */
    public function setJobNameOverride($jobId, $name)
    {
        $overrides = $this->faktura_overrides ?? [];
        if (!isset($overrides['job_names'])) {
            $overrides['job_names'] = [];
        }
        $overrides['job_names'][$jobId] = $name;
        $this->faktura_overrides = $overrides;
    }

    /**
     * Set override for job quantity
     */
    public function setJobQuantityOverride($jobId, $quantity)
    {
        $overrides = $this->faktura_overrides ?? [];
        if (!isset($overrides['job_quantities'])) {
            $overrides['job_quantities'] = [];
        }
        $overrides['job_quantities'][$jobId] = $quantity;
        $this->faktura_overrides = $overrides;
    }

    /**
     * Get display value for order title (override if exists, otherwise original)
     */
    public function getDisplayOrderTitle($orderId, $originalTitle)
    {
        $override = $this->getOrderTitleOverride($orderId);
        return $override ?? $originalTitle;
    }

    /**
     * Get display value for job name (override if exists, otherwise original)
     */
    public function getDisplayJobName($jobId, $originalName)
    {
        $override = $this->getJobNameOverride($jobId);
        return $override ?? $originalName;
    }

    /**
     * Get display value for job quantity (override if exists, otherwise original)
     */
    public function getDisplayJobQuantity($jobId, $originalQuantity)
    {
        $override = $this->getJobQuantityOverride($jobId);
        return $override ?? $originalQuantity;
    }
}
