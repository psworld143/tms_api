-- =====================================================
-- Grant Database Creation Privileges
-- =====================================================
-- Run this SQL script to grant the necessary privileges
-- for database cloning and management operations
-- =====================================================

-- Grant CREATE and DROP privileges to the existing user
GRANT CREATE, DROP ON *.* TO 'pms_nexus_tms'@'localhost';

-- Grant full privileges on all tms_* databases (present and future)
GRANT ALL PRIVILEGES ON `tms_%`.* TO 'pms_nexus_tms'@'localhost';

-- Grant SHOW DATABASES privilege
GRANT SHOW DATABASES ON *.* TO 'pms_nexus_tms'@'localhost';

-- Grant REFERENCES privilege for foreign keys
GRANT REFERENCES ON *.* TO 'pms_nexus_tms'@'localhost';

-- Apply all changes
FLUSH PRIVILEGES;

-- Verify privileges
SHOW GRANTS FOR 'pms_nexus_tms'@'localhost';

-- =====================================================
-- Expected Output:
-- =====================================================
-- You should see grants including:
-- - GRANT CREATE, DROP, SHOW DATABASES, REFERENCES ON *.* TO 'pms_nexus_tms'@'localhost'
-- - GRANT ALL PRIVILEGES ON `tms_%`.* TO 'pms_nexus_tms'@'localhost'
-- =====================================================

-- =====================================================
-- How to Run This Script:
-- =====================================================
-- Option 1: phpMyAdmin
--   1. Login to phpMyAdmin
--   2. Click "SQL" tab at the top
--   3. Copy and paste this entire file
--   4. Click "Go" button
--
-- Option 2: MySQL Command Line
--   mysql -u root -p < GRANT_PRIVILEGES.sql
--
-- Option 3: cPanel / Hosting Panel
--   Use the MySQL Database section or Remote MySQL tool
-- =====================================================

