<?php
require_once __DIR__ . '/../models/SarInvStock.php';
require_once __DIR__ . '/../models/SarInvAsset.php';
require_once __DIR__ . '/../models/SarInvRepair.php';
require_once __DIR__ . '/../models/SarInvDispatch.php';
require_once __DIR__ . '/../models/SarInvTransfer.php';
require_once __DIR__ . '/../models/SarInvMaterialRequest.php';

/**
 * SAR Inventory Alert Service
 * Inventory alerts with configurable thresholds and notification delivery
 */
class SarInvAlertService {
    private $stockModel;
    private $assetModel;
    private $repairModel;
    private $dispatchModel;
    private $transferModel;
    private $materialRequestModel;
    
    // Default alert thresholds
    private $thresholds = [
        'low_stock_warning' => 0.25,      // 25% of minimum level
        'low_stock_critical' => 0,         // At or below 0
        'repair_overdue_days' => 7,        // Days before repair is overdue
        'warranty_expiry_days' => 30,      // Days before warranty expires
        'dispatch_pending_days' => 3,      // Days before pending dispatch alert
        'transfer_pending_days' => 3,      // Days before pending transfer alert
        'request_pending_days' => 2        // Days before pending request alert
    ];
    
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_CRITICAL = 'critical';
    
    const TYPE_LOW_STOCK = 'low_stock';
    const TYPE_OUT_OF_STOCK = 'out_of_stock';
    const TYPE_REPAIR_OVERDUE = 'repair_overdue';
    const TYPE_WARRANTY_EXPIRING = 'warranty_expiring';
    const TYPE_DISPATCH_PENDING = 'dispatch_pending';
    const TYPE_TRANSFER_PENDING = 'transfer_pending';
    const TYPE_REQUEST_PENDING = 'request_pending';
    
    public function __construct() {
        $this->stockModel = new SarInvStock();
        $this->assetModel = new SarInvAsset();
        $this->repairModel = new SarInvRepair();
        $this->dispatchModel = new SarInvDispatch();
        $this->transferModel = new SarInvTransfer();
        $this->materialRequestModel = new SarInvMaterialRequest();
    }
    
    /**
     * Set custom thresholds
     * @param array $thresholds Custom thresholds
     */
    public function setThresholds(array $thresholds): void {
        $this->thresholds = array_merge($this->thresholds, $thresholds);
    }
    
    /**
     * Get current thresholds
     * @return array Current thresholds
     */
    public function getThresholds(): array {
        return $this->thresholds;
    }
    
    /**
     * Get all alerts
     * @return array All active alerts
     */
    public function getAllAlerts(): array {
        $alerts = [];
        
        $alerts = array_merge($alerts, $this->getLowStockAlerts());
        $alerts = array_merge($alerts, $this->getRepairAlerts());
        $alerts = array_merge($alerts, $this->getWarrantyAlerts());
        $alerts = array_merge($alerts, $this->getPendingDispatchAlerts());
        $alerts = array_merge($alerts, $this->getPendingTransferAlerts());
        $alerts = array_merge($alerts, $this->getPendingRequestAlerts());
        
        // Sort by severity (critical first) then by created_at
        usort($alerts, function($a, $b) {
            $severityOrder = [self::SEVERITY_CRITICAL => 0, self::SEVERITY_WARNING => 1, self::SEVERITY_INFO => 2];
            $severityCompare = ($severityOrder[$a['severity']] ?? 3) - ($severityOrder[$b['severity']] ?? 3);
            if ($severityCompare !== 0) return $severityCompare;
            return strtotime($b['created_at'] ?? 'now') - strtotime($a['created_at'] ?? 'now');
        });
        
        return $alerts;
    }
    
    /**
     * Get alerts by severity
     * @param string $severity Severity level
     * @return array Filtered alerts
     */
    public function getAlertsBySeverity(string $severity): array {
        $allAlerts = $this->getAllAlerts();
        return array_filter($allAlerts, fn($alert) => $alert['severity'] === $severity);
    }
    
