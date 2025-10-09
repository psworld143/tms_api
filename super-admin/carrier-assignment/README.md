# Carrier Users Module

## Overview
The Carrier Users module manages the assignment of users to carrier organizations. It allows super admins to assign users to specific carriers with defined roles, permissions, and departments.

## Database Schema

### Table: `carrier_user_assignments`
Tracks user assignments to carriers with the following key fields:

- **Assignment Details:**
  - `carrier_id` - Foreign key to carriers table
  - `user_id` - Foreign key to users table
  - `role_in_carrier` - Role within carrier (Admin, Manager, Dispatcher, Driver, etc.)
  - `is_primary_contact` - Boolean flag for primary contact
  - `department` - Department within carrier (Operations, Dispatch, Safety, etc.)

- **Permissions:**
  - `can_manage_loads` - Permission to manage loads
  - `can_manage_drivers` - Permission to manage drivers
  - `can_view_reports` - Permission to view reports
  - `can_manage_billing` - Permission to manage billing

- **Status & Tracking:**
  - `status` - active, inactive, suspended
  - `assignment_date` - Date of assignment
  - `start_date` - Optional start date
  - `end_date` - Optional end date
  - `assigned_by` - User who made the assignment
  - `notes` - Additional notes

### Database Setup
```sql
-- Run this file to create the schema
mysql> source tms_api/database-schema/carrier_user_assignments.sql
```

## API Endpoints

### Base URL
`{superAdmin}carrier-assignment/`

### 1. Assign User to Carrier
**Endpoint:** `assign-user-to-carrier.php`  
**Method:** POST  
**Description:** Assigns a user to a carrier with specific role and permissions

**Request Body:**
```json
{
  "carrier_id": 1,
  "user_id": 5,
  "role_in_carrier": "Dispatcher",
  "is_primary_contact": false,
  "department": "Operations",
  "can_manage_loads": true,
  "can_manage_drivers": false,
  "can_view_reports": true,
  "can_manage_billing": false,
  "status": "active",
  "start_date": "2025-01-01",
  "assigned_by": 1,
  "notes": "Main dispatcher for day shift"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "User assigned to carrier successfully",
  "data": {
    "id": 123,
    "carrier_id": 1,
    "user_id": 5,
    "carrier_name": "Swift Logistics",
    "user_name": "John Doe",
    "user_email": "john@example.com",
    "role_in_carrier": "Dispatcher",
    "is_primary_contact": false,
    "department": "Operations",
    ...
  }
}
```

### 2. Get Carrier Assignments
**Endpoint:** `get-carrier-assignments.php`  
**Method:** GET  
**Description:** Retrieves all carrier assignments with optional filters

**Query Parameters:**
- `carrier_id` - Filter by carrier ID
- `user_id` - Filter by user ID
- `status` - Filter by status (active, inactive, suspended)
- `role` - Filter by role
- `is_primary_contact` - Filter by primary contact (true/false)
- `page` - Page number (default: 1)
- `limit` - Results per page (default: 50)
- `sort_by` - Sort field (default: created_at)
- `sort_order` - ASC or DESC (default: DESC)

**Example:**
```
GET /get-carrier-assignments.php?carrier_id=1&status=active&page=1&limit=20
```

**Response:**
```json
{
  "status": "success",
  "message": "Carrier assignments retrieved successfully",
  "data": [
    {
      "id": 1,
      "carrier_id": 1,
      "carrier_name": "Swift Logistics",
      "user_id": 2,
      "user_name": "Jane Smith",
      "user_email": "jane@example.com",
      "role_in_carrier": "Carrier Admin",
      "is_primary_contact": true,
      ...
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total_records": 45,
    "total_pages": 3
  }
}
```

### 3. Carrier Users
**Endpoint:** `get-carrier-users.php`  
**Method:** GET  
**Description:** Gets all users assigned to a specific carrier

**Query Parameters:**
- `carrier_id` - Required. The carrier ID

