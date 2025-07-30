<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Process barcode scanned builds - subtract parts from inventory and add battery
 *
 * PHP version 8
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.
 *
 * @category  Submit_File
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
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . print_r(sqlsrv_errors(), true)]));
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $buildData = json_decode($input, true);

    if (!$buildData) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    function processBarcodesBuild($buildData, $conn) {
        try {
            sqlsrv_begin_transaction($conn);

            $batteryBarcode = $buildData['batteryBarcode'];
            $batteryName = $buildData['batteryName'];
            $parts = $buildData['parts'];

            $batterySql = "INSERT INTO dbo.All_Batteries (SN, BatteryName, Status) VALUES (?, ?, ?)";
            $batteryParams = [$batteryBarcode, $batteryName, 'IN-HOUSE'];
            $stmt = sqlsrv_query($conn, $batterySql, $batteryParams);
            if ($stmt === false) throw new Exception("Error adding battery to database: $batteryBarcode");
            
            foreach ($parts as $part) {
                $partNumber = $part['partNumber'];
                $requiredQty = $part['quantity'];

                $checkSql = "SELECT Amount FROM dbo.Inventory WHERE PN = ? AND PN NOT IN (SELECT SN from dbo.All_Batteries)";
                $stmt = sqlsrv_query($conn, $checkSql, [$partNumber]);
                if ($stmt === false) throw new Exception("Error checking inventory for part: $partNumber");
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if (!$row) throw new Exception("Part not found in inventory: $partNumber");
                $availableQty = $row['Amount'];
                if ($availableQty < $requiredQty) throw new Exception("Insufficient inventory for $partNumber. Available: $availableQty, Required: $requiredQty");
            }

            foreach ($parts as $part) {
                $partNumber = $part['partNumber'];
                $usedQty = $part['quantity'];

                $checkComponentSql = "SELECT SN FROM dbo.All_Batteries WHERE SN = ?";
                $stmtComponent = sqlsrv_query($conn, $checkComponentSql, [$partNumber]);
                if ($stmtComponent === false) throw new Exception("Error checking All_Batteries for $partNumber");

                if ($rowComponent = sqlsrv_fetch_array($stmtComponent, SQLSRV_FETCH_ASSOC)) {
                    $updateComponentSql = "UPDATE dbo.All_Batteries SET Status = 'USED' WHERE SN = ?";
                    $stmt = sqlsrv_query($conn, $updateComponentSql, [$partNumber]);
                    if ($stmt === false) throw new Exception("Error updating component SN $partNumber to USED");

                    $relationSql = "INSERT INTO dbo.Battery_Components (ParentSN, ComponentSN, DateUsed) VALUES (?, ?, GETDATE())";
                    $stmt = sqlsrv_query($conn, $relationSql, [$batteryBarcode, $partNumber]);
                    if ($stmt === false) throw new Exception("Error relating component SN $partNumber to battery $batteryBarcode");
                } else {
                    $updateSql = "UPDATE dbo.Inventory SET Amount = Amount - ? WHERE PN = ?";
                    $stmt = sqlsrv_query($conn, $updateSql, [$usedQty, $partNumber]);
                    if ($stmt === false) throw new Exception("Error updating inventory for part: $partNumber");
                }
            }

            $logSql = "INSERT INTO dbo.InventoryLog (ActionType, TableAffected, ProductNumber, Description, Status) VALUES (?, ?, ?, ?, ?)";
            $logParams = [
                'Add', 'Batteries', $batteryBarcode,
                "Battery built using barcode scanner",
                'IN-HOUSE'
            ];
            sqlsrv_query($conn, $logSql, $logParams);

            sqlsrv_commit($conn);

            return [
                'success' => true,
                'message' => "Battery $batteryBarcode built successfully using " . count($parts) . " parts"
            ];

        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    try {
        $result = processBarcodesBuild($buildData, $conn);
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
sqlsrv_close($conn);
?>