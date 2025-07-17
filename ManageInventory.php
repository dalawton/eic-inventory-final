<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to display current inventory and link to files to change 
 * current inventory.
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

// Get search input if present
$search = '';
if (isset($_GET['productNumber']) && $_GET['productNumber'] !== '') {
    $search = $_GET['productNumber'];
    $sql = "SELECT * FROM dbo.Inventory WHERE PN LIKE ? ORDER BY PN ASC";
    $params = ["%$search%"];
    $stmt = sqlsrv_query($conn, $sql, $params);
} else {
    $sql = "SELECT * FROM dbo.Inventory ORDER BY PN ASC";
    $stmt = sqlsrv_query($conn, $sql);
}
if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory</title>
    <link rel="stylesheet" href="styleInventory.css">
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1>Manage Inventory</h1>
            <p>Add, Edit or Delete a part from inventory or search for a specific part</p>
        </div>
        <div class="form-content">
            <div class="form-section">
                <h2 class="section-title collapsible">Add New Part to Inventory:</h2>
                <div class="collapsible-content" style="display:none;">
                    <div class="form-grid">
                        <form id="addProductForm" action="addInventory.php" class="form-grid" method="POST">
                            <label for="productNumber">Product Number:</label><br>
                            <input type="text" id="productNumber" class="form-control" name="productNumber" placeholder='Ex. A32-31241' required>
                            <label for="quantity">Quantity:</label><br>
                            <input type="number" id="quantity" class="form-control" name="quantity">
                            <label for="description">Description:</label><br>
                            <input type="text" id="description" name="description" class="form-control" placeholder='Ex. PCB, L3 BOX LED'>
                            <br>
                            <div class="action-buttons">
                                <button type="submit" class="btn">Add Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-content">
            <div class="form-section">
                <h2 class="section-title collapsible">Update Part in Inventory:</h2>
                <div class="collapsible-content" style="display:none;">
                    <div class="form-grid">
                        <form id="updateProductForm" action="updateInventory.php" class="form-grid" method="POST"> <!-- redirects to updateInventory.php on click of submit button -->
                            <label for="updateProductNumber">Product Number:</label><br>
                            <input type="text" id="updateProductNumber" class="form-control" name="updateProductNumber" placeholder='Ex. A32-31241' required>
                            <label for="updateQuantity">New Quantity:</label><br>
                            <input type="number" id="updateQuantity" class="form-control" name="updateQuantity">
                            <label for="updateDetails">New Description:</label><br>
                            <input type="text" id="updateDetails" class="form-control" name="updateDetails" placeholder='Ex. PCB, L3 BOX LED'>
                            <br>
                            <div class="action-buttons">
                                <button type="submit" class="btn">Update Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-content">
            <div class="form-section">
                <h2 class="section-title collapsible">Delete Part From Inventory:</h2>
                <div class="collapsible-content" style="display:none;">
                    <div class="form-grid">
                        <form id="deleteProductForm" action="deleteInventory.php" class="form-grid" method="POST"> <!-- redirects to deleteInventory.php on click of submit button -->
                            <label for="deleteProductNumber">Product Number:</label><br>
                            <input type="text" id="deleteProductNumber" class="form-control" name="deleteProductNumber" placeholder='Ex. A32-31241' required>
                            <br>
                            <div class="action-buttons">
                                <button type="submit" class="btn">Delete Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-content">
            <div class="form-section">
                <h2 class="section-title">Inventory Table
                    <button type="button" style="float: right; margin-right: 55px;" class="btn form-control btn-secondary" onclick="location.href='searchByBattery.php'">Search by Battery</button>
                </h2>
                <form id="searchInventory" method="get" action="">
                    <input type="search" style="width: 80%;" class="form-control" id="query" name="productNumber" placeholder="Search for Part Number..." value="<?php echo htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-secondary">Search</button>
                    <?php if ($search) : ?>
                        <a href="ManageInventory.php">Clear</a>
                    <?php endif; ?>
                </form>
                <div class="product-table-container">
                    <table  class="product-table" border="1">
                        <div class="table-header">
                            <tr>
                                <th>Part Number</th>
                                <th>Quantity</th>
                                <th>Details</th>
                            </tr>
                        </div>
                        <!-- Defines the table of inventory and its column titles from the connection to server -->
                        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['PN']) ?></td>
                                <td><?php echo htmlspecialchars($row['Amount']) ?></td>
                                <td><?php echo htmlspecialchars($row['Details'] ?? '') ?></td> <!-- The "?? '' "  allows null values to be displayed correctly -->
                            </tr>
                        <?php endwhile; ?>
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

<script>
document.querySelectorAll('.section-title.collapsible').forEach(function(header) {
    header.style.cursor = 'pointer';
    header.addEventListener('click', function() {
        var content = this.nextElementSibling;
        this.classList.toggle('active');
        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
        } else {
            content.style.display = 'none';
        }
    });
});
</script>
