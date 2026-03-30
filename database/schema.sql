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