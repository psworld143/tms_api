<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../../configurations/database-connection.php';

// Only allow PUT requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    echo json_encode([
        "status" => "error",
        "message" => "Only PUT requests are allowed"
    ]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required field
    if (!isset($input['id'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Assignment ID is required"
        ]);
        exit;
    }
    
    $assignmentId = intval($input['id']);
    
    // Check if assignment exists
    $checkStmt = $pdo->prepare("SELECT carrier_id FROM carrier_user_assignments WHERE id = :id");
    $checkStmt->execute([':id' => $assignmentId]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        echo json_encode([
            "status" => "error",
            "message" => "Assignment not found"
        ]);
        exit;
    }
    
    // Build update query dynamically
    $updateFields = [];
    $params = [':id' => $assignmentId];
    
    if (isset($input['role_in_carrier'])) {
        $updateFields[] = "role_in_carrier = :role_in_carrier";
        $params[':role_in_carrier'] = $input['role_in_carrier'];
    }
    
    if (isset($input['is_primary_contact'])) {
        $isPrimary = (bool)$input['is_primary_contact'];
        $updateFields[] = "is_primary_contact = :is_primary_contact";
        $params[':is_primary_contact'] = $isPrimary;
        
        // If setting as primary, remove primary flag from other users in same carrier
        if ($isPrimary) {
            $removePrimary = $pdo->prepare("UPDATE carrier_user_assignments SET is_primary_contact = FALSE WHERE carrier_id = :carrier_id AND id != :id");
            $removePrimary->execute([
                ':carrier_id' => $existing['carrier_id'],
                ':id' => $assignmentId
            ]);
        }
    }
    
    if (isset($input['department'])) {
        $updateFields[] = "department = :department";
        $params[':department'] = $input['department'];
    }
    
    if (isset($input['can_manage_loads'])) {
        $updateFields[] = "can_manage_loads = :can_manage_loads";
        $params[':can_manage_loads'] = (bool)$input['can_manage_loads'];
    }
    
    if (isset($input['can_manage_drivers'])) {
        $updateFields[] = "can_manage_drivers = :can_manage_drivers";
        $params[':can_manage_drivers'] = (bool)$input['can_manage_drivers'];
    }
    
    if (isset($input['can_view_reports'])) {
        $updateFields[] = "can_view_reports = :can_view_reports";
        $params[':can_view_reports'] = (bool)$input['can_view_reports'];
    }
    
    if (isset($input['can_manage_billing'])) {
        $updateFields[] = "can_manage_billing = :can_manage_billing";
        $params[':can_manage_billing'] = (bool)$input['can_manage_billing'];
    }
    
    if (isset($input['status'])) {
        $updateFields[] = "status = :status";
        $params[':status'] = $input['status'];
    }
    
    if (isset($input['start_date'])) {
        $updateFields[] = "start_date = :start_date";
        $params[':start_date'] = $input['start_date'];
    }
    
    if (isset($input['end_date'])) {
        $updateFields[] = "end_date = :end_date";
        $params[':end_date'] = $input['end_date'];
    }
    
    if (isset($input['notes'])) {
        $updateFields[] = "notes = :notes";
        $params[':notes'] = $input['notes'];
    }
    
    if (empty($updateFields)) {
        echo json_encode([
            "status" => "error",
            "message" => "No fields to update"
        ]);
        exit;
    }
    
    // Execute update
    $updateSql = "UPDATE carrier_user_assignments SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute($params);
    
    // Get updated assignment
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
        "message" => "Assignment updated successfully",
        "data" => $assignment
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

