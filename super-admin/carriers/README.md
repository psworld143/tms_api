# Carrier Management API Endpoints

## Overview
RESTful API endpoints for Super Admin to manage carrier accounts in the TMS system.

## Base URL
`/tms_api/super-admin/carriers/`

## Authentication
All endpoints require Super Admin authentication. Include authentication token in request headers.

---

## Endpoints

### 1. Create Carrier
**Endpoint:** `POST /tms_api/super-admin/carriers/create-carrier.php`

**Description:** Create a new carrier account

**Request Body:**
```json
{
  "company_name": "Swift Logistics LLC",
  "legal_name": "Swift Logistics Limited Liability Company",
  "email": "contact@swiftlogistics.com",
  "phone": "(555) 123-4567",
  "address_line1": "123 Logistics Blvd",
  "city": "Phoenix",
  "state": "AZ",
  "zip_code": "85001",
  "mc_number": "MC-123456",
  "dot_number": "DOT-789012",
  "tax_id": "12-3456789",
  "carrier_type": "Asset-Based",
  "fleet_size": 50,
  "driver_count": 75,
  "account_status": "pending",
  "created_by": 1
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Carrier created successfully",
  "data": {
    "id": 1,
    "carrier_code": "CAR-001",
    "company_name": "Swift Logistics LLC",
    ...
  }
}
```

---

### 2. Get All Carriers
**Endpoint:** `GET /tms_api/super-admin/carriers/get-carriers.php`

**Description:** Retrieve all carriers with optional filters, pagination, and sorting

**Query Parameters:**
- `status` - Filter by account status (pending, active, inactive, suspended, terminated)
- `carrier_type` - Filter by carrier type
- `is_approved` - Filter by approval status (true/false)
- `is_preferred` - Filter by preferred status (true/false)
- `search` - Search in company name, carrier code, MC number, DOT number
- `page` - Page number (default: 1)
- `limit` - Records per page (default: 50)
- `sort_by` - Sort field (carrier_code, company_name, created_at, carrier_rating, account_status)
- `sort_order` - Sort order (ASC/DESC, default: DESC)

**Example:**
```
GET /tms_api/super-admin/carriers/get-carriers.php?status=active&page=1&limit=20&sort_by=company_name&sort_order=ASC
```

