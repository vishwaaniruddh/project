<?php
/**
 * SAR Inventory Repairs API
 * Repair management with workflow and cost tracking
 */
require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../controllers/SarInvApiController.php';
require_once '../../services/SarInvRepairService.php';

class RepairsApi extends SarInvApiController {
    private $repairService;
    
    public function __construct() {
        parent::__construct();
        $this->repairService = new SarInvRepairService();
    }
    
    public function handle(): void {
        $this->requireAuth();
        
        $method = $this->getMethod();
        $id = $this->getQueryInt('id');
        $action = $this->getQuery('action');
        
        if ($action) {
            $this->handleAction($action, $id);
            return;
        }
        
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->show($id);
                } else {
                    $this->index();
                }
                break;
            case 'POST':
                $this->requirePermission('create');
                $this->store();
                break;
            case 'PUT':
                $this->requirePermission('edit');
                if (!$id) {
                    $this->sendError('Repair ID required', 400);
                }
                $this->update($id);
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    private function handleAction(string $action, int $id): void {
        switch ($action) {
            case 'search':
                $this->search();
                break;
            case 'by_status':
                $this->getByStatus();
                break;
            case 'by_asset':
                $this->getByAsset();
                break;
            case 'by_vendor':
                $this->getByVendor();
                break;
            case 'start':
                $this->requirePermission('edit');
                $this->start($id);
                break;
            case 'complete':
                $this->requirePermission('edit');
                $this->complete($id);
                break;
            case 'cancel':
                $this->requirePermission('edit');
                $this->cancel($id);
                break;
            case 'update_cost':
                $this->requirePermission('edit');
                $this->updateCost($id);
                break;
            case 'update_diagnosis':
                $this->requirePermission('edit');
                $this->updateDiagnosis($id);
                break;
            case 'overdue':
                $this->getOverdue();
                break;
            case 'statistics':
                $this->getStatistics();
                break;
            case 'transitions':
                $this->getTransitions($id);
                break;
            case 'by_number':
                $this->getByNumber();
                break;
            default:
                $this->sendError('Unknown action', 400);
        }
    }

    private function index(): void {
        $keyword = $this->getQuery('search');
        $status = $this->getQuery('status');
        $vendorId = $this->getQueryInt('vendor_id') ?: null;
        $dateFrom = $this->getQuery('date_from');
        $dateTo = $this->getQuery('date_to');
        
        $repairs = $this->repairService->searchRepairs($keyword, $status, $vendorId, $dateFrom, $dateTo);
        
        $this->sendSuccess([
            'repairs' => $repairs,
            'total' => count($repairs)
        ]);
    }
    
    private function show(int $id): void {
        $repair = $this->repairService->getRepairWithDetails($id);
        
        if (!$repair) {
            $this->sendError('Repair not found', 404);
        }
        
        $repair['valid_transitions'] = $this->repairService->getValidStatusTransitions($id);
        
        $this->sendSuccess(['repair' => $repair]);
    }
    
    private function store(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['asset_id', 'issue_description']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->repairService->createRepair($data);
        
        if ($result['success']) {
            $this->logAction('create', 'sar_inv_repairs', $result['repair_id'], $data);
            $repair = $this->repairService->getRepairWithDetails($result['repair_id']);
            $this->sendSuccess(['repair' => $repair], $result['message'], 201);
        } else {
            $this->sendError('Failed to create repair', 400, $result['errors']);
        }
    }
    
    private function update(int $id): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        // Handle status updates
        if (isset($data['status'])) {
            switch ($data['status']) {
                case 'in_progress':
                    $result = $this->repairService->startRepair($id);
                    break;
                case 'completed':
                    $result = $this->repairService->completeRepair(
                        $id,
                        $data['repair_notes'] ?? null,
                        isset($data['cost']) ? floatval($data['cost']) : null,
                        isset($data['return_warehouse_id']) ? intval($data['return_warehouse_id']) : null
                    );
                    break;
                case 'cancelled':
                    $result = $this->repairService->cancelRepair($id, $data['reason'] ?? null);
                    break;
                default:
                    $this->sendError('Invalid status', 400);
                    return;
            }
        } else {
            // Update other fields
            if (isset($data['cost'])) {
                $result = $this->repairService->updateCost($id, floatval($data['cost']));
            } elseif (isset($data['diagnosis'])) {
                $result = $this->repairService->updateDiagnosis($id, $data['diagnosis']);
            } else {
                $this->sendError('No update data provided', 400);
                return;
            }
        }
        
        if ($result['success']) {
            $this->logAction('update', 'sar_inv_repairs', $id, $data);
            $repair = $this->repairService->getRepair($id);
            $this->sendSuccess(['repair' => $repair], $result['message']);
        } else {
            $this->sendError('Failed to update repair', 400, $result['errors']);
        }
    }
    
    private function search(): void {
        $keyword = $this->getQuery('q');
        $status = $this->getQuery('status');
        $vendorId = $this->getQueryInt('vendor_id') ?: null;
        $dateFrom = $this->getQuery('date_from');
        $dateTo = $this->getQuery('date_to');
        
        $repairs = $this->repairService->searchRepairs($keyword, $status, $vendorId, $dateFrom, $dateTo);
        $this->sendSuccess(['repairs' => $repairs]);
    }
    
    private function getByStatus(): void {
        $status = $this->getQuery('status');
        if (empty($status)) {
            $this->sendError('Status required', 400);
        }
        
        $repairs = $this->repairService->getRepairsByStatus($status);
        $this->sendSuccess(['repairs' => $repairs]);
    }
    
    private function getByAsset(): void {
        $assetId = $this->getQueryInt('asset_id');
        if (!$assetId) {
            $this->sendError('Asset ID required', 400);
        }
        
        $repairs = $this->repairService->getRepairsByAsset($assetId);
        $this->sendSuccess(['repairs' => $repairs]);
    }
    
    private function getByVendor(): void {
        $vendorId = $this->getQueryInt('vendor_id');
        $status = $this->getQuery('status');
        
        if (!$vendorId) {
            $this->sendError('Vendor ID required', 400);
        }
        
        $repairs = $this->repairService->getRepairsByVendor($vendorId, $status);
        $this->sendSuccess(['repairs' => $repairs]);
    }
    
    private function start(int $id): void {
        if (!$id) {
            $this->sendError('Repair ID required', 400);
        }
        
        $result = $this->repairService->startRepair($id);
        
        if ($result['success']) {
            $this->logAction('start', 'sar_inv_repairs', $id);
            $repair = $this->repairService->getRepair($id);
            $this->sendSuccess(['repair' => $repair], $result['message']);
        } else {
            $this->sendError('Failed to start repair', 400, $result['errors']);
        }
    }
    
    private function complete(int $id): void {
        if (!$id) {
            $this->sendError('Repair ID required', 400);
        }
        
        $data = $this->getJsonBody();
        
        $result = $this->repairService->completeRepair(
            $id,
            $data['repair_notes'] ?? null,
            isset($data['cost']) ? floatval($data['cost']) : null,
            isset($data['return_warehouse_id']) ? intval($data['return_warehouse_id']) : null
        );
        
        if ($result['success']) {
            $this->logAction('complete', 'sar_inv_repairs', $id, $data);
            $repair = $this->repairService->getRepair($id);
            $this->sendSuccess(['repair' => $repair], $result['message']);
        } else {
            $this->sendError('Failed to complete repair', 400, $result['errors']);
        }
    }
    
    private function cancel(int $id): void {
        if (!$id) {
            $this->sendError('Repair ID required', 400);
        }
        
        $data = $this->getJsonBody();
        $reason = $data['reason'] ?? null;
        
        $result = $this->repairService->cancelRepair($id, $reason);
        
        if ($result['success']) {
            $this->logAction('cancel', 'sar_inv_repairs', $id, ['reason' => $reason]);
            $repair = $this->repairService->getRepair($id);
            $this->sendSuccess(['repair' => $repair], $result['message']);
        } else {
            $this->sendError('Failed to cancel repair', 400, $result['errors']);
        }
    }
    
    private function updateCost(int $id): void {
        if (!$id) {
            $this->sendError('Repair ID required', 400);
        }
        
        $data = $this->getJsonBody();
        if (!isset($data['cost'])) {
            $this->sendError('Cost required', 400);
        }
        
        $result = $this->repairService->updateCost($id, floatval($data['cost']));
        
        if ($result['success']) {
            $this->logAction('update_cost', 'sar_inv_repairs', $id, $data);
            $this->sendSuccess(null, $result['message']);
        } else {
            $this->sendError('Failed to update cost', 400, $result['errors']);
        }
    }
    
    private function updateDiagnosis(int $id): void {
        if (!$id) {
            $this->sendError('Repair ID required', 400);
        }
        
        $data = $this->getJsonBody();
        if (empty($data['diagnosis'])) {
            $this->sendError('Diagnosis required', 400);
        }
        
        $result = $this->repairService->updateDiagnosis($id, $data['diagnosis']);
        
        if ($result['success']) {
            $this->logAction('update_diagnosis', 'sar_inv_repairs', $id, $data);
            $this->sendSuccess(null, $result['message']);
        } else {
            $this->sendError('Failed to update diagnosis', 400, $result['errors']);
        }
    }
    
    private function getOverdue(): void {
        $days = $this->getQueryInt('days', 7);
        $repairs = $this->repairService->getOverdueRepairs($days);
        $this->sendSuccess(['repairs' => $repairs]);
    }
    
    private function getStatistics(): void {
        $dateFrom = $this->getQuery('date_from');
        $dateTo = $this->getQuery('date_to');
        
        $statistics = $this->repairService->getStatistics($dateFrom, $dateTo);
        $this->sendSuccess(['statistics' => $statistics]);
    }
    
    private function getTransitions(int $id): void {
        if (!$id) {
            $this->sendError('Repair ID required', 400);
        }
        
        $transitions = $this->repairService->getValidStatusTransitions($id);
        $this->sendSuccess(['transitions' => $transitions]);
    }
    
    private function getByNumber(): void {
        $number = $this->getQuery('number');
        if (empty($number)) {
            $this->sendError('Repair number required', 400);
        }
        
        $repair = $this->repairService->getRepairByNumber($number);
        if (!$repair) {
            $this->sendError('Repair not found', 404);
        }
        
        $this->sendSuccess(['repair' => $repair]);
    }
}

// Execute API
$api = new RepairsApi();
$api->handle();
?>
