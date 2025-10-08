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
if (!isset($data['carrier_id']) || empty($data['carrier_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Carrier ID is required"
    ]);
    exit;
}

$carrierId = intval($data['carrier_id']);
$approvedBy = isset($data['approved_by']) ? intval($data['approved_by']) : null;
$action = isset($data['action']) ? trim($data['action']) : 'approve'; // approve or reject

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

    if ($action === 'approve') {
        // Approve carrier
        $updateSql = "UPDATE carriers 
                     SET is_approved = TRUE, 
                         approved_by = :approved_by, 
                         approved_at = NOW(),
                         account_status = 'active'
                     WHERE id = :id";
        
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':id' => $carrierId,
            ':approved_by' => $approvedBy
        ]);

        // Log the approval
        $auditSql = "INSERT INTO carrier_audit_log (carrier_id, action_type, action_description, changed_by) 
                     VALUES (:carrier_id, 'approved', 'Carrier account approved and activated', :changed_by)";
        $auditStmt = $pdo->prepare($auditSql);
        $auditStmt->execute([
            ':carrier_id' => $carrierId,
            ':changed_by' => $approvedBy
        ]);

        $message = "Carrier approved successfully";
        
    } elseif ($action === 'reject') {
        // Reject carrier
        $updateSql = "UPDATE carriers 
                     SET is_approved = FALSE,
                         account_status = 'inactive'
                     WHERE id = :id";
        
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([':id' => $carrierId]);

        // Log the rejection
        $rejectionReason = isset($data['reason']) ? trim($data['reason']) : 'No reason provided';
        
        $auditSql = "INSERT INTO carrier_audit_log (carrier_id, action_type, action_description, changed_by) 
                     VALUES (:carrier_id, 'rejected', :description, :changed_by)";
        $auditStmt = $pdo->prepare($auditSql);
        $auditStmt->execute([
            ':carrier_id' => $carrierId,
            ':description' => 'Carrier account rejected: ' . $rejectionReason,
            ':changed_by' => $approvedBy
        ]);

        $message = "Carrier rejected successfully";
        
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid action. Use 'approve' or 'reject'"
        ]);
        exit;
    }

    // Get updated carrier
    $getUpdatedSql = "SELECT 
                        c.*,
                        u.name AS approved_by_name
                     FROM carriers c
                     LEFT JOIN users u ON c.approved_by = u.id
                     WHERE c.id = :id";
    $getUpdatedStmt = $pdo->prepare($getUpdatedSql);
    $getUpdatedStmt->execute([':id' => $carrierId]);
    $updatedCarrier = $getUpdatedStmt->fetch(PDO::FETCH_ASSOC);

    // Parse JSON fields
    if ($updatedCarrier['service_types']) {
        $updatedCarrier['service_types'] = json_decode($updatedCarrier['service_types']);
    }
    if ($updatedCarrier['operating_regions']) {
        $updatedCarrier['operating_regions'] = json_decode($updatedCarrier['operating_regions']);
    }

    echo json_encode([
        "status" => "success",
        "message" => $message,
        "data" => $updatedCarrier
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
