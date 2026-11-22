<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16)->unique();
            $table->string('full_name');
            $table->string('phone', 20);
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'tenant'])->default('tenant');
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });

        Schema::create('garages', function (Blueprint $table) {
            $table->string('id', 4)->primary();
            $table->enum('type', ['bike', 'car', 'ev_car']);
            $table->decimal('daily_price', 12, 2);
            $table->decimal('electricity_price_per_kwh', 12, 2)->nullable();
            $table->enum('status', ['available', 'used'])->default('available');
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['bicycle', 'motorcycle', 'car', 'ev_car']);
            $table->string('license_plate', 20)->nullable();
            $table->string('brand_and_model');
            $table->decimal('battery_capacity_kwh', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('garage_id', 4);
            $table->foreign('garage_id')->references('id')->on('garages');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->enum('rental_type', ['daily', 'monthly']);
            $table->unsignedInteger('duration_days')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_cost', 14, 2);
            $table->string('payment_proof_path');
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->timestamps();
        });

        Schema::create('rental_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete();
            $table->date('old_end_date');
            $table->date('new_end_date');
            $table->decimal('additional_cost', 14, 2);
            $table->string('payment_proof_path');
            $table->foreignId('processed_by_admin_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_extensions');
        Schema::dropIfExists('rentals');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('garages');
        Schema::dropIfExists('users');
    }
};
