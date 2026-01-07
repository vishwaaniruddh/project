-- Add description column to boq_master table if not present
-- Note: MySQL doesn't support IF NOT EXISTS for ALTER TABLE ADD COLUMN
-- This will fail silently if column already exists (handled in PHP script)
ALTER TABLE boq_master 
ADD COLUMN description TEXT NULL AFTER boq_name;

-- Create boq_master_items junction table
CREATE TABLE IF NOT EXISTS boq_master_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    boq_master_id INT NOT NULL,
    boq_item_id INT NOT NULL,
    default_quantity DECIMAL(10,2) DEFAULT 1.00,
    remarks TEXT,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    FOREIGN KEY (boq_master_id) REFERENCES boq_master(boq_id) ON DELETE CASCADE,
    FOREIGN KEY (boq_item_id) REFERENCES boq_items(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_boq_item (boq_master_id, boq_item_id),
    INDEX idx_boq_master (boq_master_id),
    INDEX idx_boq_item (boq_item_id),
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
