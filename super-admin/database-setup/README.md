# Database Setup APIs - Multi-Tenant Database Management 🗄️

## Overview

These APIs allow you to create, list, and delete carrier-specific databases for multi-tenant TMS deployment. Each carrier gets their own isolated database with the same structure as the main TMS database.

---

## 📋 API Endpoints

### 1. Clone Database (Create Carrier Database)
**Endpoint:** `POST /tms_api/super-admin/database-setup/clone-database.php`

Creates a new database for a carrier by cloning the structure of the main TMS database.

**Request:**
```json
{
  "carrier_name": "2GO Logistics"
}
```

**Response (Success):**
```json
{
  "status": "success",
  "message": "Database cloned successfully",
  "data": {
    "carrier_name": "2GO Logistics",
    "source_database": "pms_nexus_tms",
    "new_database": "tms_2go_logistics",
    "tables_cloned": 15,
    "views_cloned": 2,
    "tables_list": ["users", "carriers", "loads", ...],
    "views_list": ["active_loads_view", ...],
    "config_file": "/path/to/2go_logistics-db-config.php",
    "created_at": "2025-10-09 10:30:00"
  }
}
```

**Response (Error):**
```json
{
  "status": "error",
  "message": "Database 'tms_2go_logistics' already exists. Please use a different carrier name or delete the existing database first."
}
```

**Features:**
- ✅ Sanitizes carrier name (removes spaces, special characters)
- ✅ Creates database with `tms_` prefix
- ✅ Clones all table structures
- ✅ Clones all view definitions
- ✅ Creates carrier-specific config file
- ✅ Validates database name length (max 64 characters)
- ✅ Checks for existing databases

---

### 2. List Carrier Databases
**Endpoint:** `GET /tms_api/super-admin/database-setup/list-carrier-databases.php`

Lists all carrier databases (databases starting with `tms_`).

**Request:**
```bash
GET request (no body needed)
```

**Response:**
```json
{
  "status": "success",
  "message": "Carrier databases retrieved successfully",
  "data": {
    "main_database": "pms_nexus_tms",
    "carrier_count": 3,
    "carriers": [
      {
        "database_name": "tms_2go_logistics",
        "carrier_name": "2go Logistics",
        "table_count": 15,
        "size_mb": 2.45,
        "charset": "utf8mb4",
        "collation": "utf8mb4_unicode_ci",
        "has_config_file": true,
        "config_file": "/path/to/2go_logistics-db-config.php"
      },
      {
        "database_name": "tms_swift_transport",
        "carrier_name": "Swift Transport",
        "table_count": 15,
        "size_mb": 1.89,
        "charset": "utf8mb4",
        "collation": "utf8mb4_unicode_ci",
        "has_config_file": true,
        "config_file": "/path/to/swift_transport-db-config.php"
      }
    ]
  }
}
```

---

### 3. Delete Carrier Database
**Endpoint:** `DELETE /tms_api/super-admin/database-setup/delete-carrier-database.php`

Deletes a carrier database and its configuration file.

**Request:**
```json
{
  "database_name": "tms_2go_logistics"
}
```

**Response (Success):**
```json
{
  "status": "success",
  "message": "Carrier database deleted successfully",
  "data": {
    "database_name": "tms_2go_logistics",
    "tables_deleted": 15,
    "size_mb": 2.45,
    "config_file_deleted": true,
    "deleted_at": "2025-10-09 11:00:00"
  }
}
```

**Security Features:**
- ✅ Only allows deletion of `tms_*` databases
- ✅ Prevents deletion of main TMS database
- ✅ Validates database name format
- ✅ Deletes associated config file

---

## 🧪 Testing with cURL

### Create a Carrier Database
```bash
curl -X POST http://localhost/tms_api/super-admin/database-setup/clone-database.php \
  -H "Content-Type: application/json" \
  -d '{"carrier_name": "2GO Logistics"}'
```

### List All Carrier Databases
```bash
curl http://localhost/tms_api/super-admin/database-setup/list-carrier-databases.php
```

### Delete a Carrier Database
```bash
curl -X DELETE http://localhost/tms_api/super-admin/database-setup/delete-carrier-database.php \
  -H "Content-Type: application/json" \
  -d '{"database_name": "tms_2go_logistics"}'
```

---

## 📝 Database Naming Convention

**Input:** Carrier name (any format)
```
"2GO Logistics"
"Swift & Sons Transport"
"ABC-XYZ Freight"
```

**Output:** Sanitized database name
```
tms_2go_logistics
tms_swift_sons_transport
tms_abc_xyz_freight
```

**Rules:**
- Converts to lowercase
- Replaces spaces with underscores
- Removes special characters
- Adds `tms_` prefix
- Max 64 characters (MySQL limit)

---

## 📁 Generated Files

When a carrier database is created, a configuration file is generated:

**Location:** `/tms_api/configurations/carriers/{carrier_slug}-db-config.php`

**Example:** `/tms_api/configurations/carriers/2go_logistics-db-config.php`

