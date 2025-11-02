<?php
// Application constants
define('APP_NAME', 'Site Installation Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/project');

// File upload settings
define('UPLOAD_DIR', 'assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Pagination settings
define('ITEMS_PER_PAGE', 20);

// Site status constants
define('SITE_STATUS_PENDING', 'pending');
define('SITE_STATUS_ASSIGNED', 'assigned');
define('SITE_STATUS_SURVEYED', 'surveyed');
define('SITE_STATUS_IN_PROGRESS', 'in_progress');
define('SITE_STATUS_COMPLETED', 'completed');

// Material request status constants
define('REQUEST_STATUS_PENDING', 'pending');
define('REQUEST_STATUS_APPROVED', 'approved');
define('REQUEST_STATUS_DISPATCHED', 'dispatched');

// Dispatch acknowledgment status
define('ACK_STATUS_PENDING', 'pending');
define('ACK_STATUS_RECEIVED', 'received');
?>