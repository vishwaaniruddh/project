<?php
require_once __DIR__ . '/SarInvBaseModel.php';

/**
 * SAR Inventory Item History Model
 * Manages comprehensive item history with filtering, pagination, and export
 */
class SarInvItemHistory extends SarInvBaseModel {
    protected $table = 'sar_inv_item_history';
    protected $enableCompanyIsolation = false;
    protected $enableAuditLog = false; // History table doesn't need audit logging
    
    const TYPE_STOCK_IN = 'stock_in';
    const TYPE_STOCK_OUT = 'stock_out';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_TRANSFER_IN = 'transfer_in';
    const TYPE_DISPATCH = 'dispatch';
    const TYPE_RETURN = 'return';
    const TYPE_RESERVATION = 'reservation';
    const TYPE_RELEASE = 'release';
    
    /**
     * Get history for a product
     */
    public function getByProduct($productId, $limit = 100, $offset = 0) {
        $sql = "SELECT h.*, p.name as product_name, p.sku, w.name as warehouse_name
                FROM {$this->table} h
                JOIN sar_inv_products p ON h.product_id = p.id
                LEFT JOIN sar_inv_warehouses w ON h.warehouse_id = w.id
                WHERE h.product_id = ?
                ORDER BY h.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get history for a warehouse
     */
    public function getByWarehouse($warehouseId, $limit = 100, $offset = 0) {
        $sql = "SELECT h.*, p.name as product_name, p.sku, w.name as warehouse_name
                FROM {$this->table} h
                JOIN sar_inv_products p ON h.product_id = p.id
                LEFT JOIN sar_inv_warehouses w ON h.warehouse_id = w.id
                WHERE h.warehouse_id = ?
                ORDER BY h.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$warehouseId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get history by transaction type
     */
    public function getByType($transactionType, $limit = 100, $offset = 0) {
        $sql = "SELECT h.*, p.name as product_name, p.sku, w.name as warehouse_name
                FROM {$this->table} h
                JOIN sar_inv_products p ON h.product_id = p.id
                LEFT JOIN sar_inv_warehouses w ON h.warehouse_id = w.id
                WHERE h.transaction_type = ?
                ORDER BY h.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$transactionType, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Search history with filters
     */
    public function search($filters = [], $limit = 100, $offset = 0) {
        $sql = "SELECT h.*, p.name as product_name, p.sku, w.name as warehouse_name
                FROM {$this->table} h
                JOIN sar_inv_products p ON h.product_id = p.id
                LEFT JOIN sar_inv_warehouses w ON h.warehouse_id = w.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['product_id'])) {
            $sql .= " AND h.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND h.warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }
        
        if (!empty($filters['transaction_type'])) {
            $sql .= " AND h.transaction_type = ?";
            $params[] = $filters['transaction_type'];
        }
        
        if (!empty($filters['reference_type'])) {
            $sql .= " AND h.reference_type = ?";
            $params[] = $filters['reference_type'];
        }
        
        if (!empty($filters['reference_id'])) {
            $sql .= " AND h.reference_id = ?";
            $params[] = $filters['reference_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(h.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(h.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['created_by'])) {
            $sql .= " AND h.created_by = ?";
            $params[] = $filters['created_by'];
        }
        
        if (!empty($filters['keyword'])) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR h.notes LIKE ?)";
            $keyword = "%{$filters['keyword']}%";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        $sql .= " ORDER BY h.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Count history records with filters
     */
    public function countFiltered($filters = []) {
        $sql = "SELECT COUNT(*) FROM {$this->table} h
                JOIN sar_inv_products p ON h.product_id = p.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['product_id'])) {
            $sql .= " AND h.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND h.warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }
        
        if (!empty($filters['transaction_type'])) {
            $sql .= " AND h.transaction_type = ?";
            $params[] = $filters['transaction_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(h.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(h.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['keyword'])) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR h.notes LIKE ?)";
            $keyword = "%{$filters['keyword']}%";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get paginated results
     */
    public function getPaginated($filters = [], $page = 1, $perPage = 50) {
        $offset = ($page - 1) * $perPage;
        $total = $this->countFiltered($filters);
        $data = $this->search($filters, $perPage, $offset);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total
            ]
        ];
    }
    
    /**
     * Export to CSV
     */
    public function exportToCsv($filters = [], $filename = null) {
        $data = $this->search($filters, 10000, 0); // Limit to 10000 records
        
        if (!$filename) {
            $filename = 'item_history_' . date('Y-m-d_H-i-s') . '.csv';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Header row
        fputcsv($output, [
            'ID', 'Product Name', 'SKU', 'Warehouse', 'Transaction Type',
            'Quantity', 'Balance After', 'Reference Type', 'Reference ID',
            'Notes', 'Created By', 'Created At'
        ]);
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($output, [
                $row['id'],
                $row['product_name'],
                $row['sku'],
                $row['warehouse_name'] ?? '',
                $row['transaction_type'],
                $row['quantity'],
                $row['balance_after'],
                $row['reference_type'] ?? '',
                $row['reference_id'] ?? '',
                $row['notes'] ?? '',
                $row['created_by'] ?? '',
                $row['created_at']
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return [
            'filename' => $filename,
            'content' => $csv,
            'mime_type' => 'text/csv'
        ];
    }
    
    /**
     * Export to Excel (as CSV with Excel-compatible format)
     */
    public function exportToExcel($filters = [], $filename = null) {
        $data = $this->search($filters, 10000, 0);
        
        if (!$filename) {
            $filename = 'item_history_' . date('Y-m-d_H-i-s') . '.xlsx';
        }
        
        // For simplicity, we'll create a tab-separated file that Excel can open
        // In production, you'd use a library like PhpSpreadsheet
        $output = fopen('php://temp', 'r+');
        
        // BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Header row
        fwrite($output, implode("\t", [
            'ID', 'Product Name', 'SKU', 'Warehouse', 'Transaction Type',
            'Quantity', 'Balance After', 'Reference Type', 'Reference ID',
            'Notes', 'Created By', 'Created At'
        ]) . "\n");
        
        // Data rows
        foreach ($data as $row) {
            fwrite($output, implode("\t", [
                $row['id'],
                $row['product_name'],
                $row['sku'],
                $row['warehouse_name'] ?? '',
                $row['transaction_type'],
                $row['quantity'],
                $row['balance_after'],
                $row['reference_type'] ?? '',
                $row['reference_id'] ?? '',
                str_replace(["\t", "\n", "\r"], ' ', $row['notes'] ?? ''),
                $row['created_by'] ?? '',
                $row['created_at']
            ]) . "\n");
        }
        
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        return [
            'filename' => str_replace('.xlsx', '.xls', $filename),
            'content' => $content,
            'mime_type' => 'application/vnd.ms-excel'
        ];
    }
    
    /**
     * Get summary by transaction type
     */
    public function getSummaryByType($filters = []) {
        $sql = "SELECT 
                    transaction_type,
                    COUNT(*) as count,
                    SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as total_in,
                    SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END) as total_out
                FROM {$this->table} h
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['product_id'])) {
            $sql .= " AND h.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND h.warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(h.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(h.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " GROUP BY transaction_type ORDER BY count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get daily summary
     */
    public function getDailySummary($filters = [], $days = 30) {
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as transactions,
                    SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as total_in,
                    SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END) as total_out
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        $params = [$days];
        
        if (!empty($filters['product_id'])) {
            $sql .= " AND product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }
        
        $sql .= " GROUP BY DATE(created_at) ORDER BY date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all transaction types
     */
    public static function getTransactionTypes() {
        return [
            self::TYPE_STOCK_IN => 'Stock In',
            self::TYPE_STOCK_OUT => 'Stock Out',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_TRANSFER_OUT => 'Transfer Out',
            self::TYPE_TRANSFER_IN => 'Transfer In',
            self::TYPE_DISPATCH => 'Dispatch',
            self::TYPE_RETURN => 'Return',
            self::TYPE_RESERVATION => 'Reservation',
            self::TYPE_RELEASE => 'Release'
        ];
    }
}
?>
