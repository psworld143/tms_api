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

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['carrier_id']) || !isset($input['user_id'])) {
        echo json_encode([
            "status" => "error",
            "message" => "carrier_id and user_id are required"
        ]);
        exit;
    }
    
    $carrierId = intval($input['carrier_id']);
    $userId = intval($input['user_id']);
    $roleInCarrier = $input['role_in_carrier'] ?? 'User';
    $isPrimaryContact = isset($input['is_primary_contact']) ? (bool)$input['is_primary_contact'] : false;
    $department = $input['department'] ?? null;
    $canManageLoads = isset($input['can_manage_loads']) ? (bool)$input['can_manage_loads'] : false;
    $canManageDrivers = isset($input['can_manage_drivers']) ? (bool)$input['can_manage_drivers'] : false;
    $canViewReports = isset($input['can_view_reports']) ? (bool)$input['can_view_reports'] : true;
    $canManageBilling = isset($input['can_manage_billing']) ? (bool)$input['can_manage_billing'] : false;
    $status = $input['status'] ?? 'active';
    $startDate = $input['start_date'] ?? null;
    $endDate = $input['end_date'] ?? null;
    $assignedBy = $input['assigned_by'] ?? null;
    $notes = $input['notes'] ?? null;
    
    // Check if carrier exists
    $carrierCheck = $pdo->prepare("SELECT id, company_name FROM carriers WHERE id = :carrier_id");
    $carrierCheck->execute([':carrier_id' => $carrierId]);
    $carrier = $carrierCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$carrier) {
        echo json_encode([
            "status" => "error",
            "message" => "Carrier not found"
        ]);
        exit;
    }
    
    // Check if user exists
    $userCheck = $pdo->prepare("SELECT id, name, email FROM users WHERE id = :user_id");
    $userCheck->execute([':user_id' => $userId]);
    $user = $userCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            "status" => "error",
            "message" => "User not found"
        ]);
        exit;
    }
    
    // Check if assignment already exists
    $checkAssignment = $pdo->prepare("SELECT id FROM carrier_user_assignments WHERE carrier_id = :carrier_id AND user_id = :user_id");
    $checkAssignment->execute([
        ':carrier_id' => $carrierId,
        ':user_id' => $userId
    ]);
    
    if ($checkAssignment->rowCount() > 0) {
        echo json_encode([
            "status" => "error",
            "message" => "User is already assigned to this carrier"
        ]);
        exit;
    }
    
    // If setting as primary contact, remove primary flag from other users
    if ($isPrimaryContact) {
        $removePrimary = $pdo->prepare("UPDATE carrier_user_assignments SET is_primary_contact = FALSE WHERE carrier_id = :carrier_id");
        $removePrimary->execute([':carrier_id' => $carrierId]);
    }
    
    // Insert assignment
    $sql = "INSERT INTO carrier_user_assignments (
                carrier_id, user_id, role_in_carrier, is_primary_contact, 
                department, can_manage_loads, can_manage_drivers, 
                can_view_reports, can_manage_billing, status,
                start_date, end_date, assigned_by, notes
            ) VALUES (
                :carrier_id, :user_id, :role_in_carrier, :is_primary_contact,
                :department, :can_manage_loads, :can_manage_drivers,
                :can_view_reports, :can_manage_billing, :status,
                :start_date, :end_date, :assigned_by, :notes
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':carrier_id' => $carrierId,
        ':user_id' => $userId,
        ':role_in_carrier' => $roleInCarrier,
        ':is_primary_contact' => $isPrimaryContact,
        ':department' => $department,
        ':can_manage_loads' => $canManageLoads,
        ':can_manage_drivers' => $canManageDrivers,
        ':can_view_reports' => $canViewReports,
        ':can_manage_billing' => $canManageBilling,
        ':status' => $status,
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':assigned_by' => $assignedBy,
        ':notes' => $notes
    ]);
    
    $assignmentId = $pdo->lastInsertId();
    
    // Get the complete assignment with user and carrier details
    $getAssignment = $pdo->prepare("
        SELECT 
            cua.*,
            c.company_name as carrier_name,
            c.carrier_code,
            u.name as user_name,
            u.email as user_email,
            u.role as user_role,
            ab.name as assigned_by_name
        FROM carrier_user_assignments cua
        JOIN carriers c ON cua.carrier_id = c.id
        JOIN users u ON cua.user_id = u.id
        LEFT JOIN users ab ON cua.assigned_by = ab.id
        WHERE cua.id = :id
    ");
    
    $getAssignment->execute([':id' => $assignmentId]);
    $assignment = $getAssignment->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "message" => "User assigned to carrier successfully",
        "data" => $assignment
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

