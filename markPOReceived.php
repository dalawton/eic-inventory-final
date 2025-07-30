<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to log receivement of ordered PO and the parts from the order
 * and update the inventory table respectively.
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
$poNum = $_POST['PO'] ?? null;
$receivedItems = $_POST['received_items'] ?? [];
$amountReceived = $_POST['amountReceived'] ?? [];

$sql = "SELECT * FROM dbo.POItems WHERE PONum = ?";
$stmt = sqlsrv_query($conn, $sql, [$poNum]);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

while ($item = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $itemID = $item['ItemID'];
    $pn = $item['PN'];
    $qty = $item['Quantity'];
    $received = isset($receivedItems) && in_array($itemID, $receivedItems);
    $amtInput = isset($amountReceived[$itemID]) ? intval($amountReceived[$itemID]) : 0;

    if ($received) {
        $amtToAdd = $qty;
        $remaining = 0;
    } elseif ($amtInput > 0 && $amtInput < $qty) {
        $amtToAdd = $amtInput;
        $remaining = $qty - $amtInput;
    } else {
        continue;
    }

    $invCheck = sqlsrv_query($conn, "SELECT Amount FROM dbo.Inventory WHERE PN = ?", [$pn]);
    if ($row = sqlsrv_fetch_array($invCheck, SQLSRV_FETCH_ASSOC)) {
        $newQty = $row['Amount'] + $amtToAdd;
        sqlsrv_query($conn, "UPDATE dbo.Inventory SET Amount = ? WHERE PN = ?", [$newQty, $pn]);
    } else {
        sqlsrv_query($conn, "INSERT INTO dbo.Inventory (PN, Amount) VALUES (?, ?)", [$pn, $amtToAdd]);
    }

    if ($received || $remaining == 0) {
        sqlsrv_query($conn, "DELETE FROM dbo.POItems WHERE ItemID = ?", [$itemID]);
    } else {
        sqlsrv_query($conn, "UPDATE dbo.POItems SET Quantity = ? WHERE ItemID = ?", [$remaining, $itemID]);
    }
}

$remainingItems = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM dbo.POItems WHERE PONum = ?", [$poNum]);
$cntRow = sqlsrv_fetch_array($remainingItems, SQLSRV_FETCH_ASSOC);
if ($cntRow['cnt'] == 0) {
    sqlsrv_query($conn, "UPDATE dbo.POs SET Status = 'Received' WHERE PONum = ?", [$poNum]);
}

echo "PO items processed and inventory updated!";
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="stylePurchaseOrder.css">
    </head>
    <body>
        <div class="main-container">
            <div class="navigation">
                    <button onclick="location.href='ReceivedPO.php'" class="btn btn-secondary">
                        Go Back
                    </button>
                    <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">
                        Report an Issue
                    </button>
            </div>
        </div>
    </body>
</html>
