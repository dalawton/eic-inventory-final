<?php
require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection parameters
    // This stores all the server information in variables which are local to this specific file
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

function sendPurchaseOrderEmail($formData, $products, $vendorDetails) {
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
        $mail->addCC($_ENV['PATSY_EMAIL'], 'Patricia Riley');
        $mail->addCC($_ENV['MAX_EMAIL'], 'Maxwell Landolphi');
        // $mail->addCC('', '');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Purchase Order Requisition - PO #' . ($formData['purchaseOrderNumber'] ?? 'N/A');

        // Generate email body
        $emailBody = generatePurchaseOrderEmailBody($formData, $products, $vendorDetails);
        $mail->Body = $emailBody;

        // Plain text version
        $mail->AltBody = generatePlainTextVersion($formData, $products);

        // Send the email
        $mail->send();
        return ['success' => true, 'message' => 'Purchase order email sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo];
    }
}

function generatePurchaseOrderEmailBody($formData, $products, $vendorDetails = []) {
    // Use DB values if found, otherwise fallback to form data
    $vendorID = $vendorDetails['vendorID'] ?? $formData['vendorID'] ?? $formData['otherVendorID'] ?? 'N/A';
    $vendorName = $vendorDetails['VendorName'] ?? $formData['otherName'] ?? 'N/A';
    $telephone = $vendorDetails['Telephone'] ?? $formData['vendorPhone'] ?? 'N/A';
    $contactName = $vendorDetails['ContactName'] ?? $formData['contactName'] ?? 'N/A';
    $contactEmail = $vendorDetails['ContactEmail'] ?? $formData['contactEmail'] ?? 'N/A';
    $addressLine1 = $vendorDetails['AddressLine1'] ?? $formData['streetAddress'] ?? 'N/A';
    $citySTZIP = $vendorDetails['CitySTZIP'] ?? (
        ($formData['supplierCity'] ?? '') . ', ' .
        ($formData['supplierState'] ?? '') . ' ' .
        ($formData['supplierZip'] ?? '')
    ) ?: 'N/A';

    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Purchase Order Requisition</title>
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
            .price-cell {
                text-align: right;
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
            <h1>Purchase Order Requisition Form</h1>
            <p><strong>PO Number:</strong> ' . htmlspecialchars($formData['purchaseOrderNumber'] ?? 'N/A') . '</p>
            <p><strong>Date:</strong> ' . htmlspecialchars($formData['date'] ?? 'N/A') . '</p>
            <p><strong>Submitted:</strong> ' . date('Y-m-d H:i:s') . '</p>
        </div>';

    // Special Requests Section
    if (!empty($formData['needWorkOrder']) || !empty($formData['confirming'])) {
        $html .= '
        <div class="section">
            <h3>Special Requests</h3>
            <div class="checkbox-section">';
        
        if (!empty($formData['needWorkOrder'])) {
            $html .= '<span class="checkbox-item">✓ Need Work Order</span>';
        }
        if (!empty($formData['confirming'])) {
            $html .= '<span class="checkbox-item">✓ Confirming - DO NOT DUPLICATE</span>';
        }
        
        $html .= '</div></div>';
    }

    // Vendor Information Section
    $html .= '
    <div class="section">
        <h3>Vendor Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Vendor ID:</span><br>
                ' . htmlspecialchars($vendorID) . '
            </div>
            <div class="info-item">
                <span class="info-label">Vendor Name:</span><br>
                ' . htmlspecialchars($vendorName) . '
            </div>
            <div class="info-item">
                <span class="info-label">Phone:</span><br>
                ' . htmlspecialchars($telephone) . '
            </div>
            <div class="info-item">
                <span class="info-label">Contact Name:</span><br>
                ' . htmlspecialchars($contactName) . '
            </div>
            <div class="info-item">
                <span class="info-label">Contact Email:</span><br>
                ' . htmlspecialchars($contactEmail) . '
            </div>
            <div class="info-item">
                <span class="info-label">Street Address:</span><br>
                ' . htmlspecialchars($addressLine1) . '
            </div>
            <div class="info-item">
                <span class="info-label">City, State ZipCode:</span><br>
                ' . htmlspecialchars($citySTZIP) . '
            </div>
        </div>
    </div>';

    // Payment Information Section
    $html .= '
    <div class="section">
        <h3>Payment & Order Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Payment Method:</span><br>';
    
    $paymentMethods = [];
    if (!empty($formData['citizensBank'])) {
        $paymentMethods[] = 'Citizens Bank m/c';
    }
    if (!empty($formData['employeeReimbursement'])) {
        $paymentMethods[] = 'Employee Reimbursement';
    }
    
    $html .= !empty($paymentMethods) ? implode(', ', $paymentMethods) : 'Not specified';
    
    $html .= '
            </div>
            <div class="info-item">
                <span class="info-label">Order Type:</span><br>';
    
    $orderTypes = [];
    if (!empty($formData['placeByRequester'])) {
        $orderTypes[] = 'Order Placed By Requestor';
    }
    if (!empty($formData['needPlaced'])) {
        $orderTypes[] = 'Order Needs to be Placed';
    }
    
    $html .= !empty($orderTypes) ? implode(', ', $orderTypes) : 'Not specified';
    
    $html .= '
            </div>
            <div class="info-item">
                <span class="info-label">Contract Number:</span><br>
                ' . htmlspecialchars($formData['contractNumber'] ?? 'N/A');
    
    if (!empty($formData['otherContractNumber'])) {
        $html .= ' - ' . htmlspecialchars($formData['otherContractNumber']);
    }
    
    $html .= '
            </div>
            <div class="info-item">
                <span class="info-label">Requestor:</span><br>
                ' . htmlspecialchars($formData['requestorName'] ?? 'N/A') . '
            </div>
        </div>
        
        <div class="highlight">
            <strong>Delivery Address:</strong> 111 Downey Street, Norwood, MA 02062
        </div>
    </div>';

    // Products Section with proper price formatting
    if (!empty($products)) {
        $html .= '
        <div class="section">
            <h3>Requested Products</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product Number</th>
                        <th>Description</th>
                        <th style="text-align: center;">Quantity</th>
                        <th style="text-align: right;">Unit Price</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>';

        $grandTotal = 0;
        foreach ($products as $product) {
            // Ensure proper numeric parsing
            $unitPrice = floatval($product['unitPrice'] ?? 0);
            $quantity = intval($product['quantity'] ?? 0);
            $total = floatval($product['total'] ?? 0);
            
            // If total is 0, calculate it
            if ($total == 0) {
                $total = $unitPrice * $quantity;
            }
            
            $grandTotal += $total;

            $html .= '
                <tr>
                    <td>' . htmlspecialchars($product['productNumber'] ?? '') . '</td>
                    <td>' . htmlspecialchars($product['description'] ?? '') . '</td>
                    <td style="text-align: center;">' . $quantity . '</td>
                    <td class="price-cell">$' . number_format($unitPrice, 2) . '</td>
                    <td class="price-cell">$' . number_format($total, 2) . '</td>
                </tr>';
        }

        $html .= '
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right;"><strong>Grand Total:</strong></td>
                        <td class="price-cell"><strong>$' . number_format($grandTotal, 2) . '</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>';
    }

    $html .= '
        <div style="background: white; padding: 15px; border: 2px dashed #007bff; margin-top: 20px;">
            <p><strong>Your Signature:</strong></p>
            <br>
            <div style="border-bottom: 3px solid #000; height: 50px; margin: 10px 0;"></div>
            
            <p><strong>Date:</strong></p>
            <br>
            <div style="border-bottom: 2px solid #000; height: 25px; margin: 10px 0; width: 200px;"></div>
        </div>
    </body>
    </html>';

    return $html;
}

function generatePlainTextVersion($formData, $products) {
    $text = "PURCHASE ORDER REQUISITION FORM\n";
    $text .= "================================\n\n";
    
    $text .= "PO Number: " . ($formData['purchaseOrderNumber'] ?? 'N/A') . "\n";
    $text .= "Date: " . ($formData['date'] ?? 'N/A') . "\n";
    $text .= "Submitted: " . date('Y-m-d H:i:s') . "\n\n";

    // Special Requests
    if (!empty($formData['needWorkOrder']) || !empty($formData['confirming'])) {
        $text .= "SPECIAL REQUESTS:\n";
        if (!empty($formData['needWorkOrder'])) {
            $text .= "- Need Work Order\n";
        }
        if (!empty($formData['confirming'])) {
            $text .= "- Confirming - DO NOT DUPLICATE\n";
        }
        $text .= "\n";
    }

    // Vendor Information
    $text .= "VENDOR INFORMATION:\n";
    $text .= "Vendor ID: " . ($formData['vendorID'] ?? $formData['otherVendorID'] ?? 'N/A') . "\n";
    $text .= "Vendor Name: " . ($formData['otherName'] ?? 'Selected from database') . "\n";
    
    if (!empty($formData['streetAddress'])) {
        $text .= "Address: " . $formData['streetAddress'] . "\n";
    }
    if (!empty($formData['supplierCity'])) {
        $text .= "City: " . $formData['supplierCity'] . "\n";
    }
    if (!empty($formData['supplierState'])) {
        $text .= "State: " . $formData['supplierState'] . "\n";
    }
    if (!empty($formData['supplierZip'])) {
        $text .= "Zip: " . $formData['supplierZip'] . "\n";
    }
    if (!empty($formData['vendorPhone'])) {
        $text .= "Phone: " . $formData['vendorPhone'] . "\n";
    }
    $text .= "\n";

    // Payment & Order Information
    $text .= "PAYMENT & ORDER INFORMATION:\n";
    $text .= "Requestor: " . ($formData['requestorName'] ?? 'N/A') . "\n";
    $text .= "Contract Number: " . ($formData['contractNumber'] ?? 'N/A');
    if (!empty($formData['otherContractNumber'])) {
        $text .= " - " . $formData['otherContractNumber'];
    }
    $text .= "\n";
    $text .= "Delivery Address: 111 Downey Street, Norwood, MA 02062\n\n";

    // Products with proper price formatting
    if (!empty($products)) {
        $text .= "REQUESTED PRODUCTS:\n";
        $text .= str_repeat("-", 80) . "\n";
        $text .= sprintf("%-15s %-30s %-8s %-12s %-12s\n", "Product #", "Description", "Qty", "Unit Price", "Total");
        $text .= str_repeat("-", 80) . "\n";

        $grandTotal = 0;
        foreach ($products as $product) {
            $unitPrice = floatval($product['unitPrice'] ?? 0);
            $quantity = intval($product['quantity'] ?? 0);
            $total = floatval($product['total'] ?? 0);
            
            if ($total == 0) {
                $total = $unitPrice * $quantity;
            }
            
            $grandTotal += $total;
            
            $text .= sprintf("%-15s %-30s %-8s $%-11s $%-11s\n",
                substr($product['productNumber'] ?? '', 0, 15),
                substr($product['description'] ?? '', 0, 30),
                $quantity,
                number_format($unitPrice, 2),
                number_format($total, 2)
            );
        }
        
        $text .= str_repeat("-", 80) . "\n";
        $text .= sprintf("%56s $%-11s\n", "GRAND TOTAL:", number_format($grandTotal, 2));
    }

    $text .= "\n\n";
    $text .= "APPROVAL SIGNATURES:\n";
    $text .= str_repeat("=", 50) . "\n\n";
    
    $text .= "APPROVAL:\n";
    $text .= "Signature: _________________________________ Date: __________\n";
    $text .= "Print Name: _____________________________\n";

    return $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;
    $products = json_decode($formData['productsJSON'], true);

    // Debug: Log received data
    error_log("Received products JSON: " . $formData['productsJSON']);
    
    $final_total = 0.00;
    $processedProducts = [];

    foreach ($products as $product) {
        $productNumber = $product['productNumber'] ?? '';
        $description = $product['description'] ?? '';
        $quantity = isset($product['quantity']) ? intval($product['quantity']) : 0;
        
        // Better price parsing - handle both string and numeric values
        $unitPrice = 0.00;
        if (isset($product['unitPrice'])) {
            if (is_string($product['unitPrice'])) {
                // Remove $ and other characters, then parse
                $unitPrice = floatval(preg_replace('/[^0-9.]/', '', $product['unitPrice']));
            } else {
                $unitPrice = floatval($product['unitPrice']);
            }
        }
        
        // Calculate total (don't rely on frontend calculation)
        $lineTotal = round($quantity * $unitPrice, 2);
        $final_total += $lineTotal;

        // Debug: Log individual product processing
        error_log("Processing product: {$productNumber}, Qty: {$quantity}, Unit: {$unitPrice}, Total: {$lineTotal}");

        $processedProducts[] = [
            'productNumber' => $productNumber,
            'description' => $description,
            'quantity' => $quantity,
            'unitPrice' => $unitPrice,
            'total' => $lineTotal
        ];
    }

    // Debug: Log final total
    error_log("Final total calculated: " . $final_total);

    $purchaseOrderNumber = $formData['purchaseOrderNumber'];
    $vendorID = $formData['vendorID'];
    $orderDate = $formData['date'];
    $requestor = $formData['requestorName'];
    $contractNumber = $formData['otherContractNumber'];
    $otherName = $formData['otherName'];
    $supplierStreetAddress = $formData['streetAddress'];
    $supplierCity = $formData['supplierCity'];
    $supplierState = $formData['supplierState'];
    $supplierZip = $formData['supplierZip'];
    $supplierCitySTZIP = $supplierCity . ", " . $supplierState . " " . $supplierZip;
    $vendorPhone = $formData['vendorPhone'];

    // Add new vendor if needed
    if (!empty($otherName)) {
        $sql = "INSERT INTO dbo.Vendors (VendorID, VendorName, Telephone, AddressLine1, CitySTZIP) VALUES (?, ?, ?, ?, ?)";
        $params = [$vendorID, $otherName, $vendorPhone, $supplierStreetAddress, $supplierCitySTZIP];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            error_log("Vendor insert failed: " . print_r(sqlsrv_errors(), true));
            die("Error inserting vendor: " . print_r(sqlsrv_errors(), true));
        }
    }

    // Add new contract number if needed
    if (!empty($contractNumber)) {
        $sql = "INSERT INTO dbo.contractNumbers (contractNumber) VALUES (?)";
        $params = [$contractNumber];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            error_log("Contract insert failed: " . print_r(sqlsrv_errors(), true));
            die("Error inserting contract: " . print_r(sqlsrv_errors(), true));
        }
    }

    // Insert Purchase Order with correct price
    $sql = "INSERT INTO dbo.POs (PONum, Purchaser, Date, VendorID, Price, Status) VALUES (?, ?, ?, ?, ?, ?)";
    $params = [$purchaseOrderNumber, $requestor, $orderDate, $vendorID, $final_total, "Ordered"];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        error_log("PO insert failed: " . print_r(sqlsrv_errors(), true));
        die("Error inserting PO: " . print_r(sqlsrv_errors(), true));
    }

    // Insert POItems with correct prices
    foreach ($processedProducts as $product) {
        $sql = "INSERT INTO dbo.POItems (PONum, PN, Quantity, UnitPrice, Description, Total) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [
            $purchaseOrderNumber,
            $product['productNumber'],
            $product['quantity'],
            $product['unitPrice'],
            $product['description'],
            $product['total']
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            error_log("POItem insert failed: " . print_r(sqlsrv_errors(), true));
            die("Error inserting POItem: " . print_r(sqlsrv_errors(), true));
        }
    }

    // Get vendor details for email
    $vendorDetails = null;
    if ($vendorID) {
        $sql = "SELECT vendorID, VendorName, Telephone, AddressLine1, CitySTZIP, ContactName, ContactEmail FROM dbo.Vendors WHERE VendorID = ?";
        $stmt = sqlsrv_query($conn, $sql, [$vendorID]);
        if ($stmt !== false) {
            $vendorDetails = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
    }

    // Send email
    $result = sendPurchaseOrderEmail($formData, $processedProducts, $vendorDetails);
    
    if ($result['success']) {
        echo "Purchase order submitted successfully! Total: $" . number_format($final_total, 2) . ". Email sent.";
    } else {
        echo "Purchase order submitted but email failed: " . $result['message'];
    }
}
?>
