<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to have form displaying links and connections 
 * between batteries
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

if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'getComponents') {
        $parentSN = $_POST['parentSN'];
        
        $sqlComponents = "SELECT bc.ComponentSN, bc.DateUsed, ab.BatteryName, ab.Status
                         FROM dbo.Battery_Components bc 
                         JOIN dbo.All_Batteries ab ON bc.ComponentSN = ab.SN 
                         WHERE bc.ParentSN = ?";
        $componentsStmt = sqlsrv_query($conn, $sqlComponents, [$parentSN]);
        
        $components = [];
        $componentType = "";
        while ($row = sqlsrv_fetch_array($componentsStmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['DateUsed']) && is_object($row['DateUsed'])) {
                $row['DateUsed'] = $row['DateUsed']->format('m-d-Y');
            }
            $components[] = $row;
            $componentType = $row['BatteryName'];
        }
        
        $sqlAvailable = "SELECT SN, BatteryName, Status FROM dbo.All_Batteries WHERE Status = 'IN-HOUSE' AND BatteryName = ? ORDER BY SN";
        $availableStmt = sqlsrv_query($conn, $sqlAvailable, [$componentType]);
        
        $availableBatteries = [];
        while ($row = sqlsrv_fetch_array($availableStmt, SQLSRV_FETCH_ASSOC)) {
            $availableBatteries[] = $row;
        }
        
        echo json_encode([
            'components' => $components,
            'availableBatteries' => $availableBatteries
        ]);
        exit;
    }
    
    if ($_POST['action'] === 'updateComponent') {
        $parentSN = $_POST['parentSN'];
        $oldComponentSN = $_POST['oldComponentSN'];
        $newComponentSN = $_POST['newComponentSN'];
        
        $sqlUpdate = "UPDATE dbo.Battery_Components 
                     SET ComponentSN = ? 
                     SET DateUsed = Date
                     WHERE ParentSN = ? AND ComponentSN = ?";
        $updateStmt = sqlsrv_query($conn, $sqlUpdate, [$newComponentSN, $parentSN, $oldComponentSN]);
        
        if ($updateStmt) {
            echo json_encode(['success' => true, 'message' => 'Component updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update component']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'removeComponent') {
        $parentSN = $_POST['parentSN'];
        $componentSN = $_POST['componentSN'];
        
        $sqlDelete = "DELETE FROM dbo.Battery_Components 
                     WHERE ParentSN = ? AND ComponentSN = ?";
        $deleteStmt = sqlsrv_query($conn, $sqlDelete, [$parentSN, $componentSN]);
        
        if ($deleteStmt) {
            echo json_encode(['success' => true, 'message' => 'Component removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove component']);
        }
        exit;
    }
}

$sqlBatteryType = "SELECT BatteryName FROM dbo.Battery";
$batteriesStmt = sqlsrv_query($conn, $sqlBatteryType);

$search = '';
$where = '';
$params = [];

if (isset($_GET['batterySelect']) && $_GET['batterySelect'] !== '') {
    $search = $_GET['batterySelect'];
    $where = "WHERE BatteryName LIKE ?";
    $params = ["%$search"];
}

$sql = "SELECT SN FROM dbo.All_Batteries $where ORDER BY SN DESC";
$stmt = sqlsrv_query($conn, $sql, $params);

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Battery Component Links</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="styleCheckout.css">
        <style>
            .clickable-row {
                cursor: pointer;
                transition: background-color 0.2s;
            }
            .clickable-row:hover {
                background-color: #f5f5f5;
            }
            .selected-row {
                background-color: #e3f2fd !important;
            }
            .components-section {
                margin-top: 20px;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
                background-color: #f9f9f9;
                display: none;
            }
            .component-item {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
                padding: 10px;
                border: 1px solid #ccc;
                border-radius: 3px;
                background-color: white;
            }
            .component-info {
                flex: 1;
                margin-right: 10px;
            }
            .component-actions {
                display: block;
                gap: 5px;
            }
            .btn {
                padding: 5px 10px;
                border: none;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
            }
            .btn-update {
                background-color: #4CAF50;
                color: white;
            }
            .btn-remove {
                background-color: #f44336;
                color: white;
            }
            .btn:hover {
                opacity: 0.8;
            }
            .update-form {
                display: none;
                margin-top: 10px;
            }
            .update-form select {
                margin-right: 10px;
                padding: 5px;
            }
            .message {
                padding: 10px;
                margin: 10px 0;
                border-radius: 3px;
            }
            .success {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
        </style>
    </head>
    <body>
        <div class="main-container">
            <div class="header">
                <h1>Battery Component Links</h1>
                <p>Select battery type to locate desired connection</p>
                <p>Click on a battery row to view and manage its components</p>
            </div>
            <div class="form-content">
                <form class="form-section" method="get" action="">
                    <div class="form-grid">
                        <select name="batterySelect" class="form-control" id="batterySelect" onchange="this.form.submit()">
                            <option value="">-- Filter by Battery Type --</option>
                            <?php while ($row = sqlsrv_fetch_array($batteriesStmt, SQLSRV_FETCH_ASSOC)) : ?>
                                <option value="<?php echo htmlspecialchars($row['BatteryName']) ?>" 
                                        <?php echo (isset($_GET['batterySelect']) && $_GET['batterySelect'] === $row['BatteryName']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['BatteryName']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
                <div class="product-table-container">
                    <table class="product-table" name="batteryTable" id="batteryTable">
                        <thead>
                            <?php
                            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                            if ($row) {
                                echo "<tr>";
                                foreach (array_keys($row) as $colName){
                                    echo "<th>" . htmlspecialchars($colName) . "</th>";
                                    }
                                echo "</tr>";
                                ?>
                            </thead>
                            <tbody>
                                <?php
                                echo "<tr class='clickable-row' data-sn='" . htmlspecialchars($row['SN']) . "'>";
                                foreach ($row as $value) {
                                    echo "<td>" . htmlspecialchars((string)$value) . "</td>";
                                }
                                echo "</tr>";

                                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                                    echo "<tr class='clickable-row' data-sn='" . htmlspecialchars($row['SN']) . "'>";
                                    foreach ($row as $value) {
                                        echo "<td>" . htmlspecialchars((string)$value) . "</td>";
                                    }
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='100%'>No batteries found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div id="componentsSection" class="components-section">
                    <h3>Components for Battery SN: <span id="selectedSN"></span></h3>
                    <div id="messageArea"></div>
                    <div id="componentsList"></div>
                </div>
            </div>
        </div>

        <script>
            let selectedParentSN = null;

            document.addEventListener('DOMContentLoaded', function() {
                const rows = document.querySelectorAll('.clickable-row');
                rows.forEach(row => {
                    row.addEventListener('click', function() {
                        document.querySelectorAll('.selected-row').forEach(r => r.classList.remove('selected-row'));
                        
                        this.classList.add('selected-row');
                        
                        selectedParentSN = this.getAttribute('data-sn');
                        
                        loadComponents(selectedParentSN);
                    });
                });
            });

            function loadComponents(parentSN) {
                document.getElementById('selectedSN').textContent = parentSN;
                document.getElementById('componentsSection').style.display = 'block';
                
                document.getElementById('messageArea').innerHTML = '';
                
                const formData = new FormData();
                formData.append('action', 'getComponents');
                formData.append('parentSN', parentSN);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    displayComponents(data.components, data.availableBatteries);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Error loading components', 'error');
                });
            }

            function displayComponents(components, availableBatteries) {
                const componentsList = document.getElementById('componentsList');
                componentsList.innerHTML = '';
                
                if (components.length === 0) {
                    componentsList.innerHTML = '<p>No components found for this battery.</p>';
                    return;
                }
                
                components.forEach(component => {
                    const componentDiv = document.createElement('div');
                    componentDiv.className = 'component-item';
                    componentDiv.innerHTML = `
                        <div class="component-info">
                            <strong>Component SN:</strong> ${component.ComponentSN} | 
                            <strong>Battery:</strong> ${component.BatteryName} | 
                            <strong>Status:</strong> ${component.Status} |
                            <strong>Date Used:</strong> ${component.DateUsed}
                        </div>
                        <div id="component-actions" class="component-actions">
                            <button class="btn btn-update" onclick="showUpdateForm('${component.ComponentSN}')">Update</button>
                            <button class="btn btn-remove" onclick="removeComponent('${component.ComponentSN}')">Remove</button>
                        </div>
                        <div id="updateForm_${component.ComponentSN}" class="update-form">
                            <select id="newSN_${component.ComponentSN}">
                                ${availableBatteries.map(battery => 
                                    `<option value="${battery.SN}">${battery.SN} - ${battery.BatteryName} (${battery.Status})</option>`
                                ).join('')}
                            </select>
                            <button class="btn btn-update" onclick="updateComponent('${component.ComponentSN}')">Save</button>
                            <button class="btn" onclick="hideUpdateForm('${component.ComponentSN}')">Cancel</button>
                        </div>
                    `;
                    componentsList.appendChild(componentDiv);
                });
            }

            function showUpdateForm(componentSN) {
                document.getElementById(`updateForm_${componentSN}`).style.display = 'block';
                document.getElementById(`component-actions`).style.display = 'none';
            }

            function hideUpdateForm(componentSN) {
                document.getElementById(`updateForm_${componentSN}`).style.display = 'none';
                document.getElementById(`component-actions`).style.display = 'block';
            }

            function updateComponent(oldComponentSN) {
                const newComponentSN = document.getElementById(`newSN_${oldComponentSN}`).value;
                
                const formData = new FormData();
                formData.append('action', 'updateComponent');
                formData.append('parentSN', selectedParentSN);
                formData.append('oldComponentSN', oldComponentSN);
                formData.append('newComponentSN', newComponentSN);
                formData.append('DateUsed', date);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        loadComponents(selectedParentSN);
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Error updating component', 'error');
                });
            }

            function removeComponent(componentSN) {
                if (!confirm('Are you sure you want to remove this component?')) {
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'removeComponent');
                formData.append('parentSN', selectedParentSN);
                formData.append('componentSN', componentSN);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        loadComponents(selectedParentSN);
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Error removing component', 'error');
                });
            }

            function showMessage(message, type) {
                const messageArea = document.getElementById('messageArea');
                messageArea.innerHTML = `<div class="message ${type}">${message}</div>`;
            
                if (type === 'success') {
                    setTimeout(() => {
                        messageArea.innerHTML = '';
                    }, 3000);
                }
            }
        </script>
    </body>
</html>