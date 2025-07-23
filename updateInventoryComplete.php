<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to change the inventory table upon submission of previous form
 *
 * PHP version 8
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Change_Files
 * @package   None
 * @author    Danielle Lawton <daniellelawton8@gmail.com>
 * @copyright 1999 - 2019 The PHP Group
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      https://pear.php.net/package/None
 */

// phpcs:disable Generic.Files.LineLength.TooLong

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

// Get submitted data
$selectedBattery = $_POST['selectedBattery'] ?? null;
$amountUsed = $_POST['amount_Used'] ?? [];
$serialNumber = $_POST['serialNumber'] ?? null;

// Add Serial Number into Battery Database
if ($serialNumber) {
    $sqlBattery = "INSERT INTO dbo.All_Batteries (SN, BatteryType, Status) VALUES (?,?,?)";
    $stmtBattery = sqlsrv_query($conn, $sqlBattery, [$serialNumber, $selectedBattery, "IN-HOUSE"]);
}

// Get all parts for this battery
$sql = "SELECT * FROM dbo.PartsForBatteries WHERE Name = ?";
$stmt = sqlsrv_query($conn, $sql, [$selectedBattery]);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

while ($item = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $id = $item['Id'];
    $batteryId = $item['BatteryId'];
    $pn = $item['PN'];
    $amount = $item['Amount'];
    $amountUsed = $amountUsed[$id];

    $invCheck = sqlsrv_query($conn, "SELECT Amount from dbo.Inventory WHERE PN = ?", [$pn]);
    if ($row = sqlsrv_fetch_array($invCheck, SQLSRV_FETCH_ASSOC)) {
        $newQty = $row['Amount'] - $amountUsed;
        sqlsrv_query($conn, "UPDATE dbo.Inventory SET Amount = ? WHERE PN = ?", [$newQty, $pn]);
    } else {
        sqlsrv_query($conn, "INSERT INTO dbo.Inventory (PN, Amount) VALUES (?, ?)", [$pn, 0]);
    }
}

$extraPartNumbers = $_POST['extra_partNumber'] ?? [];
$extraDescriptions = $_POST['extra_description'] ?? [];
$extraAmounts = $_POST['extra_amountUsed'] ?? [];

foreach ($extraPartNumbers as $i => $pn) {
    $desc = $extraDescriptions[$i] ?? '';
    $amt = $extraAmounts[$i] ?? 0;
    // Process as needed, e.g., update inventory, log usage, etc.
    $invCheck = sqlsrv_query($conn, "SELECT Amount from dbo.Inventory WHERE PN = ?", [$pn]);
    if ($row = sqlsrv_fetch_array($invCheck, SQLSRV_FETCH_ASSOC)) {
        $newQty = $row['Amount'] - $amt;
        sqlsrv_query($conn, "UPDATE dbo.Inventory SET Amount = ? WHERE PN = ?", [$newQty, $pn]);
    } else {
        sqlsrv_query($conn, "INSERT INTO dbo.Inventory (PN, Amount) VALUES (?, ?)", [$pn, 0]);
    }
}

echo "Parts processed and inventory updated!";
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
