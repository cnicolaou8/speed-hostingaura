-- ══════════════════════════════════════════════════════════════
-- HOSTINGAURA SPEED TEST - COMPLETE DATABASE SCHEMA
-- Safe to run multiple times - won't delete existing data
-- ══════════════════════════════════════════════════════════════

USE speed_db;

-- ══════════════════════════════════════════════════════════════
-- TABLE: users
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE DEFAULT NULL,
    phone VARCHAR(20) UNIQUE DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add last_login column if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME DEFAULT NULL AFTER created_at;

-- ══════════════════════════════════════════════════════════════
-- TABLE: speed_results
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS speed_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id VARCHAR(8) UNIQUE NOT NULL,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    isp VARCHAR(255),
    download_speed DECIMAL(10,2),
    upload_speed DECIMAL(10,2),
    ping INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_test_id (test_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key if it doesn't exist (will fail silently if exists)
SET @query = IF(
    NOT EXISTS(
        SELECT NULL FROM information_schema.TABLE_CONSTRAINTS 
        WHERE CONSTRAINT_SCHEMA = 'speed_db' 
        AND TABLE_NAME = 'speed_results' 
        AND CONSTRAINT_NAME = 'speed_results_ibfk_1'
    ),
    'ALTER TABLE speed_results ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "Foreign key already exists"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ══════════════════════════════════════════════════════════════
-- TABLE: otp_verifications
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS otp_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact (contact),
    INDEX idx_expires (expires_at),
    INDEX idx_contact_otp (contact, otp_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add columns if they don't exist
ALTER TABLE otp_verifications ADD COLUMN IF NOT EXISTS verified TINYINT(1) DEFAULT 0 AFTER expires_at;
ALTER TABLE otp_verifications ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER verified;

-- ══════════════════════════════════════════════════════════════
-- TABLE: otp_verification_attempts
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS otp_verification_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact VARCHAR(255) NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_time (contact, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- TABLE: login_attempts
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_time (contact, attempted_at),
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add columns if they don't exist
ALTER TABLE login_attempts ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) AFTER contact;
ALTER TABLE login_attempts ADD COLUMN IF NOT EXISTS user_agent VARCHAR(500) AFTER ip_address;

-- ══════════════════════════════════════════════════════════════
-- VERIFICATION: Show all tables and their row counts
-- ══════════════════════════════════════════════════════════════

SELECT 'Database schema updated successfully!' AS Status;

SHOW TABLES;

SELECT 'users' AS TableName, COUNT(*) AS RowCount FROM users
UNION ALL
SELECT 'speed_results', COUNT(*) FROM speed_results
UNION ALL
SELECT 'otp_verifications', COUNT(*) FROM otp_verifications
UNION ALL
SELECT 'otp_verification_attempts', COUNT(*) FROM otp_verification_attempts
UNION ALL
SELECT 'login_attempts', COUNT(*) FROM login_attempts;

-- ══════════════════════════════════════════════════════════════
-- ADDED THE BELLOW FOR REPORT ERROR AFTER A SPEEDTEST
-- ══════════════════════════════════════════════════════════════
ALTER TABLE report_issues
    ADD COLUMN IF NOT EXISTS reporter_phone VARCHAR(30) DEFAULT NULL AFTER reporter_email,
    ADD COLUMN IF NOT EXISTS wants_contact  TINYINT(1)  NOT NULL DEFAULT 0 AFTER description;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS phone      VARCHAR(30) DEFAULT NULL UNIQUE AFTER email,
    ADD COLUMN IF NOT EXISTS last_login DATETIME    DEFAULT NULL AFTER is_verified;



    -- ══════════════════════════════════════════════════════════════
-- HostingAura Speed Test — Full SQL Setup
-- Safe to run on a fresh OR existing database.
-- ══════════════════════════════════════════════════════════════

-- 1. SPEED RESULTS
CREATE TABLE IF NOT EXISTS speed_results (
    id           INT           UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    test_id      VARCHAR(16)   NOT NULL UNIQUE,
    user_id      INT           UNSIGNED DEFAULT NULL,
    dl_speed     DECIMAL(10,2) NOT NULL DEFAULT 0,
    ul_speed     DECIMAL(10,2) NOT NULL DEFAULT 0,
    ping         SMALLINT      UNSIGNED NOT NULL DEFAULT 0,
    isp          VARCHAR(120)  DEFAULT NULL,
    device       VARCHAR(120)  DEFAULT NULL,
    ip_address   VARCHAR(45)   DEFAULT NULL,
    share_token  VARCHAR(32)   DEFAULT NULL UNIQUE,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user    (user_id),
    INDEX idx_created (created_at),
    INDEX idx_share   (share_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. REPORT ISSUES
CREATE TABLE IF NOT EXISTS report_issues (
    id              INT           UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    test_id         VARCHAR(16)   DEFAULT NULL,
    user_id         INT           UNSIGNED DEFAULT NULL,
    reporter_email  VARCHAR(191)  DEFAULT NULL,
    reporter_phone  VARCHAR(30)   DEFAULT NULL,
    category        ENUM('wrong_speed','test_failed','wrong_location','save_failed','other')
                    NOT NULL DEFAULT 'other',
    description     TEXT          NOT NULL,
    wants_contact   TINYINT(1)    NOT NULL DEFAULT 0,
    dl_speed        DECIMAL(10,2) DEFAULT NULL,
    ul_speed        DECIMAL(10,2) DEFAULT NULL,
    ping            SMALLINT      DEFAULT NULL,
    isp             VARCHAR(120)  DEFAULT NULL,
    device          VARCHAR(120)  DEFAULT NULL,
    reporter_ip     VARCHAR(45)   DEFAULT NULL,
    status          ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    admin_notes     TEXT          DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user    (user_id),
    INDEX idx_status  (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. USERS
CREATE TABLE IF NOT EXISTS users (
    id            INT          UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(191) DEFAULT NULL UNIQUE,
    phone         VARCHAR(30)  DEFAULT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_verified   TINYINT(1)   NOT NULL DEFAULT 0,
    last_login    DATETIME     DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. OTP CODES
CREATE TABLE IF NOT EXISTS otp_codes (
    id         INT          UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    contact    VARCHAR(191) NOT NULL,
    otp        VARCHAR(10)  NOT NULL,
    type       ENUM('register','reset') NOT NULL DEFAULT 'register',
    used       TINYINT(1)   NOT NULL DEFAULT 0,
    expires_at DATETIME     NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact (contact),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. LOGIN ATTEMPTS
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT          UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    contact      VARCHAR(191) NOT NULL,
    ip_address   VARCHAR(45)  NOT NULL,
    attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact   (contact),
    INDEX idx_ip        (ip_address),
    INDEX idx_attempted (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- ALREADY HAVE TABLES? Run only these ALTERs (safe, no data loss)
-- ══════════════════════════════════════════════════════════════
ALTER TABLE report_issues
    ADD COLUMN IF NOT EXISTS reporter_phone VARCHAR(30) DEFAULT NULL AFTER reporter_email,
    ADD COLUMN IF NOT EXISTS wants_contact  TINYINT(1)  NOT NULL DEFAULT 0 AFTER description;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS phone      VARCHAR(30) DEFAULT NULL UNIQUE AFTER email,
    ADD COLUMN IF NOT EXISTS last_login DATETIME    DEFAULT NULL AFTER is_verified;