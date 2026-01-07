<?php
require_once __DIR__ . '/../models/SarInvWarehouse.php';
require_once __DIR__ . '/../models/SarInvProduct.php';
require_once __DIR__ . '/../models/SarInvStock.php';
require_once __DIR__ . '/../models/SarInvDispatch.php';
require_once __DIR__ . '/../models/SarInvTransfer.php';
require_once __DIR__ . '/../models/SarInvAsset.php';
require_once __DIR__ . '/../models/SarInvRepair.php';
require_once __DIR__ . '/../models/SarInvItemHistory.php';

/**
 * SAR Inventory Reporting Service
 * Customizable report generation with multiple output formats
 */
class SarInvReportingService {
    private $warehouseModel;
    private $productModel;
    private $stockModel;
    private $dispatchModel;
    private $transferModel;
    private $assetModel;
    private $repairModel;
    private $historyModel;
    
    public function __construct() {
        $this->warehouseModel = new SarInvWarehouse();
        $this->productModel = new SarInvProduct();
        $this->stockModel = new SarInvStock();
        $this->dispatchModel = new SarInvDispatch();
        $this->transferModel = new SarInvTransfer();
        $this->assetModel = new SarInvAsset();
        $this->repairModel = new SarInvRepair();
        $this->historyModel = new SarInvItemHistory();
    }
    
    /**
     * Generate stock report
     * @param array $filters Filter criteria
     * @param string $format Output format (csv, excel, pdf)
     * @return array Report data with content
     */
    public function generateStockReport(array $filters = [], string $format = 'csv'): array {
        $data = $this->getStockReportData($filters);
        
        $headers = ['Product Name', 'SKU', 'Warehouse', 'Quantity', 'Reserved', 'Available', 'Min Level', 'Status'];
        
        return $this->formatReport($data, $headers, 'stock_report', $format);
    }
    
    /**
     * Get stock report data
     */
    private function getStockReportData(array $filters): array {
        $db = $this->stockModel->getDb();
        
        $sql = "SELECT 
                    p.name as product_name,
                    p.sku,
                    w.name as warehouse_name,
                    s.quantity,
                    s.reserved_quantity,
                    (s.quantity - s.reserved_quantity) as available,
                    p.minimum_stock_level,
                    CASE 
                        WHEN (s.quantity - s.reserved_quantity) <= 0 THEN 'Out of Stock'
                        WHEN (s.quantity - s.reserved_quantity) < p.minimum_stock_level THEN 'Low Stock'
                        ELSE 'In Stock'
                    END as status
                FROM sar_inv_stock s
                JOIN sar_inv_products p ON s.product_id = p.id
                JOIN sar_inv_warehouses w ON s.warehouse_id = w.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND s.warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }
        
        if (!empty($filters['product_id'])) {
            $sql .= " AND s.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'low_stock') {
                $sql .= " AND (s.quantity - s.reserved_quantity) < p.minimum_stock_level AND (s.quantity - s.reserved_quantity) > 0";
            } elseif ($filters['status'] === 'out_of_stock') {
                $sql .= " AND (s.quantity - s.reserved_quantity) <= 0";
            }
        }
        
        $sql .= " ORDER BY p.name, w.name";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate dispatch report
     * @param array $filters Filter criteria
     * @param string $format Output format
     * @return array Report data with content
     */
    public function generateDispatchReport(array $filters = [], string $format = 'csv'): array {
        $data = $this->getDispatchReportData($filters);
        
        $headers = ['Dispatch #', 'Source Warehouse', 'Destination', 'Status', 'Dispatch Date', 'Received Date', 'Items Count'];
        
        return $this->formatReport($data, $headers, 'dispatch_report', $format);
    }
    
    /**
     * Get dispatch report data
     */
    private function getDispatchReportData(array $filters): array {
        $db = $this->dispatchModel->getDb();
        
        $sql = "SELECT 
                    d.dispatch_number,
                    w.name as source_warehouse,
                    CONCAT(d.destination_type, ': ', COALESCE(d.destination_address, 'N/A')) as destination,
                    d.status,
                    d.dispatch_date,
                    d.received_date,
                    (SELECT COUNT(*) FROM sar_inv_dispatch_items WHERE dispatch_id = d.id) as items_count
                FROM sar_inv_dispatches d
                JOIN sar_inv_warehouses w ON d.source_warehouse_id = w.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND d.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND d.source_warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND d.dispatch_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND d.dispatch_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY d.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate transfer report
     * @param array $filters Filter criteria
     * @param string $format Output format
     * @return array Report data with content
     */
    public function generateTransferReport(array $filters = [], string $format = 'csv'): array {
        $data = $this->getTransferReportData($filters);
        
        $headers = ['Transfer #', 'Source Warehouse', 'Destination Warehouse', 'Status', 'Transfer Date', 'Received Date', 'Items Count'];
        
        return $this->formatReport($data, $headers, 'transfer_report', $format);
    }
    
    /**
     * Get transfer report data
     */
    private function getTransferReportData(array $filters): array {
        $db = $this->transferModel->getDb();
        
        $sql = "SELECT 
                    t.transfer_number,
                    sw.name as source_warehouse,
                    dw.name as destination_warehouse,
                    t.status,
                    t.transfer_date,
                    t.received_date,
                    (SELECT COUNT(*) FROM sar_inv_transfer_items WHERE transfer_id = t.id) as items_count
                FROM sar_inv_transfers t
                JOIN sar_inv_warehouses sw ON t.source_warehouse_id = sw.id
                JOIN sar_inv_warehouses dw ON t.destination_warehouse_id = dw.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['source_warehouse_id'])) {
            $sql .= " AND t.source_warehouse_id = ?";
            $params[] = $filters['source_warehouse_id'];
        }
        
        if (!empty($filters['destination_warehouse_id'])) {
            $sql .= " AND t.destination_warehouse_id = ?";
            $params[] = $filters['destination_warehouse_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND t.transfer_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND t.transfer_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate asset report
     * @param array $filters Filter criteria
     * @param string $format Output format
     * @return array Report data with content
     */
    public function generateAssetReport(array $filters = [], string $format = 'csv'): array {
        $data = $this->getAssetReportData($filters);
        
        $headers = ['Serial Number', 'Barcode', 'Product', 'Status', 'Location Type', 'Purchase Date', 'Warranty Expiry'];
        
        return $this->formatReport($data, $headers, 'asset_report', $format);
    }
    
    /**
     * Get asset report data
     */
    private function getAssetReportData(array $filters): array {
        $db = $this->assetModel->getDb();
        
        $sql = "SELECT 
                    a.serial_number,
                    a.barcode,
                    p.name as product_name,
                    a.status,
                    a.current_location_type,
                    a.purchase_date,
                    a.warranty_expiry
                FROM sar_inv_assets a
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['product_id'])) {
            $sql .= " AND a.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['location_type'])) {
            $sql .= " AND a.current_location_type = ?";
            $params[] = $filters['location_type'];
        }
        
        $sql .= " ORDER BY p.name, a.serial_number";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate repair report
     * @param array $filters Filter criteria
     * @param string $format Output format
     * @return array Report data with content
     */
    public function generateRepairReport(array $filters = [], string $format = 'csv'): array {
        $data = $this->getRepairReportData($filters);
        
        $headers = ['Repair #', 'Asset Serial', 'Product', 'Status', 'Issue', 'Cost', 'Start Date', 'Completion Date'];
        
        return $this->formatReport($data, $headers, 'repair_report', $format);
    }
    
    /**
     * Get repair report data
     */
    private function getRepairReportData(array $filters): array {
        $db = $this->repairModel->getDb();
        
        $sql = "SELECT 
                    r.repair_number,
                    a.serial_number,
                    p.name as product_name,
                    r.status,
                    r.issue_description,
                    r.cost,
                    r.start_date,
                    r.completion_date
                FROM sar_inv_repairs r
                JOIN sar_inv_assets a ON r.asset_id = a.id
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND r.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['vendor_id'])) {
            $sql .= " AND r.vendor_id = ?";
            $params[] = $filters['vendor_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND r.start_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND r.start_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate inventory movement report
     * @param array $filters Filter criteria
     * @param string $format Output format
     * @return array Report data with content
     */
    public function generateMovementReport(array $filters = [], string $format = 'csv'): array {
        $data = $this->historyModel->search($filters, 10000, 0);
        
        $formattedData = [];
        foreach ($data as $row) {
            $formattedData[] = [
                'date' => $row['created_at'],
                'product_name' => $row['product_name'],
                'sku' => $row['sku'],
                'warehouse_name' => $row['warehouse_name'] ?? '',
                'transaction_type' => $row['transaction_type'],
                'quantity' => $row['quantity'],
                'balance_after' => $row['balance_after'],
                'notes' => $row['notes'] ?? ''
            ];
        }
        
        $headers = ['Date', 'Product', 'SKU', 'Warehouse', 'Transaction Type', 'Quantity', 'Balance After', 'Notes'];
        
        return $this->formatReport($formattedData, $headers, 'movement_report', $format);
    }
    
    /**
     * Generate warehouse summary report
     * @param string $format Output format
     * @return array Report data with content
     */
    public function generateWarehouseSummaryReport(string $format = 'csv'): array {
        $warehouses = $this->warehouseModel->getActiveWarehouses();
        $data = [];
        
        foreach ($warehouses as $warehouse) {
            $utilization = $this->warehouseModel->getCapacityUtilization($warehouse['id']);
            $stockSummary = $this->warehouseModel->getStockSummary($warehouse['id']);
            
            $data[] = [
                'warehouse_name' => $warehouse['name'],
                'warehouse_code' => $warehouse['code'],
                'location' => $warehouse['location'] ?? '',
                'capacity' => $utilization['capacity'],
                'used' => $utilization['used'],
                'available' => $utilization['available'],
                'utilization' => $utilization['utilization_percentage'] . '%',
                'products_count' => count($stockSummary)
            ];
        }
        
        $headers = ['Warehouse', 'Code', 'Location', 'Capacity', 'Used', 'Available', 'Utilization', 'Products'];
        
        return $this->formatReport($data, $headers, 'warehouse_summary', $format);
    }
    
    /**
     * Format report to specified format
     * @param array $data Report data
     * @param array $headers Column headers
     * @param string $reportName Report name
     * @param string $format Output format
     * @return array Formatted report
     */
    private function formatReport(array $data, array $headers, string $reportName, string $format): array {
        $filename = $reportName . '_' . date('Y-m-d_H-i-s');
        
        switch ($format) {
            case 'excel':
                return $this->formatAsExcel($data, $headers, $filename);
            case 'pdf':
                return $this->formatAsPdf($data, $headers, $filename, $reportName);
            case 'csv':
            default:
                return $this->formatAsCsv($data, $headers, $filename);
        }
    }
    
    /**
     * Format as CSV
     */
    private function formatAsCsv(array $data, array $headers, string $filename): array {
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }
        
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        return [
            'filename' => $filename . '.csv',
            'content' => $content,
            'mime_type' => 'text/csv',
            'format' => 'csv'
        ];
    }
    
    /**
     * Format as Excel (tab-separated for compatibility)
     */
    private function formatAsExcel(array $data, array $headers, string $filename): array {
        $output = fopen('php://temp', 'r+');
        
        // BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Write headers
        fwrite($output, implode("\t", $headers) . "\n");
        
        // Write data
        foreach ($data as $row) {
            $values = array_map(function($val) {
                return str_replace(["\t", "\n", "\r"], ' ', $val ?? '');
            }, array_values($row));
            fwrite($output, implode("\t", $values) . "\n");
        }
        
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        return [
            'filename' => $filename . '.xls',
            'content' => $content,
            'mime_type' => 'application/vnd.ms-excel',
            'format' => 'excel'
        ];
    }
    
    /**
     * Format as PDF (HTML for PDF conversion)
     */
    private function formatAsPdf(array $data, array $headers, string $filename, string $title): array {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<title>' . ucwords(str_replace('_', ' ', $title)) . '</title>';
        $html .= '<style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            h1 { font-size: 18px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #4CAF50; color: white; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .footer { margin-top: 20px; font-size: 10px; color: #666; }
        </style></head><body>';
        
        $html .= '<h1>' . ucwords(str_replace('_', ' ', $title)) . '</h1>';
        $html .= '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>';
        
        $html .= '<table><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach (array_values($row) as $value) {
                $html .= '<td>' . htmlspecialchars($value ?? '') . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= '<div class="footer">Total Records: ' . count($data) . '</div>';
        $html .= '</body></html>';
        
        return [
            'filename' => $filename . '.html',
            'content' => $html,
            'mime_type' => 'text/html',
            'format' => 'pdf',
            'note' => 'HTML format for PDF conversion. Use a PDF library like TCPDF or mPDF for actual PDF generation.'
        ];
    }
    
    /**
     * Get available report types
     * @return array Available reports
     */
    public function getAvailableReports(): array {
        return [
            'stock' => [
                'name' => 'Stock Report',
                'description' => 'Current stock levels across warehouses',
                'filters' => ['warehouse_id', 'product_id', 'status']
            ],
            'dispatch' => [
                'name' => 'Dispatch Report',
                'description' => 'Dispatch history and status',
                'filters' => ['status', 'warehouse_id', 'date_from', 'date_to']
            ],
            'transfer' => [
                'name' => 'Transfer Report',
                'description' => 'Inter-warehouse transfer history',
                'filters' => ['status', 'source_warehouse_id', 'destination_warehouse_id', 'date_from', 'date_to']
            ],
            'asset' => [
                'name' => 'Asset Report',
                'description' => 'Asset inventory and status',
                'filters' => ['status', 'product_id', 'location_type']
            ],
            'repair' => [
                'name' => 'Repair Report',
                'description' => 'Repair history and costs',
                'filters' => ['status', 'vendor_id', 'date_from', 'date_to']
            ],
            'movement' => [
                'name' => 'Inventory Movement Report',
                'description' => 'Stock movement history',
                'filters' => ['product_id', 'warehouse_id', 'transaction_type', 'date_from', 'date_to']
            ],
            'warehouse_summary' => [
                'name' => 'Warehouse Summary Report',
                'description' => 'Warehouse capacity and utilization',
                'filters' => []
            ]
        ];
    }
}
?>
