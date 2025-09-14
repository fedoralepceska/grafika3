<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'warehouses';

    protected $fillable = [
        'name',
        'address',
        'phone',
    ];

    /**
     * Check if this warehouse is a special warehouse (case-insensitive)
     */
    public function isSpecialWarehouse()
    {
        $specialWarehouses = ['магацин 2 трговија', 'магацин 3 основни средства'];
        return in_array(strtolower($this->name), array_map('strtolower', $specialWarehouses));
    }

    /**
     * Check if this warehouse is a trade warehouse (case-insensitive)
     */
    public function isTradeWarehouse()
    {
        return strtolower($this->name) === strtolower('магацин 2 трговија');
    }

    /**
     * Get all special warehouses (case-insensitive query)
     */
    public static function getSpecialWarehouses()
    {
        $specialWarehouses = ['магацин 2 трговија', 'магацин 3 основни средства'];
        return self::where(function($query) use ($specialWarehouses) {
            foreach ($specialWarehouses as $warehouse) {
                $query->orWhereRaw('LOWER(name) = ?', [strtolower($warehouse)]);
            }
        })->get();
    }

    /**
     * Get trade warehouses only (case-insensitive query)
     */
    public static function getTradeWarehouses()
    {
        return self::whereRaw('LOWER(name) = ?', [strtolower('магацин 2 трговија')])->get();
    }
}
