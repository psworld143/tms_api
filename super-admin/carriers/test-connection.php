<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../../configurations/database-connection.php';

try {
    // Test 1: Check if carriers table exists
    $tableCheckSql = "SHOW TABLES LIKE 'carriers'";
    $tableStmt = $pdo->query($tableCheckSql);
    $tableExists = $tableStmt->rowCount() > 0;

    // Test 2: Count carriers
    $countSql = "SELECT COUNT(*) as total FROM carriers";
    $countStmt = $pdo->query($countSql);
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Test 3: Get sample carriers
    $sampleSql = "SELECT 
                    carrier_code, 
                    company_name, 
                    account_status, 
                    is_approved,
                    created_at
                FROM carriers 
                LIMIT 5";
    $sampleStmt = $pdo->query($sampleSql);
    $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);

    // Test 4: Check related tables
    $contactsCountSql = "SELECT COUNT(*) as total FROM carrier_contacts";
    $contactsStmt = $pdo->query($contactsCountSql);
    $contactsCount = $contactsStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $insuranceCountSql = "SELECT COUNT(*) as total FROM carrier_insurance";
    $insuranceStmt = $pdo->query($insuranceCountSql);
    $insuranceCount = $insuranceStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        "status" => "success",
        "message" => "Database connection successful",
        "database" => "pms_nexus_tms",
        "tests" => [
            "table_exists" => $tableExists,
            "carriers_count" => $count,
            "contacts_count" => $contactsCount,
            "insurance_count" => $insuranceCount,
        ],
        "sample_carriers" => $samples,
        "recommendations" => $count == 0 ? [
            "No carriers found in database",
            "Please run: tms_api/database-schema/insert_sample_carriers.sql",
            "Or use the carrier management UI to add carriers"
        ] : [
            "Database has $count carriers",
            "Carrier management system is ready to use"
        ]
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage(),
        "recommendations" => [
            "Check if carriers table exists",
            "Run: tms_api/database-schema/carrier_admin_schema_simple.sql",
            "Verify database connection settings in configurations/database-connection.php"
        ]
    ], JSON_PRETTY_PRINT);
}
?>
