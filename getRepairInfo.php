<?php

/**
 * File to get the details of selected repair
 */

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

$serialNumber = $_GET['serialNumber'] ?? '';
if (!$serialNumber) {
    echo json_encode(['error' => 'No Serial Number']);
    exit;
}

$sql = "SELECT SerialNumber, Requester, DateReceived, Details, Status FROM dbo.Repairs WHERE SerialNumber = ?";
$stmt = sqlsrv_query($conn, $sql, [$serialNumber]);
if ($stmt === false) {
    echo json_encode(['error' => 'Query failed']);
    exit;
}

$results = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if (isset($row['DateReceived']) && $row['DateReceived'] instanceof DateTime) {
        $row['DateReceived'] = $row['DateReceived']->format('Y-m-d H:i:s');
    }
    $results[] = $row;
}
echo json_encode($results);
?>