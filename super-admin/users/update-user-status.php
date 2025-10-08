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

$data = json_decode(file_get_contents("php://input"), true);

// Get user ID and status
$userId = isset($data['id']) ? (int)$data['id'] : 0;
$status = isset($data['status']) ? trim($data['status']) : '';

if ($userId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Valid user ID is required"
    ]);
    exit;
}

// Validate status
$validStatuses = ['active', 'inactive', 'pending', 'suspended'];
if (!in_array($status, $validStatuses)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid status. Must be one of: " . implode(', ', $validStatuses)
    ]);
    exit;
}

try {
    // Check if user exists
    $checkSql = "SELECT id, name, email, role, status FROM users WHERE id = :id";
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

    $oldStatus = $user['status'];

    // Update status
    $updateSql = "UPDATE users SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        ':status' => $status,
        ':id' => $userId
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "User status updated from '{$oldStatus}' to '{$status}'",
        "data" => [
            "id" => $user['id'],
            "name" => $user['name'],
            "email" => $user['email'],
            "old_status" => $oldStatus,
            "new_status" => $status
        ]
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
