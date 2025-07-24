<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Form to subtract parts from the selected completed battery from the
 * inventory table with serial number display for components.
 *
 * PHP version 8
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Submit_File
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

$sql = "SELECT BatteryName FROM dbo.Battery WHERE Complete = 'Yes'";
$stmt = sqlsrv_query($conn, $sql);
$batteryOptions = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $batteryOptions[] = $row['BatteryName'];
}
if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="styleCheckout.css">
        <title>Incomming Repair</title>
        <script 
            src="https://code.jquery.com/jquery-3.6.0.min.js">
        </script>
        <link 
            href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" 
            rel="stylesheet" /> 
        <script 
            src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js">
        </script>
    </head>
    <body>
        <div class="main-container">
            <div class="header">
                <h1>Inbound Repair</h1>
                <p>Complete the form below to submit a record of this incomming repair</p>
            </div>
            <div class="form-content">
                <div class="form-section">
                    <!-- Information to be stored in a database of repairs -->
                    <form id="newRepairRequest" method="POST" action="newInboundRepair.php">
                        <div class="form-group">
                            <label for="productSerialNumber">Product Serial Number:</label>
                            <input type="text" id="productSerialNumber" class="form-control" name="productSerialNumber" placeholder="Ex. 6060" required>
                            <br><br>
                            <label for="batteryName">Select Battery Type:</label>
                            <select name="batteryName" class="form-control" id="batteryName" required>
                                <option value="">--Select Battery Type--</option>
                                <?php foreach ($batteryOptions as $Name) : ?>
                                <option value="<?php echo htmlspecialchars($Name) ?>" 
                                    <?php echo $selectedBattery === $Name ? 'selected' : '' ?>> 
                                    <?php echo htmlspecialchars($Name) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <br><br>
                            <label for="customerName">Customer Name:</label>
                            <input type="text" id="customerName" class="form-control" name="customerName" placeholder="Ex. Dillon Aero" required>
                            <br><br>
                            <label for="issueDescription">Issue Description:</label>
                            <textarea id="issueDescription" class="form-control" name="issueDescription" rows="4" cols="50" placeholder="If provided, include details..."></textarea>
                        </div>
                        <br><br>
                        <div class="action-buttons">
                            <button type="submit" class="btn" id="submitRepairRequest">Submit Inbound Repair</button>
                        </div>
                    </form>
                </div>
            </div>
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