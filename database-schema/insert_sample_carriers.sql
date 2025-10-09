-- Insert Sample Carrier Data for Testing
-- Date: 2025-10-09
-- Purpose: Populate carriers table with sample data
-- Database: pms_nexus_tms

USE pms_nexus_tms;

-- First, ensure the carriers table exists
-- If you haven't run the carrier schema yet, run carrier_admin_schema_simple.sql first

-- Sample Carrier 1: Swift Logistics LLC
INSERT INTO carriers (
    carrier_code, 
    company_name, 
    legal_name, 
    email, 
    phone, 
    fax,
    website,
    address_line1, 
    address_line2,
    city, 
    state, 
    zip_code, 
    country,
    mc_number, 
    dot_number, 
    tax_id, 
    scac_code,
    carrier_type, 
    service_types,
    operating_regions,
    fleet_size, 
    driver_count,
    account_status, 
    onboarding_status,
    payment_terms,
    credit_limit,
    safety_rating,
    carrier_rating,
    is_preferred, 
    is_approved,
    notes,
    created_by,
    approved_by,
    approved_at
) VALUES (
    'CAR-001',
    'Swift Logistics LLC',
    'Swift Logistics Limited Liability Company',
    'contact@swiftlogistics.com',
    '(555) 123-4567',
    '(555) 123-4568',
    'www.swiftlogistics.com',
    '123 Logistics Blvd',
    'Suite 100',
    'Phoenix',
    'AZ',
    '85001',
    'USA',
    'MC-123456',
    'DOT-789012',
    '12-3456789',
    'SWFT',
    'Asset-Based',
    '["FTL", "LTL", "Expedited"]',
    '["Southwest", "West Coast", "Midwest"]',
    50,
    75,
    'active',
    'completed',
    'Net 30',
    100000.00,
    'Satisfactory',
    4.50,
    TRUE,
    TRUE,
    'Premium carrier with excellent safety record',
    1,
    1,
    NOW()
);

-- Sample Carrier 2: Freight Masters Inc
INSERT INTO carriers (
    carrier_code, 
    company_name, 
    email, 
    phone,
    address_line1, 
    city, 
    state, 
    zip_code,
    mc_number, 
    dot_number,
    carrier_type,
    fleet_size, 
    driver_count,
    account_status,
    onboarding_status,
    payment_terms,
    carrier_rating,
    is_preferred, 
    is_approved,
    created_by,
    approved_by,
    approved_at
) VALUES (
    'CAR-002',
    'Freight Masters Inc',
    'info@freightmasters.com',
    '(555) 987-6543',
    '456 Transport Ave',
    'Dallas',
    'TX',
    '75201',
    'MC-654321',
    'DOT-210987',
    'Asset-Based',
    30,
    45,
    'active',
    'completed',
    'Net 30',
    4.20,
    TRUE,
    TRUE,
    1,
    1,
    NOW()
);

-- Sample Carrier 3: National Freight Solutions (Pending)
INSERT INTO carriers (
    carrier_code, 
    company_name, 
    email, 
    phone,
    address_line1, 
    city, 
    state, 
    zip_code,
    mc_number, 
    dot_number,
    carrier_type,
    fleet_size, 
    driver_count,
    account_status,
    onboarding_status,
    carrier_rating,
    is_approved,
    created_by
) VALUES (
    'CAR-003',
    'National Freight Solutions',
    'contact@nationalfreight.com',
    '(555) 456-7890',
    '789 Highway Blvd',
    'Atlanta',
    'GA',
    '30301',
    'MC-789456',
    'DOT-456789',
    'Broker',
    0,
    0,
    'pending',
    'in_progress',
    0.00,
    FALSE,
    1
);

