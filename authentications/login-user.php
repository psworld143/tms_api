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

require __DIR__ . '/../configurations/database-connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Email and password are required."
    ]);
    exit;
}

$email = trim($data['email']);
$password = md5(trim($data['password']));

try {
    $sql = "SELECT id, name, email, role, status FROM users WHERE email = :email AND password = :password LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email, ':password' => $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // If Carrier Admin, include dedicated database config if available
        $dbConfig = null;
        try {
            // Attempt to determine carrier_id for this user
            $carrierId = null;
            $carrierIdQuery = "SELECT carrier_id FROM carrier_user_assignments WHERE user_id = :uid AND status = 'active' LIMIT 1";
            $cidStmt = $pdo->prepare($carrierIdQuery);
            $cidStmt->execute([':uid' => $user['id']]);
            $cidRow = $cidStmt->fetch(PDO::FETCH_ASSOC);
            if ($cidRow && isset($cidRow['carrier_id'])) {
                $carrierId = (int)$cidRow['carrier_id'];
            }

            if ($carrierId !== null) {
                // Look up dedicated DB credentials from carrier_databases
                $dbMetaQuery = "SELECT database_name, db_username, db_password FROM carrier_databases WHERE carrier_id = :carrier_id LIMIT 1";
                $dbStmt = $pdo->prepare($dbMetaQuery);
                $dbStmt->execute([':carrier_id' => $carrierId]);
                $dbMeta = $dbStmt->fetch(PDO::FETCH_ASSOC);
                if ($dbMeta) {
                    $dbConfig = [
                        'database_name' => $dbMeta['database_name'],
                        'db_username' => $dbMeta['db_username'],
                        'db_password' => $dbMeta['db_password'],
                    ];
                }
            }
        } catch (Exception $e) {
            // Ignore db config errors to not block auth
        }

        echo json_encode([
            "status" => "success",
            "message" => "Login successful.",
            "user" => $user,
            "carrier_db" => $dbConfig
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid email or password."
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
