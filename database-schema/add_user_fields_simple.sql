-- Add missing fields to users table
-- Run this in your MySQL database: pms_nexus_tms
-- Date: 2025-10-08

USE pms_nexus_tms;

-- Add columns (comment out any that already exist)
ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email;
ALTER TABLE users ADD COLUMN department VARCHAR(100) NULL AFTER phone;
ALTER TABLE users ADD COLUMN location VARCHAR(255) NULL AFTER department;
ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER location;
ALTER TABLE users ADD COLUMN last_login DATETIME NULL AFTER avatar;
ALTER TABLE users ADD COLUMN is_email_verified TINYINT(1) DEFAULT 0 AFTER last_login;
ALTER TABLE users ADD COLUMN is_phone_verified TINYINT(1) DEFAULT 0 AFTER is_email_verified;
ALTER TABLE users ADD COLUMN notes TEXT NULL AFTER is_phone_verified;

-- Update last_login for existing users
UPDATE users SET last_login = updated_at WHERE last_login IS NULL;

-- Verify the structure
DESCRIBE users;

-- Sample data update (optional)
-- UPDATE users SET phone = '+1 (555) 123-4567', department = 'IT', location = 'Phoenix, AZ' WHERE id = 1;
