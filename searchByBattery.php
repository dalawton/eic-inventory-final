<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to filter through parts using battery type
 *
 * PHP version 8
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Get_File
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

$batteriesQuery = "SELECT BatteryName FROM dbo.Battery";
$batteriesStmt = sqlsrv_query($conn, $batteriesQuery);
if ($batteriesStmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}
if (isset($_POST['action']) && $_POST['action'] === 'getBatteryParts') {
    $batteryName = $_POST['batteryName'] ?? 0;
    
    $sql = "SELECT 
                pfb.PN, 
                pfb.Amount as PartsNeeded,
                ISNULL(inv.Details, 'No Description') as Description,
                ISNULL(inv.Amount, 0) as InventoryAmount
            FROM dbo.PartsForBatteries pfb
            LEFT JOIN dbo.Inventory inv ON pfb.PN = inv.PN
            WHERE pfb.BatteryName = ?
            ORDER BY pfb.PN";
    
    $stmt = sqlsrv_query($conn, $sql, [$batteryName]);
    $parts = [];
    
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $parts[] = $row;
        }
    } else {
        error_log("SQL Error: " . print_r(sqlsrv_errors(), true));
        $parts = ['error' => 'Database query failed', 'details' => sqlsrv_errors()];
    }
    
    header('Content-Type: application/json');
    echo json_encode($parts);
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Battery Parts Calculator</title>
    <link rel="stylesheet" href="styleCheckout.css">
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
        .insufficient-stock {
            background-color: #ffe6e6;
        }
        
        .sufficient-stock {
            background-color: #e6ffe6;
        }
        
        .loading {
            display: none;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1>Filter Parts by Battery Type</h1>
        </div>
        <div class="form-content">
            <div class="form-group">
                <label for="batterySelect">Select Battery:</label>
                <select name="batterySelect" class="form-control" id="batterySelect" onchange="loadBatteryParts()">
                    <option value="">-- Select a Battery --</option>
                    <?php while ($row = sqlsrv_fetch_array($batteriesStmt, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?php echo htmlspecialchars($row['BatteryName']) ?>">
                        <?php echo htmlspecialchars($row['BatteryName']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <br>
            <div class="form-group">
                <label for="quantityInput">Number of Batteries:</label>
                <input type="number" id="quantityInput" class="form-control" min="1" value="1" 
                       onchange="calculateRequiredParts()" oninput="calculateRequiredParts()">
            </div>
            
            <div class="loading" id="loading">Loading parts information...</div>
            <br>
            <div class="product-table-container">
                <table class="product-table" id="partsTable" style="display: none;" border="1">
                    <div class="table-header" id="header" style="display:none"></div>
                    <thead>
                        <tr>
                            <th>Part Number</th>
                            <th>Description</th>
                            <th>Parts per Battery</th>
                            <th>Total Parts Needed</th>
                            <th>Current Inventory with Buffer</th>
                            <th>Stock Status</th>
                        </tr>
                    </thead>
                    <tbody id="partsTableBody">
                    </tbody>
                </table>
            </div>
        </div>
        <div class="navigation">
            <button onclick="location.href='checkoutCompletedBuilds.php'" class="btn btn-secondary">
                Go Back
            </button>
            <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">
                Report an Issue
            </button>
        </div>
    </div>

    <script>
        let currentParts = [];
        
        function loadBatteryParts() {
            const batteryName = document.getElementById('batterySelect').value;
            const loading = document.getElementById('loading');
            const table = document.getElementById('partsTable');
            const header = document.getElementById('header');
            
            if (!batteryName) {
                table.style.display = 'none';
                header.style.display = 'none';
                return;
            }
            
            loading.style.display = 'block';
            table.style.display = 'none';
            header.style.display = 'none';
            
            const formData = new FormData();
            formData.append('action', 'getBatteryParts');
            formData.append('batteryName', batteryName);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                return response.text();
            })
            .then(text => {
                
                try {
                    const data = JSON.parse(text);
                    
                    if (Array.isArray(data)) {
                        currentParts = data;
                        displayParts();
                        loading.style.display = 'none';
                        table.style.display = 'table';
                        header.style.display = 'block';
                    } else if (data.error) {
                        console.error('Server error:', data.error);
                        alert('Database error: ' + data.error);
                        loading.style.display = 'none';
                    } else {
                        console.error('Unexpected response format:', data);
                        alert('Unexpected response format. Check console for details.');
                        loading.style.display = 'none';
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Raw response that failed to parse:', text);
                    alert('Invalid JSON response. Check console for details.');
                    loading.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                loading.style.display = 'none';
                alert('Error loading battery parts: ' + error.message);
            });
        }
        
        function displayParts() {
            const tbody = document.getElementById('partsTableBody');
            const quantity = parseInt(document.getElementById('quantityInput').value) || 1;
            
            tbody.innerHTML = '';
            
            if (!Array.isArray(currentParts)) {
                console.error('currentParts is not an array:', currentParts);
                tbody.innerHTML = '<tr><td colspan="6">Error: Invalid data format</td></tr>';
                return;
            }
            
            if (currentParts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">No parts found for this battery</td></tr>';
                return;
            }
            
            currentParts.forEach(part => {
                const totalNeeded = part.PartsNeeded * quantity;
                const inventoryWithBuffer = Math.round(part.InventoryAmount * 1.2);
                const hasEnough = inventoryWithBuffer >= totalNeeded;
                
                const row = document.createElement('tr');
                row.className = hasEnough ? 'sufficient-stock' : 'insufficient-stock';
                
                row.innerHTML = `
                    <td>${escapeHtml(part.PN)}</td>
                    <td>${escapeHtml(part.Description)}</td>
                    <td>${part.PartsNeeded}</td>
                    <td>${totalNeeded}</td>
                    <td>${inventoryWithBuffer}</td>
                    <td>${hasEnough ? 'Sufficient' : 'Insufficient'}</td>
                `;
                
                tbody.appendChild(row);
            });
        }
        
        function calculateRequiredParts() {
            if (currentParts.length > 0) {
                displayParts();
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>