<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../../configurations/database-connection.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "message" => "Only POST requests are allowed"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
$requiredFields = ['name', 'email', 'password', 'role'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields: " . implode(', ', $missingFields)
    ]);
    exit;
}

// Validate email format
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email format"
    ]);
    exit;
}

// Extract and sanitize data
$name = trim($data['name']);
$email = trim($data['email']);
$password = md5(trim($data['password'])); // Using MD5 to match existing system
$role = trim($data['role']);
$status = isset($data['status']) ? trim($data['status']) : 'active';
$phone = isset($data['phone']) ? trim($data['phone']) : null;
$department = isset($data['department']) ? trim($data['department']) : null;
$location = isset($data['location']) ? trim($data['location']) : null;
$notes = isset($data['notes']) ? trim($data['notes']) : null;

try {
    // Check if email already exists
    $checkSql = "SELECT id FROM users WHERE email = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':email' => $email]);
    
    if ($checkStmt->fetch()) {
        echo json_encode([
            "status" => "error",
            "message" => "Email already exists"
        ]);
        exit;
    }

    // Get existing columns
    $columnCheckSql = "SHOW COLUMNS FROM users";
    $columnStmt = $pdo->query($columnCheckSql);
    $existingColumns = $columnStmt->fetchAll(PDO::FETCH_COLUMN);

    // Build dynamic INSERT based on existing columns
    $columns = ['name', 'email', 'password', 'role', 'status'];
    $values = [':name', ':email', ':password', ':role', ':status'];
    $params = [
        ':name' => $name,
        ':email' => $email,
        ':password' => $password,
        ':role' => $role,
        ':status' => $status
    ];

    // Add optional columns if they exist in the table
    $optionalFields = [
        'phone' => $phone,
        'department' => $department,
        'location' => $location,
        'notes' => $notes
    ];

    foreach ($optionalFields as $col => $val) {
        if (in_array($col, $existingColumns) && $val !== null) {
            $columns[] = $col;
            $values[] = ":$col";
            $params[":$col"] = $val;
        }
    }

    $columnList = implode(', ', $columns);
    $valueList = implode(', ', $values);

    $sql = "INSERT INTO users ($columnList) VALUES ($valueList)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $userId = $pdo->lastInsertId();

    // Get the created user
    $getUserSql = "SELECT * FROM users WHERE id = :id";
    $getUserStmt = $pdo->prepare($getUserSql);
    $getUserStmt->execute([':id' => $userId]);
    $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);

    // Remove password from response
    unset($user['password']);

    // Split name into first_name and last_name
    $nameParts = explode(' ', $user['name'], 2);
    $user['first_name'] = $nameParts[0] ?? '';
    $user['last_name'] = $nameParts[1] ?? '';

    // Add permissions based on role
    $user['permissions'] = getRolePermissions($user['role']);

    echo json_encode([
        "status" => "success",
        "message" => "User created successfully",
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
