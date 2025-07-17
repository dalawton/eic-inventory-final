<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to mark selected repair as shipped and completed
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

// Get all non-shipped Repairs
$sql = "SELECT SerialNumber FROM dbo.Repairs WHERE Status != 'Completed'";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ship Out Completed Repair</title>
        <link rel="stylesheet" href="styleRepair.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    </head>
    <body>
        <div class="main-container">
            <div class="header">
                <h1>Ship Out Completed Repair</h1>
                <p>Complete the form below to mark your repair as completed and shipped</p>
            </div>
            <div class="form-content">
                <div class="form-group">
                    <label class="section-title" for="serialNumber">Select Repair:</label>
                    <select name="serialNumber" class="form-control" id="serialNumber"> <!-- Dropdown for unreceived Repairs -->
                        <option value="">--Select--</option>
                        <option value="not_listed">Repair not listed</option>
                        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) : ?>
                        <option value="<?php echo htmlspecialchars($row['SerialNumber']) ?>">
                            <?php echo htmlspecialchars($row['SerialNumber']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="vendor-info" id="repairInfo" style="display:none;">
                    <strong>Repair Details:</strong>
                    <div id="repairDetails"></div>
                    <button type="button" class="btn btn-secondary" style="margin-top: 15px;" onclick="location.href='fixRepair.php'">
                        Error in Repair Info
                    </button>
                </div>
                <div class="form-section" id="repairFields" style="display:none;">
                    <div class="form-grid">
                        <!-- Form to ship fixed repairs -->
                        <form id="shipFixedRepairsForm" action="markRepairShipped.php" method="POST">
                            <div class="form-group">
                                <input type="hidden" id="hiddenSerialNumber" name="serialNumber" value="">
                                <label for="details">Details:</label>
                                <textarea id="details" name="details" class="form-control" rows="4" cols="50"></textarea>
                                <br><br>

                                <label for="shippingDate">Shipping Date:</label>
                                <input type="date" id="shippingDate" class="form-control" name="shippingDate" required>
                                <br><br>

                                <label for="shippingLocation">Shipping Location:</label>
                                <input type="text" id="shippingLocation" class="form-control" name="shippingLocation" required>
                                <br><br>
                                <div class="action-buttons">       
                                    <button type="submit" class="btn" id="submitShipping">Submit Shipping Details</button>
                                </div>
                            </div>
                        </form>
                    </div>
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
        <script>
            $('#serialNumber').on('change', function() {
                var val = $(this).val();
                var repairInfo = $('#repairInfo');
                var repairDetails = $('#repairDetails');
                var otherFields = $('#repairFields');
                
                if (val === 'not_listed') {
                    repairInfo.hide();
                    repairDetails.html('');
                    otherFields.hide();
                    // Clear the hidden field
                    $('#hiddenSerialNumber').val('');
                } else if (val) {
                    // Set the hidden field value
                    $('#hiddenSerialNumber').val(val);
                    
                    // Fetch and show vendor info
                    $.get('getRepairInfo.php', { serialNumber: val }, function(data) {
                        var info = '';
                        try {
                            var repair = (typeof data === 'string') ? JSON.parse(data) : data;
                            var r = Array.isArray(repair) && repair.length > 0 ? repair[0] : {};
                            info += 'Serial Number: ' + (r.SerialNumber ?? '') + '<br>';
                            info += 'Customer Name: ' + (r.Requester ?? '') + '<br>';
                            info += 'Date: ' + (r.DateReceived ?? '') + '<br>';
                            info += 'Details: ' + (r.Details ?? '') + '<br>';
                            info += 'Status: ' + (r.Status ?? '') + '<br>';
                            repairDetails.html(info);
                            repairInfo.show();
                            otherFields.show();
                        }
                        catch (e) {
                            repairDetails.html('Error loading details.');
                            repairInfo.show();
                        }
                    });
                } else {
                    // No selection - hide everything and clear hidden field
                    repairInfo.hide();
                    otherFields.hide();
                    $('#hiddenSerialNumber').val('');
                }
            });
            </script>
    </body>
</html>    
<?php
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="styleRepair.css">
    </head>
    <body>
        <div class="main-container">
            <div class="navigation">
                    <button onclick="location.href='FrontPage.html'" class="btn btn-secondary">
                        Go Back
                    </button>
                    <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">
                        Report an Issue
                    </button>
            </div>
        </div>
    </body>
</html>
