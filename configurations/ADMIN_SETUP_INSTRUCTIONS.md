# Admin Database Connection Setup

## Purpose
The `admin-database-connection.php` file is used for database management operations that require elevated privileges such as:
- Creating new databases
- Dropping databases
- Listing all databases
- Cloning database structures

## Required MySQL Privileges

The admin user needs the following privileges:
- `CREATE` - To create new databases
- `DROP` - To delete databases
- `SELECT` - To read database information
- `SHOW DATABASES` - To list databases
- `REFERENCES` - For foreign key constraints

## Setup Instructions

### Option 1: Use MySQL Root User (Recommended for Development)

1. Open `admin-database-connection.php`
2. Set the credentials:
```php
$admin_username = "root";
$admin_password = "your_root_password"; // Your MySQL root password
```

### Option 2: Create a Dedicated Admin User

Run these SQL commands in MySQL:

```sql
-- Create a dedicated database admin user
CREATE USER 'tms_admin'@'localhost' IDENTIFIED BY 'your_secure_password';

-- Grant necessary privileges
GRANT CREATE, DROP, SELECT, SHOW DATABASES, REFERENCES ON *.* TO 'tms_admin'@'localhost';

-- Grant full privileges on tms_* databases
GRANT ALL PRIVILEGES ON `tms_%`.* TO 'tms_admin'@'localhost';

-- Grant full privileges on the main database
GRANT ALL PRIVILEGES ON `pms_nexus_tms`.* TO 'tms_admin'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;
```

Then update `admin-database-connection.php`:
```php
$admin_username = "tms_admin";
$admin_password = "your_secure_password";
```

### Option 3: Grant Privileges to Existing User

Grant additional privileges to the existing `pms_nexus_tms` user:

```sql
-- Grant CREATE and DROP privileges
GRANT CREATE, DROP ON *.* TO 'pms_nexus_tms'@'localhost';

-- Grant privileges on tms_* databases
GRANT ALL PRIVILEGES ON `tms_%`.* TO 'pms_nexus_tms'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;
```

Then in `admin-database-connection.php`:
```php
$admin_username = "pms_nexus_tms";
$admin_password = "020894TMS25";
```

## Security Considerations

### For Production:
1. **Never use root user** in production
2. **Create a dedicated admin user** with minimal required privileges
3. **Use environment variables** for credentials
4. **Restrict access** to this file (chmod 600)
5. **Enable SSL/TLS** for database connections
6. **Audit database operations** regularly

### File Permissions:
```bash
chmod 600 admin-database-connection.php
```

## Testing the Connection

### Quick Test:
```bash
curl -X POST http://localhost/tms_api/super-admin/database-setup/clone-database.php \
  -H "Content-Type: application/json" \
  -d '{"carrier_name":"Test Carrier"}'
```

### Expected Success:
```json
{
  "status": "success",
  "database_name": "tms_test_carrier",
  "tables_cloned": 45,
  ...
}
```

### Expected Error (if no privileges):
```json
{
  "status": "error",
  "message": "Access denied for user 'username'@'localhost'",
  "error_code": "42000"
}
```

## Troubleshooting

### Error: "Access denied for user"
**Solution:** Grant proper privileges (see setup instructions above)

### Error: "Admin connection failed"
**Solution:** Check credentials in `admin-database-connection.php`

### Error: "Can't connect to MySQL server"
**Solution:** Ensure MySQL is running

### Error: "Unknown database 'pms_nexus_tms'"
**Solution:** Verify the source database exists

## Default Configuration

By default, the file is configured as:
```php
$admin_username = "root";
$admin_password = ""; // Empty for XAMPP default
```

**⚠️ IMPORTANT:** Update the password before using in production!

## Files Using Admin Connection

1. `super-admin/database-setup/clone-database.php`
2. `super-admin/database-setup/list-carrier-databases.php`
3. `super-admin/database-setup/delete-carrier-database.php`

## Regular vs Admin Connection

**Regular Connection** (`database-connection.php`):
- Used for: Normal CRUD operations
- Database: `pms_nexus_tms`
- Privileges: SELECT, INSERT, UPDATE, DELETE on specific database

**Admin Connection** (`admin-database-connection.php`):
- Used for: Database management operations
- Database: None (connects to MySQL server)
- Privileges: CREATE, DROP, SHOW DATABASES on all databases

## Support

For issues with database operations, check:
1. MySQL user privileges
2. Database connection credentials
3. MySQL server status
4. PHP error logs
5. Browser console logs (Flutter app)

