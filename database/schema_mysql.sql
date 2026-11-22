CREATE DATABASE IF NOT EXISTS sewa_garasi;
USE sewa_garasi;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(16) NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'tenant') NOT NULL DEFAULT 'tenant',
    verification_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE garages (
    id VARCHAR(4) PRIMARY KEY,
    type ENUM('bike', 'car', 'ev_car') NOT NULL,
    daily_price DECIMAL(12, 2) NOT NULL,
    electricity_price_per_kwh DECIMAL(12, 2) NULL,
    status ENUM('available', 'used') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('bicycle', 'motorcycle', 'car', 'ev_car') NOT NULL,
    license_plate VARCHAR(20) NULL,
    brand_and_model VARCHAR(255) NOT NULL,
    battery_capacity_kwh DECIMAL(8, 2) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_vehicles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE rentals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    garage_id VARCHAR(4) NOT NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    rental_type ENUM('daily', 'monthly') NOT NULL,
    duration_days INT UNSIGNED NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_cost DECIMAL(14, 2) NOT NULL,
    payment_proof_path VARCHAR(255) NOT NULL,
    status ENUM('active', 'completed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_rentals_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_rentals_garage FOREIGN KEY (garage_id) REFERENCES garages(id),
    CONSTRAINT fk_rentals_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

CREATE TABLE rental_extensions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rental_id BIGINT UNSIGNED NOT NULL,
    old_end_date DATE NOT NULL,
    new_end_date DATE NOT NULL,
    additional_cost DECIMAL(14, 2) NOT NULL,
    payment_proof_path VARCHAR(255) NOT NULL,
    processed_by_admin_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_ext_rental FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE,
    CONSTRAINT fk_ext_admin FOREIGN KEY (processed_by_admin_id) REFERENCES users(id)
);
