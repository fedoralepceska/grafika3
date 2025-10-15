<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'client_id'
    ];
    protected $casts = [
        'merge_groups' => 'array',
        'is_split_invoice' => 'boolean',
        'faktura_overrides' => 'array',
    ];

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
