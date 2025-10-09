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
    // Validate carrier_id
    if (!isset($_GET['carrier_id']) || empty($_GET['carrier_id'])) {
        echo json_encode([
            "status" => "error",
            "message" => "carrier_id is required"
        ]);
        exit;
    }
    
    $carrierId = intval($_GET['carrier_id']);
    
    // Check if carrier exists
    $carrierCheck = $pdo->prepare("SELECT id, company_name, carrier_code FROM carriers WHERE id = :carrier_id");
    $carrierCheck->execute([':carrier_id' => $carrierId]);
    $carrier = $carrierCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$carrier) {
        echo json_encode([
            "status" => "error",
            "message" => "Carrier not found"
        ]);
        exit;
    }
    
    // Get all users assigned to this carrier
    $sql = "SELECT 
                cua.id as assignment_id,
                cua.role_in_carrier,
                cua.is_primary_contact,
                cua.department,
                cua.can_manage_loads,
                cua.can_manage_drivers,
                cua.can_view_reports,
                cua.can_manage_billing,
                cua.status as assignment_status,
                cua.assignment_date,
                cua.start_date,
                cua.end_date,
                cua.notes,
                cua.created_at as assigned_at,
                u.id as user_id,
                u.name as user_name,
                u.email,
                u.phone,
                u.role as user_role,
                u.status as user_status,
                ab.name as assigned_by_name
            FROM carrier_user_assignments cua
            JOIN users u ON cua.user_id = u.id
            LEFT JOIN users ab ON cua.assigned_by = ab.id
            WHERE cua.carrier_id = :carrier_id
            ORDER BY cua.is_primary_contact DESC, u.name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':carrier_id' => $carrierId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats = [
        'total_users' => count($users),
        'active_users' => count(array_filter($users, fn($u) => $u['assignment_status'] === 'active')),
        'primary_contacts' => count(array_filter($users, fn($u) => $u['is_primary_contact'] == 1)),
        'roles' => []
    ];
    
    // Count by role
    foreach ($users as $user) {
        $role = $user['role_in_carrier'];
        if (!isset($stats['roles'][$role])) {
            $stats['roles'][$role] = 0;
        }
        $stats['roles'][$role]++;
    }
    
    echo json_encode([
        "status" => "success",
        "message" => "Carrier users retrieved successfully",
        "carrier" => $carrier,
        "data" => $users,
        "statistics" => $stats
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

