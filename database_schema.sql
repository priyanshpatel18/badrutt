-- Create database
CREATE DATABASE IF NOT EXISTS hotel_management;
USE hotel_management;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Room types table
CREATE TABLE IF NOT EXISTS room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    price_per_night DECIMAL(10, 2) NOT NULL,
    capacity INT NOT NULL
);

-- Rooms table
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    room_type_id INT NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    floor INT NOT NULL,
    description TEXT,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    special_requests TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Insert sample data for room types
INSERT INTO room_types (name, description, price_per_night, capacity) VALUES
('Standard', 'A comfortable room with basic amenities', 99.99, 2),
('Deluxe', 'Spacious room with premium amenities', 149.99, 2),
('Suite', 'Luxury suite with separate living area', 249.99, 4),
('Family', 'Large room suitable for families', 199.99, 5);

-- Insert sample rooms
INSERT INTO rooms (room_number, room_type_id, status, floor, description) VALUES
('101', 1, 'available', 1, 'Standard room with city view'),
('102', 1, 'available', 1, 'Standard room with garden view'),
('201', 2, 'available', 2, 'Deluxe room with balcony'),
('202', 2, 'available', 2, 'Deluxe room with ocean view'),
('301', 3, 'available', 3, 'Executive suite with jacuzzi'),
('401', 4, 'available', 4, 'Family room with two queen beds');

-- Insert admin user
INSERT INTO users (username, password, email, full_name, role) VALUES
('admin', '$2y$10$6SLe.qO/HZVFiKO7OX1g5eMzJRwmEj/WoYl7sd7ZUKpEieYQn5jee', 'admin@hotel.com', 'Admin User', 'admin');
-- Default password is 'admin123' (hashed with bcrypt)