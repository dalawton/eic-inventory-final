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

$sql = "SELECT BatteryName FROM dbo.Battery";
$stmt = sqlsrv_query($conn, $sql);
$batteryOptions = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $batteryOptions[] = $row['BatteryName'];
}
if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}

$selectedBattery = $_GET['batteryName'] ?? '';
$items = [];

if ($selectedBattery) {
    $sql = "SELECT * FROM dbo.PartsForBatteries WHERE BatteryName = ?";
    $stmt = sqlsrv_query($conn, $sql, [$selectedBattery]);
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $items[] = $row;
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'getComponentsForPart') {
    $partNumber = $_POST['partNumber'] ?? '';
    
    $partNumberLower = strtolower($partNumber);
    $components = [];
    
    if (strpos($partNumberLower, 'subpack') !== false) {
        
        $sql1 = "SELECT SN FROM dbo.All_Batteries 
                WHERE BatteryName LIKE '$partNumber' 
                AND Status = 'IN-HOUSE'
                ORDER BY SN";
        $stmt = sqlsrv_query($conn, $sql1);
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $components[] = $row;
            }
        }
        
    } elseif (strpos($partNumberLower, 'cellpack') !== false) {
        
        $sql2 = "SELECT SN FROM dbo.All_Batteries 
                WHERE BatteryName LIKE '$partNumber' 
                AND Status = 'IN-HOUSE'
                ORDER BY SN";
        $stmt = sqlsrv_query($conn, $sql2);
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $components[] = $row;
            }
        }
    } elseif (strpos($partNumberLower, 'module') !== false) {
        
        $sql3 = "SELECT SN FROM dbo.All_Batteries 
                WHERE BatteryName LIKE '$partNumber' 
                AND Status = 'IN-HOUSE'
                ORDER BY SN";
        $stmt = sqlsrv_query($conn, $sql3);
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $components[] = $row;
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($components);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="styleCheckout.css">
        <title>Log Battery Changes</title>
        <script 
            src="https://code.jquery.com/jquery-3.6.0.min.js">
        </script>
        <link 
            href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" 
            rel="stylesheet" /> 
        <script 
            src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js">
        </script>
        <style>
            .component-section {
                margin: 20px 0;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                background-color: #f9f9f9;
            }
            .component-section h3 {
                margin-top: 0;
                color: #333;
            }
            .serial-select {
                margin: 5px 0;
            }
            .selected-components {
                margin-top: 10px;
                padding: 10px;
                background-color: #e8f5e8;
                border-radius: 3px;
            }
            .component-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 5px 0;
                border-bottom: 1px solid #ccc;
            }
            .remove-component {
                background-color: #dc3545;
                color: white;
                border: none;
                padding: 2px 8px;
                border-radius: 3px;
                cursor: pointer;
            }
            .component-row {
                background-color: #f0f8ff;
                border-left: 4px solid #007bff;
            }
            .component-checkboxes {
                padding: 10px;
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 3px;
                margin-top: 5px;
            }
            .component-checkboxes h5 {
                margin: 0 0 10px 0;
                color: #333;
                font-weight: bold;
            }
            .checkbox-group {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 10px;
                max-height: 150px;
                overflow-y: auto;
            }
            .checkbox-item {
                display: flex;
                align-items: center;
                padding: 5px;
                background-color: white;
                border: 1px solid #ccc;
                border-radius: 3px;
            }
            .checkbox-item input[type="checkbox"] {
                margin-right: 8px;
            }
            .checkbox-item label {
                font-size: 12px;
                margin: 0;
                cursor: pointer;
            }
        </style>
    </head>
    <body>
        <div class="main-container">
            <div class="header">
                <h1>Submit Battery Steps</h1>
                <p>Select model of the battery completed:</p>
                <p>Update the amount of parts used and input serial number to add battery into database.</p>
            </div>
            <div class="navigation" style="background-color: #f9f9f9;">
                <button onclick="location.href='barcodeScanning.php'" class="btn btn-secondary">Use Scanner</button>
            </div>
            <div class="form-content" style="padding-top: 15px;">
                <form class="form-section" method="GET" id="batterySelectForm">
                    <div class="form-group">
                        <label for="batteryName">Select Battery:</label>
                        <select name="batteryName" class="form-control" id="batteryName">
                            <option value="">--Select Battery--</option>
                            <?php foreach ($batteryOptions as $Name) : ?>
                            <option value="<?php echo htmlspecialchars($Name) ?>" 
                                <?php echo $selectedBattery === $Name ? 'selected' : '' ?>> 
                                <?php echo htmlspecialchars($Name) ?>
                            </option>
                            <?php endforeach; ?>
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
                <form id="existingBatteryForm" class="battery-form" method="POST" 
                    action="updateInventoryComplete.php" style="display:none;">
                    <div class="form-section">
                        <div class="table-header">
                            <h2>Parts Used for Battery 
                                <?php echo htmlspecialchars($selectedBattery) ?> </h2>
                        </div>
                        <div class="desc-info">
                            <p>Update the amount used of each part for the battery</p>
                            <p>If a certain listed part is not used, change amount to 0</p>
                            <p>For subpack/module/cellpack parts, select the specific serial numbers being used</p>
                            <p>Add not listed parts at the bottom if used</p>
                            <p>Include the serial number of the created battery and submit form</p>
                        </div>
                        <input type="hidden" name="selectedBattery" 
                            value="<?php echo htmlspecialchars($selectedBattery) ?>">
                        
                        <table class="product-table" border="1">
                            <tr>
                                <th>Part Number</th>
                                <th>Description</th>
                                <th>Amount Used</th>
                                <th>Component Selection</th>
                            </tr>
                            <?php foreach ($items as $index => $item) : 
                                $partNumber = $item['PN'] ?? '';
                                $isComponentPart = false;
                                $componentType = '';
                                
                                $partNumberLower = strtolower($partNumber);
                                
                                if (strpos($partNumberLower, 'subpack') !== false) {
                                    $isComponentPart = true;
                                    $componentType = $partNumber;
                                } elseif (strpos($partNumberLower, 'cellpack') !== false) {
                                    $isComponentPart = true;
                                    $componentType = $partNumber;
                                }  elseif (strpos($partNumberLower, 'module') !== false) {
                                    $isComponentPart = true;
                                    $componentType = $partNumber;
                                }
                            ?>
                            <tr <?php echo $isComponentPart ? 'class="component-row"' : ''; ?>>
                                <td><?php echo htmlspecialchars($partNumber) ?></td>
                                <td style="width:20px;" id="desc-<?php echo $index ?>">
                                    <?php echo htmlspecialchars($item['Description'] ?? '') ?></td>
                                <td><input type="number" class="form-control" style="color:#7a7d80"
                                    name="amount_Used[]" 
                                    value="<?php echo htmlspecialchars($item['Amount']) ?>"></td>
                                <td>
                                    <?php if ($isComponentPart): ?>
                                        <div class="component-checkboxes">
                                            <h5>Select <?php echo ucfirst($componentType); ?>s:</h5>
                                            <div class="checkbox-group" id="components-<?php echo $index ?>" data-part="<?php echo htmlspecialchars($partNumber) ?>">
                                                <em>Loading available <?php echo $componentType; ?>s...</em>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #666;">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <tr id="add-part-row">
                                <td>
                                    <input type="text" class="form-control" id="newPartNumber" placeholder="Part Number">
                                </td>
                                <td>
                                    <input type="text" class="form-control" id="newPartDescription" placeholder="Description">
                                </td>
                                <td>
                                    <input type="number" class="form-control" id="newAmountUsed" min="0">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-secondary" id="addPartBtn">Add Part</button>
                                </td>
                            </tr>
                        </table>
                        <br>
                        <div class="form-section">
                            <div class="form-group">
                                <label for="serialNumber">Serial Number: </label>
                                <input type="text" class="form-control" id="serialNumber" name="serialNumber" placeholder="Enter serial number">
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button type="submit" id="submitCompleted" class="btn btn-secondary">Mark as Completed</button>
                        </div>
                    </div>
                </form>
                <form id="newBatteryForm" method="POST" action="addNewBattery.php" style="display:none;">
                    <div class="form-section">
                        <div class="form-group">
                            <label for="newBatteryName">New Battery Name</label>
                            <input type="text" id="newBatteryName" name="newBatteryName" class="form-control">
                            <br>
                            <select name="complete" id="complete" class="form-control">
                                <option value="">-- Is this a full battery? --</option>
                                <option value="Yes">Yes - Full Battery</option>
                                <option value="No">No - Part of a Full Battery</option>
                            </select>
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
                    </div>
                </form>  
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

