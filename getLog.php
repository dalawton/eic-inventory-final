<?php
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

$sql = "SELECT TOP 100 * FROM dbo.InventoryLog ORDER BY ActionTime DESC";
$stmt = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory & Repair Action Log</title>
    <link rel="stylesheet" href="stylePurchaseOrder.css">
    <style>
        .log-table th, .log-table td {
            text-align: center;
            vertical-align: middle;
        }
        .log-table th {
            background: #f0f4fa;
            color: #333;
        }
        .log-table tr:nth-child(even) {
            background: #f9fbfd;
        }
        .log-table tr.reverted {
            background: #ffeaea;
            color: #a33;
        }
        .log-table td span.reverted-label {
            color: #a33;
            font-weight: bold;
        }
        .log-table td span.action-label {
            padding: 2px 8px;
            border-radius: 4px;
            color: #fff;
            font-size: 0.95em;
        }
        .log-table td span.action-add { background: #4caf50; }
        .log-table td span.action-update { background: #2196f3; }
        .log-table td span.action-delete { background: #f44336; }
        .log-table td span.action-other { background: #888; }
        .log-table td span.table-inventory { color: #2196f3; font-weight: bold; }
        .log-table td span.table-repairs { color: #ff9800; font-weight: bold; }
        .log-table td .btn[disabled] {
            opacity: 0.5;
            pointer-events: none;
        }
        .log-table-container {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1>Inventory & Repair Action Log</h1>
            <p>View all add, update, and delete actions performed on inventory and repairs.</p>
            <br>
            <p><strong>NOTE: THE REVERT BUTTON MAY NOT WORK IF OPERATIONS HAVE BEEN PERFORMED USING CHANGED DATA</strong></p>
        </div>
        <div class="form-content">
            <div class="form-section">
                <div class="product-table-container log-table-container">
                    <div class="table-header">
                        <h3>Recent Actions</h3>
                    </div>
                    <table class="product-table log-table">
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Table</th>
                            <th>Product Number</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Repair Serial #</th>
                            <th>Repair Requester</th>
                            <th>Repair Details</th>
                            <th>Repair Status</th>
                            <th>Reverted</th>
                            <th>Revert</th>
                        </tr>
                        <?php
                        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                            $isReverted = isset($row['Reverted']) && $row['Reverted'];
                            $actionType = strtolower($row['ActionType']);
                            $tableType = strtolower($row['TableAffected'] ?? '');
                            $actionClass = "action-other";
                            if ($actionType === "add") $actionClass = "action-add";
                            elseif ($actionType === "update") $actionClass = "action-update";
                            elseif ($actionType === "delete") $actionClass = "action-delete";
                            $tableClass = $tableType === "repairs" ? "table-repairs" : "table-inventory";
                            echo "<tr" . ($isReverted ? " class='reverted'" : "") . ">";
                            echo "<td>" . htmlspecialchars($row['ActionTime']->format('Y-m-d H:i:s')) . "</td>";
                            echo "<td><span class='action-label $actionClass'>" . htmlspecialchars(ucfirst($row['ActionType'])) . "</span></td>";
                            echo "<td><span class='$tableClass'>" . htmlspecialchars($row['TableAffected'] ?? '') . "</span></td>";
                            echo "<td>" . htmlspecialchars($row['ProductNumber'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['Description'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['Quantity'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['RepairSerialNumber'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['RepairRequester'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['RepairDetails'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['RepairStatus'] ?? '') . "</td>";
                            echo "<td>";
                            if ($isReverted) {
                                echo "<span class='reverted-label'>Yes</span>";
                            } else {
                                echo "No";
                            }
                            echo "</td>";
                            echo "<td>";
                            if (!$isReverted) {
                                echo "<form method='POST' action='revertLog.php' style='margin:0;'>
                                        <input type='hidden' name='logId' value='" . htmlspecialchars($row['LogId']) . "'>
                                        <button type='submit' class='btn btn-secondary' onclick=\"return confirm('Are you sure you want to revert this action?');\">Revert</button>
                                      </form>";
                            } else {
                                echo "<span style='color:gray;'>Reverted</span>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </table>
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
<?php
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>