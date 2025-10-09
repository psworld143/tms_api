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
    // Build query with optional filters
    $whereClauses = [];
    $params = [];
    
    // Filter by carrier
    if (isset($_GET['carrier_id']) && !empty($_GET['carrier_id'])) {
        $whereClauses[] = "cua.carrier_id = :carrier_id";
        $params[':carrier_id'] = intval($_GET['carrier_id']);
    }
    
    // Filter by user
    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $whereClauses[] = "cua.user_id = :user_id";
        $params[':user_id'] = intval($_GET['user_id']);
    }
    
    // Filter by status
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $whereClauses[] = "cua.status = :status";
        $params[':status'] = $_GET['status'];
    }
    
    // Filter by role
    if (isset($_GET['role']) && !empty($_GET['role'])) {
        $whereClauses[] = "cua.role_in_carrier = :role";
        $params[':role'] = $_GET['role'];
    }
    
    // Filter by primary contact
    if (isset($_GET['is_primary_contact'])) {
        $whereClauses[] = "cua.is_primary_contact = :is_primary_contact";
        $params[':is_primary_contact'] = (bool)$_GET['is_primary_contact'];
    }
    
    // Build WHERE clause
    $whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
    
    // Pagination
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = ($page - 1) * $limit;
    
    // Sorting
    $allowedSortFields = ['carrier_name', 'user_name', 'role_in_carrier', 'assignment_date', 'created_at'];
    $sortField = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowedSortFields) ? $_GET['sort_by'] : 'created_at';
    $sortOrder = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC';
    
    // Handle sorting by carrier/user name
    if ($sortField === 'carrier_name') {
        $sortField = 'c.company_name';
    } elseif ($sortField === 'user_name') {
        $sortField = 'u.name';
    } else {
        $sortField = "cua.$sortField";
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total 
                 FROM carrier_user_assignments cua 
                 JOIN carriers c ON cua.carrier_id = c.id
                 JOIN users u ON cua.user_id = u.id
                 $whereSQL";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get assignments with details
    $sql = "SELECT 
                cua.*,
                c.company_name as carrier_name,
                c.carrier_code,
                c.carrier_type,
                c.account_status as carrier_status,
                u.name as user_name,
                u.first_name,
                u.last_name,
                u.email as user_email,
                u.phone as user_phone,
                u.role as user_role,
                u.status as user_status,
                ab.name as assigned_by_name
            FROM carrier_user_assignments cua
            JOIN carriers c ON cua.carrier_id = c.id
            JOIN users u ON cua.user_id = u.id
            LEFT JOIN users ab ON cua.assigned_by = ab.id
            $whereSQL
            ORDER BY $sortField $sortOrder
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind pagination parameters
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    // Bind filter parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination metadata
    $totalPages = ceil($totalCount / $limit);
    
    echo json_encode([
        "status" => "success",
        "message" => "Carrier assignments retrieved successfully",
        "data" => $assignments,
        "pagination" => [
            "current_page" => $page,
            "per_page" => $limit,
            "total_records" => $totalCount,
            "total_pages" => $totalPages
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

