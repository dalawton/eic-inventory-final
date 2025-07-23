<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to process the deletion of a PO order,
 * AKA changing the status to cancelled and send email notice
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
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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

/**
 * This is an function that sends an Email after submission of previous form
 *
 * @param string $formData      responses from previous form
 * @param array  $POnumber      response of purchase order number submitted on previous form
 *
 * @return string Return either success or failure
 */
function sendCancelEmail($formData, $POnumber)
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

        // Recipients
        $mail->setFrom($_ENV['SMTP_EMAIL'], 'EIC Inventory System');
        $mail->addAddress($_ENV['TRUNG_EMAIL'], 'Trung Nguyen');
        $mail->addCC($_ENV['PATSY_EMAIL'], 'Patricia Riley');
        $mail->addCC($_ENV['MAX_EMAIL'], 'Maxwell Landolphi');
        $mail->addCC($_ENV['DANIELLE_EMAIL'], 'Danielle Lawton');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Purchase Order CANCELLED - PO #' . ($formData['PO'] ?? 'N/A');

        // Generate email body
        $emailBody = generateCancelledEmailBody($formData, $POnumber);
        $mail->Body = $emailBody;
        $mail->AltBody = generatePlainTextVersion($formData, $POnumber);

        // Send the email
        $mail->send();
        return ['success' => true, 'message' => 'Purchase order email sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo];
    }
}

/**
 * This is an function that generates the body of the email
 *
 * @param string $formData      responses from previous form
 * @param array  $POnumber      response of PO number
 *
 * @return string Returns email body with information from prior form
 */
function generateCancelledEmailBody($formData, $POnumber)
{
    $POnumber = $formData['PO'];
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Purchase Order - CANCELLED</title>
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
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Purchase Order CANCELLED Notice</h1>
            <p><strong>PO Number:</strong> ' . htmlspecialchars($formData['PO']) . '</p>
            <p><strong>Date:</strong> ' . date('Y-m-d') . '</p>
        </div>
    </body>
    </html>';

    return $html;
}

/**
 * This is an function that generates the body of the email in plain text
 *
 * @param string $formData responses from previous form
 * @param array  $POnumber response of PONum
 *
 * @return string Returns email body with information from prior form in plain text
 */
function generatePlainTextVersion($formData, $products)
{
    $text = "PURCHASE ORDER CANCELLED\n";
    $text .= "================================\n\n";

    $text .= "PO Number: " . ($formData['PO']) . "\n";
    $text .= "Submitted: " . date('Y-m-d') . "\n\n";

    return $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;

    $POnumber = $formData['PO'];

    $logSql = "UPDATE dbo.POs SET Status = ? WHERE PONum = ?";
    $logParams = ['Cancelled', $POnumber];
    $stmt = sqlsrv_query($conn, $logSql, $logParams);
    // Execute the query
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    } else {
        echo "Record deleted successfully.";
    }

    $result = sendCancelEmail($formData, $POnumber);

    if ($result['success']) {
        echo "Purchase order cancelled succesfully!";
    } else {
        echo "Purchase order cancelled failed.";
    }
}

// Free the statement and close the connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="styleInventory.css">
    </head>
    <body>
        <div class="main-container">
            <div class="navigation">
                    <button onclick="location.href='TrackPO.php'" class="btn btn-secondary">
                        Go Back
                    </button>
                    <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">
                        Report an Issue
                    </button>
            </div>
        </div>
    </body>
</html>
