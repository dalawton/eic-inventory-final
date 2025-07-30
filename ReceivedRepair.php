<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to mark selected repair as received and inhouse
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

$sql = "SELECT SerialNumber FROM dbo.Repairs WHERE Status = 'INBOUND'";
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
        <link rel="stylesheet" href="styleRepair.css">
        <title>Received a Repair</title>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    </head>
    <body>
        <div class="main-container">
            <div class="header">
                <h1>Received Inbound Repair</h1>
                <p>Complete the form below to submit a record of this received repair</p>
            </div>
            <div class="form-content">
                <div class="form-group">
                    <label class="section-title" for="serialNumber">Select Repair:</label>
                    <select name="serialNumber" class="form-control" id="serialNumber">
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
                </div>
                <div class="form-section" id="repairFields" style="display:none;">
                    <div class="form-grid">
                        <form id="ReceivedRepairsForm" action="newRepair.php" method="POST">
                            <div class="form-group">
                                <input type="hidden" id="hiddenSerialNumber" name="serialNumber" value="">
                                <label for="receivedDate">Date Received:</label>
                                <input type="date" id="receivedDate" class="form-control" name="receivedDate" required>
                                <br><br>
                                <label for="receiver">Receiver Name:</label>
                                <input type="text" id="receiver" class="form-control" name="receiver" required>
                                <br><br>
                                <label for="receiver-email">Receiver Email:</label>
                                <input type="email" id="receiver-email" class="form-control" name="receiver-email">
                                <br><br>
                                <div class="action-buttons">       
                                    <button type="submit" class="btn" id="submitReceived">Submit</button>
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
                    $('#hiddenSerialNumber').val('');
                } else if (val) {
                    $('#hiddenSerialNumber').val(val);
                    
                    $.get('getRepairInfo.php', { serialNumber: val }, function(data) {
                        var info = '';
                        try {
                            var repair = (typeof data === 'string') ? JSON.parse(data) : data;
                            var r = Array.isArray(repair) && repair.length > 0 ? repair[0] : {};
                            info += 'Serial Number: ' + (r.SerialNumber ?? '') + '<br>';
                            info += 'Customer Name: ' + (r.Requester ?? '') + '<br>';
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