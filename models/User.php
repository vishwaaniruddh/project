<?php
require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function create($data) {
        // Hash password before storing
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['plain_password'] = $data['password']; // Store plain password for testing
            unset($data['password']);
        }
        
        return parent::create($data);
    }
    
    public function update($id, $data) {
        // Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['plain_password'] = $data['password']; // Store plain password for testing
            unset($data['password']);
        } else {
            // Remove password field if empty (don't update password)
            unset($data['password']);
        }
        
        return parent::update($id, $data);
    }
    
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function findByPhone($phone) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE phone = ?");
        $stmt->execute([$phone]);
        return $stmt->fetch();
    }
    
    public function findByEmailOrPhone($emailOrPhone) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? OR phone = ?");
        $stmt->execute([$emailOrPhone, $emailOrPhone]);
        return $stmt->fetch();
    }
    
    public function updateToken($userId, $token) {
        return $this->update($userId, ['jwt_token' => $token]);
    }
    
    public function getAllWithPagination($page = 1, $limit = 20, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        
        if (!empty($search)) {
            $whereClause = "WHERE u.username LIKE ? OR u.email LIKE ? OR u.role LIKE ? OR v.name LIKE ?";
            $searchTerm = "%$search%";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$this->table} u LEFT JOIN vendors v ON u.vendor_id = v.id $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated results with vendor information
        $sql = "SELECT u.*, v.name as vendor_name 
                FROM {$this->table} u 
                LEFT JOIN vendors v ON u.vendor_id = v.id 
                $whereClause 
                ORDER BY u.created_at DESC 
                LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    public function getVendors() {
        return $this->findAll(['role' => 'vendor', 'status' => 'active']);
    }
    
    public function validateUserData($data, $isUpdate = false, $userId = null) {
        $errors = [];
        
        // Username validation
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif (strlen($data['username']) > 50) {
            $errors['username'] = 'Username must not exceed 50 characters';
        } else {
            // Check if username already exists
            $existingUser = $this->findByUsername($data['username']);
            if ($existingUser && (!$isUpdate || $existingUser['id'] != $userId)) {
                $errors['username'] = 'Username already exists';
            }
        }
        
        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } else {
            // Check if email already exists
            $existingUser = $this->findByEmail($data['email']);
            if ($existingUser && (!$isUpdate || $existingUser['id'] != $userId)) {
                $errors['email'] = 'Email already exists';
            }
        }
        
        // Phone validation
        if (empty($data['phone'])) {
            $errors['phone'] = 'Phone number is required';
        } elseif (!preg_match('/^[\+]?[1-9][\d]{0,15}$/', $data['phone'])) {
            $errors['phone'] = 'Invalid phone number format';
        } else {
            // Check if phone already exists
            $existingUser = $this->findByPhone($data['phone']);
            if ($existingUser && (!$isUpdate || $existingUser['id'] != $userId)) {
                $errors['phone'] = 'Phone number already exists';
            }
        }
        
        // Password validation (only for new users or when password is provided)
        if (!$isUpdate || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }
        }
        
        // Role validation
        if (empty($data['role'])) {
            $errors['role'] = 'Role is required';
        } elseif (!in_array($data['role'], ['admin', 'vendor'])) {
            $errors['role'] = 'Invalid role selected';
        }
        
        // Status validation
        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
            $errors['status'] = 'Invalid status selected';
        }
        
        // Vendor validation for vendor role
        if ($data['role'] === 'vendor') {
            if (empty($data['vendor_id'])) {
                $errors['vendor_id'] = 'Please select a vendor when role is vendor';
            } else {
                // Check if vendor exists and is active
                $stmt = $this->db->prepare("SELECT id FROM vendors WHERE id = ? AND status = 'active'");
                $stmt->execute([$data['vendor_id']]);
                if (!$stmt->fetch()) {
                    $errors['vendor_id'] = 'Selected vendor is not valid or inactive';
                }
            }
        }
        
        return $errors;
    }
    
    public function findWithVendor($id) {
        $stmt = $this->db->prepare("
            SELECT u.*, v.name as vendor_name 
            FROM {$this->table} u 
            LEFT JOIN vendors v ON u.vendor_id = v.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getUserStats() {
        $stats = [];
        
        // Total users
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = $stmt->fetchColumn();
        
        // Active users
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'active'");
        $stats['active'] = $stmt->fetchColumn();
        
        // Users by role
        $stmt = $this->db->query("SELECT role, COUNT(*) as count FROM {$this->table} GROUP BY role");
        $roleStats = $stmt->fetchAll();
        foreach ($roleStats as $role) {
            $stats['by_role'][$role['role']] = $role['count'];
        }
        
        // Recent users (last 30 days)
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['recent'] = $stmt->fetchColumn();
        
        return $stats;
    }
}