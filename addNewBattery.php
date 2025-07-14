<?php

/////////////////////////////////////////////////
/* 
- This file contains the process of adding a new
battery template to the dbo.Battery table.

- Change this file if the columns of the tables in
the database change.
*/
/////////////////////////////////////////////////

require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection parameters
    // This stores all the server information in variables which are local to this specific file
$serverName = $_ENV['DB_HOST'];
$dbUser = $_ENV['DB_USER'];
$databaseName = $_ENV['DB_DATABASE'];
$dbPassword = $_ENV['DB_PASSWORD'];

// This establishes the login information as combined
$connectionOptions = [
    "Database" => (string)$databaseName,
    "Uid" => (string)$dbUser,
    "PWD" => (string)$dbPassword,
    "Encrypt" => false,
    "TrustServerCertificate" => true,
];

// Connect to the sql server using the server name and the combined login data
$conn = sqlsrv_connect($serverName, $connectionOptions);

// throws an error if the connection cannot be established
if ($conn === false) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

$newBatteryName = $_POST['newBatteryName'] ?? '';
$partNumbers = $_POST['partNumber'] ?? [];
$descriptions = $_POST['description'] ?? [];
$amountsUsed = $_POST['amountUsed'] ?? [];

$sql = "INSERT INTO dbo.Battery (Name, Amount) VALUES (?, ?)";
$params = [$newBatteryName, 0];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true)); }


foreach ($partNumbers as $i => $pn) {
    $sqlNum = "SELECT BatteryId FROM dbo.Battery WHERE Name = ?";
    $stmtNum = sqlsrv_query($conn, $sqlNum, [$newBatteryName]);
    if ($stmtNum === false) {
        echo json_encode(['error' => 'Query failed']);
        exit;
    }
    $batteryIdRow = sqlsrv_fetch_array($stmtNum, SQLSRV_FETCH_ASSOC);
    $batteryId = $batteryIdRow['BatteryId'] ?? null;
    if (!$batteryId) {
        echo "Could not find BatteryId for $newBatteryName";
        exit;
    }
    $desc = $descriptions[$i] ?? '';
    $amt = $amountsUsed[$i] ?? 0;
    $sqlPNs = "INSERT INTO dbo.PartsForBatteries (BatteryId, PN, Amount) VALUES (?, ?, ?)";
    $params = [$batteryId, $pn, $amt];
    $stmtIns = sqlsrv_query($conn, $sqlPNs, $params);

    if ($stmtIns === false) {
        die(print_r(sqlsrv_errors(), true));
    } else {
        echo "Record added successfully.<br>";
    }
}
    // frees up the $stmt variable and closes the connection to allow for additional statements and security for the server
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="styleCheckout.css">
    </head>
    <body>
        <div class="main-container">
            <div class="navigation">
                    <button onclick="location.href='checkoutCompletedBuilds.php'" class="btn btn-secondary">
                        Go Back
                    </button>
                    <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">
                        Report an Issue
                    </button>
            </div>
        </div>
    </body>
</html>