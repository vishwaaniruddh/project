<?php
/**
 * SAR Inventory API Base Controller
 * Provides common functionality for all SAR Inventory API endpoints
 */
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/auth.php';

class SarInvApiController extends BaseController {
    
    protected $userId;
    protected $userRole;
    
    public function __construct() {
        parent::__construct();
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Check if user is authenticated
     * @return bool
     */
    protected function isAuthenticated(): bool {
        if (!Auth::isLoggedIn()) {
            return false;
        }
        
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->userRole = $_SESSION['role'] ?? null;
        
        return $this->userId !== null;
    }
    
    /**
     * Require authentication - sends error response if not authenticated
     */
    protected function requireAuth(): void {
        if (!$this->isAuthenticated()) {
            $this->sendError('Unauthorized', 401);
        }
    }
    
    /**
     * Check if user has required permission
     * @param string $permission Permission to check
     * @return bool
     */
    protected function hasPermission(string $permission): bool {
        // Admin has all permissions
        if ($this->userRole === 'admin') {
            return true;
        }
        
        // Check specific permissions based on role
        $rolePermissions = [
            'manager' => ['view', 'create', 'edit', 'approve'],
            'operator' => ['view', 'create'],
            'viewer' => ['view']
        ];
        
        $permissions = $rolePermissions[$this->userRole] ?? [];
        return in_array($permission, $permissions);
    }
    
    /**
     * Require specific permission
     * @param string $permission Permission required
     */
    protected function requirePermission(string $permission): void {
        if (!$this->hasPermission($permission)) {
            $this->sendError('Permission denied', 403);
        }
    }

    /**
     * Send success response
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     */
    protected function sendSuccess($data = null, string $message = 'Success', int $statusCode = 200): void {
        http_response_code($statusCode);
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Send error response
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Additional error details
     */
    protected function sendError(string $message, int $statusCode = 400, array $errors = []): void {
        http_response_code($statusCode);
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Send paginated response
     * @param array $data Data array
     * @param int $page Current page
     * @param int $perPage Items per page
     * @param int $total Total items
     */
    protected function sendPaginated(array $data, int $page, int $perPage, int $total): void {
        $totalPages = ceil($total / $perPage);
        
        $this->sendSuccess([
            'items' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]);
    }
    
    /**
     * Get request method
     * @return string
     */
    protected function getMethod(): string {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Get JSON body from request
     * @return array
     */
    protected function getJsonBody(): array {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        return is_array($data) ? $data : [];
    }
    
    /**
     * Get query parameter
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function getQuery(string $key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Get integer query parameter
     * @param string $key Parameter key
     * @param int $default Default value
     * @return int
     */
    protected function getQueryInt(string $key, int $default = 0): int {
        return isset($_GET[$key]) ? intval($_GET[$key]) : $default;
    }
    
    /**
     * Get POST parameter
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function getPost(string $key, $default = null) {
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get pagination parameters
     * @return array [page, perPage]
     */
    protected function getPagination(): array {
        $page = max(1, $this->getQueryInt('page', 1));
        $perPage = min(100, max(1, $this->getQueryInt('per_page', 20)));
        return [$page, $perPage];
    }
    
    /**
     * Validate required fields
     * @param array $data Data to validate
     * @param array $required Required field names
     * @return array Validation errors
     */
    protected function validateRequired(array $data, array $required): array {
        $errors = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        return $errors;
    }
    
    /**
     * Log API action for audit
     * @param string $action Action performed
     * @param string $entity Entity type
     * @param int|null $entityId Entity ID
     * @param array $details Additional details
     */
    protected function logAction(string $action, string $entity, ?int $entityId = null, array $details = []): void {
        try {
            require_once __DIR__ . '/../models/SarInvAuditLog.php';
            $auditLog = new SarInvAuditLog();
            $auditLog->createLog(
                $entity,
                $entityId,
                $action,
                null,
                $details,
                $this->userId,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
        } catch (Exception $e) {
            error_log("Failed to log API action: " . $e->getMessage());
        }
    }
    
    /**
     * Handle common CRUD operations
     * @param string $method HTTP method
     * @param object $service Service instance
     * @param int|null $id Resource ID
     */
    protected function handleCrud(string $method, object $service, ?int $id = null): void {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->handleShow($service, $id);
                } else {
                    $this->handleIndex($service);
                }
                break;
            case 'POST':
                $this->handleStore($service);
                break;
            case 'PUT':
                if (!$id) {
                    $this->sendError('Resource ID required', 400);
                }
                $this->handleUpdate($service, $id);
                break;
            case 'DELETE':
                if (!$id) {
                    $this->sendError('Resource ID required', 400);
                }
                $this->handleDelete($service, $id);
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Default index handler - override in child classes
     */
    protected function handleIndex(object $service): void {
        $this->sendError('Not implemented', 501);
    }
    
    /**
     * Default show handler - override in child classes
     */
    protected function handleShow(object $service, int $id): void {
        $this->sendError('Not implemented', 501);
    }
    
    /**
     * Default store handler - override in child classes
     */
    protected function handleStore(object $service): void {
        $this->sendError('Not implemented', 501);
    }
    
    /**
     * Default update handler - override in child classes
     */
    protected function handleUpdate(object $service, int $id): void {
        $this->sendError('Not implemented', 501);
    }
    
    /**
     * Default delete handler - override in child classes
     */
    protected function handleDelete(object $service, int $id): void {
        $this->sendError('Not implemented', 501);
    }
}
?>
