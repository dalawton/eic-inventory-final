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

// Get all non-received POs
$sql = "SELECT PONum FROM POs WHERE Status != 'Received'";
$stmt = sqlsrv_query($conn, $sql);
$poOptions = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $poOptions[] = $row['PONum'];
}

// On dropdown selection
$selectedPO = $_GET['PO'] ?? null; 
$items = [];
if ($selectedPO) {
    $sql = "SELECT * FROM dbo.POItems WHERE PONum = ?";
    $stmt = sqlsrv_query($conn, $sql, [$selectedPO]);
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Received Purchase Order Form</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="stylePurchaseOrder.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1>Received Purchase Order Form</h1>
            <p>Complete the form upon receiving a Purchase Order</p>
        </div>
        <div class="form-content">
            <div class="form-section">
                <form method="get" action="">
                    <div class="form-group">
                        <label for="PO">Select PO:</label>
                        <select name="PO" class="form-control" id="PO" onchange="this.form.submit()"> <!-- Dropdown for unreceived POs -->
                            <option value="">--Select--</option>
                            <?php foreach ($poOptions as $PONum): ?>
                                <option value="<?= htmlspecialchars($PONum) ?>" <?= $selectedPO == $PONum ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($PONum) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selectedPO): ?>
            <div class="form-section">
                <div class="product-table-container">
                    <div class="table-header">
                        <h2>Items Ordered for PO <?= htmlspecialchars($selectedPO) ?></h2>
                    </div>
                    <div class="desc-info">
                        <p> If the full quantity of parts are received, click checkbox for each fully quantity of part</p>
                        <p> If not all items are received, change 'Amount Received' to the amount in the shipment and submit. Once the rest of the parts arrive, resubmit for those items</p>
                    </div>
                    <form method="post" action="markPOReceived.php">
                        <input type="hidden" name="PO" value="<?= htmlspecialchars($selectedPO) ?>">
                        <table class="product-table" border="1">
                            <tr>
                                <th>Part Number</th>
                                <th>Amount</th>
                                <th>Received</th>
                                <th>Amount Received</th>
                            </tr>
                            <!-- Loops through the POItems that are assigned the selected PO number and displays them -->
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['PN'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['Quantity'] ?? '') ?></td>
                                <td>
                                    <!-- Assigns a checkbox next to each item in the order to mark that item as received -->
                                    <input type="checkbox" name="received_items[]" value="<?= htmlspecialchars($item['ItemID']) ?>">
                                </td>
                                <td><input type="number" class="form-control" name="amountRecieved" value="<?= htmlspecialchars($item['Quantity'] ?? '') ?>"></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        <div class="action-buttons">
                            <button type="submit" id="receivedButton" class="btn btn-success">
                                Mark Items as Received
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        <!-- Navigation -->
        <div class="navigation">
            <button onclick="location.href='FrontPage.html'" class="btn btn-secondary">
                Return to Front Page
            </button>
            <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">
                Report an Issue
            </button>
        </div>
    </div>
</body>
</html>
<?php
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>