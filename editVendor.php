<?php
// Creates connection to the host, references the .env file to add additional security to the server
// If any of the login information for the server changes, update in .env file.
require_once __DIR__ . '/vendor/autoload.php';      // This acts as a bridge from this file to the .env file to get the information stored in the .env file.
use Dotenv\Dotenv;

// Load environment variables (from .env)
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection parameters
    // This stores all the server information in variables which are local to this specific file
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

$vendorID = $_POST['vendorID'];
$newVendorID = $_POST['vendorIDFix'];
$newVendorName = $_POST['vendorNameFix'];
$newVendorPhone = $_POST['vendorPhoneFix'];
$newVendorAddress = $_POST['vendorAddressFix'];
$newVendorCitySTZIP = $_POST['vendorCitySTZIPFix'];
$newContactName = $_POST['contactNameFix'];
$newContactEmail = $_POST['contactEmailFix'];

if (!empty($newVendorID)) {
    $sql = "UPDATE dbo.Vendors SET VendorID='$newVendorID' WHERE VendorID='$vendorID'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $vendorID = $newVendorID;
    sqlsrv_free_stmt($stmt);
    echo "VendorID updated successfully! \n";
}

if (!empty($newVendorName)) {
    $sql = "UPDATE dbo.Vendors SET VendorName='$newVendorName' WHERE VendorID='$vendorID'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
    echo "Vendor Name updated successfully! \n";
}

if (!empty($newVendorPhone)) {
    $sql = "UPDATE dbo.Vendors SET Telephone='$newVendorPhone' WHERE VendorID='$vendorID'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
    echo "Vendor phone number updated successfully! \n";
}

if (!empty($newVendorAddress)) {
    $sql = "UPDATE dbo.Vendors SET AddressLine1='$newVendorAddress' WHERE VendorID='$vendorID'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
    echo "Vendor address updated successfully! \n";
}

if (!empty($newVendorCitySTZIP)) {
    $sql = "UPDATE dbo.Vendors SET CitySTZIP='$newVendorCitySTZIP' WHERE VendorID='$vendorID'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
    echo "Vendor City, State ZIP updated successfully! \n";
}

if (!empty($newContactName)) {
    $sql = "UPDATE dbo.Vendors SET ContactName='$newContactName' WHERE VendorID='$vendorID'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
    echo "Contact Name updated successfully! \n";
}

if (!empty($newContactEmail)) {
    $sql = "UPDATE dbo.Vendors SET ContactEmail='$newContactEmail' WHERE VendorID='$vendorID'";
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