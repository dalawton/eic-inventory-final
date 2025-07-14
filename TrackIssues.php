<?php
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

$sql = "SELECT * FROM dbo.Issues";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styleIssueReport.css">
    <title>Track Issues</title>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1>Track Reported Issues</h1>
            <p>See submitted issues and feature requests and their respective statuses</p>
        </div>
        <div class="form-content">
            <div class="product-table-container">
                <div class="table-header">
                    <h3>Reported Issues/Requested Features</h3>
                </div>
                <table class="product-table" border="1">
                    <tr>
                        <th>IssueID</th>
                        <th>Type of Request</th>
                        <th>Date Submitted</th>
                        <th>Requestor</th>
                        <th>Details</th>
                        <th>Status</th>
                    </tr>
                    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['IssueID']) ?></td>
                            <td><?= htmlspecialchars($row['TypeRequest']) ?></td>
                            <td><?= htmlspecialchars(($row['Date'])->format('Y-m-d H:i:s')) ?></td>
                            <td><?= htmlspecialchars($row['Requestor'] ?? '') ?></td> <!-- The "?? '' "  allows null values to be displayed correctly -->
                            <td><?= htmlspecialchars($row['Details']) ?></td>
                            <td><?= htmlspecialchars($row['Status']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
        <!-- Navigation -->
        <div class="navigation">
            <br><br>
            <button onclick="location.href='FrontPage.html'" class="btn btn-secondary">Return to Front Page</button>
            <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">Report an Issue</button>
        </div>
    </div>
</body>
</html>