<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Form to subtract parts from the selected completed battery from the
 * inventory table.
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

$sql = "SELECT Name FROM dbo.Battery";
$stmt = sqlsrv_query($conn, $sql);
$batteryOptions = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $batteryOptions[] = $row['Name'];
}
if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}

$selectedBattery = $_GET['batteryName'] ?? null;
$items = [];
if ($selectedBattery) {
    $batteryIDstmt = sqlsrv_query($conn, "SELECT BatteryId FROM dbo.Battery WHERE Name = ?", [$selectedBattery]); // phpcs:ignore
    $batteryIDrow = sqlsrv_fetch_array($batteryIDstmt, SQLSRV_FETCH_ASSOC);
    $batteryID = $batteryIDrow['BatteryId'] ?? null;
    if ($batteryID) {
        $sql = "SELECT * FROM dbo.PartsForBatteries WHERE BatteryId = ?";
        $stmt = sqlsrv_query($conn, $sql, [$batteryID]);
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $items[] = $row;
        }
    }
}
?>

<?php
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Sets the default character alphabet, the default screen size and links 
            to the style sheet with additional formatting --> 
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="styleCheckout.css">
        <title>Submit Completed Battery</title>
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
                <h1>Submit Completed Battery</h1>
                <p>Select the type of completed battery and how many of each part used</p>
                <p>Submit the form along with new battery's serial number to remove parts from inventory</p>
            </div>
            <div class="form-content">
                <form class="form-section" method="GET" id="batterySelectForm">
                    <div class="form-group">
                        <label for="batteryName">Select Battery:</label>
                        <select name="batteryName" class="form-control" id="batteryName">
                            <option value="">--Select Battery--</option>
                            <?php foreach ($batteryOptions as $Name): ?>
                            <option value="<?php echo htmlspecialchars($Name) ?>" 
                                <?php echo $selectedBattery === $Name ? 'selected' : '' ?>> 
                                <?php echo htmlspecialchars($Name) ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="uncompleteBattery" 
                                <?php echo $selectedBattery === 'uncompleteBattery' ? 'selected' : '' ?>>
                                    Log completed step
                                </option>
                            <option value="newBattery" 
                                <?php echo $selectedBattery === 'newBattery' ? 'selected' : '' ?>>
                                    Create a New Battery Option
                            </option>
                        </select>

                        <script>
                            $(document).ready(function() {
                                $('#batteryName').select2({});
                            });
                        </script>
                    </div>
                </form>
                <div class="form-section">
                    <!-- Existing Battery Form -->
                    <form id="existingBatteryForm" class="battery-form" method="POST" 
                        action="updateInventoryComplete.php" style="display:none;">
                        <div class="table-header">
                            <h2>Parts Used for Battery 
                                <?php echo htmlspecialchars($selectedBattery) ?> </h2>
                        </div>
                        <div class="desc-info">
                            <p>Update the amount used of each part for the completed battery</p>
                            <p>If a certain listed part is not used, change amount to 0</p>
                            <p>Add not listed parts at the bottom if used</p>
                            <p>Include the serial number of the create battery and submit form</p>
                        </div>
                        <input type="hidden" name="selectedBattery" 
                            value="<?php echo htmlspecialchars($selectedBattery) ?>">
                        <table class="product-table" border="1">
                            <tr>
                                <th>Part Number</th>
                                <th>Description</th>
                                <th>Amount Used</th>
                            </tr>
                            <?php foreach ($items as $index => $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['PN'] ?? '') ?></td>
                                <td id="desc-<?php echo $index ?>">
                                    <?php echo htmlspecialchars($item['Description'] ?? '') ?></td>
                                <td><input type="number" class="form-control" style="color:#7a7d80"
                                    name="amount_Used" 
                                    value="<?php echo htmlspecialchars($item['Amount']) ?>"></td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Add Part Section -->
                            <tr id="add-part-row">
                                <td>
                                    <input type="text" class="form-control" id="newPartNumber" placeholder="Part Number">
                                </td>
                                <td>
                                    <input type="text" class="form-control" id="newPartDescription" placeholder="Description">
                                </td>
                                <td>
                                    <input type="number" class="form-control" id="newAmountUsed" min="0">
                                    <button type="button" class="btn btn-secondary" id="addPartBtn" style="margin-left:8px;">Add Part</button>
                                </td>
                            </tr>
                        </table>
                        <br>
                        <div class="form-section">
                            <div class="form-group">
                                <label for="serialNumber">Serial Number: </label>
                                <input type="number" class="form-control" id="serialNumber" name="serialNumber">
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button type="submit" id="submitCompleted" class="btn btn-secondary">Mark as Completed</button>
                        </div>
                    </form>
                    <!-- New Battery Form -->
                    <form id="newBatteryForm" method="POST" action="addNewBattery.php" style="display:none;">
                        <div class="form-group">
                            <label for="newBatteryName">New Battery Name</label>
                            <input type="text" id="newBatteryName" name="newBatteryName" class="form-control">
                        </div>
                        <div class="product-table-container">
                            <div class="table-header">
                                <h3>New Form for Parts Used in New Battery</h3>
                            </div>
                            <div class="desc-info">
                                <p>Add the parts used in new battery and their respective amounts</p>
                                <p>Add descriptions for new parts</p>
                                <p>Submit to create a new template for a new battery.</p>
                            </div>
                            <table class="product-table" id="partsForNewBattery" border="1">
                                <tr>
                                    <th>Part Number</th>
                                    <th>Description</th>
                                    <th>Amount Used</th>
                                </tr>
                                <tr class="add-product-form" id="add-part-row">
                                    <td>
                                        <input type="text" class="form-control" id="newPartNumber" placeholder="Part Number">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" id="newPartDescription" placeholder="Description">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" id="newAmountUsed" min="0">
                                        <button type="button" class="btn btn-secondary" id="addPartBtn" style="margin-left:8px;">Add Part</button>
                                    </td>
                                </tr>
                            </table>
                            <div class="action-buttons">
                                <button type="submit" id="submitNew" class="btn btn-secondary">Mark as Completed</button>
                            </div>
                        </div>
                    </form>
                    <form id="logStepForm" method="POST" action="logStep.php" style="display:none;">                   
                        <div class="product-table-container">
                            <div class="table-header">
                                <h3>Parts used during build process</h3>
                            </div>
                            <div class="desc-info">
                                <p>For logging a intermediate step, add the parts and amounts used</p>
                                <p>Submit to remove from inventory</p>
                            </div>
                            <table class="product-table" id="partsForStep" border="1">
                                <tr>
                                    <th>Part Number</th>
                                    <th>Description</th>
                                    <th>Amount Used</th>
                                </tr>
                                <tr class="add-product-form" id="add-part-step">
                                    <td><input type="text" class="form-control" id="stepPartNumber" placeholder="Part Number"></td>
                                    <td><input type="text" class="form-control" id="stepPartDescription" placeholder="Description"></td>
                                    <td>
                                        <input type="number" class="form-control" id="stepAmountUsed" min="0">
                                        <button type="button" class="btn btn-secondary" id="addStepBtn" style="margin-left:8px;">Add Part</button>
                                    </td>
                                </tr>
                            </table>
                            <div class="action-buttons">
                                <button type="submit" id="partSubmit" class="btn btn-secondary">Mark as Built</button>
                            </div>
                        </div>
                    </form>
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

