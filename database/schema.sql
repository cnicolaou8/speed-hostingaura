-- ══════════════════════════════════════════════════════════════
-- schema.sql — Complete database schema for speed.hostingaura.com
-- Run this file once in phpMyAdmin to set up all tables
-- Location: Plesk → phpMyAdmin → speed_db → SQL tab → paste & run
-- ══════════════════════════════════════════════════════════════

-- Create and select database
CREATE DATABASE IF NOT EXISTS speed_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE speed_db;

-- ══════════════════════════════════════════════════════════════
-- TABLE: users
-- Stores registered user accounts
-- Supports both email and phone registration (one or the other)
-- ══════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255) UNIQUE DEFAULT NULL,   -- NULL if registered via phone
    phone         VARCHAR(20)  UNIQUE DEFAULT NULL,   -- NULL if registered via email
    password_hash VARCHAR(255) NOT NULL,              -- BCRYPT hashed password
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login    DATETIME DEFAULT NULL,              -- Updated on each login
    INDEX idx_email (email),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- TABLE: speed_results
-- Stores every speed test result (guests and logged-in users)
-- user_id is NULL for guest tests
-- ══════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS speed_results (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    test_id        VARCHAR(8)    UNIQUE NOT NULL,     -- 8-char shareable ID
    user_id        INT           DEFAULT NULL,         -- NULL for guest tests
    ip_address     VARCHAR(45)   NOT NULL,             -- Supports IPv6
    isp            VARCHAR(255),                       -- Internet Service Provider
    download_speed DECIMAL(10,2),                     -- In Mbps
    upload_speed   DECIMAL(10,2),                     -- In Mbps
    ping           INT,                               -- In milliseconds
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_test_id    (test_id),
    INDEX idx_user_id    (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- TABLE: otp_verifications
-- Stores generated OTP codes during registration
-- verified=1 once the user enters the correct code
-- ══════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS otp_verifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    contact    VARCHAR(255) NOT NULL,                 -- Email or phone number
    otp_code   VARCHAR(6)   NOT NULL,                 -- 6-digit code
    expires_at DATETIME     NOT NULL,                 -- OTP expiry time
    verified   TINYINT(1)   DEFAULT 0,                -- 0=unused, 1=used
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact     (contact),
    INDEX idx_expires     (expires_at),
    INDEX idx_contact_otp (contact, otp_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- TABLE: otp_verification_attempts
-- Tracks wrong OTP attempts to prevent brute force attacks
-- Rate limit: max 5 attempts per 5 minutes (set in config.php)
-- ══════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS otp_verification_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    contact      VARCHAR(255) NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_time (contact, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- TABLE: login_attempts
-- Tracks failed login attempts per contact and IP address
-- Used for brute force protection and security alerts
-- ══════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    contact      VARCHAR(255) NOT NULL,
    ip_address   VARCHAR(45),                         -- Supports IPv6
    user_agent   VARCHAR(500),                        -- Browser info
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_time (contact, attempted_at),
    INDEX idx_ip_time      (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- SCHEDULED CLEANUP (run via cron job or manually)
-- Keeps the database clean from expired/old records
-- ══════════════════════════════════════════════════════════════

-- Delete expired OTPs older than 1 hour:
-- DELETE FROM otp_verifications WHERE expires_at < NOW();

-- Delete old OTP attempts older than 24 hours:
-- DELETE FROM otp_verification_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Delete old login attempts older than 24 hours:
-- DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);