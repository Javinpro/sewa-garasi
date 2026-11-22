<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Vehicle
 * Merepresentasikan kendaraan milik penyewa.
 */
class Vehicle extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'license_plate',
        'brand_and_model',
        'battery_capacity_kwh',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Mengembalikan daftar tipe garasi yang diizinkan untuk kendaraan ini.
     * - Sepeda & Sepeda Motor -> bike
     * - Mobil Bensin -> car
     * - Mobil Listrik -> car, ev_car
     *
     * @return array<string>
     */
    public function getAllowedGarageTypes(): array
    {
        return match ($this->type) {
            'bicycle', 'motorcycle' => ['bike'],
            'car' => ['car'],
            'ev_car' => ['car', 'ev_car'],
            default => [],
        };
    }
}
