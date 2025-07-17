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

// Search logic for search button
$search = '';
$where = '';
$params = [];

if (isset($_GET['searchPO']) && $_GET['searchPO'] !== '') {
    $search = $_GET['searchPO'];
    $where = "WHERE PONum LIKE ?";
    $params = ["%$search%"];
} elseif (isset($_GET['searchVendor']) && $_GET['searchVendor'] !== '') {
    $search = $_GET['searchVendor'];
    $where = "WHERE VendorID LIKE ?";
    $params = ["%$search%"];
} elseif (isset($_GET['searchName']) && $_GET['searchName'] !== '') {
    $search = $_GET['searchName'];
    $where = "WHERE Purchaser LIKE ?";
    $params = ["%$search%"];
}
// Searches through POs using the search critera
$sql = "SELECT * FROM POs $where";
$stmt = sqlsrv_query($conn, $sql, $params);

// if fails, throws an error
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
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1>All Purchase Orders</h1>
        </div>
        <!-- Search Form -->
        <div class="form-content">
            <form class="form-control" method="get" action="">
                <input type="search" style="width: 29%;" class="form-control" name="searchPO" placeholder="Search PO Number..." value="<?php echo htmlspecialchars($_GET['searchPO'] ?? '') ?>">
                <input type="search" style="width: 29%;" class="form-control" name="searchVendor" placeholder="Search VendorID..." value="<?php echo htmlspecialchars($_GET['searchVendor'] ?? '') ?>">
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
                        // Fetch the first row to get column names
                        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                        if ($row) {
                            foreach (array_keys($row) as $colName) {
                                echo "<th>" . htmlspecialchars($colName) . "</th>";
                            }
                            echo "</div>";
                            echo "</tr>";

                            // Output the first row
                            echo "<tr class='text'>";
                            foreach ($row as $colName => $value) {
                                if ($value instanceof DateTime) {
                                    echo "<td>" . htmlspecialchars($value->format('Y-m-d H:i:s')) . "</td>";
                                } elseif (strtolower($colName) === 'price') {
                                    echo "<td>$" . number_format((float)$value, 2) . "</td>";
                                } else {
                                    echo "<td>" . htmlspecialchars((string)$value) . "</td>";
                                }
                            }
                            echo "</tr>";

                            // Output the rest
                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                                echo "<tr class='text'>";
                                foreach ($row as $colName => $value) {
                                    if ($value instanceof DateTime) {
                                        echo "<td>" . htmlspecialchars($value->format('Y-m-d H:i:s')) . "</td>";
                                    } elseif (strtolower($colName) === 'price') {
                                        echo "<td>$" . number_format((float)$value, 2) . "</td>";
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