**Example:**
```
GET /get-carrier-users.php?carrier_id=1
```

**Response:**
```json
{
  "status": "success",
  "message": "Carrier users retrieved successfully",
  "carrier": {
    "id": 1,
    "company_name": "Swift Logistics",
    "carrier_code": "SWF001"
  },
  "data": [
    {
      "assignment_id": 1,
      "user_id": 2,
      "user_name": "Jane Smith",
      "email": "jane@example.com",
      "role_in_carrier": "Carrier Admin",
      "is_primary_contact": true,
      "department": "Operations",
      ...
    }
  ],
  "statistics": {
    "total_users": 5,
    "active_users": 5,
    "primary_contacts": 1,
    "roles": {
      "Carrier Admin": 1,
      "Dispatcher": 2,
      "Driver": 2
    }
  }
}
```

### 4. Get Available Users
**Endpoint:** `get-available-users.php`  
**Method:** GET  
**Description:** Gets users not yet assigned to a specific carrier

**Query Parameters:**
- `carrier_id` - Optional. Excludes users assigned to this carrier

**Example:**
```
GET /get-available-users.php?carrier_id=1
```

**Response:**
```json
{
  "status": "success",
  "message": "Available users retrieved successfully",
  "data": [
    {
      "id": 10,
      "name": "Bob Johnson",
      "email": "bob@example.com",
      "phone": "555-1234",
      "role": "User",
      "status": "active",
      "department": "Operations",
      ...
    }
  ],
  "count": 15
}
```

### 5. Update Assignment
**Endpoint:** `update-assignment.php`  
**Method:** PUT  
**Description:** Updates an existing carrier assignment

**Request Body:**
```json
{
  "id": 123,
  "role_in_carrier": "Manager",
  "department": "Dispatch",
  "can_manage_loads": true,
  "can_manage_drivers": true,
  "is_primary_contact": false
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Assignment updated successfully",
  "data": {
    "id": 123,
    "carrier_id": 1,
    "user_id": 5,
    "role_in_carrier": "Manager",
    ...
  }
}
```

### 6. Remove Assignment
**Endpoint:** `remove-assignment.php`  
**Method:** DELETE  
**Description:** Removes a user from a carrier

**Query Parameters:**
- `id` - Required. The assignment ID

**Example:**
```
DELETE /remove-assignment.php?id=123
```

**Response:**
```json
{
  "status": "success",
  "message": "User assignment removed successfully",
  "data": {
    "assignment_id": 123,
    "carrier_name": "Swift Logistics",
    "user_name": "John Doe",
    "user_email": "john@example.com",
    "role_in_carrier": "Dispatcher",
    "removed_at": "2025-10-09 14:30:00"
  }
}
```

## Flutter Integration

### Service: `CarrierAssignmentService`
Location: `lib/platforms/shared/components/services/carrier_assignment_service.dart`

**Methods:**
```dart
// Assign user to carrier
static Future<Map<String, dynamic>> assignUserToCarrier({
  required int carrierId,
  required int userId,
  String roleInCarrier = 'User',
  bool isPrimaryContact = false,
  String? department,
  bool canManageLoads = false,
  bool canManageDrivers = false,
  bool canViewReports = true,
  bool canManageBilling = false,
  // ... other parameters
});

// Get all assignments with filters
static Future<Map<String, dynamic>> getCarrierAssignments({
  int? carrierId,
  int? userId,
  String? status,
  // ... other parameters
});

// Get users for specific carrier
static Future<Map<String, dynamic>> getCarrierUsers(int carrierId);

// Get available (unassigned) users
static Future<Map<String, dynamic>> getAvailableUsers(int carrierId);

// Update assignment
static Future<Map<String, dynamic>> updateAssignment(
  int assignmentId,
  Map<String, dynamic> updates,
);

// Remove assignment
static Future<Map<String, dynamic>> removeAssignment(int assignmentId);
```

