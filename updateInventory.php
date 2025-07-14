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

// Get POST data
$id = $_POST['updateProductNumber'];
$newValue = $_POST['updateQuantity'];
$newDetails = $_POST['updateDetails'] ?? '';

if (!empty($newValue)) {
    // SQL query with parameters to prevent injection
    $sql = "UPDATE dbo.Inventory SET Amount = ? WHERE PN = ?";
    $params = [$newValue, $id];

    // Execute the query
    $stmt = sqlsrv_query($conn, $sql, $params);
}

if (!empty($newDetails)) {
    $sql = "UPDATE dbo.Inventory SET Details = ? WHERE PN = ?";
    $params = [$newDetails, $id];
    $stmt = sqlsrv_query($conn, $sql, $params);
}
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
} else {
    echo "Record updated successfully.";
    // Log the action
    $logSql = "INSERT INTO dbo.InventoryLog (ActionType, ProductNumber, Description, Quantity) VALUES (?, ?, ?, ?)";
    $logParams = ['update', $id, $newDetails, $newValue];
    sqlsrv_query($conn, $logSql, $logParams);
}
// Free the statement and close the connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>

<html>
    <div class="container">
            <br><br>
            <button onclick="location.href='ManageInventory.php'">Go Back</button>
            <button onclick="location.href='ReportIssue.html'">Report an Issue</button>
        </div>
</html>