<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to get parts for a specific battery
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

header('Content-Type: application/json');

$sn = $_GET['serialNumber'] ?? '';
$parts = [];

if ($sn) {
    $batteryTypeStmt = sqlsrv_query($conn, "SELECT BatteryName FROM dbo.All_Batteries WHERE SN = ?", [$sn]);
    if ($batteryTypeRow = sqlsrv_fetch_array($batteryTypeStmt, SQLSRV_FETCH_ASSOC)) {
        $batteryType = $batteryTypeRow['BatteryName'];
        $partsSql = "SELECT DISTINCT PN FROM dbo.PartsForBatteries WHERE BatteryName LIKE ?";
        $stmtParts = sqlsrv_query($conn, $partsSql, [$batteryType . '%']);
        while ($row = sqlsrv_fetch_array($stmtParts, SQLSRV_FETCH_ASSOC)) {
            $parts[] = $row['PN'];
        }
    }
}

echo json_encode($parts);