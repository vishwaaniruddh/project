<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvDispatchService.php';
require_once '../../../services/SarInvWarehouseService.php';

Auth::requireRole(ADMIN_ROLE);

$dispatchService = new SarInvDispatchService();
$warehouseService = new SarInvWarehouseService();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die('Invalid dispatch ID');
}

$dispatch = $dispatchService->getDispatchWithDetails($id);
if (!$dispatch) {
    die('Dispatch not found');
}

// Get source warehouse details
$warehouse = $warehouseService->getWarehouse($dispatch['source_warehouse_id']);

// Generate barcode data (dispatch number)
$barcodeData = $dispatch['dispatch_number'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Label - <?php echo htmlspecialchars($dispatch['dispatch_number']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .label-container {
            width: 4in;
            min-height: 6in;
            background: white;
            margin: 0 auto;
            padding: 0.25in;
            border: 2px solid #000;
            position: relative;
        }
        
        .label-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .label-header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .dispatch-number {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        
        .barcode-section {
            text-align: center;
            margin: 15px 0;
            padding: 10px 0;
            border-top: 1px dashed #ccc;
            border-bottom: 1px dashed #ccc;
        }
        
        .barcode {
            font-family: 'Libre Barcode 39', cursive;
            font-size: 48px;
            line-height: 1;
        }
        
        .barcode-text {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .address-section {
            margin: 15px 0;
        }
        
        .address-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
        }
        
        .address-box {
            border: 1px solid #ccc;
            padding: 10px;
            min-height: 80px;
            margin-bottom: 15px;
        }
        
        .address-box.destination {
            border: 2px solid #000;
            background: #f9f9f9;
        }
        
        .address-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .address-text {
            font-size: 12px;
            line-height: 1.4;
        }
        
        .items-section {
            margin: 15px 0;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        .items-header {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
        }
        
        .items-table {
            width: 100%;
            font-size: 10px;
            border-collapse: collapse;
        }
        
        .items-table th,
        .items-table td {
            padding: 4px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .items-table th {
            font-weight: bold;
            background: #f5f5f5;
        }
        
        .items-table td.qty {
            text-align: right;
        }
        
        .label-footer {
            position: absolute;
            bottom: 0.25in;
            left: 0.25in;
            right: 0.25in;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            font-size: 9px;
            color: #666;
        }
        
        .footer-row {
            display: flex;
            justify-content: space-between;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 3px;
            background: #e5e7eb;
            color: #374151;
        }
        
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.approved { background: #dbeafe; color: #1e40af; }
        .status-badge.shipped { background: #dbeafe; color: #1e40af; }
        .status-badge.in_transit { background: #e0e7ff; color: #3730a3; }
        .status-badge.delivered { background: #d1fae5; color: #065f46; }
        .status-badge.cancelled { background: #fee2e2; color: #991b1b; }
        
        .print-controls {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .print-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 30px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            margin-right: 10px;
        }
        
        .print-btn:hover {
            background: #1d4ed8;
        }
        
        .back-btn {
            background: #6b7280;
            color: white;
            border: none;
            padding: 10px 30px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            text-decoration: none;
        }
        
        .back-btn:hover {
            background: #4b5563;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .print-controls {
                display: none;
            }
            
            .label-container {
                border: none;
                width: 4in;
                height: 6in;
                page-break-after: always;
            }
        }
        
        /* Simple barcode using CSS */
        .barcode-visual {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            height: 50px;
            gap: 1px;
        }
        
        .barcode-bar {
            background: #000;
            width: 2px;
        }
        
        .barcode-bar.thin { width: 1px; }
        .barcode-bar.thick { width: 3px; }
        .barcode-bar.space { background: white; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap" rel="stylesheet">
</head>
<body>
    <div class="print-controls">
        <button class="print-btn" onclick="window.print()">
            🖨️ Print Label
        </button>
        <a href="<?php echo url('/admin/sar-inventory/dispatches/view.php?id=' . $id); ?>" class="back-btn">
            ← Back to Dispatch
        </a>
    </div>
    
    <div class="label-container">
        <div class="label-header">
            <h1>SHIPPING LABEL</h1>
            <div class="dispatch-number"><?php echo htmlspecialchars($dispatch['dispatch_number']); ?></div>
            <span class="status-badge <?php echo $dispatch['status']; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $dispatch['status'])); ?>
            </span>
        </div>
        
        <div class="barcode-section">
            <div class="barcode">*<?php echo htmlspecialchars($barcodeData); ?>*</div>
            <div class="barcode-text"><?php echo htmlspecialchars($barcodeData); ?></div>
        </div>
        
        <div class="address-section">
            <div class="address-label">From (Sender)</div>
            <div class="address-box">
                <div class="address-name"><?php echo htmlspecialchars($warehouse['name'] ?? 'Warehouse'); ?></div>
                <div class="address-text">
                    <?php echo htmlspecialchars($warehouse['code'] ?? ''); ?><br>
                    <?php echo nl2br(htmlspecialchars($warehouse['address'] ?? $warehouse['location'] ?? '')); ?>
                </div>
            </div>
            
            <div class="address-label">To (Recipient)</div>
            <div class="address-box destination">
                <div class="address-name"><?php echo ucfirst(htmlspecialchars($dispatch['destination_type'])); ?></div>
                <div class="address-text">
                    <?php echo nl2br(htmlspecialchars($dispatch['destination_address'])); ?>
                </div>
            </div>
        </div>
        
        <div class="items-section">
            <div class="items-header">Package Contents (<?php echo count($dispatch['items']); ?> items)</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>SKU</th>
                        <th class="qty">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalQty = 0;
                    foreach ($dispatch['items'] as $item): 
                        $totalQty += $item['quantity'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['sku']); ?></td>
                        <td class="qty"><?php echo number_format($item['quantity']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight: bold; background: #f5f5f5;">
                        <td colspan="2">Total</td>
                        <td class="qty"><?php echo number_format($totalQty); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="label-footer">
            <div class="footer-row">
                <span>Created: <?php echo date('M j, Y', strtotime($dispatch['created_at'])); ?></span>
                <span>
                    <?php if ($dispatch['dispatch_date']): ?>
                    Ship Date: <?php echo date('M j, Y', strtotime($dispatch['dispatch_date'])); ?>
                    <?php else: ?>
                    Ship Date: Pending
                    <?php endif; ?>
                </span>
            </div>
            <?php if (!empty($dispatch['notes'])): ?>
            <div style="margin-top: 5px; font-style: italic;">
                Note: <?php echo htmlspecialchars(substr($dispatch['notes'], 0, 100)); ?>
                <?php if (strlen($dispatch['notes']) > 100): ?>...<?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