    /**
     * Get alerts by type
     * @param string $type Alert type
     * @return array Filtered alerts
     */
    public function getAlertsByType(string $type): array {
        $allAlerts = $this->getAllAlerts();
        return array_filter($allAlerts, fn($alert) => $alert['type'] === $type);
    }
    
    /**
     * Get critical alerts count
     * @return int Count of critical alerts
     */
    public function getCriticalAlertsCount(): int {
        return count($this->getAlertsBySeverity(self::SEVERITY_CRITICAL));
    }
    
    /**
     * Get alerts summary
     * @return array Summary of alerts by type and severity
     */
    public function getAlertsSummary(): array {
        $alerts = $this->getAllAlerts();
        
        $summary = [
            'total' => count($alerts),
            'by_severity' => [
                self::SEVERITY_CRITICAL => 0,
                self::SEVERITY_WARNING => 0,
                self::SEVERITY_INFO => 0
            ],
            'by_type' => []
        ];
        
        foreach ($alerts as $alert) {
            $summary['by_severity'][$alert['severity']]++;
            
            if (!isset($summary['by_type'][$alert['type']])) {
                $summary['by_type'][$alert['type']] = 0;
            }
            $summary['by_type'][$alert['type']]++;
        }
        
        return $summary;
    }

    /**
     * Get low stock alerts
     * @return array Low stock alerts
     */
    public function getLowStockAlerts(): array {
        $lowStockItems = $this->stockModel->getLowStockItems();
        $alerts = [];
        
        foreach ($lowStockItems as $item) {
            $available = floatval($item['available_quantity']);
            $minLevel = floatval($item['minimum_stock_level']);
            
            // Determine severity
            if ($available <= 0) {
                $severity = self::SEVERITY_CRITICAL;
                $type = self::TYPE_OUT_OF_STOCK;
                $message = "Out of stock: {$item['product_name']} in {$item['warehouse_name']}";
            } elseif ($available <= $minLevel * $this->thresholds['low_stock_warning']) {
                $severity = self::SEVERITY_CRITICAL;
                $type = self::TYPE_LOW_STOCK;
                $message = "Critical low stock: {$item['product_name']} in {$item['warehouse_name']} ({$available} remaining)";
            } else {
                $severity = self::SEVERITY_WARNING;
                $type = self::TYPE_LOW_STOCK;
                $message = "Low stock: {$item['product_name']} in {$item['warehouse_name']} ({$available} remaining, min: {$minLevel})";
            }
            
            $alerts[] = [
                'type' => $type,
                'severity' => $severity,
                'message' => $message,
                'data' => [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'],
                    'warehouse_id' => $item['warehouse_id'] ?? null,
                    'warehouse_name' => $item['warehouse_name'],
                    'available_quantity' => $available,
                    'minimum_level' => $minLevel
                ],
                'action_url' => "/admin/sar-inventory/stock-entry/create.php?product_id={$item['product_id']}",
                'action_label' => 'Add Stock',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Get repair alerts (overdue repairs)
     * @return array Repair alerts
     */
    public function getRepairAlerts(): array {
        $overdueRepairs = $this->repairModel->getOverdueRepairs($this->thresholds['repair_overdue_days']);
        $alerts = [];
        
        foreach ($overdueRepairs as $repair) {
            $daysPending = intval($repair['days_pending']);
            
            $severity = $daysPending > ($this->thresholds['repair_overdue_days'] * 2) 
                ? self::SEVERITY_CRITICAL 
                : self::SEVERITY_WARNING;
            
            $alerts[] = [
                'type' => self::TYPE_REPAIR_OVERDUE,
                'severity' => $severity,
                'message' => "Overdue repair: {$repair['repair_number']} ({$daysPending} days pending)",
                'data' => [
                    'repair_id' => $repair['id'],
                    'repair_number' => $repair['repair_number'],
                    'asset_serial' => $repair['serial_number'],
                    'product_name' => $repair['product_name'],
                    'days_pending' => $daysPending,
                    'status' => $repair['status']
                ],
                'action_url' => "/admin/sar-inventory/repairs/view.php?id={$repair['id']}",
                'action_label' => 'View Repair',
                'created_at' => $repair['created_at']
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Get warranty expiry alerts
     * @return array Warranty alerts
     */
    public function getWarrantyAlerts(): array {
        $expiringAssets = $this->assetModel->getExpiringWarranty($this->thresholds['warranty_expiry_days']);
        $alerts = [];
        
        foreach ($expiringAssets as $asset) {
            $expiryDate = strtotime($asset['warranty_expiry']);
            $daysUntilExpiry = ceil(($expiryDate - time()) / 86400);
            
            $severity = $daysUntilExpiry <= 7 ? self::SEVERITY_WARNING : self::SEVERITY_INFO;
            
            $alerts[] = [
                'type' => self::TYPE_WARRANTY_EXPIRING,
                'severity' => $severity,
                'message' => "Warranty expiring: {$asset['serial_number']} ({$asset['product_name']}) in {$daysUntilExpiry} days",
                'data' => [
                    'asset_id' => $asset['id'],
                    'serial_number' => $asset['serial_number'],
                    'product_name' => $asset['product_name'],
                    'warranty_expiry' => $asset['warranty_expiry'],
                    'days_until_expiry' => $daysUntilExpiry
                ],
                'action_url' => "/admin/sar-inventory/assets/view.php?id={$asset['id']}",
                'action_label' => 'View Asset',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Get pending dispatch alerts
     * @return array Dispatch alerts
     */
    public function getPendingDispatchAlerts(): array {
        $pendingDispatches = $this->dispatchModel->getByStatus(SarInvDispatch::STATUS_PENDING);
        $alerts = [];
        
        foreach ($pendingDispatches as $dispatch) {
            $createdAt = strtotime($dispatch['created_at']);
            $daysPending = ceil((time() - $createdAt) / 86400);
            
            if ($daysPending >= $this->thresholds['dispatch_pending_days']) {
                $severity = $daysPending > ($this->thresholds['dispatch_pending_days'] * 2) 
                    ? self::SEVERITY_WARNING 
                    : self::SEVERITY_INFO;
                
                $alerts[] = [
                    'type' => self::TYPE_DISPATCH_PENDING,
                    'severity' => $severity,
                    'message' => "Pending dispatch: {$dispatch['dispatch_number']} awaiting approval ({$daysPending} days)",
                    'data' => [
                        'dispatch_id' => $dispatch['id'],
                        'dispatch_number' => $dispatch['dispatch_number'],
                        'source_warehouse' => $dispatch['source_warehouse_name'] ?? '',
                        'days_pending' => $daysPending
                    ],
                    'action_url' => "/admin/sar-inventory/dispatches/view.php?id={$dispatch['id']}",
                    'action_label' => 'Review Dispatch',
                    'created_at' => $dispatch['created_at']
                ];
            }
        }
        
        return $alerts;
    }

    /**
     * Get pending transfer alerts
     * @return array Transfer alerts
     */
    public function getPendingTransferAlerts(): array {
        $pendingTransfers = $this->transferModel->getByStatus(SarInvTransfer::STATUS_PENDING);
        $alerts = [];
        
        foreach ($pendingTransfers as $transfer) {
            $createdAt = strtotime($transfer['created_at']);
            $daysPending = ceil((time() - $createdAt) / 86400);
            
            if ($daysPending >= $this->thresholds['transfer_pending_days']) {
                $severity = $daysPending > ($this->thresholds['transfer_pending_days'] * 2) 
                    ? self::SEVERITY_WARNING 
                    : self::SEVERITY_INFO;
                
                $alerts[] = [
                    'type' => self::TYPE_TRANSFER_PENDING,
                    'severity' => $severity,
                    'message' => "Pending transfer: {$transfer['transfer_number']} awaiting approval ({$daysPending} days)",
                    'data' => [
                        'transfer_id' => $transfer['id'],
                        'transfer_number' => $transfer['transfer_number'],
                        'source_warehouse' => $transfer['source_warehouse_name'] ?? '',
                        'destination_warehouse' => $transfer['destination_warehouse_name'] ?? '',
                        'days_pending' => $daysPending
                    ],
                    'action_url' => "/admin/sar-inventory/transfers/view.php?id={$transfer['id']}",
                    'action_label' => 'Review Transfer',
                    'created_at' => $transfer['created_at']
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Get pending material request alerts
     * @return array Request alerts
     */
    public function getPendingRequestAlerts(): array {
        $pendingRequests = $this->materialRequestModel->getByStatus(SarInvMaterialRequest::STATUS_PENDING);
        $alerts = [];
        
        foreach ($pendingRequests as $request) {
            $createdAt = strtotime($request['created_at']);
            $daysPending = ceil((time() - $createdAt) / 86400);
            
            if ($daysPending >= $this->thresholds['request_pending_days']) {
                $severity = $daysPending > ($this->thresholds['request_pending_days'] * 2) 
                    ? self::SEVERITY_WARNING 
                    : self::SEVERITY_INFO;
                
                $alerts[] = [
                    'type' => self::TYPE_REQUEST_PENDING,
                    'severity' => $severity,
                    'message' => "Pending request: {$request['request_number']} awaiting approval ({$daysPending} days)",
                    'data' => [
                        'request_id' => $request['id'],
                        'request_number' => $request['request_number'],
                        'material_name' => $request['material_name'] ?? $request['product_name'] ?? '',
                        'quantity' => $request['quantity'],
                        'requester' => $request['requester_name'] ?? '',
                        'days_pending' => $daysPending
                    ],
                    'action_url' => "/admin/sar-inventory/materials/requests/view.php?id={$request['id']}",
                    'action_label' => 'Review Request',
                    'created_at' => $request['created_at']
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Send alert notification (placeholder for email/SMS integration)
     * @param array $alert Alert data
     * @param string $channel Notification channel (email, sms, etc.)
     * @param array $recipients Recipients
     * @return array Result
     */
    public function sendNotification(array $alert, string $channel, array $recipients): array {
        // This is a placeholder for actual notification implementation
        // In production, integrate with email service, SMS gateway, etc.
        
        $notification = [
            'alert' => $alert,
            'channel' => $channel,
            'recipients' => $recipients,
            'sent_at' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        ];
        
        switch ($channel) {
            case 'email':
                // Integrate with email service
                $notification['status'] = 'queued';
                $notification['message'] = 'Email notification queued for delivery';
                break;
                
            case 'sms':
                // Integrate with SMS gateway
                $notification['status'] = 'queued';
                $notification['message'] = 'SMS notification queued for delivery';
                break;
                
            case 'in_app':
                // Store in database for in-app notifications
                $notification['status'] = 'delivered';
                $notification['message'] = 'In-app notification created';
                break;
                
            default:
                $notification['status'] = 'failed';
                $notification['message'] = 'Unknown notification channel';
        }
        
        return $notification;
    }
    
    /**
     * Get alert types
     * @return array Available alert types
     */
    public static function getAlertTypes(): array {
        return [
            self::TYPE_LOW_STOCK => 'Low Stock',
            self::TYPE_OUT_OF_STOCK => 'Out of Stock',
            self::TYPE_REPAIR_OVERDUE => 'Repair Overdue',
            self::TYPE_WARRANTY_EXPIRING => 'Warranty Expiring',
            self::TYPE_DISPATCH_PENDING => 'Dispatch Pending',
            self::TYPE_TRANSFER_PENDING => 'Transfer Pending',
            self::TYPE_REQUEST_PENDING => 'Request Pending'
        ];
    }
    
    /**
     * Get severity levels
     * @return array Available severity levels
     */
    public static function getSeverityLevels(): array {
        return [
            self::SEVERITY_CRITICAL => 'Critical',
            self::SEVERITY_WARNING => 'Warning',
            self::SEVERITY_INFO => 'Info'
        ];
    }
}
?>
