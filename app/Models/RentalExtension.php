<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class RentalExtension
 * Mencatat riwayat perpanjangan tanggal selesai sewa oleh Admin.
 */
class RentalExtension extends Model
{
    protected $fillable = [
        'rental_id',
        'old_end_date',
        'new_end_date',
        'additional_cost',
        'payment_proof_path',
        'processed_by_admin_id',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_admin_id');
    }
}
