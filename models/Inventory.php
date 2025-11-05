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
            $conditions[] = "(item_name LIKE ? OR item_code LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        if (!empty($category)) {
            $conditions[] = "category = ?";
            $params[] = $category;
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(" AND ", $conditions);
        }
        
        // Use the new inventory_summary view for aggregated stock data
        $sql = "SELECT * FROM inventory_summary $whereClause ORDER BY item_name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAvailableStock($boqItemId, $requiredQuantity = null) {
        $sql = "SELECT ist.*, bi.item_name, bi.item_code, bi.unit
                FROM inventory_stock ist
                JOIN boq_items bi ON CAST(ist.boq_item_id AS CHAR) = CAST(bi.id AS CHAR)
                WHERE ist.boq_item_id = ? 
                AND ist.item_status = 'available'
                AND ist.quality_status = 'good'
                ORDER BY ist.id ASC";
        
        $params = [$boqItemId];
        if ($requiredQuantity) {
            $sql .= " LIMIT ?";
            $params[] = $requiredQuantity;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStockBySerialNumbers($serialNumbers) {
        if (empty($serialNumbers)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($serialNumbers) - 1) . '?';
        $sql = "SELECT ist.*, bi.item_name, bi.item_code, bi.unit
                FROM inventory_stock ist
                JOIN boq_items bi ON CAST(ist.boq_item_id AS CHAR) = CAST(bi.id AS CHAR)
                WHERE ist.serial_number IN ($placeholders)
                AND ist.item_status = 'available'
                ORDER BY ist.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($serialNumbers);
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
                JOIN boq_items bi ON CAST(ist.boq_item_id AS CHAR) = CAST(bi.id AS CHAR)
                $whereClause
                ORDER BY bi.item_name, ist.batch_number, ist.serial_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStockByItem($boqItemId) {
        $sql = "SELECT ist.*, bi.item_name, bi.item_code, bi.unit, bi.category
                FROM inventory_stock ist
                JOIN boq_items bi ON CAST(ist.boq_item_id AS CHAR) = CAST(bi.id AS CHAR)
                WHERE ist.boq_item_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boqItemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateStockLevels($boqItemId, $minStock, $maxStock, $unitCost) {
        $currentUser = Auth::getCurrentUser();
        
        // In the new schema, we don't have min/max stock per item
        // This method is now primarily for updating unit costs
        if ($unitCost > 0) {
            $sql = "UPDATE inventory_stock 
                    SET unit_cost = ?, updated_by = ?
                    WHERE boq_item_id = ? AND item_status = 'available'";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$unitCost, $currentUser['id'], $boqItemId]);
        } else {
            // Just update the updated_by field to mark as processed
            $sql = "UPDATE inventory_stock 
                    SET updated_by = ?
                    WHERE boq_item_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$currentUser['id'], $boqItemId]);
        }
        
        return $result;
    }
    
    public function addIndividualStockEntry($data) {
        $currentUser = Auth::getCurrentUser();
        $userId = $currentUser ? $currentUser['id'] : null;
        
        $sql = "INSERT INTO inventory_stock 
                (boq_item_id, serial_number, batch_number, unit_cost, purchase_date, 
                 expiry_date, warranty_period, location_type, location_id, location_name,
                 item_status, quality_status, supplier_name, purchase_order_number, 
                 invoice_number, notes, created_by, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['boq_item_id'],
            $data['serial_number'] ?? null,
            $data['batch_number'] ?? null,
            $data['unit_cost'],
            $data['purchase_date'] ?? null,
            $data['expiry_date'] ?? null,
            $data['warranty_period'] ?? null,
            $data['location_type'] ?? 'warehouse',
            $data['location_id'] ?? null,
            $data['location_name'] ?? 'Main Warehouse',
            'available', // Default status
            $data['quality_status'] ?? 'good',
            $data['supplier_name'] ?? null,
            $data['purchase_order_number'] ?? null,
            $data['invoice_number'] ?? null,
            $data['notes'] ?? null,
            $userId,
            $userId
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    public function addBulkStockEntries($boqItemId, $quantity, $data) {
        $stockIds = [];
        
        for ($i = 0; $i < $quantity; $i++) {
            $itemData = $data;
            $itemData['boq_item_id'] = $boqItemId;
            
            // Generate serial number if not provided
            if (empty($itemData['serial_number']) && !empty($data['serial_prefix'])) {
                $itemData['serial_number'] = $data['serial_prefix'] . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            }
            
            $stockId = $this->addIndividualStockEntry($itemData);
            if ($stockId) {
                $stockIds[] = $stockId;
            }
        }
        
        return $stockIds;
    }
    
    public function updateIndividualStockEntry($id, $data) {
        $currentUser = Auth::getCurrentUser();
        
        $fields = [];
        $values = [];
        
        $allowedFields = [
            'unit_cost', 'batch_number', 'serial_number', 'location_type', 'location_id', 'location_name',
            'purchase_date', 'expiry_date', 'supplier_name', 'quality_status', 'warranty_period', 
            'item_status', 'notes'
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
        
        // Always update the updated_by field
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
        // In the new schema, each item is individual, so quantity should always be 1
        // This method creates individual entries for each item
        for ($i = 0; $i < $quantity; $i++) {
            $itemData = [
                'boq_item_id' => $boqItemId,
                'unit_cost' => $unitCost,
                'batch_number' => $batchNumber,
                'serial_number' => $serialNumber,
                'supplier_name' => $supplier,
                'purchase_date' => $purchaseDate,
                'expiry_date' => $expiryDate,
                'quality_status' => $qualityStatus,
                'notes' => $remarks
            ];
            
            $this->addIndividualStockEntry($itemData);
        }
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
                         JOIN boq_items bi ON CAST(iii.boq_item_id AS CHAR) = CAST(bi.id AS CHAR)
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
        $currentUser = Auth::getCurrentUser();
        $totalItems = 0;
        $totalValue = 0;
        
        foreach ($items as $item) {
            $boqItemId = $item['boq_item_id'];
            $requestedQuantity = intval($item['quantity_dispatched']);
            $specifiedSerials = $item['serial_numbers'] ?? [];
            
            // Get available stock items for dispatch
            $stockItems = [];
            
            if (!empty($specifiedSerials)) {
                // Admin specified serial numbers - try to get those specific items
                $stockItems = $this->getStockBySerialNumbers($specifiedSerials);
                
                // Check if all specified serials are available
                $foundSerials = array_column($stockItems, 'serial_number');
                $missingSerials = array_diff($specifiedSerials, $foundSerials);
                
                if (!empty($missingSerials)) {
                    throw new Exception("Serial numbers not available: " . implode(', ', $missingSerials));
                }
                
                // Check if we have enough items
                if (count($stockItems) < $requestedQuantity) {
                    throw new Exception("Not enough items available for BOQ item ID $boqItemId");
                }
            } else {
                // No serial numbers specified - auto-select available items
                $stockItems = $this->getAvailableStock($boqItemId, $requestedQuantity);
                
                if (count($stockItems) < $requestedQuantity) {
                    $available = count($stockItems);
                    throw new Exception("Insufficient stock for BOQ item ID $boqItemId. Requested: $requestedQuantity, Available: $available");
                }
            }
            
            // Dispatch the selected items
            $itemsToDispatch = array_slice($stockItems, 0, $requestedQuantity);
            
            foreach ($itemsToDispatch as $stockItem) {
                // Insert dispatch item record
                $sql = "INSERT INTO inventory_dispatch_items 
                        (dispatch_id, inventory_stock_id, boq_item_id, unit_cost, 
                         item_condition, dispatch_notes, warranty_period)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $dispatchId,
                    $stockItem['id'],
                    $boqItemId,
                    $stockItem['unit_cost'],
                    $item['item_condition'] ?? 'new',
                    $item['remarks'] ?? null,
                    $item['warranty_period'] ?? null
                ]);
                
                // Update stock item status to 'dispatched'
                $this->updateStockItemStatus($stockItem['id'], 'dispatched', $dispatchId);
                
                $totalItems++;
                $totalValue += $stockItem['unit_cost'];
            }
        }
        
        // Update dispatch totals
        $this->updateDispatchTotals($dispatchId, $totalItems, $totalValue);
        
        return true;
    }
    
    public function updateStockItemStatus($stockId, $status, $dispatchId = null) {
        $currentUser = Auth::getCurrentUser();
        
        $sql = "UPDATE inventory_stock 
                SET item_status = ?, 
                    dispatch_id = ?,
                    dispatched_at = CASE WHEN ? = 'dispatched' THEN NOW() ELSE dispatched_at END,
                    delivered_at = CASE WHEN ? = 'delivered' THEN NOW() ELSE delivered_at END,
                    updated_by = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $status,
            $dispatchId,
            $status,
            $status,
            $currentUser['id'],
            $stockId
        ]);
    }
    
    private function updateDispatchTotals($dispatchId, $totalItems = null, $totalValue = null) {
        if ($totalItems === null || $totalValue === null) {
            // Calculate from database
            $sql = "UPDATE inventory_dispatches 
                    SET total_items = (SELECT COUNT(*) FROM inventory_dispatch_items WHERE dispatch_id = ?),
                        total_value = (SELECT SUM(unit_cost) FROM inventory_dispatch_items WHERE dispatch_id = ?)
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dispatchId, $dispatchId, $dispatchId]);
        } else {
            // Use provided values
            $sql = "UPDATE inventory_dispatches 
                    SET total_items = ?, total_value = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$totalItems, $totalValue, $dispatchId]);
        }
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
                JOIN boq_items bi ON CAST(it.boq_item_id AS CHAR) = CAST(bi.id AS CHAR)
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
        
        // Use the new inventory_summary view for aggregated data
        $sql = "SELECT 
                    COUNT(DISTINCT boq_item_id) as total_items,
                    SUM(total_stock) as total_quantity,
                    SUM(total_value) as total_value,
                    SUM(available_stock) as available_quantity,
                    SUM(dispatched_stock) as dispatched_quantity,
                    SUM(delivered_stock) as delivered_quantity
                FROM inventory_summary";
        
        $stmt = $this->db->query($sql);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Total individual entries
        $sql = "SELECT COUNT(*) as total_entries
                FROM inventory_stock ist
                JOIN boq_items bi ON CAST(ist.boq_item_id AS CHAR) = CAST(bi.id AS CHAR)
                WHERE bi.status = 'active'";
        
        $stmt = $this->db->query($sql);
        $stats['total_entries'] = $stmt->fetchColumn();
        
        // Recent additions (last 7 days)
        $sql = "SELECT COUNT(*) as recent_additions
                FROM inventory_stock 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $stmt = $this->db->query($sql);
        $stats['recent_additions'] = $stmt->fetchColumn();
        
        // Pending dispatches
        $sql = "SELECT COUNT(*) as pending_dispatches
                FROM inventory_dispatches 
                WHERE dispatch_status IN ('prepared', 'dispatched', 'in_transit')";
        
        $stmt = $this->db->query($sql);
        $stats['pending_dispatches'] = $stmt->fetchColumn();
        
        // Status-wise breakdown
        $sql = "SELECT item_status, COUNT(*) as count
                FROM inventory_stock 
                GROUP BY item_status";
        
        $stmt = $this->db->query($sql);
        $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stats['by_status'] = [];
        foreach ($statusStats as $status) {
            $stats['by_status'][$status['item_status']] = $status['count'];
        }
        
        // Location-wise breakdown
        $sql = "SELECT location_type, COUNT(*) as count
                FROM inventory_stock 
                GROUP BY location_type";
        
        $stmt = $this->db->query($sql);
        $locationStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stats['by_location'] = [];
        foreach ($locationStats as $location) {
            $stats['by_location'][$location['location_type']] = $location['count'];
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
        $sql = "SELECT idi.*, ist.serial_number, ist.batch_number, ist.item_status,
                       bi.item_name, bi.item_code, bi.unit, bi.icon_class
                FROM inventory_dispatch_items idi
                LEFT JOIN inventory_stock ist ON idi.inventory_stock_id = ist.id
                LEFT JOIN boq_items bi ON CAST(idi.boq_item_id AS CHAR) = CAST(bi.id AS CHAR)
                WHERE idi.dispatch_id = ?
                ORDER BY bi.item_name, ist.serial_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dispatchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDispatchItemsSummary($dispatchId) {
        $sql = "SELECT bi.id as boq_item_id, bi.item_name, bi.item_code, bi.unit, bi.icon_class,
                       COUNT(idi.id) as quantity_dispatched,
                       AVG(idi.unit_cost) as avg_unit_cost,
                       SUM(idi.unit_cost) as total_cost,
                       GROUP_CONCAT(ist.serial_number ORDER BY ist.serial_number) as serial_numbers
                FROM inventory_dispatch_items idi
                LEFT JOIN inventory_stock ist ON idi.inventory_stock_id = ist.id
                LEFT JOIN boq_items bi ON CAST(idi.boq_item_id AS CHAR) = CAST(bi.id AS CHAR)
                WHERE idi.dispatch_id = ?
                GROUP BY bi.id, bi.item_name, bi.item_code, bi.unit, bi.icon_class
                ORDER BY bi.item_name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dispatchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function checkStockAvailabilityForItems($requestedItems) {
        $stockAvailability = [];
        
        foreach ($requestedItems as $item) {
            if (empty($item['boq_item_id'])) {
                continue;
            }
            
            $boqItemId = $item['boq_item_id'];
            $requestedQuantity = intval($item['quantity']);
            
            // Get available stock for this item
            $availableStock = $this->getAvailableStock($boqItemId);
            $availableQuantity = count($availableStock);
            
            // Get item details
            $sql = "SELECT item_name, item_code, unit FROM boq_items WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$boqItemId]);
            $itemDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stockAvailability[$boqItemId] = [
                'item_name' => $itemDetails['item_name'] ?? 'Unknown Item',
                'item_code' => $itemDetails['item_code'] ?? 'N/A',
                'unit' => $itemDetails['unit'] ?? 'Nos',
                'requested_quantity' => $requestedQuantity,
                'available_quantity' => $availableQuantity,
                'is_sufficient' => $availableQuantity >= $requestedQuantity,
                'shortage' => max(0, $requestedQuantity - $availableQuantity),
                'available_items' => $availableStock
            ];
        }
        
        return $stockAvailability;
    }
    
    public function getStockSummaryForItem($boqItemId) {
        $sql = "SELECT 
                    COUNT(CASE WHEN item_status = 'available' THEN 1 END) as available_stock,
                    COUNT(CASE WHEN item_status = 'dispatched' THEN 1 END) as dispatched_stock,
                    COUNT(CASE WHEN item_status = 'delivered' THEN 1 END) as delivered_stock,
                    COUNT(*) as total_stock,
                    AVG(unit_cost) as avg_unit_cost
                FROM inventory_stock 
                WHERE boq_item_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boqItemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getStockDetailsByItem($boqItemId) {
        $sql = "SELECT inv.*, 
                       inv.item_status as status
                FROM inventory_stock inv
                WHERE inv.boq_item_id = ?
                ORDER BY inv.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boqItemId]);
        return $stmt->fetchAll();
    }

    public function getStockSummaryByItem($boqItemId) {
        $sql = "SELECT 
                    COUNT(*) as total_stock,
                    SUM(CASE WHEN item_status = 'available' THEN 1 ELSE 0 END) as available_stock,
                    SUM(CASE WHEN item_status = 'dispatched' THEN 1 ELSE 0 END) as dispatched_stock,
                    AVG(unit_cost) as avg_unit_cost,
                    SUM(unit_cost) as total_value
                FROM inventory_stock 
                WHERE boq_item_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boqItemId]);
        return $stmt->fetch();
    }

    public function getStockHistoryByItem($boqItemId, $limit = 50) {
        $sql = "SELECT 
                    'inward' as transaction_type,
                    1 as quantity,
                    inv.created_at,
                    inv.purchase_order_number as reference_number,
                    u.username as user_name,
                    inv.notes
                FROM inventory_stock inv
                LEFT JOIN users u ON inv.created_by = u.id
                WHERE inv.boq_item_id = ?
                
                UNION ALL
                
                SELECT 
                    'dispatch' as transaction_type,
                    1 as quantity,
                    inv2.updated_at as created_at,
                    '' as reference_number,
                    u2.username as user_name,
                    'Item dispatched' as notes
                FROM inventory_stock inv2
                LEFT JOIN users u2 ON inv2.updated_by = u2.id
                WHERE inv2.boq_item_id = ? AND inv2.item_status = 'dispatched'
                
                ORDER BY created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boqItemId, $boqItemId, $limit]);
        return $stmt->fetchAll();
    }

}
?>