<?php
// Admin Database Connection for Database Management Operations
// This connection has CREATE DATABASE, DROP DATABASE privileges
// Use ONLY for database setup operations, not for regular queries

$servername = "localhost";
$admin_username = "root"; // Use root or admin user with CREATE DATABASE privilege
$admin_password = ""; // Set your MySQL root password here
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
    die(json_encode([
        "status" => "error",
        "message" => "Admin connection failed: " . $e->getMessage(),
        "help" => "Please set up admin credentials in configurations/admin-database-connection.php"
    ]));
}
?>

