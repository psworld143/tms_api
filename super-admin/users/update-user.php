<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../../configurations/database-connection.php';

// Get user ID from query parameter or POST data
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = json_decode(file_get_contents("php://input"), true);

// If ID not in URL, check in POST data
if ($userId <= 0 && isset($data['id'])) {
    $userId = (int)$data['id'];
}

if ($userId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Valid user ID is required"
    ]);
    exit;
}

try {
    // Check if user exists
    $checkSql = "SELECT id FROM users WHERE id = :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':id' => $userId]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode([
            "status" => "error",
            "message" => "User not found"
        ]);
        exit;
    }

    // Get existing columns
    $columnCheckSql = "SHOW COLUMNS FROM users";
    $columnStmt = $pdo->query($columnCheckSql);
    $existingColumns = $columnStmt->fetchAll(PDO::FETCH_COLUMN);

    // Build dynamic UPDATE based on provided data and existing columns
    $updateFields = [];
    $params = [':id' => $userId];

    // Updatable fields
    $allowedFields = [
        'name' => 'name',
        'email' => 'email',
        'role' => 'role',
        'status' => 'status',
        'phone' => 'phone',
        'department' => 'department',
        'location' => 'location',
        'avatar' => 'avatar',
        'notes' => 'notes',
        'is_email_verified' => 'is_email_verified',
        'is_phone_verified' => 'is_phone_verified'
    ];

    foreach ($allowedFields as $jsonKey => $dbColumn) {
        if (isset($data[$jsonKey]) && in_array($dbColumn, $existingColumns)) {
            $updateFields[] = "$dbColumn = :$dbColumn";
            
            // Convert boolean fields
            if (in_array($dbColumn, ['is_email_verified', 'is_phone_verified'])) {
                $params[":$dbColumn"] = $data[$jsonKey] ? 1 : 0;
            } else {
                $params[":$dbColumn"] = trim($data[$jsonKey]);
            }
        }
    }

    // Handle password update separately (only if provided)
    if (isset($data['password']) && !empty(trim($data['password']))) {
        $updateFields[] = "password = :password";
        $params[':password'] = md5(trim($data['password']));
    }

    if (empty($updateFields)) {
        echo json_encode([
            "status" => "error",
            "message" => "No valid fields to update"
        ]);
        exit;
    }

    // Check if email is being changed and if it already exists
    if (isset($data['email'])) {
        $emailCheckSql = "SELECT id FROM users WHERE email = :email AND id != :id";
        $emailCheckStmt = $pdo->prepare($emailCheckSql);
        $emailCheckStmt->execute([':email' => trim($data['email']), ':id' => $userId]);
        
        if ($emailCheckStmt->fetch()) {
            echo json_encode([
                "status" => "error",
                "message" => "Email already exists for another user"
            ]);
            exit;
        }
    }

    // Add updated_at if column exists
    if (in_array('updated_at', $existingColumns)) {
        $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
    }

    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Get the updated user
    $getUserColumns = ['id', 'name', 'email', 'role', 'status', 'created_at', 'updated_at'];
    $optionalColumns = ['phone', 'department', 'location', 'avatar', 'last_login', 
                        'is_email_verified', 'is_phone_verified', 'notes'];
    
    foreach ($optionalColumns as $col) {
        if (in_array($col, $existingColumns)) {
            $getUserColumns[] = $col;
        }
    }
    
    $columnList = implode(', ', $getUserColumns);
    $getUserSql = "SELECT $columnList FROM users WHERE id = :id";
    $getUserStmt = $pdo->prepare($getUserSql);
    $getUserStmt->execute([':id' => $userId]);
    $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);

    // Split name into first_name and last_name
    $nameParts = explode(' ', $user['name'], 2);
    $user['first_name'] = $nameParts[0] ?? '';
    $user['last_name'] = $nameParts[1] ?? '';

    // Add permissions based on role
    $user['permissions'] = getRolePermissions($user['role']);

    // Convert boolean fields
    $user['is_email_verified'] = (bool)($user['is_email_verified'] ?? false);
    $user['is_phone_verified'] = (bool)($user['is_phone_verified'] ?? false);

    echo json_encode([
        "status" => "success",
        "message" => "User updated successfully",
        "data" => $user
    ], JSON_PRETTY_PRINT);

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
