<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $warehouseNameLower = Str::lower($this->name);
        $specialLower = array_map(fn ($n) => Str::lower($n), $specialWarehouses);
        return in_array($warehouseNameLower, $specialLower, true);
    }

    /**
     * Check if this warehouse is a trade warehouse (case-insensitive)
     */
    public function isTradeWarehouse()
    {
        return Str::lower($this->name) === Str::lower('магацин 2 трговија');
    }

    /**
     * Get all special warehouses (case-insensitive query)
     */
    public static function getSpecialWarehouses()
    {
        $specialWarehouses = ['магацин 2 трговија', 'магацин 3 основни средства'];

        // SQLite LOWER is ASCII-only; fallback to PHP filter when using sqlite
        if (DB::getDriverName() === 'sqlite') {
            $specialLower = array_map(fn ($n) => Str::lower($n), $specialWarehouses);
            return self::all()->filter(function ($warehouse) use ($specialLower) {
                return in_array(Str::lower($warehouse->name), $specialLower, true);
            })->values();
        }

        return self::where(function($query) use ($specialWarehouses) {
            foreach ($specialWarehouses as $warehouse) {
                $query->orWhereRaw('LOWER(name) = LOWER(?)', [$warehouse]);
            }
        })->get();
    }

    /**
     * Get trade warehouses only (case-insensitive query)
     */
    public static function getTradeWarehouses()
    {
        $target = 'магацин 2 трговија';

        if (DB::getDriverName() === 'sqlite') {
            return self::all()->filter(function ($warehouse) use ($target) {
                return Str::lower($warehouse->name) === Str::lower($target);
            })->values();
        }

        return self::whereRaw('LOWER(name) = LOWER(?)', [$target])->get();
    }
}