-- Sample Carrier 4: Express Transport Co
INSERT INTO carriers (
    carrier_code, 
    company_name, 
    legal_name,
    email, 
    phone,
    website,
    address_line1, 
    city, 
    state, 
    zip_code,
    mc_number, 
    dot_number,
    tax_id,
    carrier_type,
    service_types,
    fleet_size, 
    driver_count,
    account_status,
    onboarding_status,
    payment_terms,
    safety_rating,
    carrier_rating,
    is_preferred, 
    is_approved,
    created_by,
    approved_by,
    approved_at
) VALUES (
    'CAR-004',
    'Express Transport Co',
    'Express Transport Company LLC',
    'dispatch@expresstransport.com',
    '(555) 234-5678',
    'www.expresstransport.com',
    '321 Speed Lane',
    'Chicago',
    'IL',
    '60601',
    'MC-234567',
    'DOT-567890',
    '98-7654321',
    'Asset-Based',
    '["Expedited", "Hot Shot", "Time Critical"]',
    25,
    35,
    'active',
    'completed',
    'Net 45',
    'Satisfactory',
    4.80,
    TRUE,
    TRUE,
    1,
    1,
    NOW()
);

-- Sample Carrier 5: Coastal Shipping LLC
INSERT INTO carriers (
    carrier_code, 
    company_name, 
    email, 
    phone,
    address_line1, 
    city, 
    state, 
    zip_code,
    mc_number, 
    dot_number,
    carrier_type,
    service_types,
    operating_regions,
    fleet_size, 
    driver_count,
    account_status,
    onboarding_status,
    carrier_rating,
    is_approved,
    created_by,
    approved_by,
    approved_at
) VALUES (
    'CAR-005',
    'Coastal Shipping LLC',
    'operations@coastalshipping.com',
    '(555) 345-6789',
    '555 Harbor Drive',
    'Los Angeles',
    'CA',
    '90001',
    'MC-345678',
    'DOT-678901',
    '3PL',
    '["FTL", "Intermodal", "Drayage"]',
    '["West Coast", "Pacific Northwest"]',
    40,
    55,
    'active',
    'completed',
    4.30,
    FALSE,
    1,
    1,
    NOW()
);

-- Sample Carrier 6: Midwest Haulers (Inactive)
INSERT INTO carriers (
    carrier_code, 
    company_name, 
    email, 
    phone,
    address_line1, 
    city, 
    state, 
    zip_code,
    mc_number, 
    dot_number,
    carrier_type,
    fleet_size, 
    driver_count,
    account_status,
    onboarding_status,
    carrier_rating,
    is_approved,
    notes,
    created_by
) VALUES (
    'CAR-006',
    'Midwest Haulers',
    'info@midwesthaulers.com',
    '(555) 567-8901',
    '777 Interstate Pkwy',
    'Kansas City',
    'MO',
    '64101',
    'MC-567890',
    'DOT-890123',
    'Asset-Based',
    15,
    20,
    'inactive',
    'completed',
    3.50,
    TRUE,
    'Currently not taking new loads',
    1
);

-- Sample Carrier 7: Premier Logistics Group
INSERT INTO carriers (
    carrier_code, 
    company_name, 
    legal_name,
    email, 
    phone,
    website,
    address_line1, 
    city, 
    state, 
    zip_code,
    mc_number, 
    dot_number,
    carrier_type,
    service_types,
    fleet_size, 
    driver_count,
    account_status,
    onboarding_status,
    payment_terms,
    credit_limit,
    carrier_rating,
    is_preferred, 
    is_approved,
    created_by,
    approved_by,
    approved_at
) VALUES (
    'CAR-007',
    'Premier Logistics Group',
    'Premier Logistics Group Inc',
    'contact@premierlogistics.com',
    '(555) 678-9012',
    'www.premierlogistics.com',
    '888 Business Center Dr',
    'Nashville',
    'TN',
    '37201',
    'MC-678901',
    'DOT-901234',
    'Freight Forwarder',
    '["FTL", "LTL", "Cross-Border", "International"]',
    60,
    85,
    'active',
    'completed',
    'Net 30',
    150000.00,
    4.60,
    TRUE,
    TRUE,
    1,
    1,
    NOW()
);

-- Sample Carrier 8: Quick Haul Express (Pending Approval)
INSERT INTO carriers (
    carrier_code, 
    company_name, 
    email, 
    phone,
    address_line1, 
    city, 
    state, 
    zip_code,
    mc_number, 
    dot_number,
    carrier_type,
    fleet_size, 
    driver_count,
    account_status,
    onboarding_status,
    carrier_rating,
    is_approved,
    created_by
) VALUES (
    'CAR-008',
    'Quick Haul Express',
    'admin@quickhaul.com',
    '(555) 789-0123',
    '999 Fast Lane',
    'Denver',
    'CO',
    '80201',
    'MC-789012',
    'DOT-012345',
    'Asset-Based',
    20,
    28,
    'pending',
    'in_progress',
    0.00,
    FALSE,
    1
);

