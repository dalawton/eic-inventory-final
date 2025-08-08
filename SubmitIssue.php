<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to send email about a submitted issue
 *
 * PHP version 8
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Submit_Files
 * @package   None
 * @author    Danielle Lawton <daniellelawton8@gmail.com>
 * @copyright 1999 - 2019 The PHP Group
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      https://pear.php.net/package/None
 */

// phpcs:disable Generic.Files.LineLength.TooLong

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

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

$mail = new PHPMailer(true);

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
    $mail->addAddress($_ENV['DANIELLE_EMAIL'], 'Danielle Lawton');

    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $mail->addReplyTo($_POST['email'], $_POST['name'] ?? '');
    }

    $mail->isHTML(true);

    $requestType = $_POST['typeRequest'] ?? 'General Request';
    $submitterName = $_POST['name'] ?? 'Anonymous';
    $mail->Subject = "[$requestType] - $submitterName";

    $body = "<html><body>";
    $body .= "<h2>New Issue Submission</h2>";
    $body .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    $body .= "<tr><td><strong>Type of Request:</strong></td><td>" . htmlspecialchars($_POST['typeRequest'] ?? 'Not specified') . "</td></tr>";
    $body .= "<tr><td><strong>Name:</strong></td><td>" . htmlspecialchars($_POST['name'] ?? 'Not provided') . "</td></tr>";
    $body .= "<tr><td><strong>Email:</strong></td><td>" . htmlspecialchars($_POST['email'] ?? 'Not provided') . "</td></tr>";
    $body .= "<tr><td><strong>Submitted:</strong></td><td>" . htmlspecialchars($_POST['date'] ?? 'Not provided') . "</td></tr>";
    $body .= "</table>";
    $body .= "<h3>Description:</h3>";
    $body .= "<div style='background-color: #f5f5f5; padding: 10px; border-left: 4px solid #007cba;'>";
    $body .= nl2br(htmlspecialchars($_POST['message'] ?? 'No message provided'));
    $body .= "</div>";
    $body .= "</body></html>";

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadTmpPath = $_FILES['attachment']['tmp_name'];
        $uploadName = $_FILES['attachment']['name'];
        $mail->addAttachment($uploadTmpPath, $uploadName);
    }

    $mail->Body = $body;

    $altBody = "New Issue Submission\n\n";
    $altBody .= "Type: " . ($_POST['typeRequest'] ?? 'Not specified') . "\n";
    $altBody .= "Name: " . ($_POST['name'] ?? 'Not provided') . "\n";
    $altBody .= "Email: " . ($_POST['email'] ?? 'Not provided') . "\n";
    $altBody .= "Submitted: " . ($_POST['date'] ?? 'Not provided') . "\n\n";
    $altBody .= "Description:\n" . ($_POST['message'] ?? 'No message provided') . "\n";

    $mail->AltBody = $altBody;

    $mail->send();

    echo "<!DOCTYPE html>";
    echo "<html><head><title>Success</title></head><body>";
    echo "<h2>✅ Issue submitted successfully!</h2>";
    echo "<p>Thank you for your submission. We'll get back to you soon.</p>";
    echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";
    echo "</body></html>";
} catch (Exception $e) {
    error_log("Mail error: " . $e->getMessage());

    echo "<!DOCTYPE html>";
    echo "<html><head><title>Error</title></head><body>";
    echo "<h2>❌ Failed to submit issue</h2>";
    echo "<p>We're sorry, but there was a problem submitting your request.</p>";
    echo "<p>Please try again later or contact support directly</p>";
    echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";

    echo "</body></html>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;
    $typeRequest = $formData['typeRequest'];
    $date = $formData['date'];
    $requestorName = $formData['name'] ?? '';
    $description = $formData['message'];

    $sql = "INSERT INTO dbo.Issues (TypeRequest, Date, Requestor, Details, Status) VALUES (?, ?, ?, ?, ?)";
    $params = [$typeRequest, $date, $requestorName, $description, 'Requested'];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
}
