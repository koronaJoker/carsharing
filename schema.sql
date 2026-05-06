CREATE TABLE clients (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    idnp VARCHAR(13) UNIQUE NOT NULL CHECK (idnp ~ '^[0-9]{13}$'),
    driver_license VARCHAR(30) UNIQUE NOT NULL,
    driver_rating NUMERIC(2,1) DEFAULT 5.0 CHECK (driver_rating >= 0 AND driver_rating <= 5),
    role VARCHAR(30) NOT NULL DEFAULT 'client'
        CHECK (role IN ('client', 'admin')),
    password_hash TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE cars (
    id SERIAL PRIMARY KEY,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT CHECK (year >= 1990),
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    fuel_type VARCHAR(30),
    transmission VARCHAR(30),
    price_per_minute NUMERIC(6,2) NOT NULL CHECK (price_per_minute > 0),
    status VARCHAR(30) DEFAULT 'available'
        CHECK (status IN ('available', 'rented', 'maintenance', 'inactive'))
);

CREATE TABLE rentals (
    id SERIAL PRIMARY KEY,
    client_id INT NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    car_id INT NOT NULL REFERENCES cars(id) ON DELETE CASCADE,
    start_time TIMESTAMP NOT NULL DEFAULT NOW(),
    end_time TIMESTAMP,
    total_cost NUMERIC(10,2) DEFAULT 0 CHECK (total_cost >= 0),
    status VARCHAR(30) DEFAULT 'active'
        CHECK (status IN ('active', 'finished', 'cancelled'))
);

CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    rental_id INT NOT NULL REFERENCES rentals(id) ON DELETE CASCADE,
    amount NUMERIC(10,2) NOT NULL CHECK (amount >= 0),
    payment_method VARCHAR(30) NOT NULL
        CHECK (payment_method IN ('card', 'cash', 'online')),
    payment_status VARCHAR(30) DEFAULT 'pending'
        CHECK (payment_status IN ('pending', 'paid', 'failed', 'refunded')),
    paid_at TIMESTAMP
);

CREATE TABLE fines (
    id SERIAL PRIMARY KEY,

    client_id INT NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    rental_id INT REFERENCES rentals(id) ON DELETE SET NULL,

    title VARCHAR(100) NOT NULL,
    description TEXT,

    amount NUMERIC(8,2) NOT NULL CHECK (amount > 0),

    rating_penalty NUMERIC(2,1) NOT NULL DEFAULT 0.1
        CHECK (rating_penalty >= 0 AND rating_penalty <= 5),

    status VARCHAR(30) NOT NULL DEFAULT 'unpaid'
        CHECK (status IN ('unpaid', 'paid', 'cancelled')),

    created_at TIMESTAMP DEFAULT NOW()
);

INSERT INTO clients 
(full_name, email, phone, idnp, driver_license, driver_rating, role, password_hash)
VALUES
('Ion Popescu', 'ion@gmail.com', '+37368111222', '2000123456789', 'MD123456', 4.8, 'client', 'hash123'),
('Maria Rusu', 'maria@gmail.com', '+37369123456', '2000987654321', 'MD654321', 5.0, 'client', 'hash456'),
('admin', 'admin', '+37360000123', '9999999999999', 'ADMIN12345', 5.0, 'admin', '$2y$10$rO1K4wDk6luxge4urIbZTe.853oumSDl.z9FZDIRx7FxwXj1DT0P6');

INSERT INTO cars
(brand, model, year, plate_number, fuel_type, transmission, price_per_minute, status)
VALUES
('Toyota', 'Yaris', 2021, 'KAA123', 'hybrid', 'automatic', 2.50, 'available'),
('Skoda', 'Octavia', 2020, 'KBB456', 'diesel', 'automatic', 3.00, 'available'),
('Renault', 'Zoe', 2022, 'KCC789', 'electric', 'automatic', 2.80, 'maintenance');

INSERT INTO rentals
(client_id, car_id, start_time, end_time, total_cost, status)
VALUES
(1, 1, NOW() - INTERVAL '60 minutes', NOW(), 150.00, 'finished');

INSERT INTO payments
(rental_id, amount, payment_method, payment_status, paid_at)
VALUES
(1, 150.00, 'card', 'paid', NOW());

TRUNCATE TABLE cars RESTART IDENTITY CASCADE;

INSERT INTO cars (brand, model, year, plate_number, fuel_type, transmission, price_per_minute, status) VALUES
('Dacia', 'Logan', 2019, 'KAA111', 'petrol', 'manual', 2.20, 'available'),
('Dacia', 'Sandero', 2020, 'KBB222', 'petrol', 'manual', 2.30, 'available'),
('Renault', 'Clio', 2021, 'KCC333', 'petrol', 'automatic', 2.60, 'available'),
('Volkswagen', 'Polo', 2021, 'KDD444', 'petrol', 'automatic', 2.80, 'available'),
('Skoda', 'Octavia', 2022, 'KEE555', 'petrol', 'automatic', 3.20, 'available'),
('Toyota', 'Corolla', 2022, 'KFF666', 'hybrid', 'automatic', 3.30, 'available'),
('Hyundai', 'i30', 2021, 'KGG777', 'petrol', 'automatic', 3.00, 'available'),
('Kia', 'Ceed', 2021, 'KHH888', 'petrol', 'automatic', 3.00, 'available'),
('Nissan', 'Leaf', 2020, 'KII999', 'electric', 'automatic', 2.50, 'available'),
('BMW', '1 Series', 2023, 'KJJ000', 'petrol', 'automatic', 5.00, 'available');


