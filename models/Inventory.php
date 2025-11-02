<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

class Inventory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // ==================== STOCK MANAGEMENT ====================
    
    public function getStockOverview($search = '', $category = '', $lowStock = false) {
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        if (!empty($search)) {
            $conditions[] = "(bi.item_name LIKE ? OR bi.item_code LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        if (!empty($category)) {
            $conditions[] = "bi.category = ?";
            $params[] = $category;
        }
        
        if ($lowStock) {
            $conditions[] = "iss.total_stock <= iss.minimum_stock";
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(" AND ", $conditions);
        }
        
        // Use the summary view for aggregated stock data
        $sql = "SELECT iss.*, bi.item_name, bi.item_code, bi.unit, bi.category, bi.icon_class,
                       CASE 
                           WHEN iss.total_stock <= iss.minimum_stock THEN 'low'
                           WHEN iss.total_stock >= iss.maximum_stock THEN 'high'
                           ELSE 'normal'
                       END as stock_status,
                       iss.total_stock as current_stock,
                       iss.total_available as available_stock,
                       iss.total_reserved as reserved_stock,
                       iss.total_value,
                       iss.avg_unit_cost as unit_cost
                FROM inventory_stock_summary iss
                JOIN boq_items bi ON iss.boq_item_id = bi.id
                $whereClause
                ORDER BY bi.item_name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getIndividualStockEntries($boqItemId = null, $search = '', $location = '') {
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        if ($boqItemId) {
            $conditions[] = "ist.boq_item_id = ?";
            $params[] = $boqItemId;
        }
        
        if (!empty($search)) {
            $conditions[] = "(bi.item_name LIKE ? OR bi.item_code LIKE ? OR ist.batch_number LIKE ? OR ist.serial_number LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($location)) {
            $conditions[] = "ist.location_type = ?";
            $params[] = $location;
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(" AND ", $conditions);
        }
        
        $sql = "SELECT ist.*, bi.item_name, bi.item_code, bi.unit, bi.category, bi.icon_class
                FROM inventory_stock ist
                JOIN boq_items bi ON ist.boq_item_id = bi.id
                $whereClause
                ORDER BY bi.item_name, ist.batch_number, ist.serial_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStockByItem($boqItemId) {
        $sql = "SELECT ist.*, bi.item_name, bi.item_code, bi.unit, bi.category
                FROM inventory_stock ist
                JOIN boq_items bi ON ist.boq_item_id = bi.id
                WHERE ist.boq_item_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boqItemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateStockLevels($boqItemId, $minStock, $maxStock, $unitCost) {
        $currentUser = Auth::getCurrentUser();
        
        // Update all entries for this BOQ item
        $sql = "UPDATE inventory_stock 
                SET minimum_stock = ?, maximum_stock = ?, updated_by = ?
                WHERE boq_item_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$minStock, $maxStock, $currentUser['id'], $boqItemId]);
        
        // Update unit cost only if provided and different
        if ($unitCost > 0) {
            $sql = "UPDATE inventory_stock 
                    SET unit_cost = ?, total_value = current_stock * ?, updated_by = ?
                    WHERE boq_item_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$unitCost, $unitCost, $currentUser['id'], $boqItemId]);
        }
        
        return $result;
    }
    
    public function addIndividualStockEntry($data) {
        $currentUser = Auth::getCurrentUser();
        $userId = $currentUser ? $currentUser['id'] : null;
        
        $sql = "INSERT INTO inventory_stock 
                (boq_item_id, current_stock, available_stock, reserved_stock, minimum_stock, maximum_stock,
                 unit_cost, total_value, batch_number, serial_number, location_type, location_id, 
                 location_name, purchase_date, expiry_date, supplier_name, quality_status, 
                 warranty_period, notes, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $availableStock = $data['current_stock'] - ($data['reserved_stock'] ?? 0);
        $totalValue = $data['current_stock'] * $data['unit_cost'];
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['boq_item_id'],
            $data['current_stock'],
            $availableStock,
            $data['reserved_stock'] ?? 0,
            $data['minimum_stock'] ?? 0,
            $data['maximum_stock'] ?? 0,
            $data['unit_cost'],
            $totalValue,
            $data['batch_number'] ?? null,
            $data['serial_number'] ?? null,
            $data['location_type'] ?? 'warehouse',
            $data['location_id'] ?? null,
            $data['location_name'] ?? 'Main Warehouse',
            $data['purchase_date'] ?? null,
            $data['expiry_date'] ?? null,
            $data['supplier_name'] ?? null,
            $data['quality_status'] ?? 'good',
            $data['warranty_period'] ?? null,
            $data['notes'] ?? null,
            $userId
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    public function updateIndividualStockEntry($id, $data) {
        $currentUser = Auth::getCurrentUser();
        
        $fields = [];
        $values = [];
        
        $allowedFields = [
            'current_stock', 'reserved_stock', 'minimum_stock', 'maximum_stock', 'unit_cost',
            'batch_number', 'serial_number', 'location_type', 'location_id', 'location_name',
            'purchase_date', 'expiry_date', 'supplier_name', 'quality_status', 'warranty_period', 'notes'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        // Always update calculated fields
        $fields[] = "available_stock = current_stock - reserved_stock";
        $fields[] = "total_value = current_stock * unit_cost";
        $fields[] = "updated_by = ?";
        $values[] = $currentUser['id'];
        
        $sql = "UPDATE inventory_stock SET " . implode(', ', $fields) . " WHERE id = ?";
        $values[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    // ==================== INWARD RECEIPTS ====================
    
    public function createInwardReceipt($data) {
        $currentUser = Auth::getCurrentUser();
        
        $sql = "INSERT INTO inventory_inwards 
                (receipt_number, receipt_date, supplier_name, supplier_contact, 
                 purchase_order_number, invoice_number, invoice_date, total_amount, 
                 received_by, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['receipt_number'],
            $data['receipt_date'],
            $data['supplier_name'],
            $data['supplier_contact'],
            $data['purchase_order_number'],
            $data['invoice_number'],
            $data['invoice_date'],
            $data['total_amount'],
            $currentUser['id'],
            $data['remarks']
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    public function addInwardItems($inwardId, $items) {
        $currentUser = Auth::getCurrentUser();
        
        // Get inward receipt details for supplier info
        $inwardSql = "SELECT receipt_date, supplier_name FROM inventory_inwards WHERE id = ?";
        $inwardStmt = $this->db->prepare($inwardSql);
        $inwardStmt->execute([$inwardId]);
        $inwardData = $inwardStmt->fetch(PDO::FETCH_ASSOC);
        
        $sql = "INSERT INTO inventory_inward_items 
                (inward_id, boq_item_id, quantity_received, unit_cost, batch_number, 
                 serial_numbers, expiry_date, quality_status, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($items as $item) {
            $serialNumbers = !empty($item['serial_numbers']) ? json_encode($item['serial_numbers']) : null;
            
            // Add to inward_items table
            $stmt->execute([
                $inwardId,
                $item['boq_item_id'],
                $item['quantity_received'],
                $item['unit_cost'],
                $item['batch_number'],
                $serialNumbers,
                $item['expiry_date'],
                $item['quality_status'] ?? 'good',
                $item['remarks']
            ]);
            
            // Create individual stock entries
            $this->createIndividualStockEntries(
                $item['boq_item_id'],
                $item['quantity_received'],
                $item['unit_cost'],
                $item['batch_number'],
                $item['serial_numbers'] ?? [],
                $inwardData['supplier_name'] ?? null,
                $inwardData['receipt_date'] ?? null,
                $item['expiry_date'] ?? null,
                $item['quality_status'] ?? 'good',
                $item['remarks'] ?? null
            );
        }
        
        return true;
    }
    
    private function createIndividualStockEntries($boqItemId, $quantity, $unitCost, $batchNumber, $serialNumbers, $supplier, $purchaseDate, $expiryDate, $qualityStatus, $remarks) {
        $currentUser = Auth::getCurrentUser();
        
        if (!empty($serialNumbers) && is_array($serialNumbers)) {
            // Create individual entries for each serial number
            foreach ($serialNumbers as $serialNumber) {
                $this->createSingleStockEntry($boqItemId, 1, $unitCost, $batchNumber, $serialNumber, $supplier, $purchaseDate, $expiryDate, $qualityStatus, $remarks, $currentUser['id']);
            }
        } else {
            // Create a single entry for the batch quantity
            $this->createSingleStockEntry($boqItemId, $quantity, $unitCost, $batchNumber, null, $supplier, $purchaseDate, $expiryDate, $qualityStatus, $remarks, $currentUser['id']);
        }
    }
    
    private function createSingleStockEntry($boqItemId, $quantity, $unitCost, $batchNumber, $serialNumber, $supplier, $purchaseDate, $expiryDate, $qualityStatus, $remarks, $userId) {
        $sql = "INSERT INTO inventory_stock 
                (boq_item_id, current_stock, available_stock, unit_cost, total_value, 
                 batch_number, serial_number, location_type, location_name, 
                 purchase_date, expiry_date, supplier_name, quality_status, notes, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'warehouse', 'Main Warehouse', ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $totalValue = $quantity * $unitCost;
        
        $stmt->execute([
            $boqItemId,
            $quantity,
            $quantity, // available_stock = current_stock initially
            $unitCost,
            $totalValue,
            $batchNumber,
            $serialNumber,
            $purchaseDate,
            $expiryDate,
            $supplier,
            $qualityStatus,
            $remarks,
            $userId
        ]);
    }
    
    public function getInwardReceipts($page = 1, $limit = 20, $search = '', $status = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        if (!empty($search)) {
            $conditions[] = "(receipt_number LIKE ? OR supplier_name LIKE ? OR invoice_number LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($status)) {
            $conditions[] = "status = ?";
            $params[] = $status;
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(" AND ", $conditions);
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM inventory_inwards $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated results
        $sql = "SELECT ii.*, u.username as received_by_name,
                       (SELECT COUNT(*) FROM inventory_inward_items WHERE inward_id = ii.id) as item_count
                FROM inventory_inwards ii
                LEFT JOIN users u ON ii.received_by = u.id
                $whereClause
                ORDER BY ii.receipt_date DESC, ii.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'receipts' => $receipts,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    public function getInwardReceiptDetails($inwardId) {
        $sql = "SELECT ii.*, u.username as received_by_name, u2.username as verified_by_name
                FROM inventory_inwards ii
                LEFT JOIN users u ON ii.received_by = u.id
                LEFT JOIN users u2 ON ii.verified_by = u2.id
                WHERE ii.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$inwardId]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($receipt) {
            // Get receipt items
            $itemsSql = "SELECT iii.*, bi.item_name, bi.item_code, bi.unit, bi.category
                         FROM inventory_inward_items iii
                         JOIN boq_items bi ON iii.boq_item_id = bi.id
                         WHERE iii.inward_id = ?
                         ORDER BY bi.item_name";
            
            $stmt = $this->db->prepare($itemsSql);
            $stmt->execute([$inwardId]);
            $receipt['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $receipt;
    }
    
    // ==================== DISPATCHES ====================
    
    public function createDispatch($data) {
        $currentUser = Auth::getCurrentUser();
        
        $sql = "INSERT INTO inventory_dispatches 
                (dispatch_number, dispatch_date, material_request_id, site_id, vendor_id,
                 contact_person_name, contact_person_phone, delivery_address, courier_name,
                 tracking_number, expected_delivery_date, dispatch_status, dispatched_by, delivery_remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['dispatch_number'],
            $data['dispatch_date'],
            $data['material_request_id'],
            $data['site_id'],
            $data['vendor_id'],
            $data['contact_person_name'],
            $data['contact_person_phone'],
            $data['delivery_address'],
            $data['courier_name'],
            $data['tracking_number'],
            $data['expected_delivery_date'],
            $data['dispatch_status'] ?? 'dispatched',
            $currentUser['id'],
            $data['delivery_remarks'] ?? null
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    public function addDispatchItems($dispatchId, $items) {
        $sql = "INSERT INTO inventory_dispatch_items 
                (dispatch_id, boq_item_id, quantity_dispatched, unit_cost, batch_number,
                 serial_numbers, item_condition, warranty_period, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($items as $item) {
            $serialNumbers = !empty($item['serial_numbers']) ? json_encode($item['serial_numbers']) : null;
            
            $stmt->execute([
                $dispatchId,
                $item['boq_item_id'],
                $item['quantity_dispatched'],
                $item['unit_cost'],
                $item['batch_number'],
                $serialNumbers,
                $item['item_condition'] ?? 'new',
                $item['warranty_period'] ?? null,
                $item['remarks']
            ]);
        }
        
        // Update dispatch totals
        $this->updateDispatchTotals($dispatchId);
        
        return true;
    }
    
    private function updateDispatchTotals($dispatchId) {
        $sql = "UPDATE inventory_dispatches 
                SET total_items = (SELECT COUNT(*) FROM inventory_dispatch_items WHERE dispatch_id = ?),
                    total_value = (SELECT SUM(total_cost) FROM inventory_dispatch_items WHERE dispatch_id = ?)
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dispatchId, $dispatchId, $dispatchId]);
    }
    
    public function getDispatches($page = 1, $limit = 20, $search = '', $status = '', $siteId = null) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        if (!empty($search)) {
            $conditions[] = "(id.dispatch_number LIKE ? OR id.contact_person_name LIKE ? OR id.tracking_number LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($status)) {
            $conditions[] = "id.dispatch_status = ?";
            $params[] = $status;
        }
        
        if ($siteId) {
            $conditions[] = "id.site_id = ?";
            $params[] = $siteId;
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(" AND ", $conditions);
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM inventory_dispatches id $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated results
        $sql = "SELECT id.*, s.site_id as site_code, v.name as vendor_name, u.username as dispatched_by_name
                FROM inventory_dispatches id
                LEFT JOIN sites s ON id.site_id = s.id
                LEFT JOIN vendors v ON id.vendor_id = v.id
                LEFT JOIN users u ON id.dispatched_by = u.id
                $whereClause
                ORDER BY id.dispatch_date DESC, id.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $dispatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'dispatches' => $dispatches,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    // ==================== TRACKING ====================
    
    public function getMaterialTracking($search = '', $siteId = null, $vendorId = null, $status = '') {
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        if (!empty($search)) {
            $conditions[] = "(bi.item_name LIKE ? OR bi.item_code LIKE ? OR it.serial_number LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($siteId) {
            $conditions[] = "it.site_id = ?";
            $params[] = $siteId;
        }
        
        if ($vendorId) {
            $conditions[] = "it.vendor_id = ?";
            $params[] = $vendorId;
        }
        
        if (!empty($status)) {
            $conditions[] = "it.status = ?";
            $params[] = $status;
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(" AND ", $conditions);
        }
        
        $sql = "SELECT it.*, bi.item_name, bi.item_code, bi.unit,
                       s.site_id as site_code, v.name as vendor_name,
                       id.dispatch_number, id.dispatch_date
                FROM inventory_tracking it
                JOIN boq_items bi ON it.boq_item_id = bi.id
                LEFT JOIN sites s ON it.site_id = s.id
                LEFT JOIN vendors v ON it.vendor_id = v.id
                LEFT JOIN inventory_dispatches id ON it.dispatch_id = id.id
                $whereClause
                ORDER BY it.last_movement_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateMaterialLocation($trackingId, $newLocation, $locationId, $remarks) {
        $currentUser = Auth::getCurrentUser();
        
        $sql = "UPDATE inventory_tracking 
                SET current_location_type = ?, current_location_id = ?, 
                    movement_remarks = ?, last_movement_date = NOW(), updated_by = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newLocation, $locationId, $remarks, $currentUser['id'], $trackingId]);
    }
    
    // ==================== REPORTS & ANALYTICS ====================
    
    public function getInventoryStats() {
        $stats = [];
        
        // Total unique BOQ items and aggregated values
        $sql = "SELECT 
                    COUNT(DISTINCT iss.boq_item_id) as total_items,
                    SUM(iss.total_stock) as total_quantity,
                    SUM(iss.total_value) as total_value,
                    COUNT(CASE WHEN iss.total_stock <= iss.minimum_stock THEN 1 END) as low_stock_items
                FROM inventory_stock_summary iss
                JOIN boq_items bi ON iss.boq_item_id = bi.id
                WHERE bi.status = 'active'";
        
        $stmt = $this->db->query($sql);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Total individual entries
        $sql = "SELECT COUNT(*) as total_entries
                FROM inventory_stock ist
                JOIN boq_items bi ON ist.boq_item_id = bi.id
                WHERE bi.status = 'active'";
        
        $stmt = $this->db->query($sql);
        $stats['total_entries'] = $stmt->fetchColumn();
        
        // Recent movements
        $sql = "SELECT COUNT(*) as recent_movements
                FROM inventory_movements 
                WHERE movement_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $stmt = $this->db->query($sql);
        $stats['recent_movements'] = $stmt->fetchColumn();
        
        // Pending dispatches
        $sql = "SELECT COUNT(*) as pending_dispatches
                FROM inventory_dispatches 
                WHERE dispatch_status IN ('prepared', 'dispatched', 'in_transit')";
        
        $stmt = $this->db->query($sql);
        $stats['pending_dispatches'] = $stmt->fetchColumn();
        
        // Location-wise breakdown
        $sql = "SELECT location_type, COUNT(*) as count, SUM(current_stock) as total_stock
                FROM inventory_stock 
                GROUP BY location_type";
        
        $stmt = $this->db->query($sql);
        $locationStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stats['by_location'] = [];
        foreach ($locationStats as $location) {
            $stats['by_location'][$location['location_type']] = [
                'entries' => $location['count'],
                'stock' => $location['total_stock']
            ];
        }
        
        return $stats;
    }
    
    public function generateReceiptNumber() {
        $prefix = 'RCP';
        $date = date('Ymd');
        
        $sql = "SELECT COUNT(*) + 1 as next_number 
                FROM inventory_inwards 
                WHERE receipt_number LIKE ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$prefix . $date . '%']);
        $nextNumber = $stmt->fetchColumn();
        
        return $prefix . $date . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
    
    public function generateDispatchNumber() {
        $prefix = 'DSP';
        $date = date('Ymd');
        
        $sql = "SELECT COUNT(*) + 1 as next_number 
                FROM inventory_dispatches 
                WHERE dispatch_number LIKE ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$prefix . $date . '%']);
        $nextNumber = $stmt->fetchColumn();
        
        return $prefix . $date . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
    
    public function createTrackingEntry($data) {
        $currentUser = Auth::getCurrentUser();
        $userId = $currentUser ? $currentUser['id'] : null;
        
        $sql = "INSERT INTO inventory_tracking 
                (boq_item_id, batch_number, serial_number, current_location_type, 
                 current_location_id, current_location_name, site_id, vendor_id, 
                 dispatch_id, inward_id, quantity, status, movement_remarks, updated_by, last_movement_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['boq_item_id'],
            $data['batch_number'] ?? null,
            $data['serial_number'] ?? null,
            $data['current_location_type'],
            $data['current_location_id'] ?? null,
            $data['current_location_name'] ?? null,
            $data['site_id'] ?? null,
            $data['vendor_id'] ?? null,
            $data['dispatch_id'] ?? null,
            $data['inward_id'] ?? null,
            $data['quantity'],
            $data['status'] ?? 'available',
            $data['movement_remarks'] ?? null,
            $userId
        ]);
    }
    
    public function getDispatchByRequestId($requestId) {
        $sql = "SELECT * FROM inventory_dispatches 
                WHERE material_request_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$requestId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function confirmDelivery($dispatchId, $deliveryData) {
        $sql = "UPDATE inventory_dispatches 
                SET dispatch_status = 'delivered',
                    delivery_date = ?,
                    delivery_time = ?,
                    received_by = ?,
                    received_by_phone = ?,
                    actual_delivery_address = ?,
                    delivery_notes = ?,
                    lr_copy_path = ?,
                    additional_documents = ?,
                    item_confirmations = ?,
                    confirmed_by = ?,
                    confirmation_date = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $deliveryData['delivery_date'],
            $deliveryData['delivery_time'],
            $deliveryData['received_by'],
            $deliveryData['received_by_phone'],
            $deliveryData['delivery_address'],
            $deliveryData['delivery_notes'],
            $deliveryData['lr_copy_path'],
            $deliveryData['additional_documents'],
            $deliveryData['item_confirmations'],
            $deliveryData['confirmed_by'],
            $deliveryData['confirmation_date'],
            $dispatchId
        ]);
    }
    
    public function createDeliveryNotification($data) {
        $sql = "INSERT INTO delivery_notifications 
                (request_id, dispatch_id, vendor_id, message, type, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['request_id'],
            $data['dispatch_id'],
            $data['vendor_id'],
            $data['message'],
            $data['type'],
            $data['created_by']
        ]);
    }
    
    public function getReceivedMaterialsForVendor($vendorId) {
        $sql = "SELECT id.*, mr.id as material_request_id, s.site_id as site_code
                FROM inventory_dispatches id
                LEFT JOIN material_requests mr ON id.material_request_id = mr.id
                LEFT JOIN sites s ON id.site_id = s.id
                WHERE id.vendor_id = ? 
                AND id.dispatch_status IN ('dispatched', 'delivered', 'confirmed')
                ORDER BY id.delivery_date DESC, id.dispatch_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDispatchItems($dispatchId) {
        $sql = "SELECT idi.*, bi.item_name, bi.item_code, bi.unit, bi.icon_class
                FROM inventory_dispatch_items idi
                LEFT JOIN boq_items bi ON idi.boq_item_id = bi.id
                WHERE idi.dispatch_id = ?
                ORDER BY idi.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dispatchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
?>