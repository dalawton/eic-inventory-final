<?php

/////////////////////////////////////////////////
/* 
- This file contains the process of adding a new
part to the dbo.Inventory table.

- Change this file if the columns of the tables in
the database change.
*/
/////////////////////////////////////////////////


// Creates connection to the host, references the .env file to add additional security to the server
// If any of the login information for the server changes, update in .env file.
require_once __DIR__ . '/vendor/autoload.php';      // This acts as a bridge from this file to the .env file to get the information stored in the .env file.
use Dotenv\Dotenv;

// Load environment variables
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
// Get POST data (from ManageInventory.php)
    // Because this file is directly referenced after a button click in ManageInventory and requires information from that file
    // we must get that information using the POST method 
    // (which sends the data to a different page) for html forms instead of the GET method (which sends the data to the same page)
$productNumber = $_POST['productNumber'];
$quantity = $_POST['quantity'] ?? NULL;
$description = $_POST['description'];

// SQL INSERT statement
    // dbo.Inventory is the name of the Table
    // (PN, Amount, Details) are the column titles in the Table
    // (?, ?, ?) is apart of the inventory statement which escentially tells the compiler to reference the values that are defined next
$sql = "INSERT INTO dbo.Inventory (PN, Amount, Details) VALUES (?, ?, ?)";
$params = [$productNumber, $quantity, $description];

// Creates the sql statement, establishes the connection, declares the statement and adds the values wishing to be inserted
$stmt = sqlsrv_query($conn, $sql, $params);

// throws an error if $stmt does not execute correctly and prints the error
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
} else {
    echo "Record added successfully.";
    // Log the action
    $logSql = "INSERT INTO dbo.InventoryLog (ActionType, ProductNumber, Description, Quantity) VALUES (?, ?, ?, ?)";
    $logParams = ['add', $productNumber, $description, $quantity];
    sqlsrv_query($conn, $logSql, $logParams);
}

// frees up the $stmt variable and closes the connection to allow for additional statements and security for the server
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>

<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="styleInventory.css">
    </head>
    <body>
        <div class="main-container">
            <div class="navigation">
                    <button onclick="location.href='ManageInventory.php'" class="btn btn-secondary">
                        Go Back
                    </button>
                    <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">
                        Report an Issue
                    </button>
            </div>
        </div>
    </body>
</html>
