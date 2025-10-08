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

// Get user ID from query parameter
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Valid user ID is required"
    ]);
    exit;
}

try {
    // Get all columns that exist in the users table
    $columnCheckSql = "SHOW COLUMNS FROM users";
    $columnStmt = $pdo->query($columnCheckSql);
    $existingColumns = $columnStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Build dynamic SQL based on existing columns (excluding password)
    $columns = ['id', 'name', 'email', 'role', 'status', 'created_at', 'updated_at'];
    $optionalColumns = ['phone', 'department', 'location', 'avatar', 'last_login', 
                        'is_email_verified', 'is_phone_verified', 'notes'];
    
    foreach ($optionalColumns as $col) {
        if (in_array($col, $existingColumns)) {
            $columns[] = $col;
        }
    }
    
    $columnList = implode(', ', $columns);
    
    $sql = "SELECT $columnList FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $userId]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Split name into first_name and last_name
        $nameParts = explode(' ', $user['name'], 2);
        $user['first_name'] = $nameParts[0] ?? '';
        $user['last_name'] = $nameParts[1] ?? '';
        
        // Add default values for optional fields
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
        
        echo json_encode([
            "status" => "success",
            "message" => "User retrieved successfully",
            "data" => $user
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "User not found"
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
