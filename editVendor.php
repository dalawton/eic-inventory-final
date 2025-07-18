<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to edit the information about a specific vendor
 * and have the information automatically change in the vendor table.
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
$vendorName = $_POST['vendorName'];
$newVendorName = $_POST['vendorNameFix'];
$newVendorPhone = $_POST['vendorPhoneFix'];
$newVendorAddress = $_POST['vendorAddressFix'];
$newVendorCitySTZIP = $_POST['vendorCitySTZIPFix'];
$newContactName = $_POST['contactNameFix'];
$newContactEmail = $_POST['contactEmailFix'];


if (!empty($newVendorName)) {
    $sql = "UPDATE dbo.Vendors SET VendorName='$newVendorName' WHERE VendorName='$vendorName'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
    echo "Vendor Name updated successfully! \n";
}

if (!empty($newVendorPhone)) {
    $sql = "UPDATE dbo.Vendors SET Telephone='$newVendorPhone' WHERE VendorName='$vendorName'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
    echo "Vendor phone number updated successfully! \n";
}

if (!empty($newVendorAddress)) {
    $sql = "UPDATE dbo.Vendors SET AddressLine1='$newVendorAddress' WHERE VendorName='$vendorName'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
    echo "Vendor address updated successfully! \n";
}

if (!empty($newVendorCitySTZIP)) {
    $sql = "UPDATE dbo.Vendors SET CitySTZIP='$newVendorCitySTZIP' WHERE VendorName='$vendorName'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
    echo "Vendor City, State ZIP updated successfully! \n";
}

if (!empty($newContactName)) {
    $sql = "UPDATE dbo.Vendors SET ContactName='$newContactName' WHERE VendorName='$vendorName'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
    echo "Contact Name updated successfully! \n";
}

if (!empty($newContactEmail)) {
    $sql = "UPDATE dbo.Vendors SET ContactEmail='$newContactEmail' WHERE VendorName='$vendorName'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
    echo "Contact Email updated successfully! \n";
}

echo "Vendor info updated successfully!";
sqlsrv_close($conn);
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="stylePurchaseOrder.css">
    </head>
    <body>
        <div class="main-container">
            <div class="navigation">
                    <button onclick="location.href='CreatePO.php'" class="btn btn-secondary">
                        Go Back
                    </button>
                    <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">
                        Report an Issue
                    </button>
            </div>
        </div>
    </body>
</html>
