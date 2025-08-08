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
 * @category  Change_File
 * @package   None
 * @author    Danielle Lawton <daniellelawton8@gmail.com>
 * @copyright 1999 - 2019 The PHP Group
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      https://pear.php.net/package/None
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
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}
$productNumber = $_POST['productNumber'];
$quantity = $_POST['quantity'] ?? null;
$description = $_POST['description'];

$sql = "INSERT INTO dbo.Inventory (PN, Amount, Details) VALUES (?, ?, ?)";
$params = [$productNumber, $quantity, $description];

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
} else {
    echo "Record added successfully.";
    $logSql = "INSERT INTO dbo.InventoryLog (ActionType, TableAffected, ProductNumber, Description, Quantity) VALUES (?, ?, ?, ?, ?)"; // phpcs:ignore
    $logParams = ['Add', 'Inventory', $productNumber, $description, $quantity];
    sqlsrv_query($conn, $logSql, $logParams);
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
