<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File to log scanning of barcodes for inventory purposes
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

$sql = "SELECT BatteryName FROM dbo.Battery";
$stmt = sqlsrv_query($conn, $sql);
$batteryOptions = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $batteryOptions[] = $row['BatteryName'];
}
if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Scanner - Build Battery</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="styleScanning.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1>Barcode Scanner - Build Battery</h1>
            <p>Scan parts to collect them, then scan a battery barcode to complete the build</p>
        </div>
        <div class="form-content">
            <div class="scanner-container">                
                <div class="section-title">
                    <h3 id="scannerStatus">Scan a part barcode to begin collecting</h3>
                    <p id="scannerInstructions">Place cursor in the input field and scan barcodes with your scanner</p>
                </div>
                
                <input type="text" 
                       id="barcodePartsInput" 
                       class="form-control" 
                       placeholder="Click here and scan barcode..."
                       autocomplete="off">
                       
                <div id="messageAreaParts"></div>
            </div>

            <div class="parts-collected">
                <h3 class="section-title">Parts Collected (<span id="partCount">0</span>)</h3>
                <div id="collectedParts">
                    <p style="color: #666; font-style: italic;">No parts scanned yet</p>
                </div>
                
                <button id="clearParts" class="btn btn-secondary" style="margin-top: 15px;">
                    Clear All Parts
                </button>
            </div>

            <div class="scanner-container">                
                <div class="section-title">
                    <h3 id="scannerStatus1">Scan battery barcode</h3>
                    <p id="scannerInstructions1">Place cursor in the input field and scan barcode with your scanner</p>
                </div>
                
                <input type="text" 
                       id="barcodeBatteryInput" 
                       class="form-control" 
                       placeholder="Click here and scan barcode..."
                       autocomplete="off">
                       
                <div id="messageAreaBattery"></div>
            </div>
            <div class="scanner-container">
                <select name="batteryName" class="form-control" id="batteryName" class="hidden">
                    <option value="">--Select Battery--</option>
                    <?php foreach ($batteryOptions as $Name) : ?>
                    <option value="<?php echo htmlspecialchars($Name) ?>" 
                        <?php echo $selectedBattery === $Name ? 'selected' : '' ?>> 
                        <?php echo htmlspecialchars($Name) ?>
                    </option>
                    <?php endforeach; ?>
                    <option value="newBattery" 
                        <?php echo $selectedBattery === 'newBattery' ? 'selected' : '' ?>>
                            Create a New Battery Option
                    </option>
                </select>

                <script>
                    $(document).ready(function() {
                        $('#batteryName').select2({});
                    });
                </script>
            </div>
            <div style="text-align: center;">
                <button id="completeBuild" class="complete-build-btn" disabled>
                    Complete Battery Build
                </button>
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

    <script>
        class BarcodeScanner {
            constructor() {
                this.collectedParts = new Map();
                this.isProcessing = false;
                this.init();
            }

            init() {
                this.bindEvents();
                this.focusInput();
                this.updateDisplay();
            }

            bindEvents() {
                const input = $('#barcodePartsInput');
                input.on('keypress', (e) => {
                    if (e.which === 13) {
                        e.preventDefault();
                        this.processScan(input.val().trim());
                        input.val('');
                    }
                });

                $('#clearParts').on('click', () => this.clearAllParts());
                $(document).on('click', '.remove-btn', (e) => {
                    const partNumber = $(e.target).data('part');
                    this.removePart(partNumber);
                });

                $('#completeBuild').on('click', () => {
                    const batteryBarcode = $('#barcodeBatteryInput').val().trim();
                    const batteryName = $('#batteryName').val();
                    this.processBattery(batteryBarcode, batteryName);
                });
            }


            async processScan(barcode) {
                if (!barcode || this.isProcessing) return;
                
                this.isProcessing = true;
                this.showMessage('Processing scan...', 'info');

                try {
                    await this.addPart(barcode);
                } catch (error) {
                    this.showMessage('Error processing scan: ' + error.message, 'error');
                } finally {
                    this.isProcessing = false;
                    this.focusInput();
                }
            }

            async addPart(partNumber) {
                const partInfo = await this.getPartInfo(partNumber);

                if (!partInfo.exists) {
                    this.showMessage(`Part "${partNumber}" not found in inventory or as a component`, 'error');
                    return;
                }
                if (partInfo.isComponent && partInfo.quantity <= 0) {
                    this.showMessage(`Component "${partNumber}" is not available (not IN-HOUSE)`, 'error');
                    return;
                }

                if (!partInfo.isComponent && partInfo.quantity <= 0) {
                    this.showMessage(`Part "${partNumber}" is out of stock`, 'error');
                    return;
                }

                if (this.collectedParts.has(partNumber)) {
                    const current = this.collectedParts.get(partNumber);

                    if (partInfo.isComponent) {
                        this.showMessage(`Component "${partNumber}" can only be used once`, 'error');
                        return;
                    }

                    if (current.quantity >= partInfo.quantity) {
                        this.showMessage(`Cannot add more - only ${partInfo.quantity} available in inventory`, 'error');
                        return;
                    }

                    current.quantity++;
                    current.lastScanned = new Date();
                } else {
                    this.collectedParts.set(partNumber, {
                        quantity: 1,
                        description: partInfo.description,
                        lastScanned: new Date(),
                        isComponent: partInfo.isComponent
                    });
                }

                this.showMessage(`Added: ${partNumber}`, 'success');
                this.updateDisplay();
            }

            async processBattery(batteryBarcode, batteryName) {
                if (this.collectedParts.size === 0) {
                    this.showMessage('No parts collected. Please scan some parts first.', 'error');
                    return;
                }
                const buildData = {
                    batteryBarcode: batteryBarcode,
                    batteryName: batteryName,
                    parts: Array.from(this.collectedParts.entries()).map(([partNumber, info]) => ({
                        partNumber: partNumber,
                        quantity: info.quantity,
                        description: info.description,
                        isComponent: info.isComponent
                    }))
                };

                const success = await this.submitBuild(buildData);

                if (success) {
                    this.showMessage(`Battery "${batteryBarcode}" built successfully!`, 'success');
                    this.clearAllParts();
                    $('#barcodeBatteryInput').val('');
                }
            }

            async getPartInfo(partNumber) {
                try {
                    const response = await fetch(`getPartInfo.php?partNumber=${encodeURIComponent(partNumber)}`);
                    if (!response.ok) throw new Error('Network error');
                    const data = await response.json();
                    return {
                        exists: data.exists,
                        quantity: data.quantity || 0,
                        description: data.description || '',
                        isComponent: data.isComponent
                    };
                } catch (error) {
                    console.error('Error fetching part info:', error);
                    return { exists: false, quantity: 0, description: '' };
                }
            }


            async submitBuild(buildData) {
                try {
                    const response = await fetch('processBarcodeScans.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(buildData)
                    });

                    if (!response.ok) {
                        throw new Error('Failed to process build');
                    }

                    const result = await response.text();
                    console.log('Build result:', result);
                    return true;
                } catch (error) {
                    console.error('Error submitting build:', error);
                    this.showMessage('Error submitting build: ' + error.message, 'error');
                    return false;
                }
            }

            removePart(partNumber) {
                this.collectedParts.delete(partNumber);
                this.updateDisplay();
                this.showMessage(`Removed: ${partNumber}`, 'info');
            }

            clearAllParts() {
                this.collectedParts.clear();
                this.updateDisplay();
                this.showMessage('All parts cleared', 'info');
            }

            updateDisplay() {
                const container = $('#collectedParts');
                const count = $('#partCount');
                
                count.text(this.collectedParts.size);

                if (this.collectedParts.size === 0) {
                    container.html('<p style="color: #666; font-style: italic;">No parts scanned yet</p>');
                    $('#completeBuild').prop('disabled', true);
                    return;
                }

                let html = '';
                for (const [partNumber, info] of this.collectedParts.entries()) {
                    html += `
                        <div class="part-item">
                            <div>
                                <strong>${partNumber}</strong><br>
                                <small style="color: #666;">${info.description}</small>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="quantity-badge">${info.quantity}</span>
                                <button class="remove-btn" data-part="${partNumber}">Remove</button>
                            </div>
                        </div>
                    `;
                }
                
                container.html(html);
                $('#completeBuild').prop('disabled', false);
            }

            showMessage(message, type = 'info') {
                const messageArea = $('#messageAreaParts');
                const className = type === 'error' ? 'error-message' : 
                                type === 'success' ? 'success-message' : 'info-message';
                
                messageArea.html(`<div class="${className}">${message}</div>`);
            }

            completeBuild() {
                if (this.collectedParts.size === 0) {
                    this.showMessage('No parts to process', 'error');
                    return;
                }
                
                this.showMessage('Now scan the battery barcode to complete the build', 'info');
            }
        }

        $(document).ready(() => {
            window.scanner = new BarcodeScanner();
        });
    </script>
</body>
</html>