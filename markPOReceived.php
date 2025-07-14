<?php
// Creates connection to the host, references the .env file to add additional security to the server
// If any of the login information for the server changes, update in .env file.
require_once __DIR__ . '/vendor/autoload.php';      // This acts as a bridge from this file to the .env file to get the information stored in the .env file.
use Dotenv\Dotenv;

// Load environment variables (from .env)
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
// Get submitted data
$poNum = $_POST['PO'] ?? null;
$receivedItems = $_POST['received_items'] ?? []; // checked checkboxes (fully received)
$amountReceived = $_POST['amountReceived'] ?? []; // array: ItemID => amount

// Get all items for this PO
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
        // Checkbox checked: fully received
        $amtToAdd = $qty;
        $remaining = 0;
    } elseif ($amtInput > 0 && $amtInput < $qty) {
        // Partial receipt
        $amtToAdd = $amtInput;
        $remaining = $qty - $amtInput;
    } else {
        // Not received, or invalid input
        continue;
    }

    // Update inventory
    $invCheck = sqlsrv_query($conn, "SELECT Amount FROM dbo.Inventory WHERE PN = ?", [$pn]);
    if ($row = sqlsrv_fetch_array($invCheck, SQLSRV_FETCH_ASSOC)) {
        $newQty = $row['Amount'] + $amtToAdd;
        sqlsrv_query($conn, "UPDATE dbo.Inventory SET Amount = ? WHERE PN = ?", [$newQty, $pn]);
    } else {
        sqlsrv_query($conn, "INSERT INTO dbo.Inventory (PN, Amount) VALUES (?, ?)", [$pn, $amtToAdd]);
    }

    // Update POItems
    if ($received || $remaining == 0) {
        // Fully received, remove or mark as received
        sqlsrv_query($conn, "DELETE FROM dbo.POItems WHERE ItemID = ?", [$itemID]);
    } else {
        // Update remaining quantity
        sqlsrv_query($conn, "UPDATE dbo.POItems SET Quantity = ? WHERE ItemID = ?", [$remaining, $itemID]);
    }
}

// Optionally, update PO status if all items are received
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