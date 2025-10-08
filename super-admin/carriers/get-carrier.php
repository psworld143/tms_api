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

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        "status" => "error",
        "message" => "Only GET requests are allowed"
    ]);
    exit;
}

// Validate carrier ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Carrier ID is required"
    ]);
    exit;
}

$carrierId = intval($_GET['id']);

try {
    // Get carrier details
    $carrierSql = "SELECT 
                    c.*,
                    u1.name AS created_by_name,
                    u2.name AS approved_by_name
                FROM carriers c
                LEFT JOIN users u1 ON c.created_by = u1.id
                LEFT JOIN users u2 ON c.approved_by = u2.id
                WHERE c.id = :id";
    
    $carrierStmt = $pdo->prepare($carrierSql);
    $carrierStmt->execute([':id' => $carrierId]);
    $carrier = $carrierStmt->fetch(PDO::FETCH_ASSOC);

    if (!$carrier) {
        echo json_encode([
            "status" => "error",
            "message" => "Carrier not found"
        ]);
        exit;
    }

    // Parse JSON fields
    if ($carrier['service_types']) {
        $carrier['service_types'] = json_decode($carrier['service_types']);
    }
    if ($carrier['operating_regions']) {
        $carrier['operating_regions'] = json_decode($carrier['operating_regions']);
    }

    // Get contacts
    $contactsSql = "SELECT * FROM carrier_contacts WHERE carrier_id = :carrier_id ORDER BY is_primary DESC, contact_type";
    $contactsStmt = $pdo->prepare($contactsSql);
    $contactsStmt->execute([':carrier_id' => $carrierId]);
    $carrier['contacts'] = $contactsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get insurance
    $insuranceSql = "SELECT * FROM carrier_insurance WHERE carrier_id = :carrier_id ORDER BY expiration_date DESC";
    $insuranceStmt = $pdo->prepare($insuranceSql);
    $insuranceStmt->execute([':carrier_id' => $carrierId]);
    $carrier['insurance'] = $insuranceStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get documents
    $documentsSql = "SELECT * FROM carrier_documents WHERE carrier_id = :carrier_id AND status = 'active' ORDER BY uploaded_at DESC";
    $documentsStmt = $pdo->prepare($documentsSql);
    $documentsStmt->execute([':carrier_id' => $carrierId]);
    $carrier['documents'] = $documentsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get equipment
    $equipmentSql = "SELECT * FROM carrier_equipment WHERE carrier_id = :carrier_id";
    $equipmentStmt = $pdo->prepare($equipmentSql);
    $equipmentStmt->execute([':carrier_id' => $carrierId]);
    $equipment = $equipmentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse equipment specifications JSON
    foreach ($equipment as &$equip) {
        if ($equip['specifications']) {
            $equip['specifications'] = json_decode($equip['specifications']);
        }
    }
    $carrier['equipment'] = $equipment;

    // Get bank accounts (hide sensitive info)
    $bankSql = "SELECT id, carrier_id, account_type, bank_name, account_holder_name, is_primary, is_verified, status, created_at 
                FROM carrier_bank_accounts WHERE carrier_id = :carrier_id";
    $bankStmt = $pdo->prepare($bankSql);
    $bankStmt->execute([':carrier_id' => $carrierId]);
    $carrier['bank_accounts'] = $bankStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent performance metrics
    $performanceSql = "SELECT * FROM carrier_performance_metrics WHERE carrier_id = :carrier_id ORDER BY period_end DESC LIMIT 12";
    $performanceStmt = $pdo->prepare($performanceSql);
    $performanceStmt->execute([':carrier_id' => $carrierId]);
    $carrier['performance_metrics'] = $performanceStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent audit log entries
    $auditSql = "SELECT 
                    cal.*,
                    u.name AS changed_by_name
                FROM carrier_audit_log cal
                LEFT JOIN users u ON cal.changed_by = u.id
                WHERE cal.carrier_id = :carrier_id
                ORDER BY cal.changed_at DESC
                LIMIT 50";
    $auditStmt = $pdo->prepare($auditSql);
    $auditStmt->execute([':carrier_id' => $carrierId]);
    $auditLog = $auditStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse JSON fields in audit log
    foreach ($auditLog as &$log) {
        if ($log['old_values']) {
            $log['old_values'] = json_decode($log['old_values']);
        }
        if ($log['new_values']) {
            $log['new_values'] = json_decode($log['new_values']);
        }
    }
    $carrier['audit_log'] = $auditLog;

    echo json_encode([
        "status" => "success",
        "message" => "Carrier details retrieved successfully",
        "data" => $carrier
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
