<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\ArticleController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'start_date',
        'end_date',
        'client_id',
        'contact_id',
        'invoice_title',
        'comment',
        'mockup',
        'status',
        'created_by',
        'perfect',
        'onHold',
        'ripFirst',
        'revisedArt',
        'revisedArtComplete',
        'rush',
        'additionalArt',
        'LockedNote',
        'article_id',
        'faktura_id',
        'order_number',
        'fiscal_year',
        'archived',
        'archived_at',
    ];

    protected $enumCasts = [
        'status' => InvoiceStatus::class,
    ];

    protected $casts = [
        'archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];

    /**
     * Generate the next order number for the current fiscal year
     */
    public static function generateOrderNumber(): array
    {
        $year = now()->year;

        return DB::transaction(function () use ($year) {
            $lastOrder = self::where('fiscal_year', $year)
                ->orderBy('order_number', 'desc')
                ->lockForUpdate()
                ->first();

            return [
                'order_number' => ($lastOrder?->order_number ?? 0) + 1,
                'fiscal_year' => $year,
            ];
        });
    }

    /**
     * Get formatted order number for display (e.g., #42)
     */
    public function getFormattedOrderNumberAttribute(): string
    {
        return "#{$this->order_number}";
    }

    /**
     * Get formatted order number with year (e.g., #42/2025)
     */
    public function getFullOrderNumberAttribute(): string
    {
        return "#{$this->order_number}/{$this->fiscal_year}";
    }

    /**
     * Scope for non-archived orders
     */
    public function scopeActive($query)
    {
        return $query->where('archived', false);
    }

    /**
     * Scope for specific fiscal year
     */
    public function scopeForFiscalYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

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
    public function article() {
        return $this->belongsTo(Article::class, 'article_id');
    }
    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function faktura()
    {
        return $this->belongsTo(Faktura::class, 'faktura_id');
    }
}
