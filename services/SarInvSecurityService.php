<?php
/**
 * SAR Inventory Security Service
 * Handles secure session management, timeout policies, and suspicious activity detection
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/logger.php';

class SarInvSecurityService {
    
    // Session configuration
    const SESSION_TIMEOUT = 3600; // 1 hour in seconds
    const SESSION_IDLE_TIMEOUT = 1800; // 30 minutes idle timeout
    const MAX_FAILED_LOGINS = 5; // Max failed login attempts before lockout
    const LOCKOUT_DURATION = 900; // 15 minutes lockout
    const SESSION_REGENERATE_INTERVAL = 300; // Regenerate session ID every 5 minutes
    
    // Suspicious activity thresholds
    const RAPID_REQUEST_THRESHOLD = 100; // Max requests per minute
    const SUSPICIOUS_IP_THRESHOLD = 10; // Failed logins from same IP
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Initialize secure session
     */
    public function initializeSecureSession() {
        // Set secure session parameters
        $this->configureSessionSecurity();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Validate existing session
        if ($this->isSessionValid()) {
            $this->updateSessionActivity();
            $this->checkSessionRegeneration();
        }
    }
    
    /**
     * Configure session security settings
     */
    private function configureSessionSecurity() {
        // Set secure cookie parameters
        $cookieParams = [
            'lifetime' => 0, // Session cookie
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params($cookieParams);
        } else {
            session_set_cookie_params(
                $cookieParams['lifetime'],
                $cookieParams['path'] . '; SameSite=' . $cookieParams['samesite'],
                $cookieParams['domain'],
                $cookieParams['secure'],
                $cookieParams['httponly']
            );
        }
        
        // Use strict session mode
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_trans_sid', 0);
    }
    
    /**
     * Create new session for user
     */
    public function createSession($userId, $username, $role, $vendorId = null) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['vendor_id'] = $vendorId;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regeneration'] = time();
        $_SESSION['ip_address'] = $this->getClientIp();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['session_token'] = bin2hex(random_bytes(32));
        
        // Store session in database
        $this->storeSessionInDb($userId);
        
        // Log successful login
        $this->logSecurityEvent('login_success', $userId, [
            'username' => $username,
            'role' => $role
        ]);
        
        // Clear failed login attempts
        $this->clearFailedLogins($username, $this->getClientIp());
        
        return true;
    }
    
    /**
     * Validate current session
     */
    public function isSessionValid() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
            return false;
        }
        
        // Check session timeout
        if ($this->isSessionExpired()) {
            $this->logSecurityEvent('session_expired', $_SESSION['user_id'] ?? null);
            $this->destroySession();
            return false;
        }
        
        // Check idle timeout
        if ($this->isSessionIdle()) {
            $this->logSecurityEvent('session_idle_timeout', $_SESSION['user_id'] ?? null);
            $this->destroySession();
            return false;
        }
        
        // Validate session fingerprint (IP and User Agent)
        if (!$this->validateSessionFingerprint()) {
            $this->logSecurityEvent('session_hijack_attempt', $_SESSION['user_id'] ?? null, [
                'stored_ip' => $_SESSION['ip_address'] ?? 'unknown',
                'current_ip' => $this->getClientIp(),
                'stored_ua' => $_SESSION['user_agent'] ?? 'unknown',
                'current_ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ], 'critical');
            $this->destroySession();
            return false;
        }
        
        // Validate session exists in database
        if (!$this->validateSessionInDb()) {
            $this->logSecurityEvent('session_not_in_db', $_SESSION['user_id'] ?? null);
            $this->destroySession();
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if session has expired
     */
    private function isSessionExpired() {
        $loginTime = $_SESSION['login_time'] ?? 0;
        return (time() - $loginTime) > self::SESSION_TIMEOUT;
    }
    
    /**
     * Check if session is idle
     */
    private function isSessionIdle() {
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        return (time() - $lastActivity) > self::SESSION_IDLE_TIMEOUT;
    }
    
    /**
     * Validate session fingerprint
     */
    private function validateSessionFingerprint() {
        // Check IP address (allow for some flexibility with proxies)
        $storedIp = $_SESSION['ip_address'] ?? '';
        $currentIp = $this->getClientIp();
        
        // For strict security, uncomment the following:
        // if ($storedIp !== $currentIp) {
        //     return false;
        // }
        
        // Check user agent
        $storedUa = $_SESSION['user_agent'] ?? '';
        $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if ($storedUa !== $currentUa) {
            return false;
        }
        
        return true;
    }

    /**
     * Update session activity timestamp
     */
    public function updateSessionActivity() {
        $_SESSION['last_activity'] = time();
        $this->updateSessionInDb();
    }
    
    /**
     * Check if session ID should be regenerated
     */
    private function checkSessionRegeneration() {
        $lastRegeneration = $_SESSION['last_regeneration'] ?? 0;
        
        if ((time() - $lastRegeneration) > self::SESSION_REGENERATE_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
            $this->updateSessionInDb();
        }
    }
    
    /**
     * Store session in database
     */
    private function storeSessionInDb($userId) {
        try {
            // Check if table exists
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_user_sessions'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return false;
            }
            
            $sessionId = session_id();
            $expiresAt = date('Y-m-d H:i:s', time() + self::SESSION_TIMEOUT);
            
            $sql = "INSERT INTO sar_inv_user_sessions 
                    (session_id, user_id, ip_address, user_agent, expires_at, is_active) 
                    VALUES (?, ?, ?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE 
                    last_activity = NOW(), ip_address = ?, user_agent = ?, is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $sessionId,
                $userId,
                $this->getClientIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $expiresAt,
                $this->getClientIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            return true;
        } catch (PDOException $e) {
            Logger::error('Store session failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Update session in database
     */
    private function updateSessionInDb() {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_user_sessions'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return false;
            }
            
            $sessionId = session_id();
            
            $sql = "UPDATE sar_inv_user_sessions 
                    SET last_activity = NOW() 
                    WHERE session_id = ? AND is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sessionId]);
            
            return true;
        } catch (PDOException $e) {
            Logger::error('Update session failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Validate session exists in database
     */
    private function validateSessionInDb() {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_user_sessions'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return true; // Table doesn't exist, skip validation
            }
            
            $sessionId = session_id();
            $userId = $_SESSION['user_id'] ?? null;
            
            $sql = "SELECT COUNT(*) FROM sar_inv_user_sessions 
                    WHERE session_id = ? AND user_id = ? AND is_active = 1 
                    AND expires_at > NOW()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sessionId, $userId]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            Logger::error('Validate session failed', ['error' => $e->getMessage()]);
            return true; // On error, allow session to continue
        }
    }
    
    /**
     * Destroy current session
     */
    public function destroySession() {
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
        
        // Mark session as inactive in database
        $this->deactivateSessionInDb($sessionId);
        
        // Clear session data
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
        
        // Log logout
        if ($userId) {
            $this->logSecurityEvent('logout', $userId);
        }
    }
    
    /**
     * Deactivate session in database
     */
    private function deactivateSessionInDb($sessionId) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_user_sessions'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return false;
            }
            
            $sql = "UPDATE sar_inv_user_sessions SET is_active = 0 WHERE session_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sessionId]);
            
            return true;
        } catch (PDOException $e) {
            Logger::error('Deactivate session failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Record failed login attempt
     */
    public function recordFailedLogin($username, $reason = 'invalid_credentials') {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_failed_logins'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return false;
            }
            
            $sql = "INSERT INTO sar_inv_failed_logins 
                    (username, ip_address, user_agent, reason) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $username,
                $this->getClientIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $reason
            ]);
            
            // Log security event
            $this->logSecurityEvent('login_failed', null, [
                'username' => $username,
                'reason' => $reason
            ], 'warning');
            
            // Check for suspicious activity
            $this->checkSuspiciousLoginActivity($username);
            
            return true;
        } catch (PDOException $e) {
            Logger::error('Record failed login failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Check if account is locked
     */
    public function isAccountLocked($username) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_failed_logins'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return false;
            }
            
            $lockoutTime = date('Y-m-d H:i:s', time() - self::LOCKOUT_DURATION);
            
            $sql = "SELECT COUNT(*) FROM sar_inv_failed_logins 
                    WHERE (username = ? OR ip_address = ?) 
                    AND attempt_time > ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $this->getClientIp(), $lockoutTime]);
            
            $failedAttempts = $stmt->fetchColumn();
            
            return $failedAttempts >= self::MAX_FAILED_LOGINS;
        } catch (PDOException $e) {
            Logger::error('Check account locked failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get remaining lockout time
     */
    public function getRemainingLockoutTime($username) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_failed_logins'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return 0;
            }
            
            $sql = "SELECT MAX(attempt_time) FROM sar_inv_failed_logins 
                    WHERE (username = ? OR ip_address = ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $this->getClientIp()]);
            
            $lastAttempt = $stmt->fetchColumn();
            
            if (!$lastAttempt) {
                return 0;
            }
            
            $lockoutEnd = strtotime($lastAttempt) + self::LOCKOUT_DURATION;
            $remaining = $lockoutEnd - time();
            
            return max(0, $remaining);
        } catch (PDOException $e) {
            Logger::error('Get lockout time failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Clear failed login attempts
     */
    public function clearFailedLogins($username, $ipAddress) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_failed_logins'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return false;
            }
            
            $sql = "DELETE FROM sar_inv_failed_logins 
                    WHERE username = ? OR ip_address = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $ipAddress]);
            
            return true;
        } catch (PDOException $e) {
            Logger::error('Clear failed logins failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Check for suspicious login activity
     */
    private function checkSuspiciousLoginActivity($username) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_failed_logins'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return;
            }
            
            $ipAddress = $this->getClientIp();
            $checkTime = date('Y-m-d H:i:s', time() - 3600); // Last hour
            
            // Check failed logins from same IP
            $sql = "SELECT COUNT(*) FROM sar_inv_failed_logins 
                    WHERE ip_address = ? AND attempt_time > ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$ipAddress, $checkTime]);
            $ipFailures = $stmt->fetchColumn();
            
            if ($ipFailures >= self::SUSPICIOUS_IP_THRESHOLD) {
                $this->logSecurityEvent('suspicious_ip_activity', null, [
                    'ip_address' => $ipAddress,
                    'failed_attempts' => $ipFailures
                ], 'critical');
            }
            
            // Check failed logins for same username from different IPs
            $sql = "SELECT COUNT(DISTINCT ip_address) FROM sar_inv_failed_logins 
                    WHERE username = ? AND attempt_time > ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $checkTime]);
            $differentIps = $stmt->fetchColumn();
            
            if ($differentIps >= 5) {
                $this->logSecurityEvent('distributed_attack_attempt', null, [
                    'username' => $username,
                    'different_ips' => $differentIps
                ], 'critical');
            }
        } catch (PDOException $e) {
            Logger::error('Check suspicious activity failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log security event
     */
    public function logSecurityEvent($eventType, $userId = null, $details = [], $severity = 'info') {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_security_events'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                // Fall back to file logging
                Logger::logSecurityEvent($eventType, array_merge($details, [
                    'user_id' => $userId,
                    'severity' => $severity
                ]));
                return false;
            }
            
            $sql = "INSERT INTO sar_inv_security_events 
                    (event_type, user_id, username, ip_address, user_agent, request_uri, request_method, details, severity) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $eventType,
                $userId,
                $_SESSION['username'] ?? ($details['username'] ?? null),
                $this->getClientIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['REQUEST_URI'] ?? '',
                $_SERVER['REQUEST_METHOD'] ?? '',
                json_encode($details),
                $severity
            ]);
            
            // Also log to file for critical events
            if ($severity === 'critical') {
                Logger::critical("Security Event: $eventType", $details);
            }
            
            return true;
        } catch (PDOException $e) {
            Logger::error('Log security event failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get security events
     */
    public function getSecurityEvents($filters = [], $limit = 100, $offset = 0) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_security_events'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return [];
            }
            
            $sql = "SELECT * FROM sar_inv_security_events WHERE 1=1";
            $params = [];
            
            if (!empty($filters['event_type'])) {
                $sql .= " AND event_type = ?";
                $params[] = $filters['event_type'];
            }
            
            if (!empty($filters['user_id'])) {
                $sql .= " AND user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['ip_address'])) {
                $sql .= " AND ip_address = ?";
                $params[] = $filters['ip_address'];
            }
            
            if (!empty($filters['severity'])) {
                $sql .= " AND severity = ?";
                $params[] = $filters['severity'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Get security events failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get active sessions for user
     */
    public function getUserActiveSessions($userId) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_user_sessions'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return [];
            }
            
            $sql = "SELECT * FROM sar_inv_user_sessions 
                    WHERE user_id = ? AND is_active = 1 AND expires_at > NOW()
                    ORDER BY last_activity DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Get user sessions failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Terminate all sessions for user (except current)
     */
    public function terminateOtherSessions($userId) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_user_sessions'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return false;
            }
            
            $currentSessionId = session_id();
            
            $sql = "UPDATE sar_inv_user_sessions 
                    SET is_active = 0 
                    WHERE user_id = ? AND session_id != ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $currentSessionId]);
            
            $this->logSecurityEvent('sessions_terminated', $userId, [
                'terminated_count' => $stmt->rowCount()
            ]);
            
            return true;
        } catch (PDOException $e) {
            Logger::error('Terminate sessions failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Terminate specific session
     */
    public function terminateSession($sessionId, $userId) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_user_sessions'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return false;
            }
            
            $sql = "UPDATE sar_inv_user_sessions 
                    SET is_active = 0 
                    WHERE session_id = ? AND user_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sessionId, $userId]);
            
            $this->logSecurityEvent('session_terminated', $userId, [
                'terminated_session' => $sessionId
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::error('Terminate session failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions() {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_user_sessions'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return 0;
            }
            
            $sql = "DELETE FROM sar_inv_user_sessions 
                    WHERE expires_at < NOW() OR is_active = 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            Logger::error('Cleanup sessions failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Clean up old failed login records
     */
    public function cleanupOldFailedLogins($daysToKeep = 30) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_failed_logins'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return 0;
            }
            
            $sql = "DELETE FROM sar_inv_failed_logins 
                    WHERE attempt_time < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$daysToKeep]);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            Logger::error('Cleanup failed logins failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Clean up old security events
     */
    public function cleanupOldSecurityEvents($daysToKeep = 90) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_security_events'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return 0;
            }
            
            $sql = "DELETE FROM sar_inv_security_events 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$daysToKeep]);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            Logger::error('Cleanup security events failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Get security statistics
     */
    public function getSecurityStatistics($dateFrom = null, $dateTo = null) {
        $stats = [
            'total_logins' => 0,
            'failed_logins' => 0,
            'active_sessions' => 0,
            'security_events' => 0,
            'critical_events' => 0,
            'unique_ips' => 0
        ];
        
        try {
            // Get login statistics
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_security_events'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $sql = "SELECT 
                            COUNT(CASE WHEN event_type = 'login_success' THEN 1 END) as total_logins,
                            COUNT(CASE WHEN event_type = 'login_failed' THEN 1 END) as failed_logins,
                            COUNT(*) as security_events,
                            COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_events,
                            COUNT(DISTINCT ip_address) as unique_ips
                        FROM sar_inv_security_events
                        WHERE 1=1";
                $params = [];
                
                if ($dateFrom) {
                    $sql .= " AND DATE(created_at) >= ?";
                    $params[] = $dateFrom;
                }
                
                if ($dateTo) {
                    $sql .= " AND DATE(created_at) <= ?";
                    $params[] = $dateTo;
                }
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $stats = array_merge($stats, $result);
                }
            }
            
            // Get active sessions count
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'sar_inv_user_sessions'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $sql = "SELECT COUNT(*) FROM sar_inv_user_sessions 
                        WHERE is_active = 1 AND expires_at > NOW()";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $stats['active_sessions'] = $stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            Logger::error('Get security statistics failed', ['error' => $e->getMessage()]);
        }
        
        return $stats;
    }
    
    /**
     * Detect rapid requests (potential DoS)
     */
    public function detectRapidRequests() {
        $ipAddress = $this->getClientIp();
        $cacheKey = 'request_count_' . md5($ipAddress);
        
        // Use session to track request count (simple implementation)
        if (!isset($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = [
                'count' => 0,
                'start_time' => time()
            ];
        }
        
        $data = $_SESSION[$cacheKey];
        
        // Reset counter if more than 1 minute has passed
        if ((time() - $data['start_time']) > 60) {
            $_SESSION[$cacheKey] = [
                'count' => 1,
                'start_time' => time()
            ];
            return false;
        }
        
        // Increment counter
        $_SESSION[$cacheKey]['count']++;
        
        // Check threshold
        if ($_SESSION[$cacheKey]['count'] > self::RAPID_REQUEST_THRESHOLD) {
            $this->logSecurityEvent('rapid_requests_detected', $_SESSION['user_id'] ?? null, [
                'ip_address' => $ipAddress,
                'request_count' => $_SESSION[$cacheKey]['count']
            ], 'warning');
            return true;
        }
        
        return false;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Regenerate CSRF token
     */
    public function regenerateCsrfToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}
?>
