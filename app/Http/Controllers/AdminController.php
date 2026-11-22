<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\User;
use App\Services\GarageService;
use App\Services\RentalService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Class AdminController
 * Menangani fitur manajemen garasi, verifikasi KTP, perpanjangan sewa, dan laporan.
 */
class AdminController extends Controller
{
    public function __construct(
        private GarageService $garageService,
        private RentalService $rentalService
    ) {}

    public function createGarages(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:bike,car,ev_car',
            'count' => 'required|integer|min:1|max:999',
            'daily_price' => 'required|numeric|min:1',
            'electricity_price_per_kwh' => 'required_if:type,ev_car|nullable|numeric|min:1',
        ]);

        $garages = $this->garageService->createBatchGarages(
            $validated['type'],
            $validated['count'],
            $validated['daily_price'],
            $validated['electricity_price_per_kwh'] ?? null
        );

        return response()->json([
            'message' => count($garages) . ' garasi berhasil dibuat.',
            'data' => $garages,
        ], 201);
    }

    public function verifyTenant(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $user->update(['verification_status' => $validated['status']]);

        return response()->json([
            'message' => "Status penyewa diubah menjadi {$validated['status']}.",
            'data' => $user,
        ]);
    }

    public function activeRentalsToday()
    {
        $today = Carbon::today()->toDateString();
        $rentals = Rental::with(['user', 'garage', 'vehicle'])
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        return response()->json(['data' => $rentals]);
    }

    public function extendRental(Request $request, Rental $rental)
    {
        $validated = $request->validate([
            'new_end_date' => 'required|date|after:' . $rental->end_date,
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
        $extension = $this->rentalService->extendRental(
            $rental,
            Carbon::parse($validated['new_end_date']),
            $proofPath,
            $request->user()->id
        );

        return response()->json([
            'message' => 'Sewa berhasil diperpanjang.',
            'data' => $extension,
        ]);
    }

    /**
     * Laporan sewa garasi dengan 4 filter:
     * 1. Per bulan dalam tahun tertentu (?year=2026)
     * 2. Per hari dalam bulan tertentu (?year=2026&month=11)
     * 3. Per Tipe Garasi (?garage_type=ev_car)
     * 4. Per Penyewa (?user_id=2)
     */
    public function report(Request $request)
    {
        $query = Rental::with(['user:id,full_name,nik', 'garage:id,type', 'vehicle:id,type,brand_and_model']);

        if ($request->filled('year') && !$request->filled('month')) {
            $query->whereYear('start_date', $request->year);
        }

        if ($request->filled('year') && $request->filled('month')) {
            $query->whereYear('start_date', $request->year)
                  ->whereMonth('start_date', $request->month);
        }

        if ($request->filled('garage_type')) {
            $query->whereHas('garage', fn($q) => $q->where('type', $request->garage_type));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $rentals = $query->orderByDesc('start_date')->get();

        return response()->json([
            'total_transactions' => $rentals->count(),
            'total_revenue' => $rentals->sum('total_cost'),
            'data' => $rentals,
        ]);
    }
}
