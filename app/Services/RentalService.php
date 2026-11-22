<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Class RentalService
 * Mengelola perhitungan biaya sewa, transaksi sewa baru, dan perpanjangan sewa.
 */
class RentalService
{
    /**
     * Menghitung total biaya sewa (termasuk biaya listrik penuh jika EV di garasi EV).
     */
    public function calculateTotalCost(
        Garage $garage,
        Vehicle $vehicle,
        string $rentalType,
        ?int $durationDays = null
    ): float {
        if (!in_array($garage->type, $vehicle->getAllowedGarageTypes(), true)) {
            throw new InvalidArgumentException("Kendaraan tipe {$vehicle->type} tidak boleh masuk ke garasi {$garage->type}.");
        }

        if ($rentalType === 'daily') {
            if ($durationDays === null || $durationDays < 1 || $durationDays > 30) {
                throw new InvalidArgumentException("Sewa harian harus antara 1 sampai 30 hari.");
            }
            $baseCost = (float) $garage->daily_price * $durationDays;
        } elseif ($rentalType === 'monthly') {
            $baseCost = $garage->getMonthlyPrice();
        } else {
            throw new InvalidArgumentException("Tipe sewa tidak valid.");
        }

        // Mobil listrik di garasi mobil listrik wajib mengisi listrik hingga penuh
        $electricityCost = 0.0;
        if ($vehicle->type === 'ev_car' && $garage->type === 'ev_car') {
            $electricityCost = (float) $vehicle->battery_capacity_kwh * (float) $garage->electricity_price_per_kwh;
        }

        return $baseCost + $electricityCost;
    }

    /**
     * Membuat transaksi sewa garasi baru dan mengubah status garasi menjadi 'used'.
     */
    public function createRental(
        int $userId,
        Vehicle $vehicle,
        Garage $garage,
        string $rentalType,
        ?int $durationDays,
        string $paymentProofPath
    ): Rental {
        if ($garage->status !== 'available') {
            throw new InvalidArgumentException("Garasi {$garage->id} sedang digunakan.");
        }

        $totalCost = $this->calculateTotalCost($garage, $vehicle, $rentalType, $durationDays);
        $startDate = Carbon::today();
        $endDate = $rentalType === 'daily'
            ? $startDate->copy()->addDays($durationDays)
            : $startDate->copy()->addMonth();

        return DB::transaction(function () use (
            $userId, $vehicle, $garage, $rentalType, $durationDays,
            $startDate, $endDate, $totalCost, $paymentProofPath
        ) {
            $rental = Rental::create([
                'user_id' => $userId,
                'garage_id' => $garage->id,
                'vehicle_id' => $vehicle->id,
                'rental_type' => $rentalType,
                'duration_days' => $rentalType === 'daily' ? $durationDays : null,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_cost' => $totalCost,
                'payment_proof_path' => $paymentProofPath,
                'status' => 'active',
            ]);

            $garage->markAsUsed();

            return $rental;
        });
    }

    /**
     * Memperpanjang tanggal selesai sewa oleh Admin dan menghitung sisa biaya tambahan.
     */
    public function extendRental(
        Rental $rental,
        Carbon $newEndDate,
        string $paymentProofPath,
        int $adminId
    ): RentalExtension {
        $oldEndDate = Carbon::parse($rental->end_date);
        if ($newEndDate->lessThanOrEqualTo($oldEndDate)) {
            throw new InvalidArgumentException("Tanggal selesai baru harus lebih besar dari tanggal selesai saat ini.");
        }

        $extraDays = $oldEndDate->diffInDays($newEndDate);
        $additionalCost = $extraDays * (float) $rental->garage->daily_price;

        return DB::transaction(function () use (
            $rental, $oldEndDate, $newEndDate, $additionalCost, $paymentProofPath, $adminId
        ) {
            $extension = RentalExtension::create([
                'rental_id' => $rental->id,
                'old_end_date' => $oldEndDate->toDateString(),
                'new_end_date' => $newEndDate->toDateString(),
                'additional_cost' => $additionalCost,
                'payment_proof_path' => $paymentProofPath,
                'processed_by_admin_id' => $adminId,
            ]);

            $rental->update([
                'end_date' => $newEndDate->toDateString(),
                'total_cost' => (float) $rental->total_cost + $additionalCost,
            ]);

            return $extension;
        });
    }
}
