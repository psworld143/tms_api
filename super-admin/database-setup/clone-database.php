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

// Use admin connection for database creation operations
require __DIR__ . '/../../configurations/admin-database-connection.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "message" => "Only POST requests are allowed"
    ]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['carrier_name']) || empty(trim($input['carrier_name']))) {
        echo json_encode([
            "status" => "error",
            "message" => "Carrier name is required"
        ]);
        exit;
    }
    
    $carrierName = trim($input['carrier_name']);
    $carrierId = isset($input['carrier_id']) ? intval($input['carrier_id']) : null; // Optional; used to copy users
    
    // Sanitize carrier name for database name
    // Convert to lowercase, replace spaces and special characters with underscores
    $sanitizedName = strtolower($carrierName);
    $sanitizedName = preg_replace('/[^a-z0-9_]/', '_', $sanitizedName);
    $sanitizedName = preg_replace('/_+/', '_', $sanitizedName); // Remove duplicate underscores
    $sanitizedName = trim($sanitizedName, '_'); // Remove leading/trailing underscores
    
    // Create new database name
    $newDatabaseName = 'tms_' . $sanitizedName;
    
    // Validate database name length (MySQL limit is 64 characters)
    if (strlen($newDatabaseName) > 64) {
        echo json_encode([
            "status" => "error",
            "message" => "Database name too long (max 64 characters). Please use a shorter carrier name."
        ]);
        exit;
    }
    
    // Get source database name from connection
    $sourceDatabaseName = $dbname;
    
    // Step 1: Check if database already exists
    $checkDbQuery = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :dbname";
    $checkStmt = $admin_pdo->prepare($checkDbQuery);
    $checkStmt->execute([':dbname' => $newDatabaseName]);
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Database '{$newDatabaseName}' already exists. Please use a different carrier name or delete the existing database first."
        ]);
        exit;
    }
    
    // Step 2: Create new database
    $createDbQuery = "CREATE DATABASE `{$newDatabaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $admin_pdo->exec($createDbQuery);
    
    // Step 3: Get all tables from source database
    $getTablesQuery = "SELECT TABLE_NAME 
                       FROM INFORMATION_SCHEMA.TABLES 
                       WHERE TABLE_SCHEMA = :dbname 
                       AND TABLE_TYPE = 'BASE TABLE'
                       ORDER BY TABLE_NAME";
    
    $tablesStmt = $source_pdo->prepare($getTablesQuery);
    $tablesStmt->execute([':dbname' => $sourceDatabaseName]);
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo json_encode([
            "status" => "error",
            "message" => "No tables found in source database '{$sourceDatabaseName}'"
        ]);
        exit;
    }
    
    // Step 4: Clone each table structure
    $clonedTables = [];
    $errors = [];
    
    foreach ($tables as $tableName) {
        try {
            // Get CREATE TABLE statement
            $showCreateStmt = $source_pdo->query("SHOW CREATE TABLE `{$sourceDatabaseName}`.`{$tableName}`");
            $createTableRow = $showCreateStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$createTableRow) {
                $errors[] = "Could not get CREATE statement for table: {$tableName}";
                continue;
            }
            
            $createTableSQL = $createTableRow['Create Table'];
            
            // Modify the CREATE TABLE statement to use the new database
            $createTableSQL = str_replace(
                "CREATE TABLE `{$tableName}`",
                "CREATE TABLE `{$newDatabaseName}`.`{$tableName}`",
                $createTableSQL
            );
            
            // Execute CREATE TABLE in new database
            $admin_pdo->exec($createTableSQL);
            $clonedTables[] = $tableName;
            
        } catch (PDOException $e) {
            $errors[] = "Error cloning table '{$tableName}': " . $e->getMessage();
        }
    }
    
    // Step 5: Clone views if any
    $getViewsQuery = "SELECT TABLE_NAME 
                      FROM INFORMATION_SCHEMA.TABLES 
                      WHERE TABLE_SCHEMA = :dbname 
                      AND TABLE_TYPE = 'VIEW'
                      ORDER BY TABLE_NAME";
    
    $viewsStmt = $source_pdo->prepare($getViewsQuery);
    $viewsStmt->execute([':dbname' => $sourceDatabaseName]);
    $views = $viewsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $clonedViews = [];
    
    foreach ($views as $viewName) {
        try {
            // Get CREATE VIEW statement
            $showCreateStmt = $source_pdo->query("SHOW CREATE VIEW `{$sourceDatabaseName}`.`{$viewName}`");
            $createViewRow = $showCreateStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($createViewRow) {
                $createViewSQL = $createViewRow['Create View'];
                
                // Modify to use new database
                $createViewSQL = preg_replace(
                    "/CREATE.*?VIEW `{$viewName}`/",
                    "CREATE VIEW `{$newDatabaseName}`.`{$viewName}`",
                    $createViewSQL
                );
                
                // Update any references to old database
                $createViewSQL = str_replace("`{$sourceDatabaseName}`.", "`{$newDatabaseName}`.", $createViewSQL);
                
                $admin_pdo->exec($createViewSQL);
                $clonedViews[] = $viewName;
            }
        } catch (PDOException $e) {
            $errors[] = "Error cloning view '{$viewName}': " . $e->getMessage();
        }
    }
    
    // Step 6: Create database configuration file for the new carrier database
    $configContent = "<?php
