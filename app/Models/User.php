<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 * Merepresentasikan pengguna aplikasi (Admin dan Penyewa).
 */
class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'nik',
        'full_name',
        'phone',
        'email',
        'password',
        'role',
        'verification_status',
    ];

    protected $hidden = [
        'password',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}
