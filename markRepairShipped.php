<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File set the status of a chosen repair to be shipped (AKA completed)
 * as well as send an email with confirmation
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
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
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

/**
 * This is an function that sends an Email after submission of previous form
 *
 * @param string $formData responses from previous form
 *
 * @return string Return either success or failure
 */
function sendRepairEmail($formData)
{
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_EMAIL'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->Timeout = 30;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom($_ENV['SMTP_EMAIL'], 'EIC Inventory System');
        $mail->addAddress($_ENV['TRUNG_EMAIL'], 'Trung Nguyen');
        $mail->addCC($_ENV['MAX_EMAIL'], 'Maxwell Landolphi');
        $mail->addCC($_ENV['DANIELLE_EMAIL'], 'Danielle Lawton');

        $mail->isHTML(true);
        $mail->Subject = 'Repair - Part Completed and Shipped -- Serial Number #' . ($formData['serialNumber'] ?? 'N/A');

        $emailBody = generateRepairEmailBody($formData);
        $mail->Body = $emailBody;

        $mail->AltBody = generatePlainTextVersion($formData);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo];
    }
}

/**
 * This is a function to generage the body of the email
 *
 * @param string $formData responses from prior form
 *
 * @return string Return body of email in html form
 */
function generateRepairEmailBody($formData)
{
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Repair - Part Completed and Shipped</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #f4f4f4;
                padding: 20px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .section {
                margin-bottom: 25px;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .section h3 {
                margin-top: 0;
                color: #2c5282;
                border-bottom: 2px solid #2c5282;
                padding-bottom: 5px;
            }
            .info-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin-bottom: 15px;
            }
            .info-item {
                padding: 8px;
                background-color: #f8f9fa;
                border-radius: 3px;
            }
            .info-label {
                font-weight: bold;
                color: #495057;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
            }
            th, td {
                padding: 12px;
                text-align: left;
                border: 1px solid #ddd;
            }
            th {
                background-color: #2c5282;
                color: white;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            .total-row {
                background-color: #e8f4f8 !important;
                font-weight: bold;
            }
            .checkbox-section {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin: 10px 0;
            }
            .checkbox-item {
                background-color: #e8f5e8;
                padding: 5px 10px;
                border-radius: 15px;
                font-size: 14px;
            }
            .highlight {
                background-color: #fff3cd;
                padding: 10px;
                border-radius: 5px;
                border-left: 4px solid #ffc107;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Repair - Part Completed and Shipped</h1>
            <p><strong>Serial Number:</strong> ' . htmlspecialchars($formData['serialNumber'] ?? 'N/A') . '</p>
            <p><strong>Date Shipped:</strong> ' . htmlspecialchars($formData['shippingDate'] ?? 'N/A') . '</p>
            <p><strong>Shipping Location:</strong> ' . htmlspecialchars($formData['shippingLocation'] ?? 'N/A') . '</p>
            <p><strong>Submitted:</strong> ' . date('Y-m-d') . '</p>
        </div>';

    $html .= '
    <div class="section">
        <h3>Repair Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Repair Notes:</span><br>
                ' . htmlspecialchars($formData['details'] ?? 'No repair notes provided') . '
            </div>';

    $html .= '</div></div>';
    return $html;
}

/**
 * This is a function to make the plain text version of the email
 *
 * @param string $formData responses from the prior form
 *
 * @return string Return body of email in plain text form
 */
function generatePlainTextVersion($formData)
{
    $text = "Repair - Received Part\n";
    $text .= "================================\n\n";

    $text .= "Serial Number: " . ($formData['serialNumber'] ?? 'N/A') . "\n";
    $text .= "Date Shipped: " . ($formData['shippingDate'] ?? 'N/A') . "\n";
    $text .= "Shipping Location: " . ($formData['shippingLocation'] ?? 'N/A') . "\n";
    $text .= "Submitted: " . date('Y-m-d') . "\n\n";

    $text .= "REPAIR DETAILS:\n";

    if (!empty($formData['details'])) {
        $text .= "Repair Notes: " . $formData['details'] . "\n";
    }
    $text .= "\n";
    return $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;
    $serialNum = $formData['serialNumber'];
    $shippingDate = $formData['shippingDate'];
    $repairNotes = $formData['details'] ?? '';
    $shippingLocation = $formData['shippingLocation'];
    $updateRepair = sqlsrv_query($conn, "UPDATE dbo.Repairs SET Status = 'SHIPPED' WHERE SerialNumber = ?", [$serialNum]);
    $updateDate = sqlsrv_query($conn, "UPDATE dbo.Repairs SET DateShipped = '$shippingDate' WHERE SerialNumber = ?", [$serialNum]);
    $updateDetails = sqlsrv_query($conn, "UPDATE dbo.Repairs SET ShippingDetails = '$repairNotes' WHERE SerialNumber = ?", [$serialNum]);
    $updateLocation = sqlsrv_query($conn, "UPDATE dbo.Repairs SET ShippingLocation = '$shippingLocation' WHERE SerialNumber = ?", [$serialNum]);

    $extraPartNumbers = $formData['partNumber'] ?? [];
    $extraAmounts = $formData['amountUsed'] ?? [];
    for ($i = 0; $i < count($extraPartNumbers); $i++) {
        $pn = $extraPartNumbers[$i] ?? '';
        $amt = (int)($extraAmounts[$i] ?? 0);
        
        if (!empty($pn) && $amt > 0) {
            $invCheck = sqlsrv_query($conn, "SELECT Amount from dbo.Inventory WHERE PN = ?", [$pn]);
            if ($row = sqlsrv_fetch_array($invCheck, SQLSRV_FETCH_ASSOC)) {
                $newQty = $row['Amount'] - $amt;
                $updateStmt = sqlsrv_query($conn, "UPDATE dbo.Inventory SET Amount = ? WHERE PN = ?", [$newQty, $pn]);
                
                if ($updateStmt === false) {
                    throw new Exception("Failed to update inventory for extra part $pn: " . print_r(sqlsrv_errors(), true));
                }
            } else {
                $insertStmt = sqlsrv_query($conn, "INSERT INTO dbo.Inventory (PN, Amount) VALUES (?, ?, ?)", [$pn, -$amt]);
                
                if ($insertStmt === false) {
                    throw new Exception("Failed to insert inventory for extra part $pn: " . print_r(sqlsrv_errors(), true));
                }
            }
        }
    }

    $sqlAll = "UPDATE dbo.All_Batteries SET Status = 'SHIPPED' WHERE SN = $serialNum";
    sqlsrv_query($conn, $sqlAll);

    $logSql = "INSERT INTO dbo.InventoryLog 
        (ActionType, TableAffected, ProductNumber, Description, Status) 
        VALUES (?, ?, ?, ?, ?)";
    $logParams = [
        'Update', 'Repairs', $serialNum, $repairNotes, 'SHIPPED'
    ];
    sqlsrv_query($conn, $logSql, $logParams);

    $result = sendRepairEmail($formData);

    echo "Repair marked as shipped and completed";

    sqlsrv_close($conn);
}
