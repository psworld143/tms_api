<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

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
    // This endpoint is tenant-scoped; Super Admin users are not returned
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $role = isset($_GET['role']) ? $_GET['role'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;

    $where = ["role <> 'Super Admin'"]; // exclude SA by default
    $params = [];

    if ($status !== null) { $where[] = 'status = :status'; $params[':status'] = $status; }
    if ($role !== null) { $where[] = 'role = :role'; $params[':role'] = $role; }
    if ($search !== null) { $where[] = '(name LIKE :search OR email LIKE :search)'; $params[':search'] = "%$search%"; }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("SELECT id, name, email, role, status, created_at, updated_at FROM users $whereClause ORDER BY created_at DESC");
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'message' => 'Users retrieved successfully',
        'count' => count($users),
        'data' => $users,
    ], JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()], JSON_PRETTY_PRINT);
}
?>

