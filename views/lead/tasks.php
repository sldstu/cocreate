<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Include events manager class for access to departments and other related data
require_once 'views/lead/php/events.class.php';
$eventsManager = new EventsManager($conn);

// Get action
$action = isset($_GET['action']) ? $_GET['action'] : '';
$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
$view = isset($_GET['view']) ? $_GET['view'] : 'board'; // Default view is now board view
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// First, check if the tasks table exists
try {
    $checkTable = $conn->query("SHOW TABLES LIKE 'tasks'");
    if ($checkTable->rowCount() == 0) {
        // Table doesn't exist, include the SQL to create it
        echo '<div class="alert alert-warning">
            <h4>Task Management Setup Required</h4>
            <p>The necessary database tables for task management are not yet created. Please run the SQL scripts in config/tasks.sql to set up the required tables.</p>
            <a href="?page=lead_dashboard" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">Return to Dashboard</a>
        </div>';
        exit;
    }
} catch (PDOException $e) {
    // Handle exception
    echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

// Get tasks based on filter, search, and pagination
$page = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
$tasksPerPage = 50; // Increased for board view
$offset = ($page - 1) * $tasksPerPage;

// First check for departments field in tasks table
try {
    $checkColumn = $conn->query("SHOW COLUMNS FROM tasks LIKE 'department_id'");
    $departmentColumnExists = $checkColumn->rowCount() > 0;
} catch (PDOException $e) {
    // If there's an error, assume the column doesn't exist
    $departmentColumnExists = false;
}

// Get unique task statuses from the database - for custom columns
$customStatusesQuery = "SELECT DISTINCT status FROM tasks";
$customStatusesStmt = $conn->query($customStatusesQuery);
$allStatuses = $customStatusesStmt->fetchAll(PDO::FETCH_COLUMN);

// Define default statuses and their display properties
$defaultStatuses = [
    'to_do' => [
        'name' => 'To Do',
        'icon' => 'fa-clipboard-list',
        'color' => '#FBBC05',
        'bg_color' => 'rgba(251, 188, 5, 0.1)',
        'order' => 1
    ],
    'in_progress' => [
        'name' => 'In Progress',
        'icon' => 'fa-spinner',
        'color' => '#4285F4',
        'bg_color' => 'rgba(66, 133, 244, 0.1)',
        'order' => 2
    ],
    'done' => [
        'name' => 'Completed',
        'icon' => 'fa-check-circle',
        'color' => '#34A853',
        'bg_color' => 'rgba(52, 168, 83, 0.1)',
        'order' => 3
    ],
];

// Create an array for all statuses (default + custom)
$statuses = [];

// First, add default statuses
foreach ($defaultStatuses as $key => $props) {
    $statuses[$key] = $props;
}

// Then add any custom statuses from the database
foreach ($allStatuses as $dbStatus) {
    if (!isset($defaultStatuses[$dbStatus])) {
        // This is a custom status
        $statuses[$dbStatus] = [
            'name' => ucwords(str_replace('_', ' ', $dbStatus)),
            'icon' => 'fa-bookmark',
            'color' => '#9E9E9E', // Default gray color for custom statuses
            'bg_color' => 'rgba(158, 158, 158, 0.1)',
            'order' => 4 // Custom statuses appear after default ones
        ];
    }
}

// Handle different actions
switch ($action) {
    case 'add':
        // Display add task form
        $pageTitle = 'Create New Task';
        $isEditing = false;
        $task = []; // Empty task for new form
        $departments = $eventsManager->getDepartments();
        
        // Get events for task association
        $events = $eventsManager->getAllEvents();
        
        // Get users for assignment
        $stmt = $conn->prepare("SELECT user_id, username, first_name, last_name, department_id FROM users ORDER BY username");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        include 'views/lead/templates/task_form.php';
        break;
        
    case 'edit':
        // Display edit task form
        if ($task_id <= 0) {
            echo '<div class="alert alert-danger">Invalid task ID.</div>';
            exit;
        }
        
        // Get task details
        $stmt = $conn->prepare("SELECT * FROM tasks WHERE task_id = ?");
        $stmt->bindParam(1, $task_id, PDO::PARAM_INT);
        $stmt->execute();
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) {
            echo '<div class="alert alert-danger">Task not found.</div>';
            exit;
        }
        
        $pageTitle = 'Edit Task: ' . $task['title'];
        $isEditing = true;
        $departments = $eventsManager->getDepartments();
        
        // Get events for task association
        $events = $eventsManager->getAllEvents();
        
        // Get users for assignment
        $stmt = $conn->prepare("SELECT user_id, username, first_name, last_name, department_id FROM users ORDER BY username");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        include 'views/lead/templates/task_form.php';
        break;
        
    case 'delete':
        // Display delete confirmation
        if ($task_id <= 0) {
            echo '<div class="alert alert-danger">Invalid task ID.</div>';
            exit;
        }
        
        // Get task details
        $stmt = $conn->prepare("SELECT * FROM tasks WHERE task_id = ?");
        $stmt->bindParam(1, $task_id, PDO::PARAM_INT);
        $stmt->execute();
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) {
            echo '<div class="alert alert-danger">Task not found.</div>';
            exit;
        }
        
        $pageTitle = 'Delete Task: ' . $task['title'];
        include 'views/lead/templates/task_delete_confirm.php';
        break;
        
    case 'view':
        // Display task details
        if ($task_id <= 0) {
            echo '<div class="alert alert-danger">Invalid task ID.</div>';
            exit;
        }
        
        // Get task details with related information
        $stmt = $conn->prepare("
            SELECT t.*,
                   u.username as assigned_username,
                   CONCAT(u.first_name, ' ', u.last_name) as assigned_name,
                   c.username as created_username,
                   CONCAT(c.first_name, ' ', c.last_name) as created_name,
                   e.title as event_title,
                   d.name as department_name
            FROM tasks t
            LEFT JOIN task_assignments ta ON t.task_id = ta.task_id
            LEFT JOIN users u ON ta.user_id = u.user_id
            LEFT JOIN users c ON t.created_by = c.user_id
            LEFT JOIN events e ON t.event_id = e.event_id
            LEFT JOIN departments d ON t.department_id = d.department_id
            WHERE t.task_id = ?
        ");
        $stmt->bindParam(1, $task_id, PDO::PARAM_INT);
        $stmt->execute();
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) {
            echo '<div class="alert alert-danger">Task not found.</div>';
            exit;
        }
        
        // Get task comments
        $stmt = $conn->prepare("
            SELECT c.*, u.username, u.first_name, u.last_name
            FROM task_comments c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.task_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->bindParam(1, $task_id, PDO::PARAM_INT);
        $stmt->execute();
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $pageTitle = 'Task Details: ' . $task['title'];
        include 'views/lead/templates/task_detail.php';
        break;
        
    default:
        // Display tasks list
        // Build the WHERE clause based on filters
        $whereClause = "WHERE 1=1";
        $params = [];
        
        // Filter by search term
        if (!empty($search)) {
            $whereClause .= " AND (t.title LIKE ? OR t.description LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Filter by status
        if (!empty($filter) && $filter != 'all') {
            $whereClause .= " AND t.status = ?";
            $params[] = $filter;
        }
        
        // Filter by department if present in URL and the column exists
        if (isset($_GET['department']) && !empty($_GET['department']) && $departmentColumnExists) {
            $whereClause .= " AND t.department_id = ?";
            $params[] = $_GET['department'];
        }
        
        // Filter by event if present in URL
        if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
            $whereClause .= " AND t.event_id = ?";
            $params[] = $_GET['event_id'];
        }
        
        // Filter by priority if present in URL
        if (isset($_GET['priority']) && !empty($_GET['priority'])) {
            $whereClause .= " AND t.priority = ?";
            $params[] = $_GET['priority'];
        }
        
        // Filter by assigned user if present in URL
        if (isset($_GET['assigned_to']) && !empty($_GET['assigned_to'])) {
            $whereClause .= " AND ta.user_id = ?";
            $params[] = $_GET['assigned_to'];
        }
        
        // Count total tasks matching the filters
        $countQuery = "SELECT COUNT(DISTINCT t.task_id) AS count FROM tasks t 
                        LEFT JOIN task_assignments ta ON t.task_id = ta.task_id 
                        $whereClause";
        
        $stmt = $conn->prepare($countQuery);
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
        $stmt->execute();
        $totalTasks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $totalPages = ceil($totalTasks / $tasksPerPage);
        
        // Get the tasks - Modified join to use task_assignments table and handle department_id if it exists
        $query = "
            SELECT t.*, 
                   u.username as assigned_username, 
                   CONCAT(u.first_name, ' ', u.last_name) as assigned_name,
                   " . ($departmentColumnExists ? "d.name as department_name," : "NULL as department_name,") . "
                   e.title as event_title
            FROM tasks t
            LEFT JOIN task_assignments ta ON t.task_id = ta.task_id
            LEFT JOIN users u ON ta.user_id = u.user_id
            " . ($departmentColumnExists ? "LEFT JOIN departments d ON t.department_id = d.department_id" : "") . "
            LEFT JOIN events e ON t.event_id = e.event_id
            $whereClause
            GROUP BY t.task_id
            ORDER BY 
            CASE WHEN t.status = 'to_do' THEN 1
                 WHEN t.status = 'in_progress' THEN 2
                 WHEN t.status = 'done' THEN 3
                 ELSE 4 END,
            t.priority DESC, t.deadline ASC
            LIMIT ? OFFSET ?
        ";
        
        // FIX: Create a new parameters array for the main query
        $queryParams = $params;
        $queryParams[] = $tasksPerPage;
        $queryParams[] = $offset;
        
        $stmt = $conn->prepare($query);
        
        // FIX: Use bindParam with explicit type instead of bindValue for LIMIT and OFFSET
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
        // Bind the LIMIT and OFFSET parameters as integers explicitly
        $stmt->bindParam(count($params) + 1, $tasksPerPage, PDO::PARAM_INT);
        $stmt->bindParam(count($params) + 2, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get task statistics - Use simpler queries to avoid join errors
        $statsQuery = "
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'to_do' THEN 1 ELSE 0 END) as todo_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_count,
                SUM(CASE WHEN deadline < CURDATE() AND status != 'done' THEN 1 ELSE 0 END) as overdue_count
            FROM tasks
        ";
        $stmt = $conn->prepare($statsQuery);
        $stmt->execute();
        $taskStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get departments for filter dropdown
        $departments = $eventsManager->getDepartments();
        
        // Get users for filter dropdown (only get active users)
        $stmt = $conn->prepare("SELECT user_id, username, first_name, last_name FROM users WHERE is_active = 1 ORDER BY username");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Display tasks dashboard
        ?>
        <div class="container mx-auto px-4 py-6">
            <!-- Task Management Header with Todoist-like Layout -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold" style="color: var(--color-text-primary);">Tasks</h1>
                    <p class="text-sm" style="color: var(--color-text-secondary);">Manage your team's tasks and track progress</p>
                </div>
                <div class="mt-4 md:mt-0 flex flex-wrap items-center gap-3">
                    <!-- Search Bar -->
                    <div class="relative">
                        <input type="text" id="task-search" placeholder="Search tasks..." class="pl-10 pr-4 py-2 rounded-md text-sm w-48"
                               style="background-color: var(--color-background-variant); border: 1px solid var(--color-border-light); color: var(--color-text-primary);">
                        <i class="fas fa-search absolute left-3 top-2.5" style="color: var(--color-text-tertiary);"></i>
                    </div>
                    
                    <!-- Add Task Button -->
                    <a href="?page=lead_tasks&action=add" class="px-4 py-2 rounded-md text-sm text-white transition-colors duration-200 hover:bg-indigo-700 flex items-center"
                       style="background-color: var(--color-primary);">
                        <i class="fas fa-plus mr-2"></i> Add Task
                    </a>
                </div>
            </div>
            
            <!-- Task Filters -->
            <div class="google-card p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-center">
                    <!-- Category/Department Filter -->
                    <div class="flex items-center">
                        <label for="filter-category" class="text-sm mr-2" style="color: var(--color-text-secondary);">Department:</label>
                        <select id="filter-category" class="text-sm py-1 px-2 rounded-md border"
                                style="background-color: var(--color-background-variant); border-color: var(--color-border-light); color: var(--color-text-primary);">
                            <option value="all">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>" 
                                    <?php echo (isset($_GET['department']) && $_GET['department'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Priority Filter -->
                    <div class="flex items-center">
                        <label for="filter-priority" class="text-sm mr-2" style="color: var(--color-text-secondary);">Priority:</label>
                        <select id="filter-priority" class="text-sm py-1 px-2 rounded-md border"
                                style="background-color: var(--color-background-variant); border-color: var(--color-border-light); color: var(--color-text-primary);">
                            <option value="all">All Priorities</option>
                            <option value="high" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'high') ? 'selected' : ''; ?>>High</option>
                            <option value="medium" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="low" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>
                    
                    <!-- Assignee Filter -->
                    <div class="flex items-center">
                        <label for="filter-assignee" class="text-sm mr-2" style="color: var(--color-text-secondary);">Assigned to:</label>
                        <select id="filter-assignee" class="text-sm py-1 px-2 rounded-md border"
                                style="background-color: var(--color-background-variant); border-color: var(--color-border-light); color: var(--color-text-primary);">
                            <option value="all">All Users</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>"
                                    <?php echo (isset($_GET['assigned_to']) && $_GET['assigned_to'] == $user['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Apply Filters Button -->
                    <button id="apply-filters-btn" class="px-3 py-1.5 rounded-md text-sm text-white"
                            style="background-color: var(--color-primary);">Apply Filters</button>
                    
                    <!-- Reset Filters Button -->
                    <button id="reset-filters-btn" class="px-3 py-1.5 rounded-md text-sm"
                            style="background-color: var(--color-hover); color: var(--color-text-primary);">Reset</button>
                </div>
            </div>
            
            <!-- Task Statistics Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                <div class="google-card p-4">
                    <div class="text-lg font-bold" style="color: var(--color-text-primary);"><?php echo $taskStats['total_tasks']; ?></div>
                    <div class="text-sm" style="color: var(--color-text-secondary);">Total Tasks</div>
                </div>
                <div class="google-card p-4">
                    <div class="text-lg font-bold" style="color: var(--color-text-primary);"><?php echo $taskStats['todo_count']; ?></div>
                    <div class="text-sm" style="color: var(--color-text-secondary);">To Do</div>
                </div>
                <div class="google-card p-4">
                    <div class="text-lg font-bold" style="color: var(--color-text-primary);"><?php echo $taskStats['in_progress_count']; ?></div>
                    <div class="text-sm" style="color: var(--color-text-secondary);">In Progress</div>
                </div>
                <div class="google-card p-4">
                    <div class="text-lg font-bold" style="color: var(--color-text-primary);"><?php echo $taskStats['completed_count']; ?></div>
                    <div class="text-sm" style="color: var(--color-text-secondary);">Completed</div>
                </div>
                <div class="google-card p-4">
                    <div class="text-lg font-bold" style="color: #EA4335;"><?php echo $taskStats['overdue_count']; ?></div>
                    <div class="text-sm" style="color: var(--color-text-secondary);">Overdue</div>
                </div>
            </div>
            
            <!-- View Toggle -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-2">
                    <a href="?page=lead_tasks&view=board" class="px-3 py-2 rounded-md text-sm transition-colors duration-200 <?php echo $view === 'board' ? 'text-white' : ''; ?>"
                       style="background-color: <?php echo $view === 'board' ? 'var(--color-primary)' : 'var(--color-hover)'; ?>; color: <?php echo $view === 'board' ? 'white' : 'var(--color-text-primary)'; ?>;">
                        <i class="fas fa-columns mr-1"></i> Board View
                    </a>
                    <a href="?page=lead_tasks&view=list" class="px-3 py-2 rounded-md text-sm transition-colors duration-200 <?php echo $view === 'list' ? 'text-white' : ''; ?>"
                       style="background-color: <?php echo $view === 'list' ? 'var(--color-primary)' : 'var(--color-hover)'; ?>; color: <?php echo $view === 'list' ? 'white' : 'var(--color-text-primary)'; ?>;">
                        <i class="fas fa-list mr-1"></i> List View
                    </a>
                </div>
                <div class="flex items-center space-x-2">
                    <?php if ($view === 'board'): ?>
                    <button id="add-column-btn" class="px-3 py-2 rounded-md text-sm transition-colors duration-200"
                            style="background-color: var(--color-hover); color: var(--color-text-primary);">
                        <i class="fas fa-plus-circle mr-1"></i> Add Section
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($view === 'board'): ?>
            <!-- Todoist-style Board View -->
            <div class="board-view overflow-x-auto">
                <div class="flex gap-4 min-w-max pb-4">
                    <?php foreach ($statuses as $statusKey => $statusProps): ?>
                    <div class="status-column <?php echo $statusKey; ?> w-80" data-status="<?php echo $statusKey; ?>">
                        <div class="google-card">
                            <div class="status-header p-3 border-b" style="border-color: var(--color-border-light);">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="rounded-full p-1 mr-2" style="background-color: <?php echo $statusProps['bg_color']; ?>;">
                                            <i class="fas <?php echo $statusProps['icon']; ?> text-sm" style="color: <?php echo $statusProps['color']; ?>;"></i>
                                        </div>
                                        <h3 class="text-md font-medium" style="color: var(--color-text-primary);"><?php echo $statusProps['name']; ?></h3>
                                        <?php 
                                            $statusCount = 0;
                                            foreach ($tasks as $task) {
                                                if ($task['status'] === $statusKey) $statusCount++;
                                            }
                                        ?>
                                        <span class="task-count text-xs font-medium ml-2 px-2 py-0.5 rounded-full" style="background-color: <?php echo $statusProps['bg_color']; ?>; color: <?php echo $statusProps['color']; ?>;">
                                            <?php echo $statusCount; ?>
                                        </span>
                                    </div>
                                    <button class="add-task-btn rounded-full p-2 transition-colors duration-200" 
                                            data-status="<?php echo $statusKey; ?>" style="color: <?php echo $statusProps['color']; ?>; hover:background-color: var(--color-hover);">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="task-list p-3" data-status="<?php echo $statusKey; ?>" style="height: calc(100vh - 300px); overflow-y: auto;">
                                <?php foreach ($tasks as $task): 
                                    if ($task['status'] === $statusKey):
                                        // Check if task is overdue
                                        $isOverdue = (!empty($task['deadline']) && strtotime($task['deadline']) < time() && $task['status'] != 'done');
                                        
                                        // Set priority styling
                                        $priorityColor = '';
                                        $priorityBg = '';
                                        switch ($task['priority']) {
                                            case 'high':
                                                $priorityColor = '#EA4335';
                                                $priorityBg = 'rgba(234, 67, 53, 0.1)';
                                                break;
                                            case 'medium':
                                                $priorityColor = '#FBBC05';
                                                $priorityBg = 'rgba(251, 188, 5, 0.1)';
                                                break;
                                            case 'low':
                                                $priorityColor = '#34A853';
                                                $priorityBg = 'rgba(52, 168, 83, 0.1)';
                                                break;
                                            default:
                                                $priorityColor = '#757575';
                                                $priorityBg = 'rgba(117, 117, 117, 0.1)';
                                        }
                                        
                                        // Get department color if available
                                        $deptBg = '';
                                        $deptColor = '';
                                        $hasCategory = false;
                                        if (!empty($task['department_name'])) {
                                            $deptColor = '#4285F4'; // Default to blue
                                            $deptBg = 'rgba(66, 133, 244, 0.1)';
                                            $hasCategory = true;
                                        }
                                ?>
                                <div class="task-card mb-3 p-3 rounded-md shadow-sm cursor-grab task-item" 
                                     data-task-id="<?php echo $task['task_id']; ?>" data-status="<?php echo $statusKey; ?>"
                                     data-category="<?php echo !empty($task['department_id']) ? $task['department_id'] : 'none'; ?>"
                                     style="background-color: var(--color-surface); border-left: 4px solid <?php echo $statusProps['color']; ?>;">
                                    
                                    <!-- Task Header -->
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="text-sm font-medium" style="color: var(--color-text-primary);">
                                            <?php echo htmlspecialchars($task['title']); ?>
                                        </h4>
                                        <div class="task-actions flex space-x-2">
                                            <a href="?page=lead_tasks&action=edit&task_id=<?php echo $task['task_id']; ?>" class="text-xs" style="color: #FBBC05;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?page=lead_tasks&action=view&task_id=<?php echo $task['task_id']; ?>" class="text-xs" style="color: #4285F4;">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Task Categories/Tags -->
                                    <?php if ($hasCategory): ?>
                                    <div class="mb-2">
                                        <span class="inline-block px-2 py-0.5 text-xs rounded-full" 
                                              style="background-color: <?php echo $deptBg; ?>; color: <?php echo $deptColor; ?>;">
                                            <?php echo htmlspecialchars($task['department_name']); ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Task Description (truncated) -->
                                    <?php if (!empty($task['description'])): ?>
                                    <div class="mb-2">
                                        <p class="text-xs" style="color: var(--color-text-secondary);">
                                            <?php echo substr(htmlspecialchars($task['description']), 0, 100) . (strlen($task['description']) > 100 ? '...' : ''); ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Task Metadata -->
                                    <div class="flex justify-between items-center mt-3">
                                        <!-- Priority Badge -->
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full" 
                                              style="background-color: <?php echo $priorityBg; ?>; color: <?php echo $priorityColor; ?>;">
                                            <?php echo ucfirst($task['priority']); ?>
                                        </span>
                                        
                                        <div class="flex items-center space-x-2">
                                            <!-- Deadline Badge -->
                                            <?php if (!empty($task['deadline'])): ?>
                                            <div class="flex items-center">
                                                <i class="far fa-calendar-alt text-xs mr-1" style="color: <?php echo $isOverdue ? '#EA4335' : 'var(--color-text-tertiary)'; ?>;"></i>
                                                <span class="text-xs" style="color: <?php echo $isOverdue ? '#EA4335' : 'var(--color-text-tertiary)'; ?>;">
                                                    <?php echo date('M d', strtotime($task['deadline'])); ?>
                                                    <?php echo $isOverdue ? '!' : ''; ?>
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Assignee Badge -->
                                            <?php if (!empty($task['assigned_name']) || !empty($task['assigned_username'])): ?>
                                            <div class="flex items-center">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center" 
                                                     style="background-color: var(--color-primary); color: white;">
                                                    <span class="text-xs">
                                                        <?php 
                                                            $initials = '';
                                                            if (!empty($task['assigned_name'])) {
                                                                $nameArr = explode(' ', $task['assigned_name']);
                                                                if (isset($nameArr[0])) $initials .= strtoupper(substr($nameArr[0], 0, 1));
                                                                if (isset($nameArr[1])) $initials .= strtoupper(substr($nameArr[1], 0, 1));
                                                            } elseif (!empty($task['assigned_username'])) {
                                                                $initials = strtoupper(substr($task['assigned_username'], 0, 1));
                                                            }
                                                            echo $initials;
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; endforeach; ?>
                                
                                <!-- Empty State -->
                                <?php if ($statusCount === 0): ?>
                                <div class="empty-state text-center py-4">
                                    <p class="text-sm" style="color: var(--color-text-tertiary);">
                                        No tasks in this section yet
                                    </p>
                                    <button class="add-task-btn mt-2 px-3 py-1 rounded-md text-xs" 
                                            data-status="<?php echo $statusKey; ?>"
                                            style="background-color: var(--color-hover); color: var(--color-text-secondary);">
                                        <i class="fas fa-plus mr-1"></i> Add Task
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Include SortableJS library -->
            <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
            
            <!-- Include our custom task manager script -->
            <script src="public/assets/js/task-manager.js"></script>
            
            <script>
                // Filter functionality
                document.getElementById('apply-filters-btn').addEventListener('click', function() {
                    const department = document.getElementById('filter-category').value;
                    const priority = document.getElementById('filter-priority').value;
                    const assignee = document.getElementById('filter-assignee').value;
                    
                    let url = new URL(window.location.href);
                    url.searchParams.set('view', '<?php echo $view; ?>');
                    
                    if (department !== 'all') url.searchParams.set('department', department);
                    else url.searchParams.delete('department');
                    
                    if (priority !== 'all') url.searchParams.set('priority', priority);
                    else url.searchParams.delete('priority');
                    
                    if (assignee !== 'all') url.searchParams.set('assigned_to', assignee);
                    else url.searchParams.delete('assigned_to');
                    
                    window.location.href = url.toString();
                });
                
                document.getElementById('reset-filters-btn').addEventListener('click', function() {
                    window.location.href = '?page=lead_tasks&view=<?php echo $view; ?>';
                });
            </script>
            <?php else: ?>
            <!-- List View -->
            <div class="google-card overflow-hidden">
                <table class="min-w-full">
                    <thead>
                        <tr style="background-color: var(--color-hover);">
                            <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Task</th>
                            <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Priority</th>
                            <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Deadline</th>
                            <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Assigned To</th>
                            <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Related Event</th>
                            <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): 
                            // Determine status colors
                            $statusBg = '';
                            $statusColor = '';
                            $statusProps = $statuses[$task['status']] ?? null;
                            if ($statusProps) {
                                $statusBg = $statusProps['bg_color'];
                                $statusColor = $statusProps['color'];
                            }
                            
                            // Determine priority colors
                            $priorityBg = '';
                            $priorityColor = '';
                            switch ($task['priority']) {
                                case 'high':
                                    $priorityBg = 'rgba(234, 67, 53, 0.1)';
                                    $priorityColor = '#EA4335';
                                    break;
                                case 'medium':
                                    $priorityBg = 'rgba(251, 188, 5, 0.1)';
                                    $priorityColor = '#FBBC05';
                                    break;
                                case 'low':
                                    $priorityBg = 'rgba(52, 168, 83, 0.1)';
                                    $priorityColor = '#34A853';
                                    break;
                                default:
                                    $priorityBg = 'rgba(117, 117, 117, 0.1)';
                                    $priorityColor = '#757575';
                            }
                            
                            // Check if task is overdue
                            $isOverdue = (!empty($task['deadline']) && $task['status'] != 'done' && strtotime($task['deadline']) < time());
                        ?>
                        <tr class="border-t task-row" style="border-color: var(--color-border-light);">
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="rounded-full p-2 mr-3" style="background-color: <?php echo $statusBg; ?>;">
                                        <i class="fas <?php echo $statusProps['icon'] ?? 'fa-bookmark'; ?>" style="color: <?php echo $statusColor; ?>;"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm task-title" style="color: var(--color-text-primary);">
                                            <?php echo htmlspecialchars($task['title']); ?>
                                        </div>
                                        <div class="text-xs" style="color: var(--color-text-tertiary);">
                                            <?php echo htmlspecialchars($task['department_name'] ?? 'No Department'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full" 
                                      style="background-color: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>;">
                                    <?php echo $statusProps['name'] ?? ucfirst($task['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full" 
                                      style="background-color: <?php echo $priorityBg; ?>; color: <?php echo $priorityColor; ?>;">
                                    <?php echo ucfirst($task['priority']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm" style="color: <?php echo $isOverdue ? '#EA4335' : 'var(--color-text-secondary)'; ?>;">
                                    <?php 
                                    if (!empty($task['deadline'])) {
                                        echo date('M d, Y', strtotime($task['deadline']));
                                        if ($isOverdue) {
                                            echo ' <span class="text-xs font-medium" style="color: #EA4335;">(Overdue)</span>';
                                        }
                                    } else {
                                        echo '<span class="text-xs" style="color: var(--color-text-tertiary);">No deadline</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm" style="color: var(--color-text-secondary);">
                                    <?php 
                                    if (!empty($task['assigned_name'])) {
                                        echo htmlspecialchars($task['assigned_name']);
                                    } elseif (!empty($task['assigned_username'])) {
                                        echo htmlspecialchars($task['assigned_username']);
                                    } else {
                                        echo '<span class="text-xs" style="color: var(--color-text-tertiary);">Unassigned</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm" style="color: var(--color-text-secondary);">
                                    <?php 
                                    if (!empty($task['event_title'])) {
                                        echo '<a href="?page=lead_events&event_id=' . $task['event_id'] . '" style="color: #4285F4;">' . 
                                            htmlspecialchars($task['event_title']) . '</a>';
                                    } else {
                                        echo '<span class="text-xs" style="color: var(--color-text-tertiary);">No event</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex space-x-3">
                                    <a href="?page=lead_tasks&action=view&task_id=<?php echo $task['task_id']; ?>" class="text-sm" style="color: #4285F4;" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?page=lead_tasks&action=edit&task_id=<?php echo $task['task_id']; ?>" class="text-sm" style="color: #FBBC05;" title="Edit Task">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0);" class="text-sm delete-task" data-id="<?php echo $task['task_id']; ?>" style="color: #EA4335;" title="Delete Task">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="flex justify-center mt-6">
                <div class="flex space-x-1">
                    <?php if ($page > 1): ?>
                    <a href="?page=lead_tasks&view=list&page_num=<?php echo ($page - 1); ?><?php echo !empty($filter) ? '&filter=' . htmlspecialchars($filter) : ''; ?><?php echo !empty($search) ? '&search=' . htmlspecialchars($search) : ''; ?>" class="px-3 py-1 rounded-md text-sm" style="background-color: var(--color-hover); color: var(--color-text-primary);">
                        Previous
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=lead_tasks&view=list&page_num=<?php echo $i; ?><?php echo !empty($filter) ? '&filter=' . htmlspecialchars($filter) : ''; ?><?php echo !empty($search) ? '&search=' . htmlspecialchars($search) : ''; ?>" class="px-3 py-1 rounded-md text-sm <?php echo $i == $page ? 'font-medium' : ''; ?>" style="<?php echo $i == $page ? 'background-color: var(--color-primary); color: white;' : 'background-color: var(--color-hover); color: var(--color-text-primary);'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=lead_tasks&view=list&page_num=<?php echo ($page + 1); ?><?php echo !empty($filter) ? '&filter=' . htmlspecialchars($filter) : ''; ?><?php echo !empty($search) ? '&search=' . htmlspecialchars($search) : ''; ?>" class="px-3 py-1 rounded-md text-sm" style="background-color: var(--color-hover); color: var(--color-text-primary);">
                        Next
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Delete Task Confirmation Modal -->
        <div id="deleteTaskModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
            <div class="google-card p-6 max-w-md w-full">
                <h3 class="text-xl font-bold mb-4" style="color: var(--color-text-primary);">Confirm Delete</h3>
                <p class="mb-6" style="color: var(--color-text-secondary);">Are you sure you want to delete this task? This action cannot be undone.</p>
                <div class="flex justify-end space-x-3">
                    <button id="cancelDeleteBtn" class="px-4 py-2 rounded-md text-sm" style="background-color: var(--color-hover); color: var(--color-text-primary);">Cancel</button>
                    <button id="confirmDeleteBtn" class="px-4 py-2 rounded-md text-sm text-white" style="background-color: #EA4335;">Delete</button>
                </div>
            </div>
        </div>
        
        <script>
            // Delete task functionality
            document.addEventListener('DOMContentLoaded', function() {
                let taskIdToDelete = null;
                const deleteModal = document.getElementById('deleteTaskModal');
                const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
                const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
                
                document.querySelectorAll('.delete-task').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        taskIdToDelete = this.dataset.id;
                        deleteModal.classList.remove('hidden');
                    });
                });
                
                cancelDeleteBtn.addEventListener('click', function() {
                    deleteModal.classList.add('hidden');
                    taskIdToDelete = null;
                });
                
                confirmDeleteBtn.addEventListener('click', function() {
                    if (taskIdToDelete) {
                        window.location.href = `?page=lead_tasks&action=delete&task_id=${taskIdToDelete}`;
                    }
                });
            });
        </script>
        <?php
        break;
}
?>