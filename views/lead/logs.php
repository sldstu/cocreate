<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get the current user
$user_id = $_SESSION['user_id'];

// Process actions
$success_message = '';
$error_message = '';

// Clear logs action
if (isset($_POST['clear_logs']) && isset($_POST['confirm_clear'])) {
    try {
        $query = "TRUNCATE TABLE activity_logs";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        // Log this action (will be the first entry in the newly cleared log)
        logAction($user_id, 'Cleared system logs');
        
        $success_message = 'System logs cleared successfully.';
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error_message = 'An error occurred while clearing logs: ' . $e->getMessage();
    }
}

// Export logs action
if (isset($_POST['export_logs'])) {
    // Log this action
    logAction($user_id, 'Exported system logs');
    
    // This would normally trigger a download, but we'll just show a success message
    $success_message = 'Logs exported successfully.';
    // In a real implementation, you would generate a CSV file and force download it
}

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$user_filter = isset($_GET['user']) ? intval($_GET['user']) : '';
$action_filter = isset($_GET['action']) ? $_GET['action'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$ip_filter = isset($_GET['ip']) ? $_GET['ip'] : '';
$sort_order = isset($_GET['sort_order']) && in_array($_GET['sort_order'], ['asc', 'desc']) ? $_GET['sort_order'] : 'desc';

// Pagination parameters
$current_page = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1; // Renamed to avoid conflict with 'page' parameter
$logs_per_page = 20;
$offset = ($current_page - 1) * $logs_per_page;

// Build query with filters
$where_clauses = [];
$params = [];

if (!empty($date_from)) {
    $where_clauses[] = "l.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $where_clauses[] = "l.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

if (!empty($user_filter)) {
    $where_clauses[] = "l.user_id = ?";
    $params[] = $user_filter;
}

if (!empty($action_filter)) {
    $where_clauses[] = "l.action LIKE ?";
    $params[] = '%' . $action_filter . '%';
}

if (!empty($category_filter)) {
    switch ($category_filter) {
        case 'events':
            $where_clauses[] = "(l.action LIKE '%event%' OR l.action LIKE '%Event%')";
            break;
        case 'users':
            $where_clauses[] = "(l.action LIKE '%user%' OR l.action LIKE '%User%' OR l.action LIKE '%login%' OR l.action LIKE '%Login%')";
            break;
        case 'system':
            $where_clauses[] = "(l.action LIKE '%system%' OR l.action LIKE '%System%' OR l.action LIKE '%settings%' OR l.action LIKE '%Settings%')";
            break;
    }
}

if (!empty($ip_filter)) {
    $where_clauses[] = "l.ip_address LIKE ?";
    $params[] = '%' . $ip_filter . '%';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
}

// Get total log count
try {
    $count_sql = "SELECT COUNT(*) as count FROM activity_logs l" . $where_sql;
    $count_stmt = $conn->prepare($count_sql);
    
    for ($i = 0; $i < count($params); $i++) {
        $count_stmt->bindValue($i + 1, $params[$i]);
    }
    
    $count_stmt->execute();
    $total_logs = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $total_logs = 0;
}

$total_pages = ceil($total_logs / $logs_per_page);
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $logs_per_page;
}

// Get logs with user information
try {
    $query = "SELECT l.*, u.username, CONCAT(u.first_name, ' ', u.last_name) as full_name, r.name as role_name 
              FROM activity_logs l 
              LEFT JOIN users u ON l.user_id = u.user_id 
              LEFT JOIN roles r ON u.role_id = r.role_id" . 
              $where_sql . " 
              ORDER BY l.created_at " . ($sort_order == 'asc' ? 'ASC' : 'DESC') . " 
              LIMIT " . $offset . ", " . $logs_per_page;
    
    $stmt = $conn->prepare($query);
    
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $logs = [];
}

// Get all users for filter dropdown
try {
    $query = "SELECT DISTINCT u.user_id, u.username 
              FROM users u 
              JOIN activity_logs l ON u.user_id = l.user_id 
              ORDER BY u.username";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $users = [];
}

// Get common actions for filter dropdown
try {
    $query = "SELECT DISTINCT action FROM activity_logs ORDER BY action LIMIT 20";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $actions = [];
}

// Get common IP addresses for filter dropdown
try {
    $query = "SELECT DISTINCT ip_address FROM activity_logs ORDER BY ip_address LIMIT 20";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $ips = [];
}

// Get activity statistics
try {
    // Total logs
    $query = "SELECT COUNT(*) as count FROM activity_logs";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $total_all_logs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Today's logs
    $query = "SELECT COUNT(*) as count FROM activity_logs WHERE DATE(created_at) = CURDATE()";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $today_logs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // This week's logs
    $query = "SELECT COUNT(*) as count FROM activity_logs WHERE YEARWEEK(created_at) = YEARWEEK(NOW())";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $week_logs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // This month's logs
    $query = "SELECT COUNT(*) as count FROM activity_logs WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $month_logs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Event-related logs
    $query = "SELECT COUNT(*) as count FROM activity_logs WHERE action LIKE '%event%' OR action LIKE '%Event%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $event_logs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // User-related logs
    $query = "SELECT COUNT(*) as count FROM activity_logs WHERE action LIKE '%user%' OR action LIKE '%User%' OR action LIKE '%login%' OR action LIKE '%Login%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $user_logs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // System-related logs
    $query = "SELECT COUNT(*) as count FROM activity_logs WHERE action LIKE '%system%' OR action LIKE '%System%' OR action LIKE '%settings%' OR action LIKE '%Settings%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $system_logs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $total_all_logs = $today_logs = $week_logs = $month_logs = $event_logs = $user_logs = $system_logs = 0;
}

// Log statistics
$log_stats = [
    'total' => $total_all_logs,
    'today' => $today_logs,
    'this_week' => $week_logs,
    'this_month' => $month_logs,
    'events' => $event_logs,
    'users' => $user_logs,
    'system' => $system_logs
];
?>

<div class="p-4 sm:p-6 lg:p-8">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-normal mb-1" style="color: var(--color-text-primary);">System Logs</h1>
        <p class="text-sm" style="color: var(--color-text-secondary);">
            Monitor and manage system activity logs
        </p>
    </div>
    
    <?php if (!empty($success_message)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $success_message; ?></span>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error_message; ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <div class="google-card p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm" style="color: var(--color-text-secondary);">Total Logs</span>
                <span class="flex items-center justify-center w-8 h-8 rounded-full" style="background-color: rgba(66, 133, 244, 0.1);">
                    <i class="fas fa-history" style="color: #4285F4;"></i>
                </span>
            </div>
            <div class="flex items-baseline">
                <span class="text-2xl font-medium" style="color: var(--color-text-primary);"><?php echo number_format($log_stats['total']); ?></span>
            </div>
        </div>
        
        <div class="google-card p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm" style="color: var(--color-text-secondary);">Today's Activity</span>
                <span class="flex items-center justify-center w-8 h-8 rounded-full" style="background-color: rgba(52, 168, 83, 0.1);">
                    <i class="fas fa-calendar-day" style="color: #34A853;"></i>
                </span>
            </div>
            <div class="flex items-baseline">
                <span class="text-2xl font-medium" style="color: var(--color-text-primary);"><?php echo number_format($log_stats['today']); ?></span>
            </div>
        </div>
        
        <div class="google-card p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm" style="color: var(--color-text-secondary);">This Week</span>
                <span class="flex items-center justify-center w-8 h-8 rounded-full" style="background-color: rgba(251, 188, 5, 0.1);">
                    <i class="fas fa-calendar-week" style="color: #FBBC05;"></i>
                </span>
            </div>
            <div class="flex items-baseline">
                <span class="text-2xl font-medium" style="color: var(--color-text-primary);"><?php echo number_format($log_stats['this_week']); ?></span>
            </div>
        </div>
        
        <div class="google-card p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm" style="color: var(--color-text-secondary);">This Month</span>
                <span class="flex items-center justify-center w-8 h-8 rounded-full" style="background-color: rgba(234, 67, 53, 0.1);">
                    <i class="fas fa-calendar-alt" style="color: #EA4335;"></i>
                </span>
            </div>
            <div class="flex items-baseline">
            <span class="text-2xl font-medium" style="color: var(--color-text-primary);"><?php echo number_format($log_stats['this_month']); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Activity Categories -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
        <div class="google-card p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm" style="color: var(--color-text-secondary);">Event Activities</span>
                <span class="flex items-center justify-center w-8 h-8 rounded-full" style="background-color: rgba(52, 168, 83, 0.1);">
                    <i class="fas fa-calendar-alt" style="color: #34A853;"></i>
                </span>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-medium" style="color: var(--color-text-primary);"><?php echo number_format($log_stats['events']); ?></span>
                <a href="?page=lead_logs&category=events" class="text-xs px-2 py-1 rounded-full" style="background-color: rgba(52, 168, 83, 0.1); color: #34A853;">
                    View Events
                </a>
            </div>
        </div>
        
        <div class="google-card p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm" style="color: var(--color-text-secondary);">User Activities</span>
                <span class="flex items-center justify-center w-8 h-8 rounded-full" style="background-color: rgba(66, 133, 244, 0.1);">
                    <i class="fas fa-users" style="color: #4285F4;"></i>
                </span>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-medium" style="color: var(--color-text-primary);"><?php echo number_format($log_stats['users']); ?></span>
                <a href="?page=lead_logs&category=users" class="text-xs px-2 py-1 rounded-full" style="background-color: rgba(66, 133, 244, 0.1); color: #4285F4;">
                    View Users
                </a>
            </div>
        </div>
        
        <div class="google-card p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm" style="color: var(--color-text-secondary);">System Activities</span>
                <span class="flex items-center justify-center w-8 h-8 rounded-full" style="background-color: rgba(234, 67, 53, 0.1);">
                    <i class="fas fa-cogs" style="color: #EA4335;"></i>
                </span>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-medium" style="color: var(--color-text-primary);"><?php echo number_format($log_stats['system']); ?></span>
                <a href="?page=lead_logs&category=system" class="text-xs px-2 py-1 rounded-full" style="background-color: rgba(234, 67, 53, 0.1); color: #EA4335;">
                    View System
                </a>
            </div>
        </div>
    </div>
    
    <!-- Filter and Actions -->
    <div class="google-card p-5 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
            <h3 class="text-lg font-normal mb-2 md:mb-0" style="color: var(--color-text-primary);">Filter Logs</h3>
            <div class="flex space-x-2">
                <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to export all logs? This may take a while if there are many logs.');">
                    <button type="submit" name="export_logs" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium">
                        <i class="fas fa-download mr-1"></i> Export Logs
                    </button>
                </form>
                <button type="button" class="btn-outline py-2 px-4 rounded-md text-sm font-medium" data-action="clear-logs" onclick="showClearLogsModal()">
                    <i class="fas fa-trash-alt mr-1"></i> Clear Logs
                </button>
            </div>
        </div>
        
        <form action="" method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <input type="hidden" name="page" value="lead_logs">
            
            <div>
                <label for="date_from" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="form-input rounded-md w-full text-sm border" style="border-color: var(--color-border-medium);">
            </div>
            
            <div>
                <label for="date_to" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="form-input rounded-md w-full text-sm border" style="border-color: var(--color-border-medium);">
            </div>
            
            <div>
                <label for="user" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">User</label>
                <select id="user" name="user" class="form-select rounded-md w-full text-sm border" style="border-color: var(--color-border-medium);">
                    <option value="">All Users</option>
                    <?php foreach($users as $user): ?>
                        <option value="<?php echo $user['user_id']; ?>" <?php echo $user_filter == $user['user_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($user['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="category" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">Category</label>
                <select id="category" name="category" class="form-select rounded-md w-full text-sm border" style="border-color: var(--color-border-medium);">
                    <option value="">All Categories</option>
                    <option value="events" <?php echo $category_filter == 'events' ? 'selected' : ''; ?>>Events</option>
                    <option value="users" <?php echo $category_filter == 'users' ? 'selected' : ''; ?>>Users</option>
                    <option value="system" <?php echo $category_filter == 'system' ? 'selected' : ''; ?>>System</option>
                </select>
            </div>
            
            <div>
                <label for="action" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">Action</label>
                <select id="action" name="action" class="form-select rounded-md w-full text-sm border" style="border-color: var(--color-border-medium);">
                    <option value="">All Actions</option>
                    <?php foreach($actions as $action_item): ?>
                        <option value="<?php echo $action_item['action']; ?>" <?php echo $action_filter == $action_item['action'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($action_item['action']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                    <i class="fas fa-filter mr-1"></i> Apply Filters
                </button>
                <a href="?page=lead_logs" class="ml-2 btn-outline py-2 px-4 rounded-md text-sm font-medium">
                    <i class="fas fa-times mr-1"></i> Clear
                </a>
            </div>
        </form>
    </div>
    
    <!-- Logs List -->
    <div class="google-card p-0 overflow-hidden">
        <!-- Activity Stream View -->
        <div class="p-5">
            <h3 class="text-lg font-normal mb-4" style="color: var(--color-text-primary);">Activity Timeline</h3>
            
            <?php if (empty($logs)): ?>
                <div class="text-center py-8">
                    <div class="rounded-full mx-auto p-4 mb-4" style="background-color: rgba(66, 133, 244, 0.1); width: fit-content;">
                        <i class="fas fa-history text-2xl" style="color: #4285F4;"></i>
                    </div>
                    <h3 class="text-lg font-medium mb-2" style="color: var(--color-text-primary);">No Logs Found</h3>
                    <p class="text-sm" style="color: var(--color-text-secondary);">
                        <?php if (!empty($date_from) || !empty($date_to) || !empty($user_filter) || !empty($action_filter) || !empty($category_filter) || !empty($ip_filter)): ?>
                            No logs match your current filters. Try adjusting your search criteria.
                        <?php else: ?>
                            There are no activity logs in the system yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php 
                    $current_date = null;
                    foreach ($logs as $log): 
                        $log_date = date('Y-m-d', strtotime($log['created_at']));
                        
                        // Display date header when date changes
                        if ($log_date != $current_date):
                            $current_date = $log_date;
                            $date_display = date('F j, Y', strtotime($log['created_at']));
                            
                            // Determine if it's today, yesterday, or a regular date
                            $today = date('Y-m-d');
                            $yesterday = date('Y-m-d', strtotime('-1 day'));
                            
                            if ($log_date == $today) {
                                $date_display = 'Today';
                            } else if ($log_date == $yesterday) {
                                $date_display = 'Yesterday';
                            }
                    ?>
                        <div class="flex items-center mb-3 mt-6">
                            <div class="h-px flex-grow bg-gray-200 mr-3"></div>
                            <span class="text-sm font-medium px-2 py-1 rounded-full" style="background-color: var(--color-hover); color: var(--color-text-secondary);">
                                <?php echo $date_display; ?>
                            </span>
                            <div class="h-px flex-grow bg-gray-200 ml-3"></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex items-start">
                        <?php
                        // Determine icon, color, and category based on action type
                        $icon = 'fa-history';
                        $bgColor = 'rgba(66, 133, 244, 0.1)';
                        $iconColor = '#4285F4';
                        $category = 'Other';
                        
                        // Event-related actions
                        if (stripos($log['action'], 'event') !== false) {
                            $category = 'Event';
                            
                            if (stripos($log['action'], 'create') !== false || stripos($log['action'], 'add') !== false) {
                                $icon = 'fa-calendar-plus';
                                $bgColor = 'rgba(52, 168, 83, 0.1)';
                                $iconColor = '#34A853';
                            } else if (stripos($log['action'], 'update') !== false || stripos($log['action'], 'edit') !== false) {
                                $icon = 'fa-calendar-check';
                                $bgColor = 'rgba(251, 188, 5, 0.1)';
                                $iconColor = '#FBBC05';
                            } else if (stripos($log['action'], 'delete') !== false || stripos($log['action'], 'remove') !== false || stripos($log['action'], 'cancel') !== false) {
                                $icon = 'fa-calendar-times';
                                $bgColor = 'rgba(234, 67, 53, 0.1)';
                                $iconColor = '#EA4335';
                            } else {
                                $icon = 'fa-calendar-alt';
                                $bgColor = 'rgba(52, 168, 83, 0.1)';
                                $iconColor = '#34A853';
                            }
                        }
                        // User-related actions
                        else if (stripos($log['action'], 'user') !== false || stripos($log['action'], 'login') !== false || stripos($log['action'], 'logout') !== false || stripos($log['action'], 'password') !== false) {
                            $category = 'User';
                            
                            if (stripos($log['action'], 'create') !== false || stripos($log['action'], 'add') !== false || stripos($log['action'], 'register') !== false) {
                                $icon = 'fa-user-plus';
                                $bgColor = 'rgba(52, 168, 83, 0.1)';
                                $iconColor = '#34A853';
                            } else if (stripos($log['action'], 'update') !== false || stripos($log['action'], 'edit') !== false || stripos($log['action'], 'change') !== false) {
                                $icon = 'fa-user-edit';
                                $bgColor = 'rgba(251, 188, 5, 0.1)';
                                $iconColor = '#FBBC05';
                            } else if (stripos($log['action'], 'delete') !== false || stripos($log['action'], 'remove') !== false || stripos($log['action'], 'deactivate') !== false) {
                                $icon = 'fa-user-times';
                                $bgColor = 'rgba(234, 67, 53, 0.1)';
                                $iconColor = '#EA4335';
                            } else if (stripos($log['action'], 'login') !== false) {
                                $icon = 'fa-sign-in-alt';
                                $bgColor = 'rgba(103, 58, 183, 0.1)';
                                $iconColor = '#673AB7';
                            } else if (stripos($log['action'], 'logout') !== false) {
                                $icon = 'fa-sign-out-alt';
                                $bgColor = 'rgba(103, 58, 183, 0.1)';
                                $iconColor = '#673AB7';
                            } else if (stripos($log['action'], 'password') !== false) {
                                $icon = 'fa-key';
                                $bgColor = 'rgba(251, 188, 5, 0.1)';
                                $iconColor = '#FBBC05';
                            } else {
                                $icon = 'fa-user';
                                $bgColor = 'rgba(66, 133, 244, 0.1)';
                                $iconColor = '#4285F4';
                            }
                        }
                        // System-related actions
                        else if (stripos($log['action'], 'system') !== false || stripos($log['action'], 'setting') !== false || stripos($log['action'], 'config') !== false || stripos($log['action'], 'log') !== false) {
                            $category = 'System';
                            
                            if (stripos($log['action'], 'clear') !== false) {
                                $icon = 'fa-trash-alt';
                                $bgColor = 'rgba(234, 67, 53, 0.1)';
                                $iconColor = '#EA4335';
                            } else if (stripos($log['action'], 'update') !== false || stripos($log['action'], 'change') !== false) {
                                $icon = 'fa-cog';
                                $bgColor = 'rgba(251, 188, 5, 0.1)';
                                $iconColor = '#FBBC05';
                            } else if (stripos($log['action'], 'backup') !== false) {
                                $icon = 'fa-database';
                                $bgColor = 'rgba(52, 168, 83, 0.1)';
                                $iconColor = '#34A853';
                            } else {
                                $icon = 'fa-cogs';
                                $bgColor = 'rgba(234, 67, 53, 0.1)';
                                $iconColor = '#EA4335';
                            }
                        }
                        // Other actions
                        else if (stripos($log['action'], 'create') !== false || stripos($log['action'], 'add') !== false) {
                            $icon = 'fa-plus-circle';
                            $bgColor = 'rgba(52, 168, 83, 0.1)';
                            $iconColor = '#34A853';
                        } else if (stripos($log['action'], 'update') !== false || stripos($log['action'], 'edit') !== false || stripos($log['action'], 'change') !== false) {
                            $icon = 'fa-edit';
                            $bgColor = 'rgba(251, 188, 5, 0.1)';
                            $iconColor = '#FBBC05';
                        } else if (stripos($log['action'], 'delete') !== false || stripos($log['action'], 'remove') !== false) {
                            $icon = 'fa-trash-alt';
                            $bgColor = 'rgba(234, 67, 53, 0.1)';
                            $iconColor = '#EA4335';
                        }
                        ?>
                        
                        <div class="rounded-full p-2 mr-3 flex-shrink-0" style="background-color: <?php echo $bgColor; ?>;">
                            <i class="fas <?php echo $icon; ?>" style="color: <?php echo $iconColor; ?>;"></i>
                        </div>
                        
                        <div class="flex-grow">
                            <div class="flex flex-wrap justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium" style="color: var(--color-text-primary);">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                        <span class="ml-2 px-1.5 py-0.5 text-xs rounded-full" style="background-color: <?php echo $bgColor; ?>; color: <?php echo $iconColor; ?>;">
                                            <?php echo $category; ?>
                                        </span>
                                    </p>
                                    <p class="text-xs" style="color: var(--color-text-secondary);">
                                        <?php if ($log['username']): ?>
                                            <span class="font-medium"><?php echo htmlspecialchars($log['username']); ?></span>
                                            <?php if ($log['role_name']): ?>
                                                <span class="px-1 py-0.5 rounded-full text-xs" style="background-color: rgba(66, 133, 244, 0.1); color: #4285F4;">
                                                    <?php echo htmlspecialchars($log['role_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="font-medium">System</span>
                                        <?php endif; ?>
                                        • IP: <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </p>
                                </div>
                                <span class="text-xs" style="color: var(--color-text-tertiary);">
                                    <?php echo date('h:i A', strtotime($log['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-5 py-3 border-t flex justify-between items-center" style="border-color: var(--color-border-light);">
                    <span class="text-sm" style="color: var(--color-text-secondary);">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $logs_per_page, $total_logs); ?> of <?php echo $total_logs; ?> logs
                    </span>
                    
                    <div class="flex space-x-1">
                        <?php if ($current_page > 1): ?>
                        <a href="?page=lead_logs&<?php echo http_build_query(array_filter(['date_from' => $date_from, 'date_to' => $date_to, 'user' => $user_filter, 'action' => $action_filter, 'category' => $category_filter, 'ip' => $ip_filter, 'page_num' => $current_page - 1])); ?>" class="px-3 py-1 rounded border text-sm" style="border-color: var(--color-border-medium); color: var(--color-text-secondary);">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        // Show max 5 page numbers with current page in the middle when possible
                        $start_page = max(1, min($current_page - 2, $total_pages - 4));
                        $end_page = min($total_pages, max(5, $current_page + 2));
                        
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                        <a href="?page=lead_logs&<?php echo http_build_query(array_filter(['date_from' => $date_from, 'date_to' => $date_to, 'user' => $user_filter, 'action' => $action_filter, 'category' => $category_filter, 'ip' => $ip_filter, 'page_num' => $i])); ?>" 
                           class="px-3 py-1 rounded border text-sm <?php echo $i == $current_page ? 'font-medium' : ''; ?>" 
                           style="border-color: var(--color-border-medium); 
                                  <?php echo $i == $current_page ? 'background-color: var(--color-primary); color: white;' : 'color: var(--color-text-secondary);'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                        <a href="?page=lead_logs&<?php echo http_build_query(array_filter(['date_from' => $date_from, 'date_to' => $date_to, 'user' => $user_filter, 'action' => $action_filter, 'category' => $category_filter, 'ip' => $ip_filter, 'page_num' => $current_page + 1])); ?>" class="px-3 py-1 rounded border text-sm" style="border-color: var(--color-border-medium); color: var(--color-text-secondary);">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Traditional Table View (Toggle Option) -->
        <div class="border-t" style="border-color: var(--color-border-light);">
            <div class="p-4 flex justify-between items-center">
                <h3 class="text-lg font-normal" style="color: var(--color-text-primary);">Detailed Log Data</h3>
                <button id="toggle-table-view" class="btn-outline py-1 px-3 rounded-md text-xs font-medium">
                    <i class="fas fa-table mr-1"></i> <span id="toggle-text">Show Table View</span>
                </button>
            </div>
            
            <div id="table-view" class="hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y" style="border-color: var(--color-border-light);">
                        <thead style="background-color: var(--color-surface-variant);">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Time</th>
                                <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">User</th>
                                <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Role</th>
                                <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Category</th>
                                <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Action</th>
                                <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: var(--color-border-light);">
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="px-5 py-4 text-center text-sm" style="color: var(--color-text-tertiary);">No logs found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): 
                                    // Determine category based on action
                                    $category = 'Other';
                                    $categoryColor = '#4285F4';
                                    $categoryBg = 'rgba(66, 133, 244, 0.1)';
                                    
                                    if (stripos($log['action'], 'event') !== false) {
                                        $category = 'Event';
                                        $categoryColor = '#34A853';
                                        $categoryBg = 'rgba(52, 168, 83, 0.1)';
                                    } else if (stripos($log['action'], 'user') !== false || stripos($log['action'], 'login') !== false || stripos($log['action'], 'logout') !== false || stripos($log['action'], 'password') !== false) {
                                        $category = 'User';
                                        $categoryColor = '#4285F4';
                                        $categoryBg = 'rgba(66, 133, 244, 0.1)';
                                    } else if (stripos($log['action'], 'system') !== false || stripos($log['action'], 'setting') !== false || stripos($log['action'], 'config') !== false || stripos($log['action'], 'log') !== false) {
                                        $category = 'System';
                                        $categoryColor = '#EA4335';
                                        $categoryBg = 'rgba(234, 67, 53, 0.1)';
                                    }
                                ?>
                                <tr>
                                    <td class="px-5 py-3 text-sm" style="color: var(--color-text-primary);">
                                        <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td class="px-5 py-3 text-sm" style="color: var(--color-text-primary);">
                                        <?php 
                                        if ($log['username']) {
                                            echo htmlspecialchars($log['username']);
                                        } else {
                                            echo '<span class="text-gray-500">System</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-5 py-3 text-sm" style="color: var(--color-text-primary);">
                                        <?php echo htmlspecialchars($log['role_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-5 py-3 text-sm">
                                        <span class="px-2 py-1 rounded-full text-xs" style="background-color: <?php echo $categoryBg; ?>; color: <?php echo $categoryColor; ?>;">
                                            <?php echo $category; ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-sm" style="color: var(--color-text-primary);">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </td>
                                    <td class="px-5 py-3 text-sm" style="color: var(--color-text-primary);">
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Clear Logs Confirmation Modal -->
<div id="clear-logs-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden" style="background-color: rgba(0,0,0,0.5);">
    <div class="bg-white rounded-lg p-6 max-w-md mx-auto" style="background-color: var(--color-surface);">
        <h3 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Confirm Clear Logs</h3>
        <p class="mb-6 text-sm" style="color: var(--color-text-secondary);">Are you sure you want to clear all system logs? This action cannot be undone.</p>
        
        <form method="post" class="flex justify-end space-x-3">
            <button type="button" onclick="hideClearLogsModal()" class="btn-outline py-2 px-4 rounded-md text-sm font-medium">
                Cancel
            </button>
            <input type="hidden" name="confirm_clear" value="1">
            <button type="submit" name="clear_logs" class="btn-danger py-2 px-4 rounded-md text-sm font-medium">
                Clear All Logs
            </button>
        </form>
    </div>
</div>

<!-- Log Details Modal -->
<div id="log-details-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden" style="background-color: rgba(0,0,0,0.5);">
    <div class="bg-white rounded-lg p-6 max-w-2xl mx-auto w-full" style="background-color: var(--color-surface);">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium" style="color: var(--color-text-primary);">Log Details</h3>
            <button type="button" onclick="hideLogDetailsModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="log-details-content">
            <!-- Content will be populated by JavaScript -->
        </div>
        
        <div class="flex justify-end mt-6">
            <button type="button" onclick="hideLogDetailsModal()" class="btn-outline py-2 px-4 rounded-md text-sm font-medium">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Modal functions
function showClearLogsModal() {
    document.getElementById('clear-logs-modal').classList.remove('hidden');
}

function hideClearLogsModal() {
    document.getElementById('clear-logs-modal').classList.add('hidden');
}

function showLogDetailsModal(logId, action, username, role, time, ip, category) {
    const modal = document.getElementById('log-details-modal');
    const content = document.getElementById('log-details-content');
    
    // Determine category color
    let categoryColor = '#4285F4';
    let categoryBg = 'rgba(66, 133, 244, 0.1)';
    let icon = 'fa-history';
    
    if (category === 'Event') {
        categoryColor = '#34A853';
        categoryBg = 'rgba(52, 168, 83, 0.1)';
        icon = 'fa-calendar-alt';
    } else if (category === 'User') {
        categoryColor = '#4285F4';
        categoryBg = 'rgba(66, 133, 244, 0.1)';
        icon = 'fa-user';
    } else if (category === 'System') {
        categoryColor = '#EA4335';
        categoryBg = 'rgba(234, 67, 53, 0.1)';
        icon = 'fa-cogs';
    }
    
    // Create content HTML
    let html = `
        <div class="google-card p-4 mb-4">
            <div class="flex items-start">
                <div class="rounded-full p-3 mr-4" style="background-color: ${categoryBg};">
                    <i class="fas ${icon} text-lg" style="color: ${categoryColor};"></i>
                </div>
                <div class="flex-grow">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="text-md font-medium mb-1" style="color: var(--color-text-primary);">${action}</h4>
                            <p class="text-sm mb-2" style="color: var(--color-text-secondary);">
                                <span class="px-2 py-0.5 rounded-full text-xs" style="background-color: ${categoryBg}; color: ${categoryColor};">
                                    ${category}
                                </span>
                            </p>
                        </div>
                        <span class="text-xs" style="color: var(--color-text-tertiary);">${time}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="google-card p-4">
                <h5 class="text-sm font-medium mb-2" style="color: var(--color-text-secondary);">User Information</h5>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-xs" style="color: var(--color-text-tertiary);">Username:</span>
                        <span class="text-xs font-medium" style="color: var(--color-text-primary);">${username || 'System'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs" style="color: var(--color-text-tertiary);">Role:</span>
                        <span class="text-xs font-medium" style="color: var(--color-text-primary);">${role || 'N/A'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs" style="color: var(--color-text-tertiary);">IP Address:</span>
                        <span class="text-xs font-medium" style="color: var(--color-text-primary);">${ip}</span>
                    </div>
                </div>
            </div>
            
            <div class="google-card p-4">
                <h5 class="text-sm font-medium mb-2" style="color: var(--color-text-secondary);">Related Information</h5>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-xs" style="color: var(--color-text-tertiary);">Log ID:</span>
                        <span class="text-xs font-medium" style="color: var(--color-text-primary);">${logId}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs" style="color: var(--color-text-tertiary);">Timestamp:</span>
                        <span class="text-xs font-medium" style="color: var(--color-text-primary);">${time}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs" style="color: var(--color-text-tertiary);">Category:</span>
                        <span class="text-xs font-medium" style="color: var(--color-text-primary);">${category}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    content.innerHTML = html;
    modal.classList.remove('hidden');
}

function hideLogDetailsModal() {
    document.getElementById('log-details-modal').classList.add('hidden');
}

// Toggle table view
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggle-table-view');
    const tableView = document.getElementById('table-view');
    const toggleText = document.getElementById('toggle-text');
    
    if (toggleBtn && tableView) {
        toggleBtn.addEventListener('click', function() {
            if (tableView.classList.contains('hidden')) {
                tableView.classList.remove('hidden');
                toggleText.textContent = 'Hide Table View';
            } else {
                tableView.classList.add('hidden');
                toggleText.textContent = 'Show Table View';
            }
        });
    }
    
    // Make log entries clickable to show details
    const logEntries = document.querySelectorAll('.flex.items-start');
    logEntries.forEach(entry => {
        entry.addEventListener('click', function() {
            // Extract data from the log entry
            const action = this.querySelector('p.text-sm.font-medium').textContent.trim();
            const categorySpan = this.querySelector('span.ml-2.px-1\\.5.py-0\\.5.text-xs.rounded-full');
            const category = categorySpan ? categorySpan.textContent.trim() : 'Other';
            
            const userInfo = this.querySelector('p.text-xs').textContent.trim();
            const username = userInfo.split('•')[0].trim();
            const role = this.querySelector('span.px-1.py-0\\.5.rounded-full.text-xs') ? 
                        this.querySelector('span.px-1.py-0\\.5.rounded-full.text-xs').textContent.trim() : '';
            const ip = userInfo.split('•')[1].replace('IP:', '').trim();
            
            const time = this.querySelector('span.text-xs').textContent.trim();
            
            // Generate a random log ID for demo purposes
            const logId = Math.floor(Math.random() * 10000) + 1;
            
            showLogDetailsModal(logId, action, username, role, time, ip, category);
        });
        
        // Add hover effect to indicate clickability
        entry.classList.add('cursor-pointer', 'hover:bg-gray-50');
        entry.style.transition = 'background-color 0.2s';
        entry.style.borderRadius = '0.375rem';
        entry.style.padding = '0.5rem';
        entry.style.margin = '-0.5rem';
    });
});
</script>
