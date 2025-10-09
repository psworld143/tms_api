<?php
// Admin Database Connection for Database Management Operations
// This connection needs CREATE DATABASE, DROP DATABASE privileges
// Use ONLY for database setup operations, not for regular queries

$servername = "localhost";

// Try to use the same credentials as regular connection
// If the user has CREATE DATABASE privileges, this will work
// Otherwise, you need to grant privileges or use root
$admin_username = "pms_nexus_tms";
$admin_password = "020894TMS25";
$dbname = "pms_nexus_tms"; // Source database to clone from

try {
    // Connect without specifying database for CREATE DATABASE operations
    $admin_pdo = new PDO("mysql:host=$servername;charset=utf8", $admin_username, $admin_password);
    $admin_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $admin_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Also create connection to source database for reading tables
    $source_pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $admin_username, $admin_password);
    $source_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $source_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode([
        "status" => "error",
        "message" => "Admin connection failed: " . $e->getMessage(),
        "help" => "Please grant CREATE DATABASE privilege to user '{$admin_username}' or update admin credentials in configurations/admin-database-connection.php",
        "sql_command" => "GRANT CREATE, DROP ON *.* TO '{$admin_username}'@'localhost'; FLUSH PRIVILEGES;"
    ]));
}
?>