-- Add primary contacts for carriers
INSERT INTO carrier_contacts (carrier_id, contact_type, first_name, last_name, title, email, phone, is_primary, is_active)
VALUES 
    (1, 'primary', 'John', 'Smith', 'Operations Manager', 'john.smith@swiftlogistics.com', '(555) 123-4568', TRUE, TRUE),
    (1, 'billing', 'Sarah', 'Johnson', 'Billing Manager', 'billing@swiftlogistics.com', '(555) 123-4569', FALSE, TRUE),
    (2, 'primary', 'Mike', 'Williams', 'Dispatch Supervisor', 'mike.williams@freightmasters.com', '(555) 987-6544', TRUE, TRUE),
    (3, 'primary', 'Lisa', 'Brown', 'Account Manager', 'lisa.brown@nationalfreight.com', '(555) 456-7891', TRUE, TRUE),
    (4, 'primary', 'David', 'Martinez', 'Operations Director', 'david.martinez@expresstransport.com', '(555) 234-5679', TRUE, TRUE),
    (5, 'primary', 'Jennifer', 'Davis', 'Logistics Coordinator', 'jennifer.davis@coastalshipping.com', '(555) 345-6790', TRUE, TRUE),
    (7, 'primary', 'Robert', 'Wilson', 'VP Operations', 'robert.wilson@premierlogistics.com', '(555) 678-9013', TRUE, TRUE);

-- Add some insurance records for active carriers
INSERT INTO carrier_insurance (carrier_id, insurance_type, policy_number, insurance_company, coverage_amount, effective_date, expiration_date, status, is_verified)
VALUES 
    (1, 'liability', 'POL-LIA-123456', 'Progressive Commercial', 1000000.00, '2025-01-01', '2026-01-01', 'active', TRUE),
    (1, 'cargo', 'POL-CAR-789012', 'Progressive Commercial', 500000.00, '2025-01-01', '2026-01-01', 'active', TRUE),
    (2, 'liability', 'POL-LIA-654321', 'State Farm Commercial', 1000000.00, '2025-02-01', '2026-02-01', 'active', TRUE),
    (4, 'liability', 'POL-LIA-234567', 'Allstate Commercial', 1500000.00, '2025-01-15', '2026-01-15', 'active', TRUE),
    (4, 'cargo', 'POL-CAR-234567', 'Allstate Commercial', 750000.00, '2025-01-15', '2026-01-15', 'active', TRUE);

-- Add equipment for some carriers
INSERT INTO carrier_equipment (carrier_id, equipment_type, quantity, specifications, is_available)
VALUES 
    (1, 'Dry Van', 30, '{"length": "53ft", "weight_capacity": "45000 lbs"}', TRUE),
    (1, 'Reefer', 15, '{"length": "53ft", "weight_capacity": "44000 lbs", "temp_range": "-20 to 70°F"}', TRUE),
    (1, 'Flatbed', 5, '{"length": "48ft", "weight_capacity": "48000 lbs"}', TRUE),
    (2, 'Dry Van', 25, '{"length": "53ft", "weight_capacity": "45000 lbs"}', TRUE),
    (2, 'Flatbed', 5, '{"length": "48ft", "weight_capacity": "48000 lbs"}', TRUE),
    (4, 'Sprinter Van', 10, '{"length": "14ft", "weight_capacity": "3500 lbs"}', TRUE),
    (4, 'Box Truck', 15, '{"length": "26ft", "weight_capacity": "12000 lbs"}', TRUE);

-- Verify the data was inserted
SELECT 
    carrier_code,
    company_name,
    mc_number,
    carrier_type,
    account_status,
    is_approved,
    fleet_size,
    driver_count
FROM carriers
ORDER BY carrier_code;

-- Show summary
SELECT 
    account_status,
    COUNT(*) as carrier_count
FROM carriers
GROUP BY account_status;

SELECT 
    carrier_type,
    COUNT(*) as carrier_count
FROM carriers
GROUP BY carrier_type;

-- End of script
SELECT 'Sample carriers inserted successfully!' as Status;
