<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Returns information about selected repair
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
    echo json_encode(['error' => 'Connection failed']);
    exit;
}

$serialNumber = $_GET['serialNumber'] ?? '';
if (!$serialNumber) {
    echo json_encode(['error' => 'No Serial Number']);
    exit;
}

$sql = "SELECT SerialNumber, Requester, DateReceived, Details, Status FROM dbo.Repairs WHERE SerialNumber = ?"; // phpcs:ignore
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