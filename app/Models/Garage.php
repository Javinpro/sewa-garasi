<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Garage
 * Merepresentasikan entitas garasi kendaraan (Sepeda, Mobil, Mobil Listrik).
 */
class Garage extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'daily_price',
        'electricity_price_per_kwh',
        'status',
    ];

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'garage_id', 'id');
    }

    /**
     * Menghitung harga sewa bulanan (25x harga sewa harian).
     */
    public function getMonthlyPrice(): float
    {
        return (float) $this->daily_price * 25;
    }

    /**
     * Mengubah status garasi menjadi 'used' setelah disewa.
     */
    public function markAsUsed(): void
    {
        $this->update(['status' => 'used']);
    }

    /**
     * Mengubah status garasi menjadi 'available'.
     */
    public function markAsAvailable(): void
    {
        $this->update(['status' => 'available']);
    }
}
