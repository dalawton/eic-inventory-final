<?php

/**
 * File to process the changes to vendor defaults
 */

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

// Vendor search logic
$search = $_GET['vendor_search'] ?? '';
$params = [];
$where = '';
if ($search !== '') {
    $where = "WHERE VendorName LIKE ?";
    $params[] = "%$search%";
}

$sql = "SELECT VendorID, VendorName FROM Vendors $where ORDER BY VendorName";
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="stylePurchaseOrder.css">
        <title>Fix Vendor Information</title>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    </head>
    <body>
        <div class="main-container">
            <div class="header">
                <h1>Fix Vendor Information<h1>
                <p> Select the Vendor from the dropdown and change the incorrect details and submit.<p>
            </div>
            <div class="form-content">
                <div class="form-section">
                    <h2 class="section-title">Vendor Information</h2>

                    <!-- Vendor Search and Dropdown -->
                    <div class="form-group">
                        <label for="vendorName">Select Vendor:</label>
                        <select name="vendorName" id="vendorName" class="form-control">
                            <option value="">--Select Vendor--</option>
                            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                            <option value="<?= htmlspecialchars($row['VendorID']) ?>">
                                <?= htmlspecialchars($row['VendorName']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <script>
                            $(document).ready(function() {
                                $('#vendorName').select2({
                                    placeholder: "Search or select a vendor"
                                });

                                $('#vendorName').on('change', function() {
                                    var val = $(this).val();
                                    $('#vendorID').val(val);

                                    if (val && val !== 'not_listed') {
                                        // Fetch and show vendor info
                                        $.get('getVendorInfo.php', { vendorID: val }, function(data) {
                                            var vendor = (typeof data === 'string') ? JSON.parse(data) : data;
                                            if (vendor && !vendor.error) {
                                                // Set placeholders in the form fields
                                                $('#vendorIDFix').attr('placeholder', vendor.vendorID ?? '');
                                                $('#vendorNameFix').attr('placeholder', vendor.VendorName ?? '');
                                                $('#contactNameFix').attr('placeholder', vendor.ContactName ?? '');
                                                $('#contactEmailFix').attr('placeholder', vendor.ContactEmail ?? '');
                                                $('#vendorPhoneFix').attr('placeholder', vendor.Telephone ?? '');
                                                $('#vendorAddressFix').attr('placeholder', vendor.AddressLine1 ?? '');
                                                $('#vendorCitySTZIPFix').attr('placeholder', vendor.CitySTZIP ?? '');
                                            } else {
                                                // Clear placeholders if no vendor found
                                                $('#vendorIDFix, #vendorNameFix, #contactNameFix, #contactEmailFix, #vendorPhoneFix, #vendorAddressFix, #vendorCitySTZIPFix').attr('placeholder', '');
                                            }
                                        });
                                    } else {
                                        // Clear placeholders if "not listed" or empty
                                        $('#vendorIDFix, #vendorNameFix, #contactNameFix, #contactEmailFix, #vendorPhoneFix, #vendorAddressFix, #vendorCitySTZIPFix').attr('placeholder', '');
                                    }
                                });
                            });
                        </script>
                    </div>
                    <div class="form-group">
                        <form id="editVendor" method="POST" action="editVendor.php">
                            <input type="hidden" id="vendorID" name="vendorID" value="">
                            <br>
                            <label for="vendorIDFix">New Vendor ID: </label>
                            <input type="text" id="vendorIDFix" style="width:50%;" class="form-control" name="vendorIDFix">
                            <br><br>

                            <label for="vendorNameFix">New Vendor Name: </label>
                            <input type="text" id="vendorNameFix" style="width:50%;" class="form-control" name="vendorNameFix">
                            <br><br>

                            <label for="contactNameFix">New Contact Name: </label>
                            <input type="text" id="contactNameFix" style="width:50%;" class="form-control" name="contactNameFix">
                            <br><br>

                            <label for="contactEmailFix">New Contact Email: </label>
                            <input type="text" id="contactEmailFix" style="width:50%;" class="form-control" name="contactEmailFix">
                            <br><br>

                            <label for="vendorPhoneFix">New Vendor Phone: </label>
                            <input type="text" id="vendorPhoneFix" style="width:50%;" class="form-control" name="vendorPhoneFix">
                            <br><br>
                            
                            <label for="vendorAddressFix">New Vendor Address: </label>
                            <input type="text" id="vendorAddressFix" style="width:50%;" class="form-control" name="vendorAddressFix">
                            <br><br>

                            <label for="vendorCitySTZIPFix">New Vendor City, State ZIP</label>
                            <input type="text" id="vendorCitySTZIPFix" style="width:50%;" class="form-control" name="vendorCitySTZIPFix">
                            <br> <br>

                            <div class="action-buttons">
                                <button type="submit" class="btn" id="fixVendorInfo">Submit Fixes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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

            