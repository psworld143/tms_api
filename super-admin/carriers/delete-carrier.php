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

// Get carrier ID from query string or request body
$carrierId = null;

if (isset($_GET['id'])) {
    $carrierId = intval($_GET['id']);
} else {
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['id'])) {
        $carrierId = intval($data['id']);
    }
}

if (!$carrierId) {
    echo json_encode([
        "status" => "error",
        "message" => "Carrier ID is required"
    ]);
    exit;
}

try {
    // Check if carrier exists
    $checkSql = "SELECT * FROM carriers WHERE id = :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':id' => $carrierId]);
    $carrier = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$carrier) {
        echo json_encode([
            "status" => "error",
            "message" => "Carrier not found"
        ]);
        exit;
    }

    // Check if there are any users linked to this carrier
    $userCheckSql = "SELECT COUNT(*) as user_count FROM users WHERE carrier_id = :carrier_id";
    $userCheckStmt = $pdo->prepare($userCheckSql);
    $userCheckStmt->execute([':carrier_id' => $carrierId]);
    $userCount = $userCheckStmt->fetch(PDO::FETCH_ASSOC)['user_count'];

    if ($userCount > 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Cannot delete carrier with active users. Please remove or reassign users first.",
            "user_count" => $userCount
        ]);
        exit;
    }

    // Log the deletion (before deleting, because of foreign key)
    $data = json_decode(file_get_contents("php://input"), true);
    $deletedBy = isset($data['deleted_by']) ? intval($data['deleted_by']) : null;
    
    $auditSql = "INSERT INTO carrier_audit_log (carrier_id, action_type, action_description, changed_by, old_values) 
                 VALUES (:carrier_id, 'deleted', 'Carrier account deleted', :changed_by, :old_values)";
    $auditStmt = $pdo->prepare($auditSql);
    $auditStmt->execute([
        ':carrier_id' => $carrierId,
        ':changed_by' => $deletedBy,
        ':old_values' => json_encode($carrier)
    ]);

    // Delete carrier (CASCADE will delete related records)
    $deleteSql = "DELETE FROM carriers WHERE id = :id";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([':id' => $carrierId]);

    echo json_encode([
        "status" => "success",
        "message" => "Carrier deleted successfully",
        "deleted_carrier" => [
            "id" => $carrier['id'],
            "carrier_code" => $carrier['carrier_code'],
            "company_name" => $carrier['company_name']
        ]
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
