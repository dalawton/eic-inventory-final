<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Display all Batteries and their statuses
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

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection parameters
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

$search = '';
$where = '';
$params = [];

if (isset($_GET['searchSN']) && $_GET['searchSN'] !== '') {
    $search = $_GET['searchSN'];
    $where = "WHERE SN LIKE ?";
    $params = ["%$search%"];
}
if (isset($_GET['batterySelect']) && $_GET['batterySelect'] !== '') {
    $search = $_GET['batterySelect'];
    $where = "WHERE BatteryName LIKE ?";
    $params = ["%$search"];
}
if (isset($_GET['statusSelect']) && $_GET['statusSelect'] !== '') {
    $search = $_GET['statusSelect'];
    $where = "WHERE Status LIKE ?";
    $params = ["%$search"];
}

$sql = "SELECT SN, BatteryName, Status FROM dbo.All_Batteries $where ORDER BY SN DESC";
$stmt = sqlsrv_query($conn, $sql, $params);

$sqlBatteryType = "SELECT BatteryName FROM dbo.Battery";
$batteriesStmt = sqlsrv_query($conn, $sqlBatteryType);

$sqlStatusType = "SELECT DISTINCT Status FROM dbo.All_Batteries";
$statusStmt = sqlsrv_query($conn, $sqlStatusType);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Batteries</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylePurchaseOrder.css">
    <style>
        .product-table th:nth-child(1),
        .product-table td:nth-child(1) {
            max-width: 100px;
            width: 100px;
        }
        
        .product-table th:nth-child(2),
        .product-table td:nth-child(2) {
            max-width: 100px;
            width: 100px;
        }

        .product-table th:nth-child(3),
        .product-table td:nth-child(3) {
            max-width: 100px;
            width: 100px;
        }

        .product-table th:nth-child(4),
        .product-table td:nth-child(4) {
            max-width: 100px;
            width: 100px;
            text-align: center;
        }

        .product-table th:nth-child(5),
        .product-table td:nth-child(5) {
            max-width: 100px;
            width: 100px;
        }

        .product-table th:nth-child(6),
        .product-table td:nth-child(6) {
            max-width: 100px;
            width: 100px;
            text-align: center;
        }

        .product-table td span.status-label {
            padding: 2px 8px;
            border-radius: 4px;
            color: #fff;
            font-size: 0.95em;
        }

        .product-table td span.status-shipped_out { background: #4caf50; }
        .product-table td span.status-needs_repair { background: #f44336; }
        .product-table td span.status-in_house { background: #f8a232ff; }
        .product-table td span.status-used { background: #839172ff; }
        .product-table td span.status-inbound { background: #1f55ebff; }
        .product-table td span.status-other { background: #888; }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1>All Batteries</h1>
        </div>
        <div class="form-content">
                <form class="form-control" method="get" action="">
                    <input type="search" style="width: 27%; margin-right: 10px;" class="form-control" name="searchSN" placeholder="Search by Serial Number" value="<?php echo htmlspecialchars($_GET['searchSN'] ?? '') ?>">
                    <select name="batterySelect" style="width: 27%; margin-right: 10px;" class="form-control" id="batterySelect" onchange="this.form.submit()">
                        <option value="">-- Filter by Battery Type --</option>
                        <?php while ($row = sqlsrv_fetch_array($batteriesStmt, SQLSRV_FETCH_ASSOC)) : ?>
                            <option value="<?php echo htmlspecialchars($row['BatteryName']) ?>">
                                <?php echo htmlspecialchars($row['BatteryName']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <select name="statusSelect" style="width: 27%; margin-right: 10px;" class="form-control" id="statusSelect" onchange="this.form.submit()">
                        <option value="">-- Filter by Status --</option>
                        <?php while ($row = sqlsrv_fetch_array($statusStmt, SQLSRV_FETCH_ASSOC)) : ?>
                            <option value="<?php echo htmlspecialchars($row['Status']) ?>">
                                <?php echo htmlspecialchars($row['Status']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-secondary">Search</button>
                    <?php if ($search) : ?>
                        <a href="trackBatteries.php">Clear</a>
                    <?php endif; ?>
                </form>
            <div class="product-table-container">
                <table class="product-table" name="partsTable" id="partsTable" border="1">
                    <tr>
                        <?php
                        echo "<div class='table-header'>";
                        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                        if ($row) {
                            foreach (array_keys($row) as $colName) {
                                    echo "<th>" . htmlspecialchars($colName) . "</th>";
                            }
                            echo "</div>";
                            echo "</tr>";
                            echo "<tr class='text'>";
                            foreach ($row as $colName => $value) {
                                $statusType = $row['Status'];
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

                                if (strtolower($colName) === 'status') {
                                    echo "<td><span class='status-label $statusClass'>" . htmlspecialchars(ucfirst($row['Status'])) . "</span></td>";
                                } else {
                                    echo "<td>" . htmlspecialchars((string)$value) . "</td>";
                                }
                            }
                            echo "</tr>";

                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                                echo "<tr class='text'>";
                                foreach ($row as $colName => $value) {
                                    $statusType = $row['Status'];
                                    $statusClass = "status-other";
                                    if ($statusType === "SHIPPED") {
                                        $statusClass = "status-shipped_out";
                                    } elseif ($statusType === "NEEDS REPAIR") {
                                        $statusClass = "status-needs_repair";
                                    } elseif ($statusType === "IN-HOUSE") {
                                        $statusClass = "status-in_house";
                                    } elseif ($statusType === "INBOUND") {
                                        $statusClass = "status-inbound";
                                    } elseif ($statusType === "USED") {
                                        $statusClass = "status-used";
                                    } else {
                                        $statusClass = "status-other";
                                    }

                                    if (strtolower($colName) === 'status') {
                                        echo "<td><span class='status-label $statusClass'>" . htmlspecialchars(ucfirst($row['Status'])) . "</span></td>";
                                    } else {
                                        echo "<td>" . htmlspecialchars((string)$value) . "</td>";
                                    }
                                }
                            echo "</tr>";
                            }
                        } else {
                            echo "<td colspan='100%'>No batteries found.</td></tr>";
                        }
                        ?>
                </table>
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
</body>
</html>

<?php
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>

