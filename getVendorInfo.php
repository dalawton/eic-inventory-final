<?php
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
    echo json_encode(['error' => 'Connection failed']);
    exit;
}

$vendorID = $_GET['vendorID'] ?? '';
if (!$vendorID) {
    echo json_encode(['error' => 'No vendor ID']);
    exit;
}

$sql = "SELECT vendorID, VendorName, Telephone, AddressLine1, CitySTZIP, ContactName, ContactEmail FROM dbo.Vendors WHERE VendorID = ?";
$stmt = sqlsrv_query($conn, $sql, [$vendorID]);
if ($stmt === false) {
    echo json_encode(['error' => 'Query failed']);
    exit;
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo json_encode($row);
?>