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

$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Carrier ID is required"
    ]);
    exit;
}

$carrierId = intval($data['id']);

try {
    // Get existing carrier data
    $getCarrierSql = "SELECT * FROM carriers WHERE id = :id";
    $getCarrierStmt = $pdo->prepare($getCarrierSql);
    $getCarrierStmt->execute([':id' => $carrierId]);
    $existingCarrier = $getCarrierStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingCarrier) {
        echo json_encode([
            "status" => "error",
            "message" => "Carrier not found"
        ]);
        exit;
    }

    // Build update query dynamically based on provided fields
    $updateFields = [];
    $params = [':id' => $carrierId];

    $allowedFields = [
        'carrier_code', 'company_name', 'legal_name', 'dba_name', 'email', 'phone', 'fax', 'website',
        'address_line1', 'address_line2', 'city', 'state', 'zip_code', 'country',
        'mc_number', 'dot_number', 'tax_id', 'scac_code',
        'carrier_type', 'fleet_size', 'driver_count',
        'account_status', 'onboarding_status', 'payment_terms', 'credit_limit',
        'safety_rating', 'carrier_rating', 'is_preferred', 'is_approved', 'notes'
    ];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = :$field";
            
            // Handle JSON fields
            if ($field === 'service_types' || $field === 'operating_regions') {
                $params[":$field"] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
            } else {
                $params[":$field"] = $data[$field];
            }
        }
    }

    // Handle service_types and operating_regions separately
    if (isset($data['service_types'])) {
        $updateFields[] = "service_types = :service_types";
        $params[':service_types'] = is_array($data['service_types']) ? json_encode($data['service_types']) : $data['service_types'];
    }

    if (isset($data['operating_regions'])) {
        $updateFields[] = "operating_regions = :operating_regions";
        $params[':operating_regions'] = is_array($data['operating_regions']) ? json_encode($data['operating_regions']) : $data['operating_regions'];
    }

    if (empty($updateFields)) {
        echo json_encode([
            "status" => "error",
            "message" => "No fields to update"
        ]);
        exit;
    }

    // Check for email uniqueness if email is being updated
    if (isset($data['email']) && $data['email'] !== $existingCarrier['email']) {
        $checkEmailSql = "SELECT id FROM carriers WHERE email = :email AND id != :id";
        $checkEmailStmt = $pdo->prepare($checkEmailSql);
        $checkEmailStmt->execute([':email' => $data['email'], ':id' => $carrierId]);
        
        if ($checkEmailStmt->fetch()) {
            echo json_encode([
                "status" => "error",
                "message" => "Email already exists"
            ]);
            exit;
        }
    }

    // Update carrier
    $sql = "UPDATE carriers SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Log the update in audit log
    $changedBy = isset($data['updated_by']) ? intval($data['updated_by']) : null;
    
    $auditSql = "INSERT INTO carrier_audit_log (carrier_id, action_type, action_description, changed_by, old_values, new_values) 
                 VALUES (:carrier_id, 'updated', 'Carrier information updated', :changed_by, :old_values, :new_values)";
    $auditStmt = $pdo->prepare($auditSql);
    $auditStmt->execute([
        ':carrier_id' => $carrierId,
        ':changed_by' => $changedBy,
        ':old_values' => json_encode($existingCarrier),
        ':new_values' => json_encode($data)
    ]);

    // Get updated carrier
    $getUpdatedSql = "SELECT * FROM carriers WHERE id = :id";
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
        "message" => "Carrier updated successfully",
        "data" => $updatedCarrier
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