<script>
$(document).ready(function() {
    function showFormForSelection(val) {
        $('.battery-form').hide();
        if (!val) return;
        if (val === 'newBattery') {
            $('#newBatteryForm').show();
        } else if (val === 'uncompleteBattery') {
            $('#logStepForm').show();
        } else {
            $('#existingBatteryForm').show();
        }
    }

    // On page load, show the correct form if a value is already selected
    showFormForSelection($('#batteryName').val());

    // On dropdown change, show the correct form
    $('#batteryName').on('change', function() {
        showFormForSelection($(this).val());
    });
});
</script>

<script>
$(document).ready(function() {
    // For each row, fetch and update the description
    $('table.product-table tr').each(function(index) {
        // Skip header row
        if (index === 0) return;
        var $row = $(this);
        var partNumber = $row.find('td:first').text().trim();
        var descCell = $row.find('td').eq(1);

        if (partNumber) {
            $.get('getDescription.php', { productNumber: partNumber }, function(data) {
                let description = '';
                try {
                    let rows = (typeof data === 'string') ? JSON.parse(data) : data;
                    if (Array.isArray(rows) && rows.length > 0) {
                        description = rows[0].Details ?? '';
                    }
                } catch (e) {
                    description = '';
                }
                if (description) {
                    descCell.text(description);
                }
            });
        }
    });

    // Auto-fill description when part number changes, only if description is empty
    $('#newPartNumber').on('blur', function() {
        var partNumber = $(this).val().trim();
        var $descInput = $('#newPartDescription');
        if (partNumber && !$descInput.val().trim()) {
            $.get('getDescription.php', { productNumber: partNumber }, function(data) {
                let description = '';
                try {
                    let rows = (typeof data === 'string') ? JSON.parse(data) : data;
                    if (Array.isArray(rows) && rows.length > 0) {
                        description = rows[0].Details ?? '';
                    }
                } catch (e) {
                    description = '';
                }
                if (description && !$descInput.val().trim()) {
                    $descInput.val(description);
                }
            });
        }
    });

    $('#stepPartNumber').on('blur', function() {
        var partNumber = $(this).val().trim();
        var $descInput = $('#stepPartDescription');
        if (partNumber && !$descInput.val().trim()) {
            $.get('getDescription.php', { productNumber: partNumber }, function(data) {
                let description = '';
                try {
                    let rows = (typeof data === 'string') ? JSON.parse(data) : data;
                    if (Array.isArray(rows) && rows.length > 0) {
                        description = rows[0].Details ?? '';
                    }
                } catch (e) {
                    description = '';
                }
                if (description && !$descInput.val().trim()) {
                    $descInput.val(description);
                }
            });
        }
    });

    // Add Part button click handler
    $('#addPartBtn').click(function() {
        var partNumber = $('#newPartNumber').val().trim();
        var description = $('#newPartDescription').val().trim();
        var amountUsed = $('#newAmountUsed').val().trim();

        if (partNumber === '' || amountUsed === '') {
            alert('Please enter both Part Number and Amount Used for the new part.');
            return;
        }

        // If description is empty, fetch it before adding the row
        if (!description) {
            $.get('getDescription.php', { productNumber: partNumber }, function(data) {
                let fetchedDescription = '';
                try {
                    let rows = (typeof data === 'string') ? JSON.parse(data) : data;
                    if (Array.isArray(rows) && rows.length > 0) {
                        fetchedDescription = rows[0].Details ?? '';
                    }
                } catch (e) {
                    fetchedDescription = '';
                }
                addPartRow(partNumber, fetchedDescription, amountUsed);
            });
        } else {
            addPartRow(partNumber, description, amountUsed);
        }
    });

    function addPartRow(partNumber, description, amountUsed) {
        $('#add-part-row').before(`
            <tr>
                <td><input type="text" class="form-control" name="partNumber[]" value="${partNumber}" readonly></td>
                <td><input type="text" class="form-control" name="description[]" value="${description}" readonly></td>
                <td><input type="number" class="form-control" name="amountUsed[]" value="${amountUsed}" min="0" readonly></td>
            </tr>
        `);
        // Clear the input fields
        $('#newPartNumber').val('');
        $('#newPartDescription').val('');
        $('#newAmountUsed').val('');
    }

    // Add Part button click handler
    $('#addStepBtn').click(function() {
        var partNumber = $('#stepPartNumber').val().trim();
        var description = $('#stepPartDescription').val().trim();
        var amountUsed = $('#stepAmountUsed').val().trim();

        if (partNumber === '' || amountUsed === '') {
            alert('Please enter both Part Number and Amount Used for the completed build.');
            return;
        }

        // If description is empty, fetch it before adding the row
        if (!description) {
            $.get('getDescription.php', { productNumber: partNumber }, function(data) {
                let fetchedDescription = '';
                try {
                    let rows = (typeof data === 'string') ? JSON.parse(data) : data;
                    if (Array.isArray(rows) && rows.length > 0) {
                        fetchedDescription = rows[0].Details ?? '';
                    }
                } catch (e) {
                    fetchedDescription = '';
                }
                addStepPartRow(partNumber, fetchedDescription, amountUsed);
            });
        } else {
            addStepPartRow(partNumber, description, amountUsed);
        }
    });

    function addStepPartRow(partNumber, description, amountUsed) {
        $('#add-part-step').before(`
            <tr>
                <td><input type="text" class="form-control" name="partNumber[]" value="${partNumber}" readonly></td>
                <td><input type="text" class="form-control" name="description[]" value="${description}" readonly></td>
                <td><input type="number" class="form-control" name="amountUsed[]" value="${amountUsed}" min="0" readonly></td>
            </tr>
        `);
        // Clear the input fields
        $('#stepPartNumber').val('');
        $('#stepPartDescription').val('');
        $('#stepAmountUsed').val('');
    }

    $('#batteryName').on('change', function() {
        $('#batterySelectForm').submit();
    });
});
</script>
