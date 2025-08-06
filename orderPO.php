<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to mark purchase orders as ordered
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

$sql = "SELECT PONum FROM dbo.POs WHERE Status = 'Submitted'";
$stmt = sqlsrv_query($conn, $sql);
$poOptions = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $poOptions[] = $row['PONum'];
}

if ($conn === false) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

$selectedPO = '';
?>

<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="styleInventory.css">
    </head>
    <body>
        <div class="main-container">
            <div class="header">
                <h1>Mark Purchase Orders as Ordered</h1>
                <p>Results shown are submitted POs sent for approval</p>
            </div>
            <div class="form-content">
                <div class="form-section">
                    <form method="POST" action="markPOOrdered.php">
                        <div class="form-group">
                            <label for="PO">Select Purchase Order:</label>
                            <select name="PO" class="form-control" id="PO">   
                                <option value="">--Select--</option>
                                <?php foreach ($poOptions as $poNum) : ?>
                                    <option value="<?php echo htmlspecialchars($poNum) ?>" <?php echo $selectedPO == $poNum ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($poNum) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="poNum" value="<?php echo htmlspecialchars($selectedPO) ?>">
                            <br>
                            <button type="submit" class="btn btn-submit">Mark PO as Ordered</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="navigation">
                <button onclick="location.href='FrontPage.html'" class="btn btn-secondary">
                    Go Back
                </button>
                <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">
                    Report an Issue
                </button>
            </div>
        </div>
    </body>
</html>
