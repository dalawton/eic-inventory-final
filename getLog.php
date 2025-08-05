<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to display a log of all the interactions on the website
 *
 * PHP version 8
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Get_Files
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

$sql = "SELECT TOP 100 * FROM dbo.InventoryLog ORDER BY ActionTime DESC";
$stmt = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory & Repair Action Log</title>
    <link rel="stylesheet" href="styleRepair.css">
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
        .log-table td span.status-label {
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
        .log-table td span.table-batteries { color: #308859ff; font-weight: bold; }
        .log-table td span.table-other { color: #3d3d3dff; font-weight: bold; }
        .log-table td span.status-shipped_out { background: #4caf50; }
        .log-table td span.status-needs_repair { background: #f44336; }
        .log-table td span.status-in_house { background: #f8a232ff; }
        .log-table td span.status-used { background: #839172ff; }
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
                            <th>Repair Requester</th>
                            <th>Status</th>
                        </tr>
                        <?php
                        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                            $actionType = strtolower($row['ActionType']);
                            $tableType = strtolower($row['TableAffected'] ?? '');
                            $tableClass = "table-other";
                            $actionClass = "action-other";
                            $statusType = $row['Status'] ?? '';
                            $statusClass = "status-other";
                            if ($statusType === "SHIPPED") {
                                $statusClass = "status-shipped_out";
                            } elseif ($statusType === "NEEDS REPAIR") {
                                $statusClass = "status-needs_repair";
                            } elseif ($statusType === "IN-HOUSE") {
                                $statusClass = "status-in_house";
                            } elseif ($statusType === "USED") {
                                $statusClass = "status-used";
                            } elseif ($statusType === "INBOUND") {
                                $statusClass = "status-inbound";
                            } else {
                                $statusClass = "status-other";
                            }
                            if ($actionType === "add") {
                                $actionClass = "action-add";
                            } elseif ($actionType === "update") {
                                $actionClass = "action-update";
                            } elseif ($actionType === "delete") {
                                $actionClass = "action-delete";
                            }
                            if ($tableType === "repairs") {
                                $tableClass = "table-repairs";
                            } elseif ($tableType === "inventory") {
                                $tableClass = "table-inventory";
                            } elseif ($tableType === "batteries") {
                                $tableClass = "table-batteries";
                            }
                            echo "<td style='width: 10%;'>" . htmlspecialchars($row['ActionTime']->format('Y-m-d')) . "</td>";
                            echo "<td style='width: 10%;'><span class='action-label $actionClass'>" . htmlspecialchars(ucfirst($row['ActionType'])) . "</span></td>";
                            echo "<td style='width: 8%;'><span class='$tableClass'>" . htmlspecialchars($row['TableAffected'] ?? '') . "</span></td>";
                            echo "<td style='width: 14%;'>" . htmlspecialchars($row['ProductNumber'] ?? '') . "</td>";
                            echo "<td style='width: 30%;'>" . htmlspecialchars($row['Description'] ?? '') . "</td>";
                            echo "<td style='width: 4%;'>" . htmlspecialchars($row['Quantity'] ?? '') . "</td>";
                            echo "<td style='width: 10%;'>" . htmlspecialchars($row['RepairRequester'] ?? '') . "</td>";
                            echo "<td style='width: 14%;'><span class='status-label $statusClass'>" . htmlspecialchars($row['Status'] ?? '') . "</span></td>";
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