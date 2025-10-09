<?php
$servername = "localhost";
$username = "pms_nexus_tms";
$password = "020894TMS25";
$dbname = "pms_nexus_tms";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Optional: comment this out in production
    // echo "Connected successfully";

    // Optional tenant DB override: allow requests to target a carrier's dedicated DB
    // Pass either header 'X-Carrier-DB' or query 'database_name=tms_<carrier>'
    $requestedDb = null;
    if (!empty($_SERVER['HTTP_X_CARRIER_DB'])) {
        $requestedDb = trim($_SERVER['HTTP_X_CARRIER_DB']);
    } elseif (isset($_GET['database_name']) && !empty($_GET['database_name'])) {
        $requestedDb = trim($_GET['database_name']);
    }

    if ($requestedDb && $requestedDb !== $dbname) {
        // Look up credentials from main DB mapping
        try {
            $stmt = $pdo->prepare("SELECT database_name, db_host, db_username, db_password FROM carrier_databases WHERE database_name = :db LIMIT 1");
            $stmt->execute([':db' => $requestedDb]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $tenantDb = $row['database_name'];
                $tenantHost = $row['db_host'] ?? $servername;
                $tenantUser = $row['db_username'];
                $tenantPass = $row['db_password'];

                $tenantPdo = new PDO("mysql:host=$tenantHost;dbname=$tenantDb;charset=utf8", $tenantUser, $tenantPass);
                $tenantPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $tenantPdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                // Swap connection to tenant DB
                $pdo = $tenantPdo;
            }
        } catch (Exception $e) {
            // If lookup/connect fails, silently continue with main DB
        }
    }

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
