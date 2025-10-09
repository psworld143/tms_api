-- =====================================================
-- Carrier User Assignment Schema
-- =====================================================
-- This schema manages the assignment of users to carriers
-- Allows tracking which users belong to which carrier organizations
-- =====================================================

-- Drop table if exists (for clean install)
DROP TABLE IF EXISTS carrier_user_assignments;

-- Carrier User Assignments Table
CREATE TABLE carrier_user_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrier_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Assignment Details
    role_in_carrier VARCHAR(50) DEFAULT 'User' COMMENT 'Role: Admin, Manager, Dispatcher, Driver, etc.',
    is_primary_contact BOOLEAN DEFAULT FALSE COMMENT 'Is this user the primary contact?',
    department VARCHAR(100) NULL COMMENT 'Department within carrier: Operations, Dispatch, Safety, etc.',
    
    -- Permissions
    can_manage_loads BOOLEAN DEFAULT FALSE,
    can_manage_drivers BOOLEAN DEFAULT FALSE,
    can_view_reports BOOLEAN DEFAULT TRUE,
    can_manage_billing BOOLEAN DEFAULT FALSE,
    
    -- Status
    status VARCHAR(20) DEFAULT 'active' COMMENT 'active, inactive, suspended',
    assignment_date DATE DEFAULT CURRENT_DATE,
    start_date DATE NULL,
    end_date DATE NULL,
    
    -- Metadata
    assigned_by INT NULL COMMENT 'User ID who made the assignment',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT NULL,
    
    -- Constraints
    UNIQUE KEY unique_carrier_user (carrier_id, user_id),
    FOREIGN KEY (carrier_id) REFERENCES carriers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes for performance
    INDEX idx_carrier_id (carrier_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_role (role_in_carrier),
    INDEX idx_assignment_date (assignment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Manages user assignments to carrier organizations';

-- =====================================================
-- Sample Data for Testing
-- =====================================================

-- Assign some users to carriers (assuming users and carriers exist)
INSERT INTO carrier_user_assignments (carrier_id, user_id, role_in_carrier, is_primary_contact, department, can_manage_loads, can_manage_drivers, can_view_reports, can_manage_billing, assigned_by, notes) VALUES
-- User 1 to Carrier 1 (Swift Logistics)
(1, 2, 'Carrier Admin', TRUE, 'Operations', TRUE, TRUE, TRUE, TRUE, 1, 'Primary administrator for Swift Logistics'),

-- User 2 to Carrier 1 (Swift Logistics)
(1, 3, 'Dispatcher', FALSE, 'Dispatch', TRUE, FALSE, TRUE, FALSE, 1, 'Main dispatcher for Swift Logistics'),

-- User 3 to Carrier 2 (Freight Masters)
(2, 4, 'Carrier Admin', TRUE, 'Management', TRUE, TRUE, TRUE, TRUE, 1, 'Primary contact for Freight Masters'),

-- User 4 to Carrier 3 (National Freight Solutions)
(3, 5, 'Manager', FALSE, 'Operations', TRUE, TRUE, TRUE, FALSE, 1, 'Operations manager'),

-- User 5 to Carrier 4 (Express Transport)
(4, 6, 'Dispatcher', FALSE, 'Dispatch', TRUE, FALSE, TRUE, FALSE, 1, 'Dispatcher for Express Transport');

-- =====================================================
-- Useful Queries
-- =====================================================

-- Get all users assigned to a specific carrier
-- SELECT u.*, cua.role_in_carrier, cua.department, cua.status
-- FROM carrier_user_assignments cua
-- JOIN users u ON cua.user_id = u.id
-- WHERE cua.carrier_id = 1 AND cua.status = 'active';

-- Get all carriers for a specific user
-- SELECT c.*, cua.role_in_carrier, cua.department
-- FROM carrier_user_assignments cua
-- JOIN carriers c ON cua.carrier_id = c.id
-- WHERE cua.user_id = 2 AND cua.status = 'active';

-- Get primary contacts for all carriers
-- SELECT c.company_name, u.name, u.email, cua.role_in_carrier
-- FROM carrier_user_assignments cua
-- JOIN carriers c ON cua.carrier_id = c.id
-- JOIN users u ON cua.user_id = u.id
-- WHERE cua.is_primary_contact = TRUE AND cua.status = 'active';

-- Get assignment statistics
-- SELECT 
--     c.company_name,
--     COUNT(cua.id) as total_users,
--     SUM(CASE WHEN cua.status = 'active' THEN 1 ELSE 0 END) as active_users,
--     SUM(CASE WHEN cua.is_primary_contact = TRUE THEN 1 ELSE 0 END) as primary_contacts
-- FROM carriers c
-- LEFT JOIN carrier_user_assignments cua ON c.id = cua.carrier_id
-- GROUP BY c.id, c.company_name;

