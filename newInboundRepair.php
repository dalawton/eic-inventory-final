<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to send information received by the submission of new repair form
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
        $mail->Subject = 'Repair - Inbound Notice -- Serial Number #' . ($formData['productSerialNumber'] ?? 'N/A');

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
 * This is an function that sends an Email after submission of previous form
 *
 * @param string $formData responses from previous form
 *
 * @return string Return body of the email
 */
function generateRepairEmailBody($formData)
{
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Repair - Inbound Notice</title>
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
            <h1>Repair - Inbound Notice</h1>
            <p><strong>Serial Number:</strong> ' . htmlspecialchars($formData['productSerialNumber'] ?? 'N/A') . '</p>
            <p><strong>Date Submitted:</strong> ' . date('Y-m-d') . '</p>
        </div>';

    $html .= '
    <div class="section">
        <h3>Repair Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Customer Name:</span><br>
                ' . htmlspecialchars($formData['customerName'] ?? 'N/A') . '
            </div>
            <div class="info-item">
                <span class="info-label">Issue Description:</span><br>
                ' . htmlspecialchars($formData['issueDescription'] ?? 'No request info or issue description provided') . '
            </div>';

    $html .= '</div></div>';
    return $html;
}

/**
 * This is an function that sends an Email after submission of previous form
 * specifically in plain text version
 *
 * @param string $formData responses from previous form
 *
 * @return string Return body of email in plain text version
 */
function generatePlainTextVersion($formData)
{
    $text = "Repair - Inbound Notice \n";
    $text .= "================================\n\n";

    $text .= "Serial Number: " . ($formData['productSerialNumber'] ?? 'N/A') . "\n";
    $text .= "Date Submitted: " . date('Y-m-d') . "\n\n";

    $text .= "REPAIR INFORMATION:\n";
    $text .= "Customer Name: " . ($formData['customerName'] ?? 'N/A') . "\n";

    if (!empty($formData['issueDescription'])) {
        $text .= "Issue Description: " . $formData['issueDescription'] . "\n";
    }
    $text .= "\n";
    return $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;
    $serialNumber = $formData['productSerialNumber'];
    $customerName = $formData['customerName'];
    $issueDetails = $formData['issueDescription'] ?? '';
    $selectedBattery = $formData['batteryName'];
    $status = 'INBOUND';

    $sql = "INSERT INTO dbo.Repairs (SerialNumber, Requester, Details, Status) VALUES (?, ?, ?, ?)";
    $params = [$serialNumber, $customerName, $issueDetails, $status];

    $stmt = sqlsrv_query($conn, $sql, $params);

    $check = sqlsrv_query($conn, "SELECT Status from dbo.All_Batteries WHERE SN = ?", [$serialNumber]);
    if ($row = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC)){
        $sqlAll = "UPDATE dbo.All_Batteries SET Status = 'INBOUND' WHERE SN = ?";
        $SNparams = [$serialNumber];
    } else {
        $sqlAll = "INSERT INTO dbo.All_Batteries (SN, BatteryName, Status) VALUES (?, ?, ?)";
        $SNparams = [$serialNumber, $selectedBattery, $status];
    }
    sqlsrv_query($conn, $sqlAll, $SNparams);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));

    } else {
        echo "Record added successfully. \n";
    }

    $logSql = "INSERT INTO dbo.InventoryLog 
        (ActionType, TableAffected, ProductNumber, RepairRequester, Description, Status) 
        VALUES (?, ?, ?, ?, ?, ?)";
    $logParams = [
        'Add', 'Repairs', $serialNumber, $customerName, $issueDetails, $status
    ];
    sqlsrv_query($conn, $logSql, $logParams);

    $result = sendRepairEmail($formData);

    if ($result['success']) {
        echo "'Repair - Inbound Notice Form' submitted and email sent successfully!";
    } else {
        echo "Error: " . $result['message'];
    }
}


sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