<script>
$(document).ready(function() {
    
    function showFormForSelection(val) {
        $('.battery-form').hide();
        
        if (!val) return;
        
        if (val === 'newBattery') {
            $('#newBatteryForm').show();
        } else {
            $('#existingBatteryForm').show();
            loadComponentsForParts();
        }
    }
    
    function loadComponentsForParts() {
        $('.component-row').each(function(index) {
            const $row = $(this);
            const componentContainer = $row.find('.checkbox-group');
            
            if (componentContainer.length > 0) {
                const partNumber = componentContainer.data('part');
                
                $.post('', {
                    action: 'getComponentsForPart',
                    partNumber: partNumber
                }, function(data) {
                    if (data && data.length > 0) {
                        displayComponentCheckboxes(componentContainer, data, partNumber);
                    } else {
                        componentContainer.html('<em>No available components found</em>');
                    }
                }, 'json').fail(function() {
                    componentContainer.html('<em>Error loading components</em>');
                });
            }
        });
    }
    
    function displayComponentCheckboxes(container, components, partNumber) {
        let html = '';
        
        components.forEach(function(component) {
            const checkboxId = 'component_' + partNumber.replace(/[^a-zA-Z0-9]/g, '_') + '_' + component.SN.replace(/[^a-zA-Z0-9]/g, '_');
            html += `
                <div class="checkbox-item">
                    <input type="checkbox" 
                           id="${checkboxId}" 
                           name="component_sns[]" 
                           value="${component.SN}"
                           data-part="${partNumber}"
                           onchange="updateSelectedComponents()">
                    <label for="${checkboxId}">
                        ${component.SN}<br>
                        <small style="color: #666;">${partNumber}</small>
                    </label>
                </div>
            `;
        });
        
        container.html(html);
    }
    
    window.updateSelectedComponents = function() {
    };

    showFormForSelection($('#batteryName').val());

    $('#batteryName').on('change', function() {
        showFormForSelection($(this).val());
    });
    
    $('#existingBatteryForm').on('submit', function(e) {
        const serialNumber = $('#serialNumber').val().trim();
        if (!serialNumber) {
            e.preventDefault();
            alert('Please enter a serial number for the completed battery.');
            return false;
        }
        
        let missingComponents = false;
        $('.component-row').each(function() {
            const $row = $(this);
            const amountUsed = parseInt($row.find('input[name="amount_Used[]"]').val()) || 0;
            const selectedComponents = $row.find('input[name="component_sns[]"]:checked').length;
            
            if (amountUsed > 0 && selectedComponents === 0) {
                missingComponents = true;
            }
        });
        
        if (missingComponents) {
            e.preventDefault();
            alert('Please select specific components for all subpack/cellpack parts that are being used (amount > 0).');
            return false;
        }
    });

    $('#existingBatteryForm').on('submit', function(e) {
        var selectedComponents = [];
        $('input[name="component_sns[]"]:checked').each(function() {
            selectedComponents.push($(this).val());
        });
        
        if (selectedComponents.length > 0) {
            $('<input>').attr({
                type: 'hidden',
                name: 'selectedComponentSNs',
                value: selectedComponents.join(',')
            }).appendTo(this);
        }
    });
})
</script>

<script>
$(document).ready(function() {
    $('table.product-table tr').each(function(index) {
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

    $('#addPartBtn').click(function() {
        var partNumber = $('#newPartNumber').val().trim();
        var description = $('#newPartDescription').val().trim();
        var amountUsed = $('#newAmountUsed').val().trim();

        if (partNumber === '' || amountUsed === '') {
            alert('Please enter both Part Number and Amount Used for the new part.');
            return;
        }

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
                <td><span style="color: #666;">N/A</span></td>
            </tr>
        `);
        $('#newPartNumber').val('');
        $('#newPartDescription').val('');
        $('#newAmountUsed').val('');
    }

    $('#addStepBtn').click(function() {
        var partNumber = $('#stepPartNumber').val().trim();
        var description = $('#stepPartDescription').val().trim();
        var amountUsed = $('#stepAmountUsed').val().trim();

        if (partNumber === '' || amountUsed === '') {
            alert('Please enter both Part Number and Amount Used for the completed build.');
            return;
        }

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
        $('#stepPartNumber').val('');
        $('#stepPartDescription').val('');
        $('#stepAmountUsed').val('');
    }

    $('#batteryName').on('change', function() {
        $('#batterySelectForm').submit();
    });
});
</script>