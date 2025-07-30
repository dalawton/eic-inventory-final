<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to mark completed batteries as shipped
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

$sql = "SELECT SN FROM dbo.All_Batteries WHERE Status = 'IN-HOUSE' AND BatteryName IN (SELECT BatteryName FROM dbo.Battery WHERE Complete = 'YES')";
$stmt = sqlsrv_query($conn, $sql);
$snOptions = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $snOptions[] = $row['SN'];
}

if ($conn === false) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}
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
                <h1>Mark Completed Batteries Shipped</h1>
                <p>Results shown are in-house complete batteries</p>
            </div>
            <div class="form-content">
                <div class="form-section">
                    <form method="get" action="markBatteryShipped.php">
                        <div class="form-group">
                            <label for="Battery">Select Serial Number:</label>
                            <select name="Battery" class="form-control" id="Battery">   
                                <option value="">--Select--</option>
                                <?php foreach ($snOptions as $SNum) : ?>
                                    <option value="<?php echo htmlspecialchars($SNum) ?>" <?php echo $selectedSN == $SNum ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($SNum) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <br>
                            <button type="submit" class="btn btn-submit">Mark Battery as Shipped</button>
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
