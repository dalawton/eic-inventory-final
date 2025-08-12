<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Form to submit a PO request with all needed information
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
// phpcs:disable PEAR.Commenting.FunctionComment.Missing

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

function getNextPONumber($conn)
{
    $sql = "SELECT MAX(CAST(PONum AS INT)) as maxPO FROM dbo.POs WHERE ISNUMERIC(PONum) = 1";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        die("Error getting max PO number: " . print_r(sqlsrv_errors(), true));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $maxPO = $row['maxPO'];

    return strval($maxPO + 1);
}

$nextPONumber = getNextPONumber($conn);

$search = $_GET['vendor_search'] ?? '';
$params = [];
$where = '';
if ($search !== '') {
    $where = "WHERE VendorName LIKE ?";
    $params[] = "%$search%";
}

$sql = "SELECT VendorName FROM Vendors $where ORDER BY VendorName";
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}

$contractSql = "SELECT contractNumber, Description FROM contractNumbers ORDER BY contractNumber";
$contractStmt = sqlsrv_query($conn, $contractSql);
if ($contractStmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order Requisition Form</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="stylePurchaseOrder.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1>Purchase Order Requisition</h1>
            <p>Complete the form below to submit your purchase order request</p>
        </div>

        <div class="form-content">
            <form id="supplierInfo" method="POST" action="">
                <div class="form-section">
                    <h2 class="section-title">Basic Information</h2>
                    
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="needWorkOrder" name="needWorkOrder">
                            <label for="needWorkOrder">Need Work Order</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="confirming" name="confirming">
                            <label for="confirming">Confirming - DO NOT DUPLICATE</label>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="purchaseOrderNumber">Purchase Order #:</label>
                            <input type="text" id="purchaseOrderNumber" name="purchaseOrderNumber" 
                                   class="form-control" value="<?php echo htmlspecialchars($nextPONumber) ?>" 
                                   readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            <small style="color: #666; font-size: 0.9em;">Auto-generated - cannot be modified</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="date">Date:</label>
                            <input type="date" id="date" name="date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="requestorName">Requestor:</label>
                            <input type="text" id="requestorName" name="requestorName" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="requestorEmail">Requestor's Email:</label>
                            <input type="text" id="requestorEmail" name="requestorEmail" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title">Vendor Information</h2>
                    
                    <div class="form-group">
                        <label for="vendorName">Select Vendor:</label>
                        <select name="vendorName" id="vendorName" class="form-control">
                            <option value="">--Select Vendor--</option>
                            <option value="not_listed">Vendor not listed</option>
                            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) : ?>
                            <option value="<?php echo htmlspecialchars($row['VendorName']) ?>">
                                <?php echo htmlspecialchars($row['VendorName']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>

                        <script>
                        $(document).ready(function() {
                            $('#vendorName').select2({
                                placeholder: "Search or select a vendor"
                            });
                        });
                        </script>
                    </div>

                    <div class="vendor-info" id="vendorInfo" style="display:none;">
                        <strong>Vendor Details:</strong>
                        <div id="vendorDetails"></div>
                        <button type="button" class="btn btn-secondary" style="margin-top: 15px;" onclick="location.href='fixVendor.php'">
                             Error in Vendor Info
                        </button>
                    </div>

                    <div id="otherVendorFields" style="display:none;">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="otherName">Vendor Name:</label>
                                <input type="text" id="otherName" name="otherName" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="supplierStreetAddress">Street Address:</label>
                                <input type="text" id="supplierStreetAddress" name="streetAddress" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="supplierCity">City:</label>
                                <input type="text" id="supplierCity" name="supplierCity" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="supplierState">State:</label>
                                <input type="text" id="supplierState" name="supplierState" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="supplierZip">Zip Code:</label>
                                <input type="text" id="supplierZip" name="supplierZip" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="vendorPhone">Phone Number:</label>
                                <input type="text" id="vendorPhone" name="vendorPhone" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="contactName">Contact Name:</label>
                                <input type="text" id="contactName" name="contactName" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="contactEmail">Contact Email:</label>
                                <input type="email" id="contactEmail" name="contactEmail" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title">Order Details</h2>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Payment Info:</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="citizensBank" name="citizensBank" value="Citizens Bank m/c">
                                    <label for="citizensBank">Citizens Bank m/c</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="employeeReimbursement" name="employeeReimbursement" value="Employee Reimbursement">
                                    <label for="employeeReimbursement">Employee Reimbursement</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Type of Order Request:</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="placeByRequester" id="placeByRequester" value="Order Placed By Requestor">
                                    <label for="placeByRequester">Order Placed By Requestor</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="needPlaced" id="needPlaced" value="Order Needs to be Placed">
                                    <label for="needPlaced">Need Check to Have Order Placed</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top: 25px;">
                        <div class="form-group">
                            <label for="contractNumber">Charge to Contract #:</label>
                            <select id="contractNumber" name="contractNumber" class="form-control" required>
                                <option value="">--Select Contract--</option>
                                <?php while ($cRow = sqlsrv_fetch_array($contractStmt, SQLSRV_FETCH_ASSOC)) : ?>
                                    <option value="<?php echo htmlspecialchars($cRow['contractNumber']) ?>">
                                        <?php echo htmlspecialchars($cRow['contractNumber']) . ' - ' . htmlspecialchars($cRow['Description']) ?? '' ?>
                                    </option>
                                <?php endwhile; ?>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="otherContractNumber">Other Contract Number:</label>
                            <input type="text" id="otherContractNumber" name="otherContractNumber" class="form-control">
                            <label for="otherContractDescription">Other Contract Description:</label>
                            <input type="text" id="otherContractDescription" name="otherContractDescription" class="form-control">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 25px;">
                        <label>Delivery Address:</label>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea;">
                            <strong>111 Downey Street, Norwood, MA 02062</strong>
                        </div>
                    </div>
                </div>
            </form>

            <div class="product-table-container">
                <div class="table-header">
                    <h3>Products Requested</h3>
                </div>
                
                <table class="product-table" id="productTableSubmitted" border="1">
                    <thead>
                        <tr>
                            <th>Product Number</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Description</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>

                <div class="add-product-form">
                    <form id="table" method="POST" action="getDescription.php">
                        <div class="add-product-grid">
                            <div class="form-group">
                                <label for="productNumber">Product Number:</label>
                                <input type="text" id="productNumber" name="productNumber" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantity">Quantity:</label>
                                <input type="number" id="quantity" name="quantity" class="form-control">
                            </div>

                            <div class="form-group">
                                <label for="unitPrice">Unit Price:</label>
                                <input type="number" id="unitPrice" name="unitPrice" step="0.001" class="form-control">
                            </div>
                        </div>

                        <div class="desc-info">
                            <strong>Note:</strong> If product exists in the inventory database, the description will automatically populate. You can manually override by entering a description below.
                        </div>

                        <div class="form-group">
                            <label for="productDescription">Description (Optional Override):</label>
                            <input type="text" id="productDescription" name="productDescription" class="form-control">
                        </div>

                        <div style="text-align: center; margin-top: 20px;">
                            <button type="submit" id="addToTable" class="btn btn-primary">
                                Add Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="action-buttons">
                <button type="button" id="submitAllButton" class="btn btn-success">
                    Send Purchase Order
                </button>
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
        $(document).ready(function() {
        $('#vendorName').select2({
            placeholder: "Search or select a vendor"
        });

        $('#vendorName').on('change', function() {
            var val = $(this).val();
            var otherFields = $('#otherVendorFields');
            var vendorInfo = $('#vendorInfo');
            var vendorDetails = $('#vendorDetails');

            if (val === 'not_listed') {
                otherFields.show();
                vendorInfo.hide();
                vendorDetails.html('');
            } else if (val) {
                otherFields.hide();
                $.get('getVendorInfo.php', { vendorName: val }, function(data) {
                    var info = '';
                    try {
                        var vendor = (typeof data === 'string') ? JSON.parse(data) : data;
                        if (vendor && !vendor.error) {
                            info += 'Name: ' + (vendor.VendorName ?? '') + '<br>';
                            info += 'Phone: ' + (vendor.Telephone ?? '') + '<br>';
                            info += 'Address: ' + (vendor.AddressLine1 ?? '') + '<br>';
                            info += 'City, State Zip: ' + (vendor.CitySTZIP ?? '') + '<br>';
                            info += 'Contact Name: ' + (vendor.ContactName ?? '') + '<br>';
                            info += 'Contact Email: ' + (vendor.ContactEmail ?? '') + '<br>';
                            vendorDetails.html(info);
                            vendorInfo.show();
                        } else {
                            vendorDetails.html('No details found.');
                            vendorInfo.show();
                        }
                    } catch (e) {
                        vendorDetails.html('Error loading details.');
                        vendorInfo.show();
                    }
                });
            } else {
                otherFields.hide();
                vendorInfo.hide();
                vendorDetails.html('');
            }
        });

        var initialVal = $('#vendorName').val();
        if (initialVal === 'not_listed') {
            $('#otherVendorFields').show();
            $('#vendorInfo').hide();
            $('#vendorDetails').html('');
        } else if (initialVal) {
            $('#otherVendorFields').hide();
            $('#vendorInfo').show();
        } else {
            $('#otherVendorFields').hide();
            $('#vendorInfo').hide();
            $('#vendorDetails').html('');
        }
    });

        $('#table').on('submit', function(e) {
            e.preventDefault();

            const quantity = $('#quantity').val().trim();
            const productNumber = $('#productNumber').val().trim();
            const unitPrice = $('#unitPrice').val().trim();
            const manualDescription = $('#productDescription').val().trim();
            $.get('getDescription.php', { productNumber: productNumber }, function(data) {
                let description = '';
                try {
                    let rows = (typeof data === 'string') ? JSON.parse(data) : data;
                    if (Array.isArray(rows) && rows.length > 0) {
                        description = rows[0].Details ?? '';
                    }
                } catch (e) {
                    description = '';
                }
                if (manualDescription) {description = manualDescription;}
            

            const table = document.getElementById('productTableSubmitted').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();

            newRow.innerHTML = `
                <td>${productNumber}</td>
                <td>${quantity}</td>
                <td>$${parseFloat(unitPrice).toFixed(3)}</td>
                <td>${description}</td>
                <td>$${(quantity * unitPrice).toFixed(3)}</td>
                <td>
                    <button type="button" class="btn btn-secondary btn-edit">Edit</button>
                    <button type="button" class="btn btn-danger btn-remove">Remove</button>
                </td>
            `;

            $(newRow).hover(
                function() { $(this).find('td').css('background', '#f8f9ff'); },
                function() { $(this).find('td').css('background', ''); }
            );

            $('#table')[0].reset();
        });
        });

        $('#submitAllButton').on('click', function() {
            const supplierForm = document.getElementById('supplierInfo');
            const tableRows = document.querySelectorAll("#productTableSubmitted tbody tr");
            
            const combinedData = new FormData(supplierForm);
            const products = [];
            tableRows.forEach(row => {
                const cells = row.querySelectorAll("td");
                if (cells.length === 6) {
                    const unitPriceText = cells[2].textContent.trim();
                    const totalText = cells[4].textContent.trim();
                    
                    const unitPrice = parseFloat(unitPriceText.replace(/[$,]/g, '')) || 0;
                    const total = parseFloat(totalText.replace(/[$,]/g, '')) || 0;
                    
                    products.push({
                        productNumber: cells[0].textContent.trim(),
                        quantity: parseInt(cells[1].textContent.trim()) || 0,
                        unitPrice: unitPrice,
                        description: cells[3].textContent.trim(),
                        total: total
                    });
                }
            });

            console.log('Products being sent:', products);
            if (products.length === 0) {
                console.error('Empty Purchase Order! Please add products');
                alert("Error: Empty Purchase Order");
            } else {
                combinedData.append("productsJSON", JSON.stringify(products));

                fetch('submitPO.php', {
                    method: 'POST',
                    body: combinedData
                })
                .then(response => response.text())
                .then(data => {
                    console.log('Server response:', data);
                    alert(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Error submitting PO: " + error);
                });
            }
        });

        window.removeProduct = function(button) {
            const row = button.closest('tr');
            if (row) {
                row.remove();
            }
        }

        $('#productTableSubmitted tbody').on('click', '.btn-remove', function() {
            $(this).closest('tr').remove();
        });

        $('#productTableSubmitted tbody').on('click', '.btn-edit', function() {
            const $row = $(this).closest('tr');
            if ($(this).text() === "Edit") {
                $row.data('original', {
                    productNumber: $row.find('td').eq(0).text(),
                    quantity: $row.find('td').eq(1).text(),
                    unitPrice: $row.find('td').eq(2).text().replace(/^\$/, ''),
                    description: $row.find('td').eq(3).text()
                });
                $row.find('td').each(function(i) {
                    const val = $(this).text().replace(/^\$/, '');
                    if (i < 4) {
                        $(this).html(`<input type="text" class="form-control" style="width:100%;" value="${val}">`);
                    }
                });
                $(this).text("Save");
                if ($row.find('.btn-cancel').length === 0) {
                    $(this).after('<button type="button" class="btn btn-warning btn-cancel" style="margin-left:5px;">Cancel</button>');
                }
            } else {
                const inputs = $row.find('input');
                const productNumber = $(inputs[0]).val();
                const quantity = parseFloat($(inputs[1]).val());
                const unitPrice = parseFloat($(inputs[2]).val());
                let description = $(inputs[3]).val().trim();

                if (description) {
                    $.get('getDescription.php', { productNumber: productNumber }, function(data) {
                        let dbDescription = '';
                        try {
                            let rows = (typeof data === 'string') ? JSON.parse(data) : data;
                            if (Array.isArray(rows) && rows.length > 0) {
                                dbDescription = rows[0].Details ?? '';
                            }
                        } catch (e) {
                            dbDescription = '';
                        }
                        updateRow($row, productNumber, quantity, unitPrice, dbDescription);
                    });
                } else {
                    updateRow($row, productNumber, quantity, unitPrice, description);
                }
            }
        });

        function updateRow($row, productNumber, quantity, unitPrice, description) {
            const parsedUnitPrice = parseFloat(unitPrice) || 0;
            const parsedQuantity = parseInt(quantity) || 0;
            const total = parsedUnitPrice * parsedQuantity;
            
            $row.html(`
                <td>${productNumber}</td>
                <td>${parsedQuantity}</td>
                <td>$${parsedUnitPrice.toFixed(3)}</td>
                <td>${description}</td>
                <td>$${total.toFixed(3)}</td>
                <td>
                    <button type="button" class="btn btn-secondary btn-edit">Edit</button>
                    <button type="button" class="btn btn-danger btn-remove">Remove</button>
                </td>
            `);
        }

        $('#productTableSubmitted tbody').on('click', '.btn-cancel', function() {
            const $row = $(this).closest('tr');
            const orig = $row.data('original');
            if (orig) {
                $row.html(`
                    <td>${orig.productNumber}</td>
                    <td>${orig.quantity}</td>
                    <td>$${parseFloat(orig.unitPrice).toFixed(3)}</td>
                    <td>${orig.description}</td>
                    <td>$${(parseFloat(orig.quantity) * parseFloat(orig.unitPrice)).toFixed(3)}</td>
                    <td>
                        <button type="button" class="btn btn-secondary btn-edit">Edit</button>
                        <button type="button" class="btn btn-danger btn-remove">Remove</button>
                    </td>
                `);
            }
        });
    </script>
</body>
</html>