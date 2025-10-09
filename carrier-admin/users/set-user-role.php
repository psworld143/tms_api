<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method Not Allowed']); exit; }

// Build PDO connection from provided DB connection parameters (no fallback)
$raw = file_get_contents('php://input');
$json = $raw ? json_decode($raw, true) : [];
$input = array_merge($_GET ?? [], $_POST ?? [], is_array($json) ? $json : []);

$dbHost = isset($input['db_host']) && $input['db_host'] !== '' ? $input['db_host'] : 'localhost';
$dbName = $input['db_name'] ?? $input['database_name'] ?? null;
$dbUser = $input['db_user'] ?? $input['db_username'] ?? null;
$dbPass = $input['db_pass'] ?? $input['db_password'] ?? null;

if (!$dbName || !$dbUser || $dbPass === null) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing database connection parameters. Required: db_name, db_user, db_pass (db_host optional)'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Connection failed: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['user_id']) || !isset($input['role'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'user_id and role are required']);
        exit;
    }

    $userId = (int)$input['user_id'];
    $role = trim($input['role']);

    // Prevent setting Super Admin via this endpoint
    if (strtolower($role) === 'super admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Super Admin role is not allowed']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET role = :role, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':role' => $role, ':id' => $userId]);

    echo json_encode(['status' => 'success', 'message' => 'User role updated successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