// Database configuration for carrier: {$carrierName}
// Auto-generated on " . date('Y-m-d H:i:s') . "

\$servername = \"localhost\";
\$username = \"{$admin_username}\";
\$password = \"{$admin_password}\";
\$dbname = \"{$newDatabaseName}\";

try {
    \$pdo = new PDO(\"mysql:host=\$servername;dbname=\$dbname;charset=utf8mb4\", \$username, \$password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException \$e) {
    die(\"Connection failed: \" . \$e->getMessage());
}
?>";
    
    // Save configuration file
    $configDir = __DIR__ . '/../../configurations/carriers/';
    if (!file_exists($configDir)) {
        mkdir($configDir, 0755, true);
    }
    
    $configFile = $configDir . "{$sanitizedName}-db-config.php";
    file_put_contents($configFile, $configContent);
    
    // Optional Step 7: If carrier_id provided, copy assigned users into the new database
    $copiedUsers = 0;
    $copyWarnings = [];
    if (!empty($carrierId)) {
        try {
            // Ensure destination users table exists
            $destUsersCheck = $admin_pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'users'");
            $destUsersCheck->execute([':db' => $newDatabaseName]);
            $destUsersExists = $destUsersCheck->fetchColumn() > 0;

            if ($destUsersExists) {
                // Get destination users table columns
                $destColsStmt = $admin_pdo->query("SHOW COLUMNS FROM `{$newDatabaseName}`.`users`");
                $destColumns = $destColsStmt->fetchAll(PDO::FETCH_COLUMN);

                // Fetch assigned users from source database (admin DB)
                $assignedUsersSql = "SELECT 
                        u.id as source_user_id,
                        u.name,
                        u.email,
                        u.password,
                        COALESCE(cua.role_in_carrier, u.role) AS role,
                        u.status,
                        u.phone,
                        u.department,
                        u.location,
                        u.notes
                    FROM carrier_user_assignments cua
                    JOIN users u ON u.id = cua.user_id
                    WHERE cua.carrier_id = :carrier_id
                      AND cua.status = 'active'";
                $assignedStmt = $source_pdo->prepare($assignedUsersSql);
                $assignedStmt->execute([':carrier_id' => $carrierId]);
                $assignedUsers = $assignedStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($assignedUsers)) {
                    foreach ($assignedUsers as $userRow) {
                        // Build dynamic column/value lists based on destination columns
                        $insertableColumns = [];
                        $placeholders = [];
                        $params = [];

                        // Common fields we try to insert if present
                        $fieldMap = [
                            'name' => $userRow['name'] ?? null,
                            'email' => $userRow['email'] ?? null,
                            'password' => $userRow['password'] ?? null,
                            'role' => $userRow['role'] ?? null,
                            'status' => $userRow['status'] ?? 'active',
                            'phone' => $userRow['phone'] ?? null,
                            'department' => $userRow['department'] ?? null,
                            'location' => $userRow['location'] ?? null,
                            'notes' => $userRow['notes'] ?? null,
                        ];

                        foreach ($fieldMap as $col => $val) {
                            if (in_array($col, $destColumns, true) && $val !== null) {
                                $insertableColumns[] = "`$col`";
                                $placeholders[] = ":$col";
                                $params[":$col"] = $val;
                            }
                        }

                        if (!empty($insertableColumns)) {
                            $insertSql = "INSERT INTO `{$newDatabaseName}`.`users` (" . implode(', ', $insertableColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                            $insStmt = $admin_pdo->prepare($insertSql);
                            try {
                                $insStmt->execute($params);
                                $copiedUsers++;
                            } catch (PDOException $e) {
                                $copyWarnings[] = "Failed to copy user '{$userRow['email']}' to '{$newDatabaseName}': " . $e->getMessage();
                            }
                        }
                    }
                }
            } else {
                $copyWarnings[] = "Destination users table not found in '{$newDatabaseName}'. Skipping user copy.";
            }
        } catch (Exception $e) {
            $copyWarnings[] = "Unexpected error while copying users: " . $e->getMessage();
        }
    }

    // Step 8: Persist database credentials mapping in main database
    try {
        // Ensure metadata table exists in main DB
        $createMetaTableSql = "CREATE TABLE IF NOT EXISTS `{$sourceDatabaseName}`.`carrier_databases` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `carrier_id` INT NULL,
            `carrier_name` VARCHAR(255) NOT NULL,
            `database_name` VARCHAR(128) NOT NULL,
            `db_host` VARCHAR(128) NOT NULL DEFAULT 'localhost',
            `db_username` VARCHAR(128) NOT NULL,
            `db_password` VARCHAR(256) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_carrier` (`carrier_id`),
            UNIQUE KEY `uniq_database_name` (`database_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $admin_pdo->exec($createMetaTableSql);

        // Insert or update credentials
        $upsertSql = "INSERT INTO `{$sourceDatabaseName}`.`carrier_databases`
            (carrier_id, carrier_name, database_name, db_host, db_username, db_password)
            VALUES (:carrier_id, :carrier_name, :database_name, :db_host, :db_username, :db_password)
            ON DUPLICATE KEY UPDATE 
                carrier_name = VALUES(carrier_name),
                db_host = VALUES(db_host),
                db_username = VALUES(db_username),
                db_password = VALUES(db_password)";
        $upsertStmt = $admin_pdo->prepare($upsertSql);
        $upsertStmt->execute([
            ':carrier_id' => $carrierId,
            ':carrier_name' => $carrierName,
            ':database_name' => $newDatabaseName,
            ':db_host' => $servername,
            ':db_username' => $admin_username,
            ':db_password' => $admin_password
        ]);
    } catch (Exception $e) {
        $copyWarnings[] = "Failed to persist carrier DB credentials: " . $e->getMessage();
    }

    // Prepare success response
    $response = [
        "status" => "success",
        "message" => "Database cloned successfully",
        "data" => [
            "carrier_name" => $carrierName,
            "source_database" => $sourceDatabaseName,
            "new_database" => $newDatabaseName,
            "tables_cloned" => count($clonedTables),
            "views_cloned" => count($clonedViews),
            "tables_list" => $clonedTables,
            "views_list" => $clonedViews,
            "config_file" => $configFile,
            "created_at" => date('Y-m-d H:i:s'),
            "users_copied" => $copiedUsers
        ]
    ];
    
    // Add errors if any (partial success)
    if (!empty($errors)) {
        $response["warnings"] = $errors;
        $response["message"] .= " (with some warnings)";
    }

    // Add copy warnings if any
    if (!empty($copyWarnings)) {
        if (!isset($response["warnings"])) {
            $response["warnings"] = [];
        }
        $response["warnings"] = array_merge($response["warnings"], $copyWarnings);
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    // Database error
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage(),
        "error_code" => $e->getCode()
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    // General error
    echo json_encode([
        "status" => "error",
        "message" => "Error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

