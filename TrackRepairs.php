<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to display all repairs and their information and status
 *
 * PHP version 8
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Get_Files
 * @package   None
 * @author    Danielle Lawton <daniellelawton8@gmail.com>
 * @copyright 1999 - 2019 The PHP Group
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      https://pear.php.net/package/None
 */

// phpcs:disable Generic.Files.LineLength.TooLong

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

$sql = "SELECT SerialNumber, Requester, DateReceived, Details, Status, DateShipped, ShippingLocation FROM dbo.Repairs";
$stmt = sqlsrv_query($conn, $sql);

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Track Repairs</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="styleIssueReport.css">
        <style>
            .product-table-container {
                overflow-x: auto;
                margin: 30px 0;
            }
            
            .product-table {
                width: 100%;
                border-collapse: collapse;
                table-layout: auto;
                min-width: 100%;
            }
            
            .product-table th,
            .product-table td {
                padding: 8px 6px;
                font-size: 0.85rem;
                word-break: break-word;
                border: 1px solid #e1e5e9;
                text-align: center;
                vertical-align: top;
            }
            
            .product-table th {
                background: #f8f9fa;
                font-weight: 600;
                color: #2c3e50;
                white-space: nowrap;
                min-width: 20px;
                max-width: 150px;
                position: sticky;
                top: 0;
                z-index: 10;
            }
            
            .product-table th:nth-child(1),
            .product-table td:nth-child(1) {
                max-width: 100px;
                width: 100px;
            }
            
            .product-table th:nth-child(2),
            .product-table td:nth-child(2) {
                max-width: 100px;
                width: 100px;

            }
            
            .product-table th:nth-child(3),
            .product-table td:nth-child(3) {
                max-width: 90px;
                width: 90px;
            }

            .product-table th:nth-child(4),
            .product-table td:nth-child(4) {
                max-width: 200px;
                width: 200px;
            }

            .product-table th:nth-child(5),
            .product-table td:nth-child(5) {
                max-width: 80px;
                width: 80px;
            }

            .product-table th:nth-child(6),
            .product-table td:nth-child(6) {
                max-width: 90px;
                width: 90px;

            }.product-table th:nth-child(7),
            .product-table td:nth-child(7) {
                max-width: 100px;
                width: 100px;
            }

            .product-table td {
                white-space: normal;
                max-width: 150px;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .product-table tr:hover td {
                background: #f8f9ff;
            }

            .product-table td span.status-label {
            padding: 2px 8px;
            border-radius: 4px;
            color: #fff;
            font-size: 0.95em;
            }

            .product-table td span.status-inbound { background: #2196f3; }
            .product-table td span.status-in-house { background: #f89f39ff; }
            .product-table td span.status-completed { background: #4caf50; }
            .product-table td span.status-received { background: #f44336; }
            .product-table td span.status-other { background: #888; }
                
            @media (max-width: 1200px) {
                .product-table th,
                .product-table td {
                    font-size: 0.8rem;
                    padding: 6px 4px;
                    max-width: 120px;
                }
            }
            
            @media (max-width: 768px) {
                .product-table th,
                .product-table td {
                    font-size: 0.75rem;
                    padding: 4px 2px;
                    max-width: 100px;
                }
                
                .product-table th {
                    min-width: 60px;
                }
            }
        </style>
    </head>
    <body>
        <div class="main-container">
            <div class="header">
                <h1>Track Repairs</h1>
            </div>
            <div class="form-content">
                <div class="product-table-container">
                    <div class="table-header"></div>
                    <table class="product-table">
                        <thead>
                            <?php
                            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                            if ($row) {
                                echo "<tr>";
                                foreach (array_keys($row) as $colName) {
                                    if ($colName === 'Requester') {
                                        echo "<th>Company</th>";
                                    } elseif ($colName === 'SerialNumber') {
                                        echo "<th>SN</th>";
                                    } elseif ($colName === 'DateShipped') {
                                        echo "<th>Date Shipped</th>";
                                    } elseif ($colName === 'DateReceived') {
                                        echo "<th>Date Received</th>";
                                    } elseif ($colName === 'ShippingLocation') {
                                        echo "<th>Shipping Location</th>";
                                    } else {
                                        echo "<th>" . htmlspecialchars($colName) . "</th>";
                                    }
                                }
                                echo "</tr>";
                                ?>
                        </thead>
                        <tbody>
                                <?php
                                echo "<tr>";
                                foreach ($row as $value) {
                                    $statusClass = "status-other";
                                    if ($value === "NEEDS REPAIR") {
                                        $statusClass = "status-received";
                                    } elseif ($value === "SHIPPED") {
                                        $statusClass = "status-completed";
                                    } elseif ($value === "IN-BOUND") {
                                        $statusClass = "status-inbound";
                                    } elseif ($value === "IN-HOUSE") {
                                        $statusClass = "status-in-house";
                                    } else {
                                        $statusClass = "status-other";
                                    }
                                    
                                    if ($value instanceof DateTime) {
                                        echo "<td>" . htmlspecialchars($value->format('Y-m-d')) . "</td>";
                                    } elseif ($statusClass != "status-other") {
                                        echo "<td><span class='status-label $statusClass'>" . htmlspecialchars((string)$value) . "</span></td>";
                                    } else {
                                        echo "<td>" . htmlspecialchars((string)$value) . "</td>";
                                    }
                                }
                                echo "</tr>";

                                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                                    echo "<tr>";
                                    foreach ($row as $value) {
                                        $statusClass = "status-other";
                                        if ($value === "NEEDS REPAIR") {
                                            $statusClass = "status-received";
                                        } elseif ($value === "SHIPPED") {
                                            $statusClass = "status-completed";
                                        } elseif ($value === "IN-BOUND") {
                                            $statusClass = "status-inbound";
                                        } elseif ($value === "IN-HOUSE") {
                                            $statusClass = "status-in-house";
                                        } else {
                                            $statusClass = "status-other";
                                        }
                                        
                                        if ($value instanceof DateTime) {
                                            echo "<td>" . htmlspecialchars($value->format('Y-m-d')) . "</td>";
                                        } elseif ($statusClass != "status-other") {
                                            echo "<td><span class='status-label $statusClass'>" . htmlspecialchars((string)$value) . "</span></td>";
                                        } else {
                                            echo "<td>" . htmlspecialchars((string)$value) . "</td>";
                                        }
                                    }
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='100%'>No repairs found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="navigation">
                <button onclick="location.href='FrontPage.html'" class="btn btn-secondary">
                    Return to Front Page
                </button>
                <button onclick="location.href='ReportIssue.html'" class="btn btn-secondary">
                    Report an Issue
                </button>
            </div>
        </div>
    </body>
</html>

<?php
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
