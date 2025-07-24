<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to change the inventory table upon submission of previous form
 * Now includes handling of component serial numbers
 *
 * PHP version 8
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Change_Files
 * @package   None
 * @author    Danielle Lawton <daniellelawton8@gmail.com>
 * @copyright 1999 - 2019 The PHP Group
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      https://pear.php.net/package/None
 */

// phpcs:disable Generic.Files.LineLength.TooLong

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Load environment variables (from .env)
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection parameters
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

// Get submitted data
$selectedBattery = $_POST['selectedBattery'] ?? null;
$amountUsed = $_POST['amount_Used'] ?? [];
$serialNumber = $_POST['serialNumber'] ?? null;
$selectedComponentSNs = $_POST['selectedComponentSNs'] ?? '';

// Process selected component serial numbers
$componentSNs = [];
if (!empty($selectedComponentSNs)) {
    $componentSNs = array_filter(explode(',', $selectedComponentSNs));
}

// Begin transaction for atomicity
sqlsrv_begin_transaction($conn);

try {
    // Validate serial number format (optional - adjust regex as needed for your format)
    if ($serialNumber && !preg_match('/^[A-Za-z0-9\-_]+$/', $serialNumber)) {
        throw new Exception("Invalid serial number format. Only letters, numbers, hyphens, and underscores are allowed.");
    }
    
    // Check if serial number already exists
    if ($serialNumber) {
        $checkSNStmt = sqlsrv_query($conn, "SELECT COUNT(*) as count FROM dbo.All_Batteries WHERE SN = ?", [$serialNumber]);
        $checkSNRow = sqlsrv_fetch_array($checkSNStmt, SQLSRV_FETCH_ASSOC);
        
        if ($checkSNRow['count'] > 0) {
            throw new Exception("Serial number '$serialNumber' already exists in the database.");
        }
    }

    // Add Serial Number into Battery Database
    if ($serialNumber) {
        $sqlBattery = "INSERT INTO dbo.All_Batteries (SN, BatteryType, Status) VALUES (?,?,?)";
        $stmtBattery = sqlsrv_query($conn, $sqlBattery, [$serialNumber, $selectedBattery, "IN-HOUSE"]);
        
        if ($stmtBattery === false) {
            throw new Exception("Failed to insert battery: " . print_r(sqlsrv_errors(), true));
        }
    }

    // Update status of selected components to "USED"
    if (!empty($componentSNs)) {
        foreach ($componentSNs as $componentSN) {
            $componentSN = trim($componentSN);
            if (!empty($componentSN)) {
                // First check if component exists and is available
                $checkComponentStmt = sqlsrv_query($conn, "SELECT Status FROM dbo.All_Batteries WHERE SN = ?", [$componentSN]);
                $componentRow = sqlsrv_fetch_array($checkComponentStmt, SQLSRV_FETCH_ASSOC);
                
                if (!$componentRow) {
                    throw new Exception("Component with serial number '$componentSN' not found.");
                }
                
                if ($componentRow['Status'] !== 'IN-HOUSE') {
                    throw new Exception("Component '$componentSN' is not available (current status: {$componentRow['Status']}).");
                }
                
                $sqlUpdateComponent = "UPDATE dbo.All_Batteries SET Status = 'USED' WHERE SN = ?";
                $stmtUpdateComponent = sqlsrv_query($conn, $sqlUpdateComponent, [$componentSN]);
                
                if ($stmtUpdateComponent === false) {
                    throw new Exception("Failed to update component status for SN $componentSN: " . print_r(sqlsrv_errors(), true));
                }
                
                // Optional: Create a relationship record between the new battery and its components
                if ($serialNumber) {
                    $sqlRelation = "INSERT INTO dbo.Battery_Components (ParentSN, ComponentSN, DateUsed) VALUES (?, ?, GETDATE())";
                    $stmtRelation = sqlsrv_query($conn, $sqlRelation, [$serialNumber, $componentSN]);
                    
                    if ($stmtRelation === false) {
                        throw new Exception("Failed to create component relationship: " . print_r(sqlsrv_errors(), true));
                    }
                }
            }
        }
    }

    if ($selectedBattery) {
        // Get all parts for this battery
        $sql = "SELECT * FROM dbo.PartsForBatteries WHERE BatteryName = ?";
        $stmt = sqlsrv_query($conn, $sql, [$batteryName]);
        
        if ($stmt === false) {
            throw new Exception("Failed to get battery parts: " . print_r(sqlsrv_errors(), true));
        }

        $index = 0;
        while ($item = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $pn = $item['PN'];
            $currentAmountUsed = isset($amountUsed[$index]) ? (int)$amountUsed[$index] : 0;

            // Update inventory
            $invCheck = sqlsrv_query($conn, "SELECT Amount from dbo.Inventory WHERE PN = ?", [$pn]);
            if ($row = sqlsrv_fetch_array($invCheck, SQLSRV_FETCH_ASSOC)) {
                $newQty = $row['Amount'] - $currentAmountUsed;
                $updateStmt = sqlsrv_query($conn, "UPDATE dbo.Inventory SET Amount = ? WHERE PN = ?", [$newQty, $pn]);
                
                if ($updateStmt === false) {
                    throw new Exception("Failed to update inventory for PN $pn: " . print_r(sqlsrv_errors(), true));
                }
            } else {
                // If part doesn't exist in inventory, create it with negative amount
                $insertStmt = sqlsrv_query($conn, "INSERT INTO dbo.Inventory (PN, Amount) VALUES (?, ?)", [$pn, -$currentAmountUsed]);
                
                if ($insertStmt === false) {
                    throw new Exception("Failed to insert inventory for PN $pn: " . print_r(sqlsrv_errors(), true));
                }
            }
            $index++;
        }
    }

    // Handle extra parts that were added
    $extraPartNumbers = $_POST['partNumber'] ?? [];
    $extraDescriptions = $_POST['description'] ?? [];
    $extraAmounts = $_POST['amountUsed'] ?? [];

    for ($i = 0; $i < count($extraPartNumbers); $i++) {
        $pn = $extraPartNumbers[$i] ?? '';
        $desc = $extraDescriptions[$i] ?? '';
        $amt = (int)($extraAmounts[$i] ?? 0);
        
        if (!empty($pn) && $amt > 0) {
            // Update inventory for extra parts
            $invCheck = sqlsrv_query($conn, "SELECT Amount from dbo.Inventory WHERE PN = ?", [$pn]);
            if ($row = sqlsrv_fetch_array($invCheck, SQLSRV_FETCH_ASSOC)) {
                $newQty = $row['Amount'] - $amt;
                $updateStmt = sqlsrv_query($conn, "UPDATE dbo.Inventory SET Amount = ? WHERE PN = ?", [$newQty, $pn]);
                
                if ($updateStmt === false) {
                    throw new Exception("Failed to update inventory for extra part $pn: " . print_r(sqlsrv_errors(), true));
                }
            } else {
                // If part doesn't exist in inventory, create it with description
                $insertStmt = sqlsrv_query($conn, "INSERT INTO dbo.Inventory (PN, Amount, Details) VALUES (?, ?, ?)", [$pn, -$amt, $desc]);
                
                if ($insertStmt === false) {
                    throw new Exception("Failed to insert inventory for extra part $pn: " . print_r(sqlsrv_errors(), true));
                }
            }
        }
    }

    // Commit transaction
    sqlsrv_commit($conn);
    
    $message = "Parts processed and inventory updated successfully!";
    if (!empty($componentSNs)) {
        $message .= " Components used: " . implode(', ', $componentSNs);
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    sqlsrv_rollback($conn);
    $message = "Error processing request: " . $e->getMessage();
}

sqlsrv_close($conn);
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="styleCheckout.css">
        <title>Processing Complete</title>
    </head>
    <body>
        <div class="main-container">
            <div class="header">
                <h1>Processing Complete</h1>
            </div>
            <div class="form-content">
                <div class="desc-info">
                    <p><?php echo htmlspecialchars($message); ?></p>
                    
                    <?php if (!empty($componentSNs)): ?>
                    <h3>Components Used:</h3>
                    <ul>
                        <?php foreach ($componentSNs as $sn): ?>
                        <li><?php echo htmlspecialchars(trim($sn)); ?> - Status updated to USED</li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    
                    <?php if ($serialNumber): ?>
                    <p><strong>New Battery Serial Number:</strong> <?php echo htmlspecialchars($serialNumber); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="navigation">
                <button onclick="location.href='checkoutCompletedBuilds.php'" class="btn btn-secondary">
                    Go Back
                </button>
                <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">
                    Report an Issue
                </button>
            </div>
        </div>
    </body>
</html>