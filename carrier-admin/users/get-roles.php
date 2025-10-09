<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Carrier-DB");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Allowed roles for Carrier Admin assignments
$roles = [
  'Carrier Admin',
  'Dispatcher',
  'Driver',
  'Accounting',
  'Safety Officer',
  'Billing',
  'Manager',
  'User'
];

echo json_encode([
  'status' => 'success',
  'message' => 'Roles retrieved successfully',
  'count' => count($roles),
  'data' => $roles
], JSON_PRETTY_PRINT);
?>

