<?php

/**
 * File to create a new repair request
 */

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

function sendRepairEmail($formData) {
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
    
        // Add CC if specified
        if (!empty($_ENV['CC_EMAIL'])) {
            $mail->addCC($_ENV['CC_EMAIL']);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Repair - Part Received -- Serial Number #' . ($formData['productSerialNumber'] ?? 'N/A');

        // Generate email body
        $emailBody = generateRepairEmailBody($formData);
        $mail->Body = $emailBody;

        // Plain text version
        $mail->AltBody = generatePlainTextVersion($formData);

        // Send the email
        $mail->send();
        return ['success' => true, 'message' => 'Purchase order email sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo];
    }
}

function generateRepairEmailBody($formData) {
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
            <p><strong>Serial Number:</strong> ' . htmlspecialchars($formData['productSerialNumber'] ?? 'N/A') . '</p>
            <p><strong>Date:</strong> ' . htmlspecialchars($formData['receivedDate'] ?? 'N/A') . '</p>
            <p><strong>Submitted:</strong> ' . date('Y-m-d H:i:s') . '</p>
        </div>';

    // Information Section
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
            </div>
            <div class="info-item">
                <span class="info-label">Received Date:</span><br>
                ' . htmlspecialchars($formData['receivedDate']) . '
            </div>
            <div class="info-item">
                <span class="info-label">Receiver Name:</span><br>
                ' . htmlspecialchars($formData['receivedBy']) . '
            </div>';

    $html .= '</div></div>';
    return $html;
}

function generatePlainTextVersion($formData) {
    $text = "Repair - Received Part\n";
    $text .= "================================\n\n";
    
    $text .= "Serial Number: " . ($formData['productSerialNumber'] ?? 'N/A') . "\n";
    $text .= "Date: " . ($formData['receivedDate'] ?? 'N/A') . "\n";
    $text .= "Submitted: " . date('Y-m-d H:i:s') . "\n\n";

    // Vendor Information
    $text .= "REPAIR INFORMATION:\n";
    $text .= "Customer Name: " . ($formData['customerName'] ?? 'N/A') . "\n";
    $text .= "Received Date: " . ($formData['receivedDate'] ?? 'N/A') . "\n";
    $text .= "Receiver Name: " . ($formData['receivedBy'] ?? 'N/A') . "\n";
    
    if (!empty($formData['issueDescription'])) {
        $text .= "Issue Description: " . $formData['issueDescription'] . "\n";
    }
    $text .= "\n";
    return $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;
    // get the information that was submitted from the form
    $serialNumber = $formData['productSerialNumber'];
    $customerName = $formData['customerName'];
    $issueDetails = $formData['issueDescription'] ?? '';
    $receivedDate = $formData['receivedDate'];
    $status = 'Received';
    // SQL Insert Statement
    $sql = "INSERT INTO dbo.Repairs (SerialNumber, Requester, DateReceived, Details, Status) VALUES (?, ?, ?, ?, ?)";
    $params = [$serialNumber, $customerName, $receivedDate, $issueDetails, $status];

    // Creates the sql statement, establishes the connection, declares the statement and adds the values wishing to be inserted
    $stmt = sqlsrv_query($conn, $sql, $params);

    // throws an error if $stmt does not execute correctly and prints the error
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));

    // if the $stmt statement executes correctly, a confirmation sentance will be printed
    } else {
        echo "Record added successfully. \n";
    }

    // After successful insert into Repairs table
    $logSql = "INSERT INTO dbo.InventoryLog 
        (ActionType, TableAffected, RepairSerialNumber, RepairRequester, RepairDetails, RepairStatus) 
        VALUES (?, ?, ?, ?, ?, ?)";
    $logParams = [
        'add', 'Repairs', $serialNumber, $customerName, $issueDetails, $status
    ];
    sqlsrv_query($conn, $logSql, $logParams);

    $result = sendRepairEmail($formData);

    if ($result['success']) {
        echo "'Repair - Part Received Form' submitted and email sent successfully!";
    } else {
        echo "Error: " . $result['message'];
    }
}


// frees up the $stmt variable and closes the connection to allow for additional statements and security for the server
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);