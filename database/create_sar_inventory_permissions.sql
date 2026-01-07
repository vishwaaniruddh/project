-- SAR Inventory User Permissions Table
-- Stores user-specific permissions for the inventory module

CREATE TABLE IF NOT EXISTS sar_inv_user_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    permission VARCHAR(100) NOT NULL,
    is_granted TINYINT(1) DEFAULT 1,
    granted_by INT NULL,
    granted_at TIMESTAMP NULL,
    revoked_by INT NULL,
    revoked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_permission (user_id, permission),
    INDEX idx_user_id (user_id),
    INDEX idx_permission (permission),
    INDEX idx_is_granted (is_granted),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (revoked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Security Events Log Table
-- Stores security-related events for monitoring and auditing

CREATE TABLE IF NOT EXISTS sar_inv_security_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(50) NOT NULL,
    user_id INT NULL,
    username VARCHAR(100) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    request_uri VARCHAR(500) NULL,
    request_method VARCHAR(10) NULL,
    details JSON NULL,
    severity ENUM('info', 'warning', 'critical') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session Management Table
-- Stores active sessions for security monitoring

CREATE TABLE IF NOT EXISTS sar_inv_user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(128) NOT NULL,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    
    UNIQUE KEY unique_session (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at),
    INDEX idx_last_activity (last_activity),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed Login Attempts Table
-- Tracks failed login attempts for security monitoring

CREATE TABLE IF NOT EXISTS sar_inv_failed_logins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR(100) NULL,
    
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
