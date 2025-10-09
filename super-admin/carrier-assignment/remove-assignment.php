<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../../configurations/database-connection.php';

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode([
        "status" => "error",
        "message" => "Only DELETE requests are allowed"
    ]);
    exit;
}

try {
    // Get assignment ID from query parameter
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Assignment ID is required"
        ]);
        exit;
    }
    
    $assignmentId = intval($_GET['id']);
    
    // Get assignment details before deletion
    $getAssignment = $pdo->prepare("
        SELECT 
            cua.*,
            c.company_name as carrier_name,
            u.name as user_name,
            u.email as user_email
        FROM carrier_user_assignments cua
        JOIN carriers c ON cua.carrier_id = c.id
        JOIN users u ON cua.user_id = u.id
        WHERE cua.id = :id
    ");
    
    $getAssignment->execute([':id' => $assignmentId]);
    $assignment = $getAssignment->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        echo json_encode([
            "status" => "error",
            "message" => "Assignment not found"
        ]);
        exit;
    }
    
    // Delete the assignment
    $deleteStmt = $pdo->prepare("DELETE FROM carrier_user_assignments WHERE id = :id");
    $deleteStmt->execute([':id' => $assignmentId]);
    
    echo json_encode([
        "status" => "success",
        "message" => "User assignment removed successfully",
        "data" => [
            "assignment_id" => $assignmentId,
            "carrier_name" => $assignment['carrier_name'],
            "user_name" => $assignment['user_name'],
            "user_email" => $assignment['user_email'],
            "role_in_carrier" => $assignment['role_in_carrier'],
            "removed_at" => date('Y-m-d H:i:s')
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

