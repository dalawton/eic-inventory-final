<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to change the status of an ordered PO and collect information
 * about received parts in the selected PO.
 *
 * PHP version 8
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Submit_Files
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

// Get all non-received POs
$sql = "SELECT PONum FROM POs WHERE Status = 'Ordered'";
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
    <style>
        .product-table th:nth-child(1),
        .product-table td:nth-child(1) {
            max-width: 100px;
            width: 100px;
        }
        
        .product-table th:nth-child(2),
        .product-table td:nth-child(2) {
            max-width: 100px;
            width: 100px;

        }
        
        .product-table th:nth-child(3),
        .product-table td:nth-child(3) {
            max-width: 90px;
            width: 90px;
        }

        .product-table th:nth-child(4),
        .product-table td:nth-child(4) {
            max-width: 200px;
            width: 200px;
        }
    </style>
    
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
                            <?php foreach ($poOptions as $PONum) : ?>
                                <option value="<?php echo htmlspecialchars($PONum) ?>" <?php echo $selectedPO == $PONum ? 'selected' : '' ?>>
                                    <?php echo htmlspecialchars($PONum) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selectedPO) : ?>
            <div class="form-section">
                <div class="product-table-container">
                    <div class="table-header">
                        <h2>Items Ordered for PO <?php echo htmlspecialchars($selectedPO) ?></h2>
                    </div>
                    <form class="action-buttons" method="post" action="deletePO.php">
                        <button type="button" onclick="showConfirm()" id="deleteButton" class="btn btn-secondary">
                            Delete PO
                        </button>
                        <br>
                        <div class="form-control" style="display: none;" id="confirm">
                            <p><b>CONFIRMING: TYPE 'Delete' to delete selected PO</b></p><br>
                            <input type="text" class="form-control" id="passInput">
                            <button class="btn" onclick="checkPass()">Confirm Deletion</button>
                        </div>
                        <div class="form-content" style="display:none;" id="button">
                            <button type="submit" id="deletePO" class="btn btn-submit">DELETE</button>
                        </div>
                        <input type="hidden" name="PO" value="<?php echo htmlspecialchars($selectedPO) ?>">
                    </form>
                    <div class="desc-info">
                        <p> If the full quantity of parts are received, click checkbox for each fully quantity of part</p>
                        <p> If not all items are received, change 'Amount Received' to the amount in the shipment and submit. Once the rest of the parts arrive, resubmit for those items</p>
                    </div>
                    <form method="post" action="markPOReceived.php">
                        <input type="hidden" name="PO" value="<?php echo htmlspecialchars($selectedPO) ?>">
                        <table class="product-table" border="1">
                            <tr>
                                <th>Part Number</th>
                                <th>Amount</th>
                                <th>Received</th>
                                <th>Amount Received</th>
                            </tr>
                            <!-- Loops through the POItems that are assigned the selected PO number and displays them -->
                            <?php foreach ($items as $item) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['PN'] ?? '') ?></td>
                                <td><?php echo htmlspecialchars($item['Quantity'] ?? '') ?></td>
                                <td>
                                    <!-- Assigns a checkbox next to each item in the order to mark that item as received -->
                                    <input type="checkbox" name="received_items[]" value="<?php echo htmlspecialchars($item['ItemID']) ?>">
                                </td>
                                <td><input type="number" class="form-control" name="amountRecieved" value="<?php echo htmlspecialchars($item['Quantity'] ?? '') ?>"></td>
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

<script>
    function checkPass() {
        const correctPass = "Delete";
        const userInput = document.getElementById("passInput").value;

        if (userInput === correctPass) {
            document.getElementById("button").style.display="block";
        }
    }

    function showConfirm() {
        document.getElementById("confirm").style.display="block";
    }
</script>