### Screen: `CarrierAssignmentScreen`
Location: `lib/platforms/web/presentation/carrier_assignment_screen.dart`

**Features:**
- Split-panel UI design
- Left panel: Carriers list with selection
- Right panel: Assigned users management
- Assign user dialog with full permission controls
- Edit assignment functionality
- Remove assignment with confirmation
- Real-time updates
- Tailwind CSS styling
- Empty states and loading indicators
- Statistics (total users, primary contacts, roles)

## User Interface

### Super Admin Navigation
Access the Carrier Users module from the Super Admin sidebar:
1. Dashboard
2. User Management
3. Carrier Management
4. **Carrier Users** ← New module

### Key Features
1. **Carrier Selection:** Click any carrier in the left panel to view assigned users
2. **Assign User:** Click "Assign User" button to open assignment dialog
3. **User Information:** View user details, role, department, and permissions
4. **Primary Contact:** Star icon indicates primary contact for carrier
5. **Edit Assignment:** Modify role, permissions, or department
6. **Remove Assignment:** Remove user from carrier (with confirmation)

### Permission Types
- **Can Manage Loads:** Permission to create, edit, and manage loads
- **Can Manage Drivers:** Permission to manage driver assignments
- **Can View Reports:** Permission to view analytics and reports
- **Can Manage Billing:** Permission to handle invoicing and billing

### Role Types
- **Carrier Admin:** Full access to carrier operations
- **Manager:** Management-level access
- **Dispatcher:** Load and driver management
- **Driver:** Driver-specific access
- **Billing:** Billing and invoicing access
- **Safety Officer:** Safety and compliance access
- **User:** Basic access

## Error Handling

### Common Errors
1. **"Carrier not found"** - Invalid carrier_id
2. **"User not found"** - Invalid user_id
3. **"User is already assigned to this carrier"** - Duplicate assignment
4. **"Assignment not found"** - Invalid assignment_id
5. **"No fields to update"** - Empty update request

### HTTP Status Codes
- `200` - Success
- `400` - Bad request (validation error)
- `404` - Resource not found
- `500` - Server error

## Testing

### Manual Testing Steps
1. Import database schema: `carrier_user_assignments.sql`
2. Verify table creation and sample data
3. Test API endpoints using cURL or Postman
4. Test Flutter UI in Super Admin account
5. Verify permissions and role assignments
6. Test primary contact functionality
7. Test remove assignment

### Sample cURL Commands
```bash
# Assign user to carrier
curl -X POST http://localhost/tms_api/super-admin/carrier-assignment/assign-user-to-carrier.php \
  -H "Content-Type: application/json" \
  -d '{"carrier_id":1,"user_id":5,"role_in_carrier":"Dispatcher","can_manage_loads":true}'

# Get carrier users
curl http://localhost/tms_api/super-admin/carrier-assignment/get-carrier-users.php?carrier_id=1

# Get available users
curl http://localhost/tms_api/super-admin/carrier-assignment/get-available-users.php?carrier_id=1

# Remove assignment
curl -X DELETE http://localhost/tms_api/super-admin/carrier-assignment/remove-assignment.php?id=1
```

## Best Practices

1. **Primary Contact:** Each carrier should have at least one primary contact
2. **Role Assignment:** Assign appropriate roles based on user responsibilities
3. **Permission Control:** Grant only necessary permissions to users
4. **Department Tracking:** Use departments for better organization
5. **Status Management:** Use status field to temporarily disable assignments
6. **Audit Trail:** Use `assigned_by` field to track who made assignments

## Future Enhancements
- Bulk assignment functionality
- Assignment history/audit log
- Email notifications on assignment changes
- Permission templates by role
- Carrier-specific custom roles
- Assignment expiration reminders
- Multi-carrier assignment support
- Assignment approval workflow

## Support
For issues or questions about the Carrier Users module, contact the development team or create an issue in the repository.

