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

// Use admin connection for database deletion operations
require __DIR__ . '/../../configurations/admin-database-connection.php';

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode([
        "status" => "error",
        "message" => "Only DELETE requests are allowed"
    ]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['database_name']) || empty(trim($input['database_name']))) {
        echo json_encode([
            "status" => "error",
            "message" => "Database name is required"
        ]);
        exit;
    }
    
    $databaseName = trim($input['database_name']);
    
    // Security check: Only allow deletion of databases starting with 'tms_'
    if (!preg_match('/^tms_[a-z0-9_]+$/', $databaseName)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid database name. Only carrier databases (tms_*) can be deleted."
        ]);
        exit;
    }
    
    // Prevent deletion of main database
    if ($databaseName === $dbname) {
        echo json_encode([
            "status" => "error",
            "message" => "Cannot delete the main TMS database"
        ]);
        exit;
    }
    
    // Check if database exists
    $checkQuery = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :dbname";
    $checkStmt = $admin_pdo->prepare($checkQuery);
    $checkStmt->execute([':dbname' => $databaseName]);
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Database '{$databaseName}' does not exist"
        ]);
        exit;
    }
    
    // Get database info before deletion
    $sizeQuery = "SELECT 
                    COUNT(*) as table_count,
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                  FROM INFORMATION_SCHEMA.TABLES 
                  WHERE TABLE_SCHEMA = :dbname";
    
    $sizeStmt = $admin_pdo->prepare($sizeQuery);
    $sizeStmt->execute([':dbname' => $databaseName]);
    $dbInfo = $sizeStmt->fetch(PDO::FETCH_ASSOC);
    
    // Drop the database
    $dropQuery = "DROP DATABASE `{$databaseName}`";
    $admin_pdo->exec($dropQuery);
    
    // Delete configuration file if it exists
    $carrierSlug = str_replace('tms_', '', $databaseName);
    $configFile = __DIR__ . '/../../configurations/carriers/' . $carrierSlug . '-db-config.php';
    $configDeleted = false;
    
    if (file_exists($configFile)) {
        unlink($configFile);
        $configDeleted = true;
    }
    
    echo json_encode([
        "status" => "success",
        "message" => "Carrier database deleted successfully",
        "data" => [
            "database_name" => $databaseName,
            "tables_deleted" => (int)$dbInfo['table_count'],
            "size_mb" => (float)$dbInfo['size_mb'],
            "config_file_deleted" => $configDeleted,
            "deleted_at" => date('Y-m-d H:i:s')
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage(),
        "error_code" => $e->getCode()
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

