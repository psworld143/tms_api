-- Carrier Admin Database Schema - Simple Version
-- Date: 2025-10-08
-- Run this first to create the core tables
-- Database: pms_nexus_tms

USE pms_nexus_tms;

-- Main carriers table
CREATE TABLE IF NOT EXISTS carriers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrier_code VARCHAR(50) UNIQUE NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    legal_name VARCHAR(255) NULL,
    dba_name VARCHAR(255) NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    fax VARCHAR(20) NULL,
    website VARCHAR(255) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(50) NULL,
    zip_code VARCHAR(20) NULL,
    country VARCHAR(100) DEFAULT 'USA',
    mc_number VARCHAR(50) NULL,
    dot_number VARCHAR(50) NULL,
    tax_id VARCHAR(50) NULL,
    scac_code VARCHAR(10) NULL,
    carrier_type ENUM('Asset-Based', 'Broker', '3PL', 'Freight Forwarder', 'Other') DEFAULT 'Asset-Based',
    service_types JSON NULL,
    operating_regions JSON NULL,
    fleet_size INT DEFAULT 0,
    driver_count INT DEFAULT 0,
    account_status ENUM('pending', 'active', 'inactive', 'suspended', 'terminated') DEFAULT 'pending',
    onboarding_status ENUM('incomplete', 'in_progress', 'completed') DEFAULT 'incomplete',
    payment_terms VARCHAR(50) DEFAULT 'Net 30',
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    safety_rating ENUM('Satisfactory', 'Conditional', 'Unsatisfactory', 'Not Rated') DEFAULT 'Not Rated',
    carrier_rating DECIMAL(3,2) DEFAULT 0.00,
    is_preferred BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    notes TEXT NULL,
    INDEX idx_carrier_code (carrier_code),
    INDEX idx_company_name (company_name),
    INDEX idx_mc_number (mc_number),
    INDEX idx_dot_number (dot_number),
    INDEX idx_status (account_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carrier contacts
CREATE TABLE IF NOT EXISTS carrier_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrier_id INT NOT NULL,
    contact_type ENUM('primary', 'billing', 'dispatch', 'safety', 'maintenance', 'emergency', 'other') DEFAULT 'primary',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    title VARCHAR(100) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    mobile VARCHAR(20) NULL,
    fax VARCHAR(20) NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_carrier_id (carrier_id),
    FOREIGN KEY (carrier_id) REFERENCES carriers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carrier insurance
CREATE TABLE IF NOT EXISTS carrier_insurance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrier_id INT NOT NULL,
    insurance_type ENUM('liability', 'cargo', 'workers_comp', 'general_liability', 'auto', 'other') NOT NULL,
    policy_number VARCHAR(100) NULL,
    insurance_company VARCHAR(255) NULL,
    coverage_amount DECIMAL(15,2) NULL,
    effective_date DATE NULL,
    expiration_date DATE NULL,
    certificate_url VARCHAR(500) NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    status ENUM('active', 'expired', 'pending', 'cancelled') DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_carrier_id (carrier_id),
    INDEX idx_expiration_date (expiration_date),
    FOREIGN KEY (carrier_id) REFERENCES carriers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carrier documents
CREATE TABLE IF NOT EXISTS carrier_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrier_id INT NOT NULL,
    document_type ENUM('w9', 'contract', 'insurance_cert', 'authority', 'broker_agreement', 'carrier_packet', 'other') NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    document_url VARCHAR(500) NOT NULL,
    file_size INT NULL,
    mime_type VARCHAR(100) NULL,
    expiration_date DATE NULL,
    uploaded_by INT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_verified BOOLEAN DEFAULT FALSE,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    status ENUM('active', 'expired', 'archived', 'rejected') DEFAULT 'active',
    notes TEXT NULL,
    INDEX idx_carrier_id (carrier_id),
    INDEX idx_document_type (document_type),
    FOREIGN KEY (carrier_id) REFERENCES carriers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carrier equipment
CREATE TABLE IF NOT EXISTS carrier_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrier_id INT NOT NULL,
    equipment_type VARCHAR(100) NOT NULL,
    quantity INT DEFAULT 0,
    specifications JSON NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_carrier_id (carrier_id),
    FOREIGN KEY (carrier_id) REFERENCES carriers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carrier bank accounts
CREATE TABLE IF NOT EXISTS carrier_bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrier_id INT NOT NULL,
    account_type ENUM('checking', 'savings') DEFAULT 'checking',
    bank_name VARCHAR(255) NULL,
    account_number_encrypted VARCHAR(255) NULL,
    routing_number VARCHAR(50) NULL,
    account_holder_name VARCHAR(255) NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'pending_verification') DEFAULT 'pending_verification',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_carrier_id (carrier_id),
    FOREIGN KEY (carrier_id) REFERENCES carriers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carrier performance metrics
CREATE TABLE IF NOT EXISTS carrier_performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrier_id INT NOT NULL,
    metric_period VARCHAR(20) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    total_loads INT DEFAULT 0,
    completed_loads INT DEFAULT 0,
    cancelled_loads INT DEFAULT 0,
    on_time_deliveries INT DEFAULT 0,
    late_deliveries INT DEFAULT 0,
    total_revenue DECIMAL(15,2) DEFAULT 0.00,
    average_rate_per_mile DECIMAL(10,2) DEFAULT 0.00,
    safety_incidents INT DEFAULT 0,
    document_compliance_rate DECIMAL(5,2) DEFAULT 0.00,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_carrier_id (carrier_id),
    FOREIGN KEY (carrier_id) REFERENCES carriers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carrier audit log
CREATE TABLE IF NOT EXISTS carrier_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrier_id INT NOT NULL,
    action_type ENUM('created', 'updated', 'deleted', 'status_changed', 'approved', 'rejected', 'document_uploaded', 'other') NOT NULL,
    action_description TEXT NULL,
    changed_by INT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    INDEX idx_carrier_id (carrier_id),
    INDEX idx_action_type (action_type),
    FOREIGN KEY (carrier_id) REFERENCES carriers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link users to carriers
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS carrier_id INT NULL AFTER role;

ALTER TABLE users 
ADD INDEX IF NOT EXISTS idx_carrier_id (carrier_id);

-- Verify tables created
SELECT 
    TABLE_NAME, 
    TABLE_ROWS,
    CREATE_TIME
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'pms_nexus_tms' 
    AND TABLE_NAME LIKE 'carrier%'
ORDER BY TABLE_NAME;