**Content:**
```php
<?php
// Database configuration for carrier: 2GO Logistics
// Auto-generated on 2025-10-09 10:30:00

$servername = "localhost";
$username = "pms_nexus_tms";
$password = "020894TMS25";
$dbname = "tms_2go_logistics";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
```

---

## 🔧 What Gets Cloned

### ✅ Cloned (Structure Only):
- All tables with their structure
- Indexes
- Primary keys
- Foreign keys
- Unique constraints
- Auto-increment settings
- Column definitions
- Views and their definitions

### ❌ NOT Cloned:
- Data (tables are empty)
- Stored procedures
- Triggers
- Events
- Users/Permissions

---

## 🎯 Use Cases

### 1. **Multi-Tenant SaaS**
Each carrier gets their own database for complete data isolation.

### 2. **Franchise Management**
Each franchise location has its own database instance.

### 3. **Testing Environments**
Create carrier-specific test databases.

### 4. **Data Segregation**
Comply with data residency and privacy requirements.

---

## ⚠️ Important Notes

### Security
- ⚠️ **Super Admin Only**: These endpoints should only be accessible to Super Admins
- ⚠️ **Backup**: Always backup before deleting databases
- ⚠️ **Validation**: Input is sanitized but additional authentication is recommended

### Performance
- Creating a database with many tables may take a few seconds
- Database size depends on table structures
- Initial databases are empty (structure only)

### Limitations
- Database names limited to 64 characters (MySQL)
- Only MySQL/MariaDB supported
- Requires appropriate database privileges (CREATE, DROP)

---

## 🔐 Required MySQL Privileges

The database user needs these privileges:

```sql
GRANT CREATE, DROP, SELECT, INSERT, UPDATE, DELETE, 
      CREATE VIEW, SHOW VIEW, 
      REFERENCES, INDEX, ALTER
ON *.* TO 'pms_nexus_tms'@'localhost';

FLUSH PRIVILEGES;
```

---

## 🚀 Example Workflow

```bash
# 1. Create carrier database
curl -X POST http://localhost/tms_api/super-admin/database-setup/clone-database.php \
  -H "Content-Type: application/json" \
  -d '{"carrier_name": "Swift Logistics"}'

# 2. Verify creation
curl http://localhost/tms_api/super-admin/database-setup/list-carrier-databases.php

# 3. Use the carrier database
# Update your carrier's connection to use: tms_swift_logistics

# 4. If needed, delete the database
curl -X DELETE http://localhost/tms_api/super-admin/database-setup/delete-carrier-database.php \
  -H "Content-Type: application/json" \
  -d '{"database_name": "tms_swift_logistics"}'
```

---

## 📊 Response Status Codes

| Status | Meaning |
|--------|---------|
| `success` | Operation completed successfully |
| `error` | Operation failed, check message for details |

### Common Error Messages:
- `"Carrier name is required"` - Missing carrier_name in request
- `"Database already exists"` - Carrier database already created
- `"Database name too long"` - Carrier name exceeds length limit
- `"Cannot delete main database"` - Attempted to delete main TMS database
- `"Database does not exist"` - Tried to delete non-existent database

---

## 🔄 Integration with Flutter App

To integrate with your Flutter app, create a service:

```dart
// lib/services/database_setup_service.dart

class DatabaseSetupService {
  static Future<ApiResponse> createCarrierDatabase(String carrierName) async {
    final response = await http.post(
      Uri.parse('$baseUrl/super-admin/database-setup/clone-database.php'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'carrier_name': carrierName}),
    );
    return ApiResponse.fromJson(json.decode(response.body));
  }
  
  static Future<List<CarrierDatabase>> listCarrierDatabases() async {
    final response = await http.get(
      Uri.parse('$baseUrl/super-admin/database-setup/list-carrier-databases.php'),
    );
    final data = json.decode(response.body);
    return (data['data']['carriers'] as List)
        .map((db) => CarrierDatabase.fromJson(db))
        .toList();
  }
  
  static Future<ApiResponse> deleteCarrierDatabase(String databaseName) async {
    final response = await http.delete(
      Uri.parse('$baseUrl/super-admin/database-setup/delete-carrier-database.php'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'database_name': databaseName}),
    );
    return ApiResponse.fromJson(json.decode(response.body));
  }
}
```

---

## 🆘 Troubleshooting

### Error: "Access denied"
```
Solution: Check MySQL user privileges
SHOW GRANTS FOR 'pms_nexus_tms'@'localhost';
```

### Error: "Database too large"
```
Solution: Optimize table structures or use shorter carrier name
```

### Error: "Configuration file not created"
```
Solution: Check write permissions on /configurations/carriers/ directory
chmod 755 /configurations/carriers/
```

---

## 📚 Related Documentation

- [Carrier Management](../carriers/README.md)
- [User Management](../users/README.md)
- [Database Schema](../../database-schema/)
- [API Documentation](../../README.md)

---

**Version:** 1.0
**Last Updated:** 2025-10-09
**Author:** TMS Development Team
