<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../../configurations/database-connection.php';

try {
    // Get query parameters for filtering
    $role = isset($_GET['role']) ? $_GET['role'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = ($page - 1) * $limit;

    // Get all columns that exist in the users table
    $columnCheckSql = "SHOW COLUMNS FROM users";
    $columnStmt = $pdo->query($columnCheckSql);
    $existingColumns = $columnStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Build dynamic SQL based on existing columns
    $columns = ['id', 'name', 'email', 'role', 'status', 'created_at', 'updated_at'];
    $optionalColumns = ['phone', 'department', 'location', 'avatar', 'last_login', 
                        'is_email_verified', 'is_phone_verified', 'notes'];
    
    foreach ($optionalColumns as $col) {
        if (in_array($col, $existingColumns)) {
            $columns[] = $col;
        }
    }
    
    $columnList = implode(', ', $columns);
    
    // Build WHERE clause
    $where = [];
    $params = [];
    
    if ($role !== null) {
        $where[] = "role = :role";
        $params[':role'] = $role;
    }
    
    if ($status !== null) {
        $where[] = "status = :status";
        $params[':status'] = $status;
    }
    
    if ($search !== null) {
        $where[] = "(name LIKE :search OR email LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM users $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get users with pagination
    $sql = "SELECT $columnList FROM users $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Bind filtering parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind pagination parameters
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($users) {
        // Process users to add permissions and format data
        $processedUsers = array_map(function($user) {
            // Remove password from response
            unset($user['password']);
            
            // Split name into first_name and last_name
            $nameParts = explode(' ', $user['name'], 2);
            $user['first_name'] = $nameParts[0] ?? '';
            $user['last_name'] = $nameParts[1] ?? '';
            
            // Add default values for optional fields if they don't exist
            $user['phone'] = $user['phone'] ?? '';
            $user['department'] = $user['department'] ?? null;
            $user['location'] = $user['location'] ?? null;
            $user['avatar'] = $user['avatar'] ?? null;
            $user['notes'] = $user['notes'] ?? null;
            
            // Format last_login
            $user['last_login'] = $user['last_login'] ?? $user['updated_at'];
            
            // Add permissions based on role
            $user['permissions'] = getRolePermissions($user['role']);
            
            // Convert boolean fields
            $user['is_email_verified'] = (bool)($user['is_email_verified'] ?? false);
            $user['is_phone_verified'] = (bool)($user['is_phone_verified'] ?? false);
            
            return $user;
        }, $users);

        echo json_encode([
            "status" => "success",
            "message" => "Users retrieved successfully",
            "count" => count($processedUsers),
            "total" => (int)$total,
            "page" => $page,
            "limit" => $limit,
            "data" => $processedUsers
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            "status" => "success",
            "message" => "No users found",
            "count" => 0,
            "total" => 0,
            "data" => []
        ], JSON_PRETTY_PRINT);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

// Function to get permissions based on user role
function getRolePermissions($role) {
    $permissions = [
        'Super Admin' => [
            'admin', 'user_management', 'system_settings', 'carrier_management',
            'load_management', 'driver_management', 'accounting', 'analytics',
            'reports', 'audit_logs', 'all_access'
        ],
        'Carrier Admin' => [
            'carrier_management', 'user_management', 'load_management',
            'driver_management', 'accounting', 'reports', 'analytics'
        ],
        'Dispatcher' => [
            'load_management', 'driver_assignment', 'route_planning',
            'communication', 'tracking', 'reports'
        ],
        'Driver' => [
            'view_loads', 'update_status', 'upload_documents', 'communication',
            'view_schedule', 'eld_access'
        ],
        'Accounting' => [
            'accounting', 'invoices', 'payments', 'settlements', 'reports',
            'financial_analytics'
        ],
        'Safety Manager' => [
            'safety_compliance', 'violations', 'inspections', 'hos_review',
            'driver_monitoring', 'reports'
        ],
        'Maintenance Manager' => [
            'vehicle_management', 'maintenance_schedules', 'inspections',
            'repairs', 'inventory', 'reports'
        ],
        'Marketing Manager' => [
            'marketing', 'campaigns', 'recruitment', 'analytics', 'reports'
        ],
        'Recruiter' => [
            'recruitment', 'driver_onboarding', 'applications', 'reports'
        ],
        'Support Manager' => [
            'support', 'tickets', 'communication', 'user_assistance', 'reports'
        ]
    ];
    
    return $permissions[$role] ?? ['basic_access'];
}
?>
