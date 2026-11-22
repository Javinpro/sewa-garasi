<?php

namespace Tests\Unit;

use App\Models\Garage;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\GarageService;
use App\Services\RentalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

class GarageRentalTest extends TestCase
{
    use RefreshDatabase;

    private GarageService $garageService;
    private RentalService $rentalService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->garageService = new GarageService();
        $this->rentalService = new RentalService();
    }

    public function test_it_generates_incremental_garage_ids_correctly(): void
    {
        $bikes = $this->garageService->createBatchGarages('bike', 2, 10000);
        $cars = $this->garageService->createBatchGarages('car', 1, 50000);
        $evs = $this->garageService->createBatchGarages('ev_car', 1, 70000, 2500);

        $this->assertEquals('S001', $bikes[0]->id);
        $this->assertEquals('S002', $bikes[1]->id);
        $this->assertEquals('C001', $cars[0]->id);
        $this->assertEquals('E001', $evs[0]->id);
    }

    public function test_monthly_price_is_25_times_daily_price(): void
    {
        $garage = new Garage(['type' => 'car', 'daily_price' => 40000]);
        $this->assertEquals(1000000.0, $garage->getMonthlyPrice());
    }

    public function test_daily_rental_cannot_exceed_30_days(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $garage = new Garage(['type' => 'bike', 'daily_price' => 10000]);
        $vehicle = new Vehicle(['type' => 'bicycle']);

        $this->rentalService->calculateTotalCost($garage, $vehicle, 'daily', 31);
    }

    public function test_motorcycle_cannot_park_in_car_garage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $garage = new Garage(['type' => 'car', 'daily_price' => 50000]);
        $vehicle = new Vehicle(['type' => 'motorcycle']);

        $this->rentalService->calculateTotalCost($garage, $vehicle, 'daily', 5);
    }

    public function test_ev_car_in_ev_garage_includes_full_charging_cost(): void
    {
        $garage = new Garage([
            'type' => 'ev_car',
            'daily_price' => 100000,
            'electricity_price_per_kwh' => 2500,
        ]);
        $vehicle = new Vehicle([
            'type' => 'ev_car',
            'battery_capacity_kwh' => 60,
        ]);

        // 2 hari * 100.000 = 200.000 + (60 kWh * 2.500 = 150.000) = 350.000
        $total = $this->rentalService->calculateTotalCost($garage, $vehicle, 'daily', 2);
        $this->assertEquals(350000.0, $total);
    }

    public function test_ev_car_in_regular_car_garage_has_no_charging_cost(): void
    {
        $garage = new Garage([
            'type' => 'car',
            'daily_price' => 50000,
        ]);
        $vehicle = new Vehicle([
            'type' => 'ev_car',
            'battery_capacity_kwh' => 60,
        ]);

        $total = $this->rentalService->calculateTotalCost($garage, $vehicle, 'daily', 2);
        $this->assertEquals(100000.0, $total);
    }

    public function test_creating_rental_marks_garage_status_as_used(): void
    {
        $user = User::create([
            'nik' => '3578010101010001',
            'full_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'password' => Hash::make('password123'),
            'role' => 'tenant',
            'verification_status' => 'approved',
        ]);

        $garages = $this->garageService->createBatchGarages('ev_car', 1, 100000, 2500);
        $garage = $garages[0];

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'type' => 'ev_car',
            'license_plate' => 'L 1234 EV',
            'brand_and_model' => 'Hyundai Ioniq 5',
            'battery_capacity_kwh' => 72,
        ]);

        $rental = $this->rentalService->createRental(
            $user->id,
            $vehicle,
            $garage,
            'daily',
            3,
            'payment_proofs/sample.jpg'
        );

        $garage->refresh();
        $this->assertEquals('used', $garage->status);
        $this->assertEquals(480000.0, (float) $rental->total_cost);
    }
}