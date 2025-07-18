<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to see all submitted issues and their respective statuses
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

$sql = "SELECT * FROM dbo.Issues";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styleIssueReport.css">
    <title>Track Issues</title>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1>Track Reported Issues</h1>
            <p>See submitted issues and feature requests and their respective statuses</p>
        </div>
        <div class="form-content">
            <div class="product-table-container">
                <div class="table-header">
                    <h3>Reported Issues/Requested Features</h3>
                </div>
                <table class="product-table" border="1">
                    <tr>
                        <th>IssueID</th>
                        <th>Type of Request</th>
                        <th>Date Submitted</th>
                        <th>Details</th>
                        <th>Status</th>
                    </tr>
                    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['IssueID']) ?></td>
                            <td><?php echo htmlspecialchars($row['TypeRequest']) ?></td>
                            <td><?php echo htmlspecialchars(($row['Date'])->format('Y-m-d H:i:s')) ?></td>
                            <td><?php echo htmlspecialchars($row['Details']) ?></td>
                            <td><?php echo htmlspecialchars($row['Status']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
        <!-- Navigation -->
        <div class="navigation">
            <br><br>
            <button onclick="location.href='FrontPage.html'" class="btn btn-secondary">Return to Front Page</button>
            <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">Report an Issue</button>
        </div>
    </div>
</body>
</html>