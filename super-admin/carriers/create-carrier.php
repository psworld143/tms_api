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
$requiredFields = ['company_name', 'email'];
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
$carrier_code = isset($data['carrier_code']) ? trim($data['carrier_code']) : null;
$company_name = trim($data['company_name']);
$legal_name = isset($data['legal_name']) ? trim($data['legal_name']) : null;
$dba_name = isset($data['dba_name']) ? trim($data['dba_name']) : null;
$email = trim($data['email']);
$phone = isset($data['phone']) ? trim($data['phone']) : null;
$fax = isset($data['fax']) ? trim($data['fax']) : null;
$website = isset($data['website']) ? trim($data['website']) : null;

// Address
$address_line1 = isset($data['address_line1']) ? trim($data['address_line1']) : null;
$address_line2 = isset($data['address_line2']) ? trim($data['address_line2']) : null;
$city = isset($data['city']) ? trim($data['city']) : null;
$state = isset($data['state']) ? trim($data['state']) : null;
$zip_code = isset($data['zip_code']) ? trim($data['zip_code']) : null;
$country = isset($data['country']) ? trim($data['country']) : 'USA';

// Business info
$mc_number = isset($data['mc_number']) ? trim($data['mc_number']) : null;
$dot_number = isset($data['dot_number']) ? trim($data['dot_number']) : null;
$tax_id = isset($data['tax_id']) ? trim($data['tax_id']) : null;
$scac_code = isset($data['scac_code']) ? trim($data['scac_code']) : null;

// Operational details
$carrier_type = isset($data['carrier_type']) ? trim($data['carrier_type']) : 'Asset-Based';
$service_types = isset($data['service_types']) ? json_encode($data['service_types']) : null;
$operating_regions = isset($data['operating_regions']) ? json_encode($data['operating_regions']) : null;
$fleet_size = isset($data['fleet_size']) ? intval($data['fleet_size']) : 0;
$driver_count = isset($data['driver_count']) ? intval($data['driver_count']) : 0;

// Account status
$account_status = isset($data['account_status']) ? trim($data['account_status']) : 'pending';
$onboarding_status = isset($data['onboarding_status']) ? trim($data['onboarding_status']) : 'incomplete';
$payment_terms = isset($data['payment_terms']) ? trim($data['payment_terms']) : 'Net 30';
$credit_limit = isset($data['credit_limit']) ? floatval($data['credit_limit']) : 0.00;

// Ratings
$safety_rating = isset($data['safety_rating']) ? trim($data['safety_rating']) : 'Not Rated';
$carrier_rating = isset($data['carrier_rating']) ? floatval($data['carrier_rating']) : 0.00;
$is_preferred = isset($data['is_preferred']) ? (bool)$data['is_preferred'] : false;
$is_approved = isset($data['is_approved']) ? (bool)$data['is_approved'] : false;

$notes = isset($data['notes']) ? trim($data['notes']) : null;
$created_by = isset($data['created_by']) ? intval($data['created_by']) : null;

try {
    // Check if carrier_code already exists (if provided)
    if ($carrier_code) {
        $checkSql = "SELECT id FROM carriers WHERE carrier_code = :carrier_code";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([':carrier_code' => $carrier_code]);
        
        if ($checkStmt->fetch()) {
            echo json_encode([
                "status" => "error",
                "message" => "Carrier code already exists"
            ]);
            exit;
        }
    }

    // Check if email already exists
    $checkEmailSql = "SELECT id FROM carriers WHERE email = :email";
    $checkEmailStmt = $pdo->prepare($checkEmailSql);
    $checkEmailStmt->execute([':email' => $email]);
    
    if ($checkEmailStmt->fetch()) {
        echo json_encode([
            "status" => "error",
            "message" => "Email already exists"
        ]);
        exit;
    }

    // If carrier_code not provided, generate one
    if (!$carrier_code) {
        $carrier_code = 'CAR-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    // Insert carrier
    $sql = "INSERT INTO carriers (
        carrier_code, company_name, legal_name, dba_name, email, phone, fax, website,
        address_line1, address_line2, city, state, zip_code, country,
        mc_number, dot_number, tax_id, scac_code,
        carrier_type, service_types, operating_regions, fleet_size, driver_count,
        account_status, onboarding_status, payment_terms, credit_limit,
        safety_rating, carrier_rating, is_preferred, is_approved,
        notes, created_by
    ) VALUES (
        :carrier_code, :company_name, :legal_name, :dba_name, :email, :phone, :fax, :website,
        :address_line1, :address_line2, :city, :state, :zip_code, :country,
        :mc_number, :dot_number, :tax_id, :scac_code,
        :carrier_type, :service_types, :operating_regions, :fleet_size, :driver_count,
        :account_status, :onboarding_status, :payment_terms, :credit_limit,
        :safety_rating, :carrier_rating, :is_preferred, :is_approved,
        :notes, :created_by
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':carrier_code' => $carrier_code,
        ':company_name' => $company_name,
        ':legal_name' => $legal_name,
        ':dba_name' => $dba_name,
        ':email' => $email,
        ':phone' => $phone,
        ':fax' => $fax,
        ':website' => $website,
        ':address_line1' => $address_line1,
        ':address_line2' => $address_line2,
        ':city' => $city,
        ':state' => $state,
        ':zip_code' => $zip_code,
        ':country' => $country,
        ':mc_number' => $mc_number,
        ':dot_number' => $dot_number,
        ':tax_id' => $tax_id,
        ':scac_code' => $scac_code,
        ':carrier_type' => $carrier_type,
        ':service_types' => $service_types,
        ':operating_regions' => $operating_regions,
        ':fleet_size' => $fleet_size,
        ':driver_count' => $driver_count,
        ':account_status' => $account_status,
        ':onboarding_status' => $onboarding_status,
        ':payment_terms' => $payment_terms,
        ':credit_limit' => $credit_limit,
        ':safety_rating' => $safety_rating,
        ':carrier_rating' => $carrier_rating,
        ':is_preferred' => $is_preferred,
        ':is_approved' => $is_approved,
        ':notes' => $notes,
        ':created_by' => $created_by
    ]);

    $carrierId = $pdo->lastInsertId();

    // Log the creation in audit log
    $auditSql = "INSERT INTO carrier_audit_log (carrier_id, action_type, action_description, changed_by) 
                 VALUES (:carrier_id, 'created', 'Carrier account created', :changed_by)";
    $auditStmt = $pdo->prepare($auditSql);
    $auditStmt->execute([
        ':carrier_id' => $carrierId,
        ':changed_by' => $created_by
    ]);

    // Get the created carrier
    $getCarrierSql = "SELECT * FROM carriers WHERE id = :id";
    $getCarrierStmt = $pdo->prepare($getCarrierSql);
    $getCarrierStmt->execute([':id' => $carrierId]);
    $carrier = $getCarrierStmt->fetch(PDO::FETCH_ASSOC);

    // Parse JSON fields
    if ($carrier['service_types']) {
        $carrier['service_types'] = json_decode($carrier['service_types']);
    }
    if ($carrier['operating_regions']) {
        $carrier['operating_regions'] = json_decode($carrier['operating_regions']);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Carrier created successfully",
        "data" => $carrier
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
