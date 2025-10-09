Carrier Admin API

Tenant requirement:
- Provide dedicated DB via header X-Carrier-DB or query database_name

Endpoints:
- users/get-users.php: list users (excludes Super Admin)
- users/set-user-role.php: assign/update role
- users/get-roles.php: list allowed roles

Responses: { status, message, data? }

