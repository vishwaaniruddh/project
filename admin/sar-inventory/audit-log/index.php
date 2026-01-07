<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../models/SarInvAuditLog.php';
require_once '../../../models/User.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Audit Log';
$currentUser = Auth::getCurrentUser();

$auditLog = new SarInvAuditLog();
$userModel = new User();

// Get filter parameters
$tableName = $_GET['table_name'] ?? '';
$action = $_GET['action'] ?? '';
$userId = $_GET['user_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;

// Build filters array
$filters = [];
if ($tableName) $filters['table_name'] = $tableName;
if ($action) $filters['action'] = $action;
if ($userId) $filters['user_id'] = $userId;
if ($dateFrom) $filters['date_from'] = $dateFrom;
if ($dateTo) $filters['date_to'] = $dateTo;
if ($keyword) $filters['keyword'] = $keyword;

// Get paginated logs
$offset = ($page - 1) * $perPage;
$logs = $auditLog->search($filters, $perPage, $offset);
$totalLogs = $auditLog->countLogs($filters);
$totalPages = ceil($totalLogs / $perPage);

// Get statistics
$statistics = $auditLog->getStatistics($dateFrom ?: null, $dateTo ?: null);

// Get activity by table
$activityByTable = $auditLog->getActivityByTable($dateFrom ?: null, $dateTo ?: null);

// Get dropdown data
$availableTables = $auditLog->getAvailableTables();
$users = $userModel->findAll();

// Action options
$actionOptions = [
    SarInvAuditLog::ACTION_CREATE => 'Create',
    SarInvAuditLog::ACTION_UPDATE => 'Update',
    SarInvAuditLog::ACTION_DELETE => 'Delete'
];

// Action badge colors
$actionColors = [
    'create' => 'bg-green-100 text-green-800',
    'update' => 'bg-blue-100 text-blue-800',
    'delete' => 'bg-red-100 text-red-800'
];

// Build query string for pagination
$queryParams = array_filter([
    'table_name' => $tableName,
    'action' => $action,
    'user_id' => $userId,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'keyword' => $keyword
]);
$queryString = http_build_query($queryParams);

// Format table name for display
function formatTableName($name) {
    return ucwords(str_replace(['sar_inv_', '_'], ['', ' '], $name));
}

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Audit Log</h1>
            <p class="text-gray-600">Track all changes and actions in the inventory system</p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Logs</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($statistics['total_logs'] ?? 0); ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Creates</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($statistics['create_count'] ?? 0); ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Updates</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($statistics['update_count'] ?? 0); ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Deletes</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($statistics['delete_count'] ?? 0); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activity by Table -->
<?php if (!empty($activityByTable)): ?>
<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold">Activity by Table</h3>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach (array_slice($activityByTable, 0, 6) as $activity): ?>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 truncate" title="<?php echo htmlspecialchars($activity['table_name']); ?>">
                    <?php echo formatTableName($activity['table_name']); ?>
                </p>
                <p class="text-xl font-bold text-gray-900"><?php echo number_format($activity['total_actions']); ?></p>
                <div class="flex justify-center gap-2 mt-1 text-xs">
                    <span class="text-green-600">+<?php echo $activity['creates']; ?></span>
                    <span class="text-blue-600">~<?php echo $activity['updates']; ?></span>
                    <span class="text-red-600">-<?php echo $activity['deletes']; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="form-group mb-0">
                    <label class="form-label">Table</label>
                    <select name="table_name" class="form-select">
                        <option value="">All Tables</option>
                        <?php foreach ($availableTables as $table): ?>
                        <option value="<?php echo htmlspecialchars($table); ?>" <?php echo $tableName === $table ? 'selected' : ''; ?>>
                            <?php echo formatTableName($table); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All Actions</option>
                        <?php foreach ($actionOptions as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $action === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-select">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $userId == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="form-input">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" class="form-input">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Search</label>
                    <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" 
                           class="form-input" placeholder="Search in values...">
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <a href="<?php echo url('/admin/sar-inventory/audit-log/'); ?>" class="btn btn-secondary">Clear Filters</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Results Info -->
<div class="flex items-center justify-between mb-4">
    <div class="text-sm text-gray-600">
        Showing <?php echo number_format($offset + 1); ?> 
        to <?php echo number_format(min($offset + $perPage, $totalLogs)); ?> 
        of <?php echo number_format($totalLogs); ?> records
    </div>
</div>

<!-- Audit Log Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>Date/Time</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Action</th>
                    <th>User</th>
                    <th>IP Address</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="8" class="text-center py-8 text-gray-500">
                        No audit logs found matching your criteria.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $index => $log): 
                    $actionColor = $actionColors[$log['action']] ?? 'bg-gray-100 text-gray-800';
                ?>
                <tr>
                    <td class="text-center text-gray-500"><?php echo $offset + $index + 1; ?></td>
                    <td class="whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <?php echo date('M j, Y', strtotime($log['created_at'])); ?>
                        </div>
                        <div class="text-xs text-gray-500">
                            <?php echo date('h:i:s A', strtotime($log['created_at'])); ?>
                        </div>
                    </td>
                    <td>
                        <span class="text-sm"><?php echo formatTableName($log['table_name']); ?></span>
                    </td>
                    <td class="font-mono text-sm">#<?php echo $log['record_id']; ?></td>
                    <td>
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $actionColor; ?>">
                            <?php echo ucfirst($log['action']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                    <td class="font-mono text-xs text-gray-500"><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                    <td>
                        <button type="button" onclick="showLogDetails(<?php echo $log['id']; ?>)" 
                                class="btn btn-sm btn-secondary" title="View Details">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
    <div class="text-sm text-gray-600">
        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
    </div>
    <nav class="flex items-center gap-1">
        <?php
        $baseUrl = url('/admin/sar-inventory/audit-log/');
        $buildPageUrl = function($pageNum) use ($baseUrl, $queryParams) {
            $params = array_merge($queryParams, ['page' => $pageNum]);
            return $baseUrl . '?' . http_build_query($params);
        };
        
        if ($page > 1): ?>
        <a href="<?php echo $buildPageUrl($page - 1); ?>" 
           class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <?php endif; ?>
        
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        if ($startPage > 1): ?>
        <a href="<?php echo $buildPageUrl(1); ?>" 
           class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">1</a>
        <?php if ($startPage > 2): ?>
        <span class="px-2 text-gray-500">...</span>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
        <a href="<?php echo $buildPageUrl($i); ?>" 
           class="px-3 py-2 text-sm font-medium <?php echo $i === $page 
               ? 'text-white bg-blue-600 border border-blue-600' 
               : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-md">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($endPage < $totalPages): ?>
        <?php if ($endPage < $totalPages - 1): ?>
        <span class="px-2 text-gray-500">...</span>
        <?php endif; ?>
        <a href="<?php echo $buildPageUrl($totalPages); ?>" 
           class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
            <?php echo $totalPages; ?>
        </a>
        <?php endif; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="<?php echo $buildPageUrl($page + 1); ?>" 
           class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>

<!-- Log Details Modal -->
<div id="logDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Audit Log Details</h3>
            <button onclick="closeLogDetails()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="logDetailsContent" class="space-y-4">
            <p class="text-gray-500">Loading...</p>
        </div>
    </div>
</div>

<script>
// Store log data for modal display
const logData = <?php echo json_encode(array_map(function($log) {
    return [
        'id' => $log['id'],
        'table_name' => $log['table_name'],
        'record_id' => $log['record_id'],
        'action' => $log['action'],
        'user_name' => $log['user_name'] ?? 'System',
        'ip_address' => $log['ip_address'] ?? '-',
        'created_at' => $log['created_at'],
        'old_values' => $log['old_values'] ? json_decode($log['old_values'], true) : null,
        'new_values' => $log['new_values'] ? json_decode($log['new_values'], true) : null
    ];
}, $logs)); ?>;

function showLogDetails(logId) {
    const log = logData.find(l => l.id == logId);
    if (!log) return;
    
    let html = `
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-sm text-gray-500">Table</p>
                <p class="font-medium">${formatTableName(log.table_name)}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Record ID</p>
                <p class="font-medium font-mono">#${log.record_id}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Action</p>
                <p class="font-medium capitalize">${log.action}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">User</p>
                <p class="font-medium">${log.user_name}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">IP Address</p>
                <p class="font-medium font-mono">${log.ip_address}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Date/Time</p>
                <p class="font-medium">${new Date(log.created_at).toLocaleString()}</p>
            </div>
        </div>
    `;
    
    if (log.action === 'update' && log.old_values && log.new_values) {
        html += `<div class="border-t pt-4"><h4 class="font-semibold mb-2">Changes</h4><div class="space-y-2">`;
        const allKeys = [...new Set([...Object.keys(log.old_values || {}), ...Object.keys(log.new_values || {})])];
        allKeys.forEach(key => {
            const oldVal = log.old_values?.[key];
            const newVal = log.new_values?.[key];
            if (oldVal !== newVal) {
                html += `
                    <div class="bg-gray-50 p-2 rounded">
                        <p class="text-sm font-medium text-gray-700">${key}</p>
                        <div class="flex gap-4 text-sm">
                            <span class="text-red-600 line-through">${formatValue(oldVal)}</span>
                            <span>→</span>
                            <span class="text-green-600">${formatValue(newVal)}</span>
                        </div>
                    </div>
                `;
            }
        });
        html += `</div></div>`;
    } else if (log.action === 'create' && log.new_values) {
        html += `<div class="border-t pt-4"><h4 class="font-semibold mb-2">Created Values</h4>`;
        html += `<pre class="bg-gray-50 p-3 rounded text-sm overflow-auto max-h-64">${JSON.stringify(log.new_values, null, 2)}</pre></div>`;
    } else if (log.action === 'delete' && log.old_values) {
        html += `<div class="border-t pt-4"><h4 class="font-semibold mb-2">Deleted Values</h4>`;
        html += `<pre class="bg-gray-50 p-3 rounded text-sm overflow-auto max-h-64">${JSON.stringify(log.old_values, null, 2)}</pre></div>`;
    }
    
    document.getElementById('logDetailsContent').innerHTML = html;
    document.getElementById('logDetailsModal').classList.remove('hidden');
}

function closeLogDetails() {
    document.getElementById('logDetailsModal').classList.add('hidden');
}

function formatTableName(name) {
    return name.replace(/sar_inv_/g, '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatValue(val) {
    if (val === null || val === undefined) return '<em class="text-gray-400">null</em>';
    if (typeof val === 'object') return JSON.stringify(val);
    return String(val);
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLogDetails();
});

// Close modal when clicking outside
document.getElementById('logDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) closeLogDetails();
});
</script>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