**Response:**
```json
{
  "status": "success",
  "message": "Carriers retrieved successfully",
  "data": [
    {
      "id": 1,
      "carrier_code": "CAR-001",
      "company_name": "Swift Logistics LLC",
      "primary_contact_name": "John Smith",
      "primary_contact_email": "john@swiftlogistics.com",
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

---

### 3. Get Single Carrier
**Endpoint:** `GET /tms_api/super-admin/carriers/get-carrier.php?id={id}`

**Description:** Retrieve complete carrier profile including contacts, insurance, documents, equipment, and audit log

**Query Parameters:**
- `id` (required) - Carrier ID

**Example:**
```
GET /tms_api/super-admin/carriers/get-carrier.php?id=1
```

**Response:**
```json
{
  "status": "success",
  "message": "Carrier details retrieved successfully",
  "data": {
    "id": 1,
    "carrier_code": "CAR-001",
    "company_name": "Swift Logistics LLC",
    "contacts": [...],
    "insurance": [...],
    "documents": [...],
    "equipment": [...],
    "bank_accounts": [...],
    "performance_metrics": [...],
    "audit_log": [...]
  }
}
```

---

### 4. Update Carrier
**Endpoint:** `PUT /tms_api/super-admin/carriers/update-carrier.php`

**Description:** Update carrier information

**Request Body:**
```json
{
  "id": 1,
  "company_name": "Swift Logistics LLC - Updated",
  "phone": "(555) 123-9999",
  "fleet_size": 55,
  "carrier_rating": 4.5,
  "updated_by": 1
}
```

**Note:** Only include fields that need to be updated. All fields are optional except `id`.

**Response:**
```json
{
  "status": "success",
  "message": "Carrier updated successfully",
  "data": {
    "id": 1,
    "carrier_code": "CAR-001",
    "company_name": "Swift Logistics LLC - Updated",
    ...
  }
}
```

---

### 5. Delete Carrier
**Endpoint:** `DELETE /tms_api/super-admin/carriers/delete-carrier.php?id={id}`

**Description:** Delete a carrier account (only if no users are linked)

**Query Parameters:**
- `id` (required) - Carrier ID

**Request Body (Optional):**
```json
{
  "deleted_by": 1
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Carrier deleted successfully",
  "deleted_carrier": {
    "id": 1,
    "carrier_code": "CAR-001",
    "company_name": "Swift Logistics LLC"
  }
}
```

**Error Response (if users exist):**
```json
{
  "status": "error",
  "message": "Cannot delete carrier with active users. Please remove or reassign users first.",
  "user_count": 5
}
```

---

### 6. Approve/Reject Carrier
**Endpoint:** `POST /tms_api/super-admin/carriers/approve-carrier.php`

**Description:** Approve or reject a carrier account

**Request Body (Approve):**
```json
{
  "carrier_id": 1,
  "action": "approve",
  "approved_by": 1
}
```

**Request Body (Reject):**
```json
{
  "carrier_id": 1,
  "action": "reject",
  "reason": "Incomplete documentation",
  "approved_by": 1
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Carrier approved successfully",
  "data": {
    "id": 1,
    "carrier_code": "CAR-001",
    "is_approved": true,
    "account_status": "active",
    "approved_by_name": "Admin User",
    ...
  }
}
```

---

## Error Responses

All endpoints return consistent error responses:

```json
{
  "status": "error",
  "message": "Error description here"
}
```

Common HTTP Status Codes:
- `200` - Success
- `400` - Bad Request (validation error)
- `404` - Not Found
- `500` - Server Error

---

## Data Validation

### Required Fields (Create Carrier)
- `company_name` - Must not be empty
- `email` - Must be valid email format

### Unique Fields
- `carrier_code` - Auto-generated if not provided
- `email` - Must be unique across all carriers

### Field Formats
- `email` - Valid email format
- `carrier_type` - Must be one of: Asset-Based, Broker, 3PL, Freight Forwarder, Other
- `account_status` - Must be one of: pending, active, inactive, suspended, terminated
- `safety_rating` - Must be one of: Satisfactory, Conditional, Unsatisfactory, Not Rated

---

## Related Endpoints (To Be Created)

### Contacts
- `POST /tms_api/super-admin/carriers/contacts/create.php`
- `PUT /tms_api/super-admin/carriers/contacts/update.php`
- `DELETE /tms_api/super-admin/carriers/contacts/delete.php`

### Insurance
- `POST /tms_api/super-admin/carriers/insurance/create.php`
- `PUT /tms_api/super-admin/carriers/insurance/update.php`
- `DELETE /tms_api/super-admin/carriers/insurance/delete.php`

### Documents
- `POST /tms_api/super-admin/carriers/documents/upload.php`
- `GET /tms_api/super-admin/carriers/documents/download.php`
- `DELETE /tms_api/super-admin/carriers/documents/delete.php`

### Equipment
- `POST /tms_api/super-admin/carriers/equipment/create.php`
- `PUT /tms_api/super-admin/carriers/equipment/update.php`
- `DELETE /tms_api/super-admin/carriers/equipment/delete.php`

---

## Testing

### Using cURL

**Create Carrier:**
```bash
curl -X POST http://localhost/tms_api/super-admin/carriers/create-carrier.php \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "Test Carrier",
    "email": "test@carrier.com",
    "created_by": 1
  }'
```

**Get All Carriers:**
```bash
curl -X GET "http://localhost/tms_api/super-admin/carriers/get-carriers.php?status=active"
```

**Get Single Carrier:**
```bash
curl -X GET "http://localhost/tms_api/super-admin/carriers/get-carrier.php?id=1"
```

**Update Carrier:**
```bash
curl -X PUT http://localhost/tms_api/super-admin/carriers/update-carrier.php \
  -H "Content-Type: application/json" \
  -d '{
    "id": 1,
    "fleet_size": 60,
    "updated_by": 1
  }'
```

**Approve Carrier:**
```bash
curl -X POST http://localhost/tms_api/super-admin/carriers/approve-carrier.php \
  -H "Content-Type: application/json" \
  -d '{
    "carrier_id": 1,
    "action": "approve",
    "approved_by": 1
  }'
```

**Delete Carrier:**
```bash
curl -X DELETE "http://localhost/tms_api/super-admin/carriers/delete-carrier.php?id=1" \
  -H "Content-Type: application/json" \
  -d '{
    "deleted_by": 1
  }'
```

---

## Notes

1. **Audit Logging:** All carrier changes are automatically logged in `carrier_audit_log` table
2. **Cascade Deletes:** Deleting a carrier will cascade delete all related records (contacts, insurance, documents, etc.)
3. **User Protection:** Cannot delete a carrier if users are linked to it
4. **Auto-generation:** Carrier code is auto-generated if not provided (format: CAR-######)
5. **JSON Fields:** `service_types` and `operating_regions` are stored as JSON arrays

---

## Security Considerations

1. Implement proper authentication middleware
2. Validate Super Admin role before allowing access
3. Sanitize all input data
4. Use prepared statements (already implemented)
5. Encrypt sensitive data (bank account numbers)
6. Log all actions with user ID and IP address

---

*Last Updated: October 8, 2025*
