<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to send information received by the submission of form denoting receiving repair
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

// Load environment variables
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

/**
 * This is an function that sends an Email after submission of previous form
 *
 * @param string $formData responses from previous form
 *
 * @return string Return either success or failure
 */
function sendRepairEmail($formData)
{
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_EMAIL'];
        $mail->Password = $_ENV['SMTP_PASSWORD']; // This is your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Prevent hanging
        $mail->Timeout = 30;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom($_ENV['SMTP_EMAIL'], 'EIC Inventory System');
        $mail->addAddress($_ENV['TRUNG_EMAIL'], 'Trung Nguyen');
        $mail->addCC($_ENV['MAX_EMAIL'], 'Maxwell Landolphi');
        $mail->addCC($_ENV['DANIELLE_EMAIL'], 'Danielle Lawton');

        // Add CC if specified
        if (!empty($_POST['receiver-email'])) {
            $mail->addCC($_POST['receiver-email'], $_GET['receiver']);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Repair - Part Received -- Serial Number #' . ($formData['serialNumber'] ?? 'N/A');

        // Generate email body
        $emailBody = generateRepairEmailBody($formData);
        $mail->Body = $emailBody;

        // Plain text version
        $mail->AltBody = generatePlainTextVersion($formData);

        // Send the email
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
        <title>Repair - Part Received</title>
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
            <h1>Repair - Part Received</h1>
            <p><strong>Serial Number:</strong> ' . htmlspecialchars($formData['serialNumber'] ?? 'N/A') . '</p>
            <p><strong>Submitted:</strong> ' . date('Y-m-d H:i:s') . '</p>
        </div>';

    // Information Section
    $html .= '
    <div class="section">
        <h3>Repair Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Received Date:</span><br>
                ' . htmlspecialchars($formData['receivedDate']) . '
            </div>
            <div class="info-item">
                <span class="info-label">Receiver Name:</span><br>
                ' . htmlspecialchars($formData['receiver']) . '
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
    $text = "Repair - Received Part\n";
    $text .= "================================\n\n";

    $text .= "Serial Number: " . ($formData['serialNumber'] ?? 'N/A') . "\n";
    $text .= "Date: " . ($formData['receivedDate'] ?? 'N/A') . "\n";

    // Repair Information
    $text .= "REPAIR INFORMATION:\n";
    $text .= "Received Date: " . ($formData['receivedDate'] ?? 'N/A') . "\n";
    $text .= "Receiver Name: " . ($formData['receiver'] ?? 'N/A') . "\n";

    $text .= "\n";
    return $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;
    $serialNumber = $formData['serialNumber'];
    $receivedDate = $formData['receivedDate'];
    $receivedBy = $formData['receiver'];
    $status = 'NEEDS REPAIR';
    $sql1 = "UPDATE dbo.Repairs SET Status = ? WHERE SerialNumber = ?";
    $stmt1 = sqlsrv_query($conn, $sql1, [$status, $serialNumber]);
    
    if ($stmt1 === false) {
        die("Error updating repair status: " . print_r(sqlsrv_errors(), true));
    }
    
    $sql2 = "UPDATE dbo.Repairs SET DateReceived = ? WHERE SerialNumber = ?";
    $stmt2 = sqlsrv_query($conn, $sql2, [$receivedDate, $serialNumber]);
    
    if ($stmt2 === false) {
        die("Error updating received date: " . print_r(sqlsrv_errors(), true));
    }
    
    $sql3 = "UPDATE dbo.All_Batteries SET Status = ? WHERE SN = ?";
    $stmt3 = sqlsrv_query($conn, $sql3, [$status, $serialNumber]);
    
    if ($stmt3 === false) {
        die("Error updating battery status: " . print_r(sqlsrv_errors(), true));
    }
    
    echo "Record updated successfully.\n";
    
    $customerName = $formData['customerName'] ?? 'Unknown';
    $issueDetails = $formData['issueDetails'] ?? 'Repair received';
    
    // Insert into InventoryLog
    $logSql = "INSERT INTO dbo.InventoryLog 
        (ActionType, TableAffected, ProductNumber, Description, Status) 
        VALUES (?, ?, ?, ?, ?)";
    $logParams = [
        'Update',
        'Repairs',
        $serialNumber,
        $issueDetails, 
        $status
    ];
    
    $logStmt = sqlsrv_query($conn, $logSql, $logParams);
    
    if ($logStmt === false) {
        die("Error inserting log entry: " . print_r(sqlsrv_errors(), true));
    }
    
    $result = sendRepairEmail($formData);
    
    if ($result['success']) {
        echo "'Repair - Part Received Form' submitted and email sent successfully!";
    } else {
        echo "Error: " . $result['message'];
    }
    
    sqlsrv_free_stmt($stmt1);
    sqlsrv_free_stmt($stmt2);
    sqlsrv_free_stmt($stmt3);
    sqlsrv_free_stmt($logStmt);
}

sqlsrv_close($conn);
?>