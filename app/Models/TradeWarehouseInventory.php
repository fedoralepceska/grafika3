<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeWarehouseInventory extends Model
{
    use HasFactory;

    protected $table = 'trade_warehouse_inventory';

    protected $fillable = [
        'article_id',
        'warehouse_id',
        'quantity',
        'purchase_price',
        'selling_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:5',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Add stock to inventory
     */
    public static function addStock($articleId, $warehouseId, $quantity, $purchasePrice = null, $sellingPrice = null)
    {
        $inventory = self::firstOrCreate(
            ['article_id' => $articleId, 'warehouse_id' => $warehouseId],
            ['quantity' => 0, 'purchase_price' => $purchasePrice, 'selling_price' => $sellingPrice]
        );

        $inventory->quantity += $quantity;
        if ($purchasePrice !== null) {
            $inventory->purchase_price = $purchasePrice;
        }
        if ($sellingPrice !== null) {
            $inventory->selling_price = $sellingPrice;
        }
        $inventory->save();

        return $inventory;
    }

    /**
     * Remove stock from inventory
     */
    public static function removeStock($articleId, $warehouseId, $quantity)
    {
        $inventory = self::where('article_id', $articleId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($inventory && $inventory->quantity >= $quantity) {
            $inventory->quantity -= $quantity;
            $inventory->save();
            return true;
        }

        return false;
    }

    /**
     * Check if sufficient stock is available
     */
    public static function hasStock($articleId, $warehouseId, $quantity)
    {
        $inventory = self::where('article_id', $articleId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $inventory && $inventory->quantity >= $quantity;
    }
}
