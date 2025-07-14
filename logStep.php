<?php

/**
 * File to process changes and send them to logging table
 */

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Load environment variables (from .env)
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection parameters
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
$partNumbers = $_POST['partNumber'] ?? [];
$descriptions = $_POST['description'] ?? [];
$amountsUsed = $_POST['amountUsed'] ?? [];

foreach ($partNumbers as $i => $pn) {
    $desc = $descriptions[$i] ?? '';
    $amt = $amountsUsed[$i] ?? 0;
    // Process as needed, e.g., update inventory, log usage, etc.
    $invCheck = sqlsrv_query($conn, "SELECT Amount from dbo.Inventory WHERE PN = ?", [$pn]);
    if ($row = sqlsrv_fetch_array($invCheck, SQLSRV_FETCH_ASSOC)) {
        $newQty = $row['Amount'] - $amt;
        sqlsrv_query($conn, "UPDATE dbo.Inventory SET Amount = ? WHERE PN = ?", [$newQty, $pn]);
    } else {
        sqlsrv_query($conn, "INSERT INTO dbo.Inventory (PN, Amount) VALUES (?, ?)", [$pn, 0]);
    }
}
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