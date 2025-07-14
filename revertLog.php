<?php

/**
 * File to revert a change from the log
 */

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$serverName = $_ENV['DB_HOST'];
$dbUser = $_ENV['DB_USER'];
$databaseName = $_ENV['DB_DATABASE'];
$dbPassword = $_ENV['DB_PASSWORD'];

$connectionOptions = [
    "Database" => (string)$databaseName,
    "Uid" => (string)$dbUser,
    "PWD" => (string)$dbPassword,
    "Encrypt" => false,
    "TrustServerCertificate" => true,
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

$logId = $_POST['logId'] ?? null;
if (!$logId) {
    die("No log ID provided.");
}

// Fetch the log entry
$sql = "SELECT * FROM dbo.InventoryLog WHERE LogId = ?";
$stmt = sqlsrv_query($conn, $sql, [$logId]);
$log = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$log) {
    die("Log entry not found.");
}

// Example: revert logic for 'add', 'delete', 'update'
switch ($log['TableAffected']) {
    case 'Inventory':
        switch ($log['ActionType']) {
            case 'add':
                sqlsrv_query($conn, "DELETE FROM dbo.Inventory WHERE PN = ?", [$log['ProductNumber']]);
                break;
            case 'delete':
                sqlsrv_query($conn, "INSERT INTO dbo.Inventory (PN, Details, Amount) VALUES (?, ?, ?)", [
                    $log['ProductNumber'],
                    $log['Description'],
                    $log['Quantity']
                ]);
                break;
            case 'update':
                // You would need to store the previous value in the log to revert
                break;
        }
        break;
    case 'Repairs':
        switch ($log['ActionType']) {
            case 'add':
                // Remove the added repair
                sqlsrv_query($conn, "DELETE FROM dbo.Repairs WHERE SerialNumber = ?", [$log['RepairSerialNumber']]);
                break;
            case 'delete':
                // Re-add the deleted repair (add more fields as needed)
                sqlsrv_query($conn, "INSERT INTO dbo.Repairs (SerialNumber, Requester, Details, Status) VALUES (?, ?, ?, ?)", [
                    $log['RepairSerialNumber'],
                    $log['RepairRequester'],
                    $log['RepairDetails'],
                    $log['RepairStatus']
                ]);
                break;
            case 'update':
                // Optionally revert status/details if you log previous values
                break;
        }
        break;
}

// Mark this log entry as reverted
sqlsrv_query($conn, "UPDATE dbo.InventoryLog SET Reverted = 1 WHERE LogId = ?", [$logId]);

header("Location: getLog.php");
exit;
?>