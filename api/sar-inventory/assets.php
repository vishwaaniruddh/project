<?php
/**
 * SAR Inventory Assets API
 * Asset management with tracking and location updates
 */
require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../controllers/SarInvApiController.php';
require_once '../../services/SarInvAssetService.php';

class AssetsApi extends SarInvApiController {
    private $assetService;
    
    public function __construct() {
        parent::__construct();
        $this->assetService = new SarInvAssetService();
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
                    $this->sendError('Asset ID required', 400);
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
            case 'scan':
                $this->scan();
                break;
            case 'update_location':
                $this->requirePermission('edit');
                $this->updateLocation($id);
                break;
            case 'update_status':
                $this->requirePermission('edit');
                $this->updateStatus($id);
                break;
            case 'history':
                $this->getHistory($id);
                break;
            case 'by_product':
                $this->getByProduct();
                break;
            case 'by_location':
                $this->getByLocation();
                break;
            case 'by_status':
                $this->getByStatus();
                break;
            case 'available':
                $this->getAvailable();
                break;
            case 'expiring_warranty':
                $this->getExpiringWarranty();
                break;
            case 'validate_serial':
                $this->validateSerial();
                break;
            case 'validate_barcode':
                $this->validateBarcode();
                break;
            default:
                $this->sendError('Unknown action', 400);
        }
    }

    private function index(): void {
        $keyword = $this->getQuery('search');
        $status = $this->getQuery('status');
        $productId = $this->getQueryInt('product_id') ?: null;
        $locationType = $this->getQuery('location_type');
        
        $assets = $this->assetService->searchAssets($keyword, $status, $productId, $locationType);
        
        $this->sendSuccess([
            'assets' => $assets,
            'total' => count($assets)
        ]);
    }
    
    private function show(int $id): void {
        $asset = $this->assetService->getAssetWithProduct($id);
        
        if (!$asset) {
            $this->sendError('Asset not found', 404);
        }
        
        // Include history
        $history = $this->assetService->getAssetHistory($id, 20);
        $asset['recent_history'] = $history;
        
        $this->sendSuccess(['asset' => $asset]);
    }
    
    private function store(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['product_id']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->assetService->registerAsset($data);
        
        if ($result['success']) {
            $this->logAction('create', 'sar_inv_assets', $result['asset_id'], $data);
            $asset = $this->assetService->getAsset($result['asset_id']);
            $this->sendSuccess(['asset' => $asset], $result['message'], 201);
        } else {
            $this->sendError('Failed to register asset', 400, $result['errors']);
        }
    }
    
    private function update(int $id): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $result = $this->assetService->updateAsset($id, $data);
        
        if ($result['success']) {
            $this->logAction('update', 'sar_inv_assets', $id, $data);
            $asset = $this->assetService->getAsset($id);
            $this->sendSuccess(['asset' => $asset], $result['message']);
        } else {
            $this->sendError('Failed to update asset', 400, $result['errors']);
        }
    }
    
    private function search(): void {
        $keyword = $this->getQuery('q');
        $status = $this->getQuery('status');
        $productId = $this->getQueryInt('product_id') ?: null;
        $locationType = $this->getQuery('location_type');
        
        $assets = $this->assetService->searchAssets($keyword, $status, $productId, $locationType);
        $this->sendSuccess(['assets' => $assets]);
    }
    
    private function scan(): void {
        $identifier = $this->getQuery('identifier');
        
        if (empty($identifier)) {
            $this->sendError('Identifier (serial number or barcode) required', 400);
        }
        
        $result = $this->assetService->scanAsset($identifier);
        
        if ($result['success']) {
            $this->sendSuccess($result);
        } else {
            $this->sendError('Asset not found', 404, $result['errors']);
        }
    }
    
    private function updateLocation(int $id): void {
        if (!$id) {
            $this->sendError('Asset ID required', 400);
        }
        
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['location_type']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->assetService->updateLocation(
            $id,
            $data['location_type'],
            isset($data['location_id']) ? intval($data['location_id']) : null,
            $data['notes'] ?? null
        );
        
        if ($result['success']) {
            $this->logAction('update_location', 'sar_inv_assets', $id, $data);
            $this->sendSuccess($result, $result['message']);
        } else {
            $this->sendError('Failed to update location', 400, $result['errors']);
        }
    }
    
    private function updateStatus(int $id): void {
        if (!$id) {
            $this->sendError('Asset ID required', 400);
        }
        
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['status']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->assetService->updateStatus(
            $id,
            $data['status'],
            $data['notes'] ?? null
        );
        
        if ($result['success']) {
            $this->logAction('update_status', 'sar_inv_assets', $id, $data);
            $this->sendSuccess($result, $result['message']);
        } else {
            $this->sendError('Failed to update status', 400, $result['errors']);
        }
    }
    
    private function getHistory(int $id): void {
        if (!$id) {
            $this->sendError('Asset ID required', 400);
        }
        
        $limit = $this->getQueryInt('limit', 100);
        $history = $this->assetService->getAssetHistory($id, $limit);
        
        $this->sendSuccess(['history' => $history]);
    }
    
    private function getByProduct(): void {
        $productId = $this->getQueryInt('product_id');
        $warehouseId = $this->getQueryInt('warehouse_id') ?: null;
        $status = $this->getQuery('status');
        
        if (!$productId) {
            $this->sendError('Product ID required', 400);
        }
        
        $assets = $this->assetService->getAssetsByProduct($productId, $warehouseId, $status);
        $this->sendSuccess(['assets' => $assets]);
    }
    
    private function getByLocation(): void {
        $locationType = $this->getQuery('location_type');
        $locationId = $this->getQueryInt('location_id') ?: null;
        
        if (empty($locationType)) {
            $this->sendError('Location type required', 400);
        }
        
        $assets = $this->assetService->getAssetsByLocation($locationType, $locationId);
        $this->sendSuccess(['assets' => $assets]);
    }
    
    private function getByStatus(): void {
        $status = $this->getQuery('status');
        
        if (empty($status)) {
            $this->sendError('Status required', 400);
        }
        
        $assets = $this->assetService->getAssetsByStatus($status);
        $this->sendSuccess(['assets' => $assets]);
    }
    
    private function getAvailable(): void {
        $assets = $this->assetService->getAvailableAssets();
        $this->sendSuccess(['assets' => $assets]);
    }
    
    private function getExpiringWarranty(): void {
        $days = $this->getQueryInt('days', 30);
        $assets = $this->assetService->getExpiringWarrantyAssets($days);
        $this->sendSuccess(['assets' => $assets]);
    }
    
    private function validateSerial(): void {
        $serialNumber = $this->getQuery('serial_number');
        $excludeId = $this->getQueryInt('exclude_id') ?: null;
        
        if (empty($serialNumber)) {
            $this->sendError('Serial number required', 400);
        }
        
        $result = $this->assetService->validateSerialNumber($serialNumber, $excludeId);
        $this->sendSuccess($result);
    }
    
    private function validateBarcode(): void {
        $barcode = $this->getQuery('barcode');
        $excludeId = $this->getQueryInt('exclude_id') ?: null;
        
        if (empty($barcode)) {
            $this->sendError('Barcode required', 400);
        }
        
        $result = $this->assetService->validateBarcode($barcode, $excludeId);
        $this->sendSuccess($result);
    }
}

// Execute API
$api = new AssetsApi();
$api->handle();
?>
