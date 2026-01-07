<?php
/**
 * SAR Inventory Reports API
 * Report generation with multiple output formats
 */
require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../controllers/SarInvApiController.php';
require_once '../../services/SarInvReportingService.php';

class ReportsApi extends SarInvApiController {
    private $reportingService;
    
    public function __construct() {
        parent::__construct();
        $this->reportingService = new SarInvReportingService();
    }
    
    public function handle(): void {
        $this->requireAuth();
        
        $method = $this->getMethod();
        
        if ($method !== 'GET' && $method !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $action = $this->getQuery('action');
        
        if ($action === 'available') {
            $this->getAvailableReports();
            return;
        }
        
        $reportType = $this->getQuery('type');
        
        if (empty($reportType)) {
            $this->sendError('Report type required', 400);
        }
        
        $this->generateReport($reportType);
    }
    
    private function getAvailableReports(): void {
        $reports = $this->reportingService->getAvailableReports();
        $this->sendSuccess(['reports' => $reports]);
    }
    
    private function generateReport(string $type): void {
        $format = $this->getQuery('format', 'csv');
        $download = $this->getQuery('download', 'false') === 'true';
        
        // Get filters from query params or POST body
        $filters = $this->getFilters();
        
        try {
            switch ($type) {
                case 'stock':
                    $report = $this->reportingService->generateStockReport($filters, $format);
                    break;
                case 'dispatch':
                    $report = $this->reportingService->generateDispatchReport($filters, $format);
                    break;
                case 'transfer':
                    $report = $this->reportingService->generateTransferReport($filters, $format);
                    break;
                case 'asset':
                    $report = $this->reportingService->generateAssetReport($filters, $format);
                    break;
                case 'repair':
                    $report = $this->reportingService->generateRepairReport($filters, $format);
                    break;
                case 'movement':
                    $report = $this->reportingService->generateMovementReport($filters, $format);
                    break;
                case 'warehouse_summary':
                    $report = $this->reportingService->generateWarehouseSummaryReport($format);
                    break;
                default:
                    $this->sendError('Unknown report type', 400);
                    return;
            }
            
            if ($download) {
                $this->downloadReport($report);
            } else {
                $this->sendSuccess([
                    'report' => [
                        'filename' => $report['filename'],
                        'format' => $report['format'],
                        'mime_type' => $report['mime_type'],
                        'content' => base64_encode($report['content'])
                    ]
                ]);
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to generate report: ' . $e->getMessage(), 500);
        }
    }
    
    private function getFilters(): array {
        $filters = [];
        
        // Common filters
        $filterKeys = [
            'warehouse_id', 'product_id', 'category_id', 'status',
            'source_warehouse_id', 'destination_warehouse_id',
            'vendor_id', 'location_type', 'transaction_type',
            'date_from', 'date_to'
        ];
        
        foreach ($filterKeys as $key) {
            $value = $this->getQuery($key);
            if ($value !== null && $value !== '') {
                // Convert numeric IDs
                if (strpos($key, '_id') !== false) {
                    $filters[$key] = intval($value);
                } else {
                    $filters[$key] = $value;
                }
            }
        }
        
        // Also check POST body for filters
        $postData = $this->getJsonBody();
        if (!empty($postData['filters'])) {
            $filters = array_merge($filters, $postData['filters']);
        }
        
        return $filters;
    }
    
    private function downloadReport(array $report): void {
        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: ' . $report['mime_type']);
        header('Content-Disposition: attachment; filename="' . $report['filename'] . '"');
        header('Content-Length: ' . strlen($report['content']));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        echo $report['content'];
        exit;
    }
}

// Execute API
$api = new ReportsApi();
$api->handle();
?>
