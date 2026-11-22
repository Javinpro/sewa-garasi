<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Models\Vehicle;
use App\Services\RentalService;
use Illuminate\Http\Request;

/**
 * Class RentalController
 * Menangani pengecekan garasi tersedia dan transaksi sewa garasi baru oleh penyewa.
 */
class RentalController extends Controller
{
    public function __construct(private RentalService $rentalService) {}

    /**
     * Menampilkan daftar garasi yang tersedia hari ini sesuai tipe kendaraan.
     */
    public function availableGarages(Request $request)
    {
        $validated = $request->validate([
            'vehicle_type' => 'required|in:bicycle,motorcycle,car,ev_car',
        ]);

        $dummyVehicle = new Vehicle(['type' => $validated['vehicle_type']]);
        $allowedTypes = $dummyVehicle->getAllowedGarageTypes();

        $garages = Garage::where('status', 'available')
            ->whereIn('type', $allowedTypes)
            ->orderBy('id')
            ->get();

        return response()->json([
            'allowed_garage_types' => $allowedTypes,
            'data' => $garages,
        ]);
    }

    /**
     * Menyimpan transaksi sewa garasi baru dan mengubah status garasi menjadi 'used'.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_type' => 'required|in:bicycle,motorcycle,car,ev_car',
            'license_plate' => 'required_unless:vehicle_type,bicycle|nullable|string|max:20',
            'brand_and_model' => 'required|string|max:255',
            'battery_capacity_kwh' => 'required_if:vehicle_type,ev_car|nullable|numeric|min:1',
            'garage_id' => 'required|exists:garages,id',
            'rental_type' => 'required|in:daily,monthly',
            'duration_days' => 'required_if:rental_type,daily|nullable|integer|min:1|max:30',
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = $request->user();
        if ($user->verification_status !== 'approved') {
            return response()->json(['message' => 'Akun Anda belum diverifikasi Admin.'], 403);
        }

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'type' => $validated['vehicle_type'],
            'license_plate' => $validated['vehicle_type'] === 'bicycle' ? null : $validated['license_plate'],
            'brand_and_model' => $validated['brand_and_model'],
            'battery_capacity_kwh' => $validated['vehicle_type'] === 'ev_car' ? $validated['battery_capacity_kwh'] : null,
        ]);

        $garage = Garage::findOrFail($validated['garage_id']);
        $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');

        $rental = $this->rentalService->createRental(
            $user->id,
            $vehicle,
            $garage,
            $validated['rental_type'],
            $validated['duration_days'] ?? null,
            $proofPath
        );

        return response()->json([
            'message' => 'Sewa garasi berhasil disimpan. Status garasi kini used.',
            'data' => $rental->load(['garage', 'vehicle']),
        ], 201);
    }
}
