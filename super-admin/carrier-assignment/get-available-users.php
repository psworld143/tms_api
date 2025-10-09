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

try {
    // Get carrier_id if provided (to exclude users already assigned to this carrier)
    $carrierId = isset($_GET['carrier_id']) ? intval($_GET['carrier_id']) : null;
    
    // Check if carrier_user_assignments table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'carrier_user_assignments'");
    $tableExists = $tableCheck->rowCount() > 0;
    
    // Get users that are not assigned to the specified carrier (or all users if no carrier specified)
    if ($carrierId) {
        if ($tableExists) {
            // Table exists, exclude already assigned users
            // Only return Carrier Admin users for assignment
            $sql = "SELECT 
                        u.id,
                        u.name,
                        u.email,
                        u.phone,
                        u.role,
                        u.status,
                        u.created_at
                    FROM users u
                    WHERE u.id NOT IN (
                        SELECT user_id 
                        FROM carrier_user_assignments 
                        WHERE carrier_id = :carrier_id
                    )
                    AND u.status = 'active'
                    AND u.role = 'Carrier Admin'
                    ORDER BY u.name ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':carrier_id' => $carrierId]);
        } else {
            // Table doesn't exist yet, return Carrier Admin users only
            $sql = "SELECT 
                        u.id,
                        u.name,
                        u.email,
                        u.phone,
                        u.role,
                        u.status,
                        u.created_at
                    FROM users u
                    WHERE u.status = 'active'
                    AND u.role = 'Carrier Admin'
                    ORDER BY u.name ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        }
    } else {
        // Get all Carrier Admin users (when no carrier specified)
        if ($tableExists) {
            $sql = "SELECT 
                        u.id,
                        u.name,
                        u.email,
                        u.phone,
                        u.role,
                        u.status,
                        u.created_at,
                        (SELECT COUNT(*) FROM carrier_user_assignments WHERE user_id = u.id) as assigned_carriers_count
                    FROM users u
                    WHERE u.status = 'active'
                    AND u.role = 'Carrier Admin'
                    ORDER BY u.name ASC";
        } else {
            $sql = "SELECT 
                        u.id,
                        u.name,
                        u.email,
                        u.phone,
                        u.role,
                        u.status,
                        u.created_at
                    FROM users u
                    WHERE u.status = 'active'
                    AND u.role = 'Carrier Admin'
                    ORDER BY u.name ASC";
        }
        
        $stmt = $pdo->query($sql);
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "message" => "Available users retrieved successfully",
        "data" => $users,
        "count" => count($users),
        "table_exists" => $tableExists,
        "carrier_id" => $carrierId
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage(),
        "sql_error" => $e->getCode()
    ], JSON_PRETTY_PRINT);
}
?>

