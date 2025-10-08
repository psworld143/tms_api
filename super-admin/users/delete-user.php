<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../../configurations/database-connection.php';

// Get user ID from query parameter or POST data
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['id'])) {
        $userId = (int)$data['id'];
    }
}

if ($userId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Valid user ID is required"
    ]);
    exit;
}

try {
    // Check if user exists
    $checkSql = "SELECT id, name, email, role FROM users WHERE id = :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':id' => $userId]);
    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            "status" => "error",
            "message" => "User not found"
        ]);
        exit;
    }

    // Prevent deletion of Super Admin (optional safety check)
    if ($user['role'] === 'Super Admin') {
        // Count total Super Admins
        $countSql = "SELECT COUNT(*) as count FROM users WHERE role = 'Super Admin'";
        $countStmt = $pdo->query($countSql);
        $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count <= 1) {
            echo json_encode([
                "status" => "error",
                "message" => "Cannot delete the last Super Admin user"
            ]);
            exit;
        }
    }

    // Delete the user
    $deleteSql = "DELETE FROM users WHERE id = :id";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([':id' => $userId]);

    echo json_encode([
        "status" => "success",
        "message" => "User deleted successfully",
        "data" => [
            "id" => $user['id'],
            "name" => $user['name'],
            "email" => $user['email']
        ]
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
