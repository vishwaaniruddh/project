<?php
require_once __DIR__ . '/../models/SarInvRepair.php';
require_once __DIR__ . '/../models/SarInvAsset.php';
require_once __DIR__ . '/../models/SarInvAuditLog.php';

/**
 * SAR Inventory Repair Service
 * Business logic for repair workflow management with asset status updates and cost tracking
 */
class SarInvRepairService {
    private $repairModel;
    private $assetModel;
    private $auditLog;
    
    public function __construct() {
        $this->repairModel = new SarInvRepair();
        $this->assetModel = new SarInvAsset();
        $this->auditLog = new SarInvAuditLog();
    }
    
    /**
     * Create a new repair
     * @param array $data Repair data
     * @return array Result with success status and repair ID
     */
    public function createRepair(array $data): array {
        // Validate data
        $errors = $this->repairModel->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate asset exists
        $asset = $this->assetModel->find($data['asset_id']);
        if (!$asset) {
            return ['success' => false, 'errors' => ['Asset not found']];
        }
        
        // Check if asset is already in repair
        if ($asset['status'] === SarInvAsset::STATUS_IN_REPAIR) {
            return ['success' => false, 'errors' => ['Asset is already in repair']];
        }
        
        // Check if asset is retired
        if ($asset['status'] === SarInvAsset::STATUS_RETIRED) {
            return ['success' => false, 'errors' => ['Cannot create repair for retired asset']];
        }
        
        try {
            $repairId = $this->repairModel->createRepair($data);
            
            if ($repairId) {
                $repair = $this->repairModel->find($repairId);
                return [
                    'success' => true,
                    'repair_id' => $repairId,
                    'repair_number' => $repair['repair_number'],
                    'message' => 'Repair created successfully. Asset status updated to in_repair.'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to create repair']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Start repair (change status to in_progress)
     * @param int $repairId Repair ID
     * @return array Result with success status
     */
    public function startRepair(int $repairId): array {
        $repair = $this->repairModel->find($repairId);
        if (!$repair) {
            return ['success' => false, 'errors' => ['Repair not found']];
        }
        
        if ($repair['status'] !== SarInvRepair::STATUS_PENDING) {
            return ['success' => false, 'errors' => ['Only pending repairs can be started']];
        }
        
        try {
            $result = $this->repairModel->startRepair($repairId);
            
            if ($result) {
                return ['success' => true, 'message' => 'Repair started successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to start repair']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Complete repair
     * @param int $repairId Repair ID
     * @param string|null $repairNotes Repair notes
     * @param float|null $cost Repair cost
     * @param int|null $returnWarehouseId Warehouse to return asset to
     * @return array Result with success status
     */
    public function completeRepair(int $repairId, ?string $repairNotes = null, ?float $cost = null, ?int $returnWarehouseId = null): array {
        $repair = $this->repairModel->find($repairId);
        if (!$repair) {
            return ['success' => false, 'errors' => ['Repair not found']];
        }
        
        if (!in_array($repair['status'], [SarInvRepair::STATUS_PENDING, SarInvRepair::STATUS_IN_PROGRESS])) {
            return ['success' => false, 'errors' => ['Only pending or in-progress repairs can be completed']];
        }
        
        if ($cost !== null && $cost < 0) {
            return ['success' => false, 'errors' => ['Cost cannot be negative']];
        }
        
        try {
            $result = $this->repairModel->completeRepair($repairId, $repairNotes, $cost, $returnWarehouseId);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Repair completed successfully. Asset status updated to available.'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to complete repair']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Cancel repair
     * @param int $repairId Repair ID
     * @param string|null $reason Cancellation reason
     * @return array Result with success status
     */
    public function cancelRepair(int $repairId, ?string $reason = null): array {
        $repair = $this->repairModel->find($repairId);
        if (!$repair) {
            return ['success' => false, 'errors' => ['Repair not found']];
        }
        
        if ($repair['status'] === SarInvRepair::STATUS_COMPLETED) {
            return ['success' => false, 'errors' => ['Cannot cancel completed repairs']];
        }
        
        if ($repair['status'] === SarInvRepair::STATUS_CANCELLED) {
            return ['success' => false, 'errors' => ['Repair is already cancelled']];
        }
        
        try {
            $result = $this->repairModel->cancelRepair($repairId, $reason);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Repair cancelled successfully. Asset status restored.'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to cancel repair']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Update repair cost
     * @param int $repairId Repair ID
     * @param float $cost New cost
     * @return array Result with success status
     */
    public function updateCost(int $repairId, float $cost): array {
        if ($cost < 0) {
            return ['success' => false, 'errors' => ['Cost cannot be negative']];
        }
        
        $repair = $this->repairModel->find($repairId);
        if (!$repair) {
            return ['success' => false, 'errors' => ['Repair not found']];
        }
        
        try {
            $result = $this->repairModel->updateCost($repairId, $cost);
            
            if ($result) {
                return ['success' => true, 'message' => 'Repair cost updated successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to update repair cost']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Update diagnosis
     * @param int $repairId Repair ID
     * @param string $diagnosis Diagnosis
     * @return array Result with success status
     */
    public function updateDiagnosis(int $repairId, string $diagnosis): array {
        $repair = $this->repairModel->find($repairId);
        if (!$repair) {
            return ['success' => false, 'errors' => ['Repair not found']];
        }
        
        try {
            $result = $this->repairModel->updateDiagnosis($repairId, $diagnosis);
            
            if ($result) {
                return ['success' => true, 'message' => 'Diagnosis updated successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to update diagnosis']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Get repair by ID
     * @param int $id Repair ID
     * @return array|null Repair data
     */
    public function getRepair(int $id): ?array {
        $repair = $this->repairModel->find($id);
        return $repair ?: null;
    }
    
    /**
     * Get repair with full details
     * @param int $id Repair ID
     * @return array|null Repair with asset and product info
     */
    public function getRepairWithDetails(int $id): ?array {
        $repair = $this->repairModel->getWithDetails($id);
        return $repair ?: null;
    }
    
    /**
     * Get repair by number
     * @param string $repairNumber Repair number
     * @return array|null Repair data
     */
    public function getRepairByNumber(string $repairNumber): ?array {
        $repair = $this->repairModel->findByNumber($repairNumber);
        return $repair ?: null;
    }
    
    /**
     * Get repairs by status
     * @param string $status Status filter
     * @return array Repairs
     */
    public function getRepairsByStatus(string $status): array {
        return $this->repairModel->getByStatus($status);
    }
    
    /**
     * Get repairs by asset
     * @param int $assetId Asset ID
     * @return array Repairs
     */
    public function getRepairsByAsset(int $assetId): array {
        return $this->repairModel->getByAsset($assetId);
    }
    
    /**
     * Get repairs by vendor
     * @param int $vendorId Vendor ID
     * @param string|null $status Optional status filter
     * @return array Repairs
     */
    public function getRepairsByVendor(int $vendorId, ?string $status = null): array {
        return $this->repairModel->getByVendor($vendorId, $status);
    }

    /**
     * Search repairs
     * @param string|null $keyword Search keyword
     * @param string|null $status Status filter
     * @param int|null $vendorId Vendor filter
     * @param string|null $dateFrom Date from filter
     * @param string|null $dateTo Date to filter
     * @return array Repairs
     */
    public function searchRepairs(?string $keyword = null, ?string $status = null, ?int $vendorId = null, ?string $dateFrom = null, ?string $dateTo = null): array {
        return $this->repairModel->search($keyword, $status, $vendorId, $dateFrom, $dateTo);
    }
    
    /**
     * Get overdue repairs
     * @param int $days Days threshold
     * @return array Overdue repairs
     */
    public function getOverdueRepairs(int $days = 7): array {
        return $this->repairModel->getOverdueRepairs($days);
    }
    
    /**
     * Get pending repairs count
     * @return int Count
     */
    public function getPendingCount(): int {
        $repairs = $this->repairModel->getByStatus(SarInvRepair::STATUS_PENDING);
        return count($repairs);
    }
    
    /**
     * Get in-progress repairs count
     * @return int Count
     */
    public function getInProgressCount(): int {
        $repairs = $this->repairModel->getByStatus(SarInvRepair::STATUS_IN_PROGRESS);
        return count($repairs);
    }
    
    /**
     * Get total repair costs
     * @param array $filters Optional filters
     * @return array Cost summary
     */
    public function getTotalCosts(array $filters = []): array {
        return $this->repairModel->getTotalCosts($filters);
    }
    
    /**
     * Get repair statistics
     * @param string|null $dateFrom Date from
     * @param string|null $dateTo Date to
     * @return array Statistics
     */
    public function getStatistics(?string $dateFrom = null, ?string $dateTo = null): array {
        return $this->repairModel->getStatistics($dateFrom, $dateTo);
    }
    
    /**
     * Get repair audit history
     * @param int $repairId Repair ID
     * @param int $limit Number of records
     * @return array Audit log entries
     */
    public function getAuditHistory(int $repairId, int $limit = 50): array {
        return $this->auditLog->getLogsForRecord('sar_inv_repairs', $repairId, $limit);
    }
    
    /**
     * Get valid status transitions for a repair
     * @param int $repairId Repair ID
     * @return array Valid next statuses
     */
    public function getValidStatusTransitions(int $repairId): array {
        $repair = $this->repairModel->find($repairId);
        if (!$repair) {
            return [];
        }
        
        $transitions = [
            SarInvRepair::STATUS_PENDING => [SarInvRepair::STATUS_IN_PROGRESS, SarInvRepair::STATUS_COMPLETED, SarInvRepair::STATUS_CANCELLED],
            SarInvRepair::STATUS_IN_PROGRESS => [SarInvRepair::STATUS_COMPLETED, SarInvRepair::STATUS_CANCELLED],
            SarInvRepair::STATUS_COMPLETED => [],
            SarInvRepair::STATUS_CANCELLED => []
        ];
        
        return $transitions[$repair['status']] ?? [];
    }
}
?>
