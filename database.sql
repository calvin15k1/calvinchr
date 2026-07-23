-- Calvin Christian Portfolio Database
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS calvin_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE calvin_portfolio;

-- Media table for photos and videos
CREATE TABLE IF NOT EXISTS media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('photo', 'video') NOT NULL,
    category VARCHAR(100) DEFAULT 'general',
    file_path VARCHAR(500) NOT NULL,
    thumbnail_path VARCHAR(500),
    featured TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Projects / Works table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    description TEXT,
    category ENUM('photography', 'videography', 'editing') NOT NULL,
    cover_image VARCHAR(500),
    video_url VARCHAR(500),
    client VARCHAR(255),
    year INT,
    featured TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Project media junction
CREATE TABLE IF NOT EXISTS project_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    media_id INT,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
);

-- Messages / Contact form
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin users
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin record (login is handled in php/auth.php with hardcoded credentials)
-- Username: admin  |  Password: admin123
-- The admin_users table is kept for future DB-based auth extension.
INSERT INTO admin_users (username, password) VALUES 
('admin', '$2y$10$YourHashHere.ReplaceIfSwitchingToDBAuth');

-- Sample placeholder media entries (replace file_path with your actual files)
INSERT INTO media (title, description, type, category, file_path, thumbnail_path, featured, sort_order) VALUES
('Golden Hour Bali', 'Sunset photography at Tanah Lot, Bali', 'photo', 'landscape', 'uploads/photos/sample1.jpg', 'uploads/photos/sample1.jpg', 1, 1),
('Rice Fields Ubud', 'Morning light over Tegalalang rice terraces', 'photo', 'landscape', 'uploads/photos/sample2.jpg', 'uploads/photos/sample2.jpg', 1, 2),
('Wedding Portrait', 'Intimate wedding photography session', 'photo', 'portrait', 'uploads/photos/sample3.jpg', 'uploads/photos/sample3.jpg', 1, 3),
('Bali Cinematic Reel', 'A cinematic journey through Bali', 'video', 'travel', 'uploads/videos/sample1.mp4', 'uploads/photos/sample1.jpg', 1, 1),
('Wedding Film', 'Full wedding documentary edit', 'video', 'wedding', 'uploads/videos/sample2.mp4', 'uploads/photos/sample2.jpg', 1, 2);

INSERT INTO projects (title, subtitle, description, category, cover_image, client, year, featured, sort_order) VALUES
('Bali Cinematic Series', 'Travel & Culture', 'A visual journey exploring the hidden gems of Bali — from misty rice terraces at dawn to sacred temple ceremonies at dusk.', 'videography', 'uploads/photos/sample1.jpg', 'Personal Project', 2024, 1, 1),
('Sacred Moments', 'Wedding Photography', 'Capturing love stories in the heart of Bali. Natural light, real emotions, timeless frames.', 'photography', 'uploads/photos/sample2.jpg', 'Private Client', 2024, 1, 2),
('Brand Story Edit', 'Commercial Editing', 'A full-service video editing project for a local Bali resort — color grading, sound design, motion graphics.', 'editing', 'uploads/photos/sample3.jpg', 'Komaneka Resort', 2024, 1, 3);

-- Analytics table (auto-created by PHP, but included here for manual import)
CREATE TABLE IF NOT EXISTS analytics (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    page        VARCHAR(255) NOT NULL DEFAULT '/',
    referrer    VARCHAR(500),
    user_agent  VARCHAR(500),
    ip_hash     VARCHAR(64),
    visited_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page (page),
    INDEX idx_visited (visited_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    service        ENUM('photography','videography','editing') NOT NULL,
    theme          VARCHAR(100),
    package        VARCHAR(100),
    client_name    VARCHAR(255) NOT NULL,
    client_email   VARCHAR(255) NOT NULL,
    client_phone   VARCHAR(100),
    booked_date    DATE NOT NULL,
    start_hour     TINYINT,
    duration       TINYINT DEFAULT 1,
    urgent         TINYINT DEFAULT 0,
    payment_method ENUM('bank','paypal') NOT NULL DEFAULT 'bank',
    total_price    VARCHAR(255),
    notes          TEXT,
    status         ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    delivery_date  DATE,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date_service (booked_date, service),
    INDEX idx_email (client_email)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
