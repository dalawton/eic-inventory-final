<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to display all POs and their respective information and status
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

$search = '';
$where = '';
$params = [];

if (isset($_GET['searchPO']) && $_GET['searchPO'] !== '') {
    $search = $_GET['searchPO'];
    $where = "WHERE PONum LIKE ?";
    $params = ["%$search%"];
} elseif (isset($_GET['searchVendor']) && $_GET['searchVendor'] !== '') {
    $search = $_GET['searchVendor'];
    $where = "WHERE VendorName LIKE ?";
    $params = ["%$search%"];
} elseif (isset($_GET['searchName']) && $_GET['searchName'] !== '') {
    $search = $_GET['searchName'];
    $where = "WHERE Purchaser LIKE ?";
    $params = ["%$search%"];
}
$sql = "SELECT * FROM POs $where ORDER BY PONum DESC";
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Purchase Orders</title>
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
        
        .product-table tr.reverted {
            background: #ffeaea;
            color: #a33;
        }

        .product-table td span.status-ordered { background: #2196f3; }
        .product-table td span.status-received { background: #4caf50; }
        .product-table td span.status-cancelled { background: #f44336; }
        .product-table td span.status-other { background: #888; }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1>All Purchase Orders</h1>
        </div>
        <div class="form-content">
            <form class="form-control" method="get" action="">
                <input type="search" style="width: 29%;" class="form-control" name="searchPO" placeholder="Search PO Number..." value="<?php echo htmlspecialchars($_GET['searchPO'] ?? '') ?>">
                <input type="search" style="width: 29%;" class="form-control" name="searchVendor" placeholder="Search Vendor Name..." value="<?php echo htmlspecialchars($_GET['searchVendor'] ?? '') ?>">
                <input type="search" style="width: 29%;" class="form-control" name="searchName" placeholder="Search Purchaser..." value="<?php echo htmlspecialchars($_GET['searchName'] ?? '') ?>">
                <button type="submit" class="btn btn-secondary">Search</button>
                <?php if ($search) : ?>
                    <a href="TrackPO.php">Clear</a>
                <?php endif; ?>
            </form>
            <div class="product-table-container">
                <table class="product-table" border="1">
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

                            foreach ($row as $colName => $value) {
                                $statusType = strtolower($row['Status']);
                                $isCancelled = ($row['Status'] === "Cancelled");
                                echo "<tr" . ($isCancelled ? " class='reverted'" : "text") . ">";
                                $statusClass = "status-other";
                                if ($statusType === "ordered") {
                                    $statusClass = "status-ordered";
                                } elseif ($statusType === "received") {
                                    $statusClass = "status-received";
                                } elseif ($statusType === "cancelled") {
                                    $statusClass = "status-cancelled";
                                } else {
                                    $statusClass = "status-other";
                                }

                                if ($value instanceof DateTime) {
                                    echo "<td>" . htmlspecialchars($value->format('Y-m-d')) . "</td>";
                                } elseif (strtolower($colName) === 'price') {
                                    echo "<td>$" . number_format((float)$value, 2) . "</td>";
                                } elseif (strtolower($colName) === 'status') {
                                    echo "<td><span class='status-label $statusClass'>" . htmlspecialchars(ucfirst($row['Status'])) . "</span></td>";
                                } else {
                                    echo "<td>" . htmlspecialchars((string)$value) . "</td>";
                                }
                            }
                            echo "</tr>";

                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                                $isCancelled = ($row['Status'] === "Cancelled");
                                echo "<tr" . ($isCancelled ? " class='reverted'" : "text") . ">";
                                foreach ($row as $colName => $value) {
                                    $statusType = strtolower($row['Status']);
                                    $statusClass = "status-other";
                                    if ($statusType === "ordered") {
                                        $statusClass = "status-ordered";
                                    } elseif ($statusType === "received") {
                                        $statusClass = "status-received";
                                    } elseif ($statusType === "cancelled") {
                                        $statusClass = "status-cancelled";
                                    } else {
                                        $statusClass = "status-other";
                                    }
                                    
                                    if ($value instanceof DateTime) {
                                        echo "<td>" . htmlspecialchars($value->format('Y-m-d')) . "</td>";
                                    } elseif (strtolower($colName) === 'price') {
                                        echo "<td>$" . number_format((float)$value, 2) . "</td>";
                                    } elseif (strtolower($colName) === 'status') {
                                        echo "<td><span class='status-label $statusClass'>" . htmlspecialchars(ucfirst($row['Status'])) . "</span></td>";
                                    } else {
                                        echo "<td>" . htmlspecialchars((string)$value) . "</td>";
                                    }
                                }
                                echo "</tr>";
                            }
                        } else {
                            echo "<td colspan='100%'>No purchase orders found.</td></tr>";
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
