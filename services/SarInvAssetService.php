<?php
require_once __DIR__ . '/../models/SarInvAsset.php';
require_once __DIR__ . '/../models/SarInvProduct.php';
require_once __DIR__ . '/../models/SarInvAuditLog.php';

/**
 * SAR Inventory Asset Service
 * Business logic for asset registration, tracking, and location management
 */
class SarInvAssetService {
    private $assetModel;
    private $productModel;
    private $auditLog;
    
    public function __construct() {
        $this->assetModel = new SarInvAsset();
        $this->productModel = new SarInvProduct();
        $this->auditLog = new SarInvAuditLog();
    }
    
    /**
     * Register a new asset
     * @param array $data Asset data
     * @return array Result with success status and asset ID
     */
    public function registerAsset(array $data): array {
        // Validate data
        $errors = $this->assetModel->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate product exists
        $product = $this->productModel->find($data['product_id']);
        if (!$product) {
            return ['success' => false, 'errors' => ['Product not found']];
        }
        
        // Validate serial number uniqueness
        if (!empty($data['serial_number'])) {
            if ($this->assetModel->serialNumberExists($data['serial_number'])) {
                return ['success' => false, 'errors' => ['Serial number already exists']];
            }
        }
        
        // Validate barcode uniqueness
        if (!empty($data['barcode'])) {
            if ($this->assetModel->barcodeExists($data['barcode'])) {
                return ['success' => false, 'errors' => ['Barcode already exists']];
            }
        }
        
        try {
            $assetId = $this->assetModel->register($data);
            
            if ($assetId) {
                return [
                    'success' => true,
                    'asset_id' => $assetId,
                    'message' => 'Asset registered successfully'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to register asset']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Update asset information
     * @param int $id Asset ID
     * @param array $data Updated data
     * @return array Result with success status
     */
    public function updateAsset(int $id, array $data): array {
        $asset = $this->assetModel->find($id);
        if (!$asset) {
            return ['success' => false, 'errors' => ['Asset not found']];
        }
        
        $errors = $this->assetModel->validate($data, true, $id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $result = $this->assetModel->update($id, $data);
            
            if ($result) {
                return ['success' => true, 'message' => 'Asset updated successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to update asset']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Update asset location with history logging
     * @param int $assetId Asset ID
     * @param string $locationType Location type
     * @param int|null $locationId Location ID
     * @param string|null $notes Notes
     * @return array Result with success status
     */
    public function updateLocation(int $assetId, string $locationType, ?int $locationId = null, ?string $notes = null): array {
        $asset = $this->assetModel->find($assetId);
        if (!$asset) {
            return ['success' => false, 'errors' => ['Asset not found']];
        }
        
        $validLocationTypes = [
            SarInvAsset::LOCATION_WAREHOUSE,
            SarInvAsset::LOCATION_DISPATCH,
            SarInvAsset::LOCATION_REPAIR,
            SarInvAsset::LOCATION_SITE,
            SarInvAsset::LOCATION_VENDOR,
            SarInvAsset::LOCATION_CUSTOMER
        ];
        
        if (!in_array($locationType, $validLocationTypes)) {
            return ['success' => false, 'errors' => ['Invalid location type']];
        }
        
        try {
            $result = $this->assetModel->updateLocation($assetId, $locationType, $locationId, $notes);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Asset location updated successfully',
                    'previous_location' => [
                        'type' => $asset['current_location_type'],
                        'id' => $asset['current_location_id']
                    ],
                    'new_location' => [
                        'type' => $locationType,
                        'id' => $locationId
                    ]
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to update asset location']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Update asset status
     * @param int $assetId Asset ID
     * @param string $status New status
     * @param string|null $notes Notes
     * @return array Result with success status
     */
    public function updateStatus(int $assetId, string $status, ?string $notes = null): array {
        $asset = $this->assetModel->find($assetId);
        if (!$asset) {
            return ['success' => false, 'errors' => ['Asset not found']];
        }
        
        $validStatuses = [
            SarInvAsset::STATUS_AVAILABLE,
            SarInvAsset::STATUS_DISPATCHED,
            SarInvAsset::STATUS_IN_REPAIR,
            SarInvAsset::STATUS_RETIRED,
            SarInvAsset::STATUS_LOST
        ];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'errors' => ['Invalid status']];
        }
        
        try {
            $result = $this->assetModel->updateStatus($assetId, $status, $notes);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Asset status updated successfully',
                    'previous_status' => $asset['status'],
                    'new_status' => $status
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to update asset status']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Mark asset as dispatched
     * @param int $assetId Asset ID
     * @param int $dispatchId Dispatch ID
     * @param string $destinationType Destination type
     * @param int|null $destinationId Destination ID
     * @return array Result with success status
     */
    public function markDispatched(int $assetId, int $dispatchId, string $destinationType, ?int $destinationId = null): array {
        $asset = $this->assetModel->find($assetId);
        if (!$asset) {
            return ['success' => false, 'errors' => ['Asset not found']];
        }
        
        if ($asset['status'] !== SarInvAsset::STATUS_AVAILABLE) {
            return ['success' => false, 'errors' => ['Only available assets can be dispatched']];
        }
        
        try {
            $result = $this->assetModel->markDispatched($assetId, $dispatchId, $destinationType, $destinationId);
            
            if ($result) {
                return ['success' => true, 'message' => 'Asset marked as dispatched'];
            }
            
            return ['success' => false, 'errors' => ['Failed to mark asset as dispatched']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Mark asset as received
     * @param int $assetId Asset ID
     * @param string $locationType Location type
     * @param int|null $locationId Location ID
     * @param string|null $notes Notes
     * @return array Result with success status
     */
    public function markReceived(int $assetId, string $locationType, ?int $locationId = null, ?string $notes = null): array {
        try {
            $result = $this->assetModel->markReceived($assetId, $locationType, $locationId, $notes);
            
            if ($result) {
                return ['success' => true, 'message' => 'Asset marked as received'];
            }
            
            return ['success' => false, 'errors' => ['Failed to mark asset as received']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Retire asset
     * @param int $assetId Asset ID
     * @param string|null $reason Retirement reason
     * @return array Result with success status
     */
    public function retireAsset(int $assetId, ?string $reason = null): array {
        return $this->updateStatus($assetId, SarInvAsset::STATUS_RETIRED, $reason);
    }
    
    /**
     * Find asset by serial number
     * @param string $serialNumber Serial number
     * @return array|null Asset data
     */
    public function findBySerialNumber(string $serialNumber): ?array {
        $asset = $this->assetModel->findBySerialNumber($serialNumber);
        return $asset ?: null;
    }
    
    /**
     * Find asset by barcode
     * @param string $barcode Barcode
     * @return array|null Asset data
     */
    public function findByBarcode(string $barcode): ?array {
        $asset = $this->assetModel->findByBarcode($barcode);
        return $asset ?: null;
    }
    
    /**
     * Scan asset (by serial number or barcode)
     * @param string $identifier Serial number or barcode
     * @return array Result with asset data or error
     */
    public function scanAsset(string $identifier): array {
        // Try serial number first
        $asset = $this->assetModel->findBySerialNumber($identifier);
        
        if (!$asset) {
            // Try barcode
            $asset = $this->assetModel->findByBarcode($identifier);
        }
        
        if ($asset) {
            return [
                'success' => true,
                'asset' => $asset,
                'found_by' => $asset['serial_number'] === $identifier ? 'serial_number' : 'barcode'
            ];
        }
        
        return ['success' => false, 'errors' => ['Asset not found']];
    }
    
    /**
     * Get asset by ID
     * @param int $id Asset ID
     * @return array|null Asset data
     */
    public function getAsset(int $id): ?array {
        $asset = $this->assetModel->find($id);
        return $asset ?: null;
    }
    
    /**
     * Get asset with product info
     * @param int $id Asset ID
     * @return array|null Asset with product data
     */
    public function getAssetWithProduct(int $id): ?array {
        $asset = $this->assetModel->getWithProduct($id);
        return $asset ?: null;
    }
    
    /**
     * Get asset history
     * @param int $assetId Asset ID
     * @param int $limit Number of records
     * @return array History records
     */
    public function getAssetHistory(int $assetId, int $limit = 100): array {
        return $this->assetModel->getHistory($assetId, $limit);
    }

    /**
     * Get assets by product
     * @param int $productId Product ID
     * @param int|null $warehouseId Optional warehouse filter
     * @param string|null $status Optional status filter
     * @return array Assets
     */
    public function getAssetsByProduct(int $productId, ?int $warehouseId = null, ?string $status = null): array {
        return $this->assetModel->getByProduct($productId, $warehouseId, $status);
    }
    
    /**
     * Get assets by location
     * @param string $locationType Location type
     * @param int|null $locationId Location ID
     * @return array Assets
     */
    public function getAssetsByLocation(string $locationType, ?int $locationId = null): array {
        return $this->assetModel->getByLocation($locationType, $locationId);
    }
    
    /**
     * Get assets by status
     * @param string $status Status
     * @return array Assets
     */
    public function getAssetsByStatus(string $status): array {
        return $this->assetModel->getByStatus($status);
    }
    
    /**
     * Search assets
     * @param string|null $keyword Search keyword
     * @param string|null $status Status filter
     * @param int|null $productId Product filter
     * @param string|null $locationType Location type filter
     * @return array Assets
     */
    public function searchAssets(?string $keyword = null, ?string $status = null, ?int $productId = null, ?string $locationType = null): array {
        return $this->assetModel->search($keyword, $status, $productId, $locationType);
    }
    
    /**
     * Get available assets
     * @return array Available assets
     */
    public function getAvailableAssets(): array {
        return $this->assetModel->getByStatus(SarInvAsset::STATUS_AVAILABLE);
    }
    
    /**
     * Get assets with expiring warranty
     * @param int $daysAhead Days ahead to check
     * @return array Assets with expiring warranty
     */
    public function getExpiringWarrantyAssets(int $daysAhead = 30): array {
        return $this->assetModel->getExpiringWarranty($daysAhead);
    }
    
    /**
     * Check if asset is available
     * @param int $assetId Asset ID
     * @return bool True if available
     */
    public function isAssetAvailable(int $assetId): bool {
        return $this->assetModel->isAvailable($assetId);
    }
    
    /**
     * Validate serial number
     * @param string $serialNumber Serial number
     * @param int|null $excludeId Asset ID to exclude
     * @return array Validation result
     */
    public function validateSerialNumber(string $serialNumber, ?int $excludeId = null): array {
        if (empty($serialNumber)) {
            return ['valid' => false, 'error' => 'Serial number is required'];
        }
        
        if ($this->assetModel->serialNumberExists($serialNumber, $excludeId)) {
            return ['valid' => false, 'error' => 'Serial number already exists'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validate barcode
     * @param string $barcode Barcode
     * @param int|null $excludeId Asset ID to exclude
     * @return array Validation result
     */
    public function validateBarcode(string $barcode, ?int $excludeId = null): array {
        if (empty($barcode)) {
            return ['valid' => false, 'error' => 'Barcode is required'];
        }
        
        if ($this->assetModel->barcodeExists($barcode, $excludeId)) {
            return ['valid' => false, 'error' => 'Barcode already exists'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Get asset audit history
     * @param int $assetId Asset ID
     * @param int $limit Number of records
     * @return array Audit log entries
     */
    public function getAuditHistory(int $assetId, int $limit = 50): array {
        return $this->auditLog->getLogsForRecord('sar_inv_assets', $assetId, $limit);
    }
}
?>
