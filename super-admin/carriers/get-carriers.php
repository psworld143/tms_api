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

    // Status filter
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $whereClauses[] = "c.account_status = :status";
        $params[':status'] = $_GET['status'];
    }

    // Carrier type filter
    if (isset($_GET['carrier_type']) && !empty($_GET['carrier_type'])) {
        $whereClauses[] = "c.carrier_type = :carrier_type";
        $params[':carrier_type'] = $_GET['carrier_type'];
    }

    // Approved filter
    if (isset($_GET['is_approved'])) {
        $whereClauses[] = "c.is_approved = :is_approved";
        $params[':is_approved'] = (bool)$_GET['is_approved'];
    }

    // Preferred filter
    if (isset($_GET['is_preferred'])) {
        $whereClauses[] = "c.is_preferred = :is_preferred";
        $params[':is_preferred'] = (bool)$_GET['is_preferred'];
    }

    // Search filter (company name, carrier code, MC number, DOT number)
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $whereClauses[] = "(c.company_name LIKE :search OR c.carrier_code LIKE :search OR c.mc_number LIKE :search OR c.dot_number LIKE :search)";
        $params[':search'] = $search;
    }

    // Build WHERE clause
    $whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // Pagination
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = ($page - 1) * $limit;

    // Sorting
    $allowedSortFields = ['carrier_code', 'company_name', 'created_at', 'carrier_rating', 'account_status'];
    $sortField = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowedSortFields) ? $_GET['sort_by'] : 'created_at';
    $sortOrder = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC';

    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM carriers c $whereSQL";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get carriers with primary contact
    $sql = "SELECT 
                c.*,
                CONCAT(cc.first_name, ' ', cc.last_name) AS primary_contact_name,
                cc.email AS primary_contact_email,
                cc.phone AS primary_contact_phone,
                cc.title AS primary_contact_title,
                u.name AS created_by_name
            FROM carriers c
            LEFT JOIN carrier_contacts cc ON c.id = cc.carrier_id AND cc.is_primary = TRUE
            LEFT JOIN users u ON c.created_by = u.id
            $whereSQL
            ORDER BY c.$sortField $sortOrder
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
    $carriers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse JSON fields
    foreach ($carriers as &$carrier) {
        if ($carrier['service_types']) {
            $carrier['service_types'] = json_decode($carrier['service_types']);
        }
        if ($carrier['operating_regions']) {
            $carrier['operating_regions'] = json_decode($carrier['operating_regions']);
        }
    }

    // Calculate pagination metadata
    $totalPages = ceil($totalCount / $limit);

    echo json_encode([
        "status" => "success",
        "message" => "Carriers retrieved successfully",
        "data" => $carriers,
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
