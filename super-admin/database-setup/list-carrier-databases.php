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

// Use admin connection for database listing operations
require __DIR__ . '/../../configurations/admin-database-connection.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        "status" => "error",
        "message" => "Only GET requests are allowed"
    ]);
    exit;
}

try {
    // Get all databases that start with 'tms_'
    $query = "SELECT SCHEMA_NAME, 
                     DEFAULT_CHARACTER_SET_NAME,
                     DEFAULT_COLLATION_NAME
              FROM INFORMATION_SCHEMA.SCHEMATA 
              WHERE SCHEMA_NAME LIKE 'tms_%'
              AND SCHEMA_NAME != :main_db
              ORDER BY SCHEMA_NAME";
    
    $stmt = $admin_pdo->prepare($query);
    $stmt->execute([':main_db' => $dbname]);
    $databases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $carrierDatabases = [];
    
    foreach ($databases as $db) {
        $dbName = $db['SCHEMA_NAME'];
        
        // Extract carrier name from database name
        $carrierName = str_replace('tms_', '', $dbName);
        $carrierName = str_replace('_', ' ', $carrierName);
        $carrierName = ucwords($carrierName);
        
        // Get table count
        $tableCountQuery = "SELECT COUNT(*) as table_count
                           FROM INFORMATION_SCHEMA.TABLES 
                           WHERE TABLE_SCHEMA = :dbname
                           AND TABLE_TYPE = 'BASE TABLE'";
        
        $tableStmt = $admin_pdo->prepare($tableCountQuery);
        $tableStmt->execute([':dbname' => $dbName]);
        $tableCount = $tableStmt->fetch(PDO::FETCH_ASSOC)['table_count'];
        
        // Get database size
        $sizeQuery = "SELECT 
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                      FROM INFORMATION_SCHEMA.TABLES 
                      WHERE TABLE_SCHEMA = :dbname";
        
        $sizeStmt = $admin_pdo->prepare($sizeQuery);
        $sizeStmt->execute([':dbname' => $dbName]);
        $sizeData = $sizeStmt->fetch(PDO::FETCH_ASSOC);
        $sizeMb = $sizeData['size_mb'] ?? 0;
        
        // Check if config file exists
        $configFile = __DIR__ . '/../../configurations/carriers/' . str_replace('tms_', '', $dbName) . '-db-config.php';
        $hasConfigFile = file_exists($configFile);
        
        $carrierDatabases[] = [
            'database_name' => $dbName,
            'carrier_name' => $carrierName,
            'table_count' => (int)$tableCount,
            'size_mb' => (float)$sizeMb,
            'charset' => $db['DEFAULT_CHARACTER_SET_NAME'],
            'collation' => $db['DEFAULT_COLLATION_NAME'],
            'has_config_file' => $hasConfigFile,
            'config_file' => $hasConfigFile ? $configFile : null
        ];
    }
    
    echo json_encode([
        "status" => "success",
        "message" => "Carrier databases retrieved successfully",
        "data" => [
            "main_database" => $dbname,
            "carrier_count" => count($carrierDatabases),
            "carriers" => $carrierDatabases
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

