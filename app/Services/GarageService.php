<?php

namespace App\Services;

use App\Models\Garage;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Class GarageService
 * Menangani pembuatan garasi berkelompok dan penomoran ID otomatis.
 */
class GarageService
{
    private const PREFIX_MAP = [
        'bike' => 'S',
        'car' => 'C',
        'ev_car' => 'E',
    ];

    /**
     * Membuat sejumlah garasi baru secara berkelompok sesuai tipe.
     *
     * @param string $type Tipe garasi ('bike', 'car', 'ev_car')
     * @param int $count Jumlah garasi yang ingin dibuat (1..999)
     * @param float $dailyPrice Harga sewa harian
     * @param float|null $kwhPrice Harga isi listrik per kWh (wajib jika ev_car)
     * @return array<Garage>
     * @throws InvalidArgumentException
     */
    public function createBatchGarages(
        string $type,
        int $count,
        float $dailyPrice,
        ?float $kwhPrice = null
    ): array {
        if (!isset(self::PREFIX_MAP[$type])) {
            throw new InvalidArgumentException("Tipe garasi tidak valid.");
        }

        if ($count < 1 || $count > 999) {
            throw new InvalidArgumentException("Jumlah garasi harus antara 1 sampai 999.");
        }

        if ($dailyPrice <= 0) {
            throw new InvalidArgumentException("Harga sewa harian harus lebih dari 0.");
        }

        if ($type === 'ev_car' && ($kwhPrice === null || $kwhPrice <= 0)) {
            throw new InvalidArgumentException("Harga listrik per kWh wajib diisi untuk garasi mobil listrik.");
        }

        $prefix = self::PREFIX_MAP[$type];
        $lastNumber = $this->getLastIncrementNumber($prefix);

        if ($lastNumber + $count > 999) {
            throw new InvalidArgumentException("Total garasi untuk tipe {$type} melebihi batas maksimum 999.");
        }

        $created = [];
        DB::transaction(function () use ($type, $count, $dailyPrice, $kwhPrice, $prefix, $lastNumber, &$created) {
            for ($i = 1; $i <= $count; $i++) {
                $id = sprintf('%s%03d', $prefix, $lastNumber + $i);
                $created[] = Garage::create([
                    'id' => $id,
                    'type' => $type,
                    'daily_price' => $dailyPrice,
                    'electricity_price_per_kwh' => $type === 'ev_car' ? $kwhPrice : null,
                    'status' => 'available',
                ]);
            }
        });

        return $created;
    }

    /**
     * Mengambil nomor urut terakhir untuk prefix tertentu.
     */
    private function getLastIncrementNumber(string $prefix): int
    {
        $lastGarage = Garage::where('id', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        return $lastGarage ? (int) substr($lastGarage->id, 1) : 0;
    }
}
