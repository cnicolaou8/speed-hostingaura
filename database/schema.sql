-- Speed Test Database Schema
-- Run this in your MySQL/phpMyAdmin

CREATE DATABASE IF NOT EXISTS speed_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE speed_db;

CREATE TABLE IF NOT EXISTS speed_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id VARCHAR(8) UNIQUE NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    isp VARCHAR(255),
    download_speed DECIMAL(10,2),
    upload_speed DECIMAL(10,2),
    ping INT,
    user_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_test_id (test_id),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS otp_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    verified TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact VARCHAR(255) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact (contact)
);