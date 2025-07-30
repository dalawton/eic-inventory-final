<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to fetch the information about scanned part.
 *
 * PHP version 8
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Get_File
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

header('Content-Type: application/json');

$partNumber = $_GET['partNumber'] ?? '';
if (!$partNumber) {
    echo json_encode(['exists' => false]);
    exit;
}

$sql = "SELECT Amount, Details FROM dbo.Inventory WHERE PN = ?";
$stmt = sqlsrv_query($conn, $sql, [$partNumber]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if ($row) {
    echo json_encode([
        'exists' => true,
        'quantity' => $row['Amount'],
        'description' => $row['Details'],
        'isComponent' => false
    ]);
    exit;
}

$sql2 = "SELECT Status, BatteryName FROM dbo.All_Batteries WHERE SN = ?";
$stmt2 = sqlsrv_query($conn, $sql2, [$partNumber]);
$row2 = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);

if ($row2) {
    echo json_encode([
        'exists' => true,
        'quantity' => ($row2['Status'] === 'IN-HOUSE') ? 1 : 0,
        'description' => $row2['BatteryName'],
        'isComponent' => true
    ]);
    exit;
}

echo json_encode(['exists' => false]);