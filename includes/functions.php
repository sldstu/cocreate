<?php
/**
 * Common functions for the Event Management System
 */

/**
 * Format a date for display
 * 
 * @param string $date The date to format
 * @param string $format The format to use (default: 'M d, Y h:i A')
 * @return string The formatted date
 */
function formatDate($date, $format = 'M d, Y h:i A') {
    return date($format, strtotime($date));
}

/**
 * Get the role name for a given role ID
 * 
 * @param int $roleId The role ID
 * @return string The role name
 */
function getUserRoleName($roleId) {
    switch ($roleId) {
        case ROLE_ADMIN:
            return 'Administrator';
        case ROLE_DEPARTMENT_LEAD:
            return 'Department Lead';
        case ROLE_OFFICER:
            return 'Officer';
        default:
            return 'Unknown';
    }
}

/**
 * Get the department name for a given department ID
 * 
 * @param int $departmentId The department ID
 * @param PDO $conn Database connection
 * @return string The department name
 */
function getDepartmentName($departmentId, $conn) {
    try {
        $query = "SELECT name FROM departments WHERE department_id = :department_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':department_id', $departmentId);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['name'];
        }
    } catch (PDOException $e) {
        // Handle error
        error_log("Database error: " . $e->getMessage());
    }
    
    return 'Unknown';
}

/**
 * Get the status label for a given status
 * 
 * @param string $status The status code
 * @return string The status label
 */
function getStatusLabel($status) {
    switch ($status) {
        case 'not_started':
            return 'Not Started';
        case 'in_progress':
            return 'In Progress';
        case 'review':
            return 'In Review';
        case 'done':
            return 'Done';
        default:
            return 'Unknown';
    }
}

/**
 * Get the priority label for a given priority
 * 
 * @param string $priority The priority code
 * @return string The priority label
 */
function getPriorityLabel($priority) {
    switch ($priority) {
        case 'low':
            return 'Low';
        case 'medium':
            return 'Medium';
        case 'high':
            return 'High';
        case 'urgent':
            return 'Urgent';
        default:
            return 'Unknown';
    }
}

/**
 * Truncate a string to a specified length
 * 
 * @param string $string The string to truncate
 * @param int $length The maximum length
 * @param string $append The string to append if truncated
 * @return string The truncated string
 */
function truncateString($string, $length = 100, $append = '...') {
    if (strlen($string) > $length) {
        return substr($string, 0, $length) . $append;
    }
    
    return $string;
}

/**
 * Check if a user has permission to perform an action
 * 
 * @param array $user The user array
 * @param string $permission The permission to check
 * @return bool True if the user has permission, false otherwise
 */
function hasPermission($user, $permission) {
    // Admin has all permissions
    if ($user['role_id'] == ROLE_ADMIN) {
        return true;
    }
    
    // Department Lead permissions
    if ($user['role_id'] == ROLE_DEPARTMENT_LEAD) {
        $departmentLeadPermissions = [
            'view_events',
            'create_event',
            'edit_event',
            'delete_event',
            'view_tasks',
            'create_task',
            'edit_task',
            'delete_task',
            'assign_task',
            'view_department_members',
            'create_announcement',
            'edit_announcement',
            'delete_announcement'
        ];
        
        return in_array($permission, $departmentLeadPermissions);
    }
    
    // Officer permissions
    if ($user['role_id'] == ROLE_OFFICER) {
        $officerPermissions = [
            'view_events',
            'view_tasks',
            'update_task_status',
            'view_announcements'
        ];
        
        return in_array($permission, $officerPermissions);
    }
    
    return false;
}

/**
 * Department and Role Color Coding System
 */

// Department color mapping
$departmentColors = [
    // Executive (Leadership)
    1 => [
        'name' => 'Executive',
        'color' => '#673AB7', // Deep Purple
        'light_bg' => 'rgba(103, 58, 183, 0.1)',
        'icon' => 'fas fa-star'
    ],
    // Marketing
    2 => [
        'name' => 'Marketing',
        'color' => '#009688', // Teal
        'light_bg' => 'rgba(0, 150, 136, 0.1)',
        'icon' => 'fas fa-bullhorn'
    ],
    // Operations
    3 => [
        'name' => 'Operations',
        'color' => '#E91E63', // Pink/Crimson
        'light_bg' => 'rgba(233, 30, 99, 0.1)',
        'icon' => 'fas fa-cogs'
    ],
    // Community Development
    4 => [
        'name' => 'Community Development',
        'color' => '#FF9800', // Orange
        'light_bg' => 'rgba(255, 152, 0, 0.1)',
        'icon' => 'fas fa-coins'
    ],
    // Research & Development (if added later)
    5 => [
        'name' => 'Research & Development',
        'color' => '#3F51B5', // Indigo
        'light_bg' => 'rgba(63, 81, 181, 0.1)',
        'icon' => 'fas fa-flask'
    ],
];

// Role color mapping
$roleColors = [
    // Admin/CEO/Chapter Lead (ROLE_ADMIN)
    1 => [
        'name' => 'Chapter Lead',
        'color' => '#673AB7', // Deep Purple (matching Executive)
        'light_bg' => 'rgba(103, 58, 183, 0.1)',
        'icon' => 'fas fa-crown'
    ],
    // Department Lead (ROLE_DEPARTMENT_LEAD)
    2 => [
        'name' => 'Department Lead',
        'color' => '#3F51B5', // Indigo
        'light_bg' => 'rgba(63, 81, 181, 0.1)',
        'icon' => 'fas fa-user-tie'
    ],
    // Officer (ROLE_OFFICER)
    3 => [
        'name' => 'Officer',
        'color' => '#00BCD4', // Cyan
        'light_bg' => 'rgba(0, 188, 212, 0.1)',
        'icon' => 'fas fa-user-cog'
    ],
    // Member (ROLE_MEMBER)
    4 => [
        'name' => 'Member',
        'color' => '#4CAF50', // Green
        'light_bg' => 'rgba(76, 175, 80, 0.1)',
        'icon' => 'fas fa-user'
    ]
];

/**
 * Get department color information
 * @param int $departmentId The department ID
 * @return array Department color information
 */
function getDepartmentColor($departmentId) {
    global $departmentColors;
    
    if (isset($departmentColors[$departmentId])) {
        return $departmentColors[$departmentId];
    }
    
    // Default color if department not found
    return [
        'name' => 'Unknown',
        'color' => '#9E9E9E', // Gray
        'light_bg' => 'rgba(158, 158, 158, 0.1)',
        'icon' => 'fas fa-question'
    ];
}

/**
 * Get role color information
 * @param int $roleId The role ID
 * @return array Role color information
 */
function getRoleColor($roleId) {
    global $roleColors;
    
    if (isset($roleColors[$roleId])) {
        return $roleColors[$roleId];
    }
    
    // Default color if role not found
    return [
        'name' => 'Unknown',
        'color' => '#9E9E9E', // Gray
        'light_bg' => 'rgba(158, 158, 158, 0.1)',
        'icon' => 'fas fa-question'
    ];
}

/**
 * Generate HTML for a department badge
 * @param int $departmentId The department ID
 * @return string HTML for the department badge
 */
/**
 * Get a styled badge for a department
 * 
 * @param int $departmentId The department ID
 * @return string HTML for the badge
 */
function getDepartmentBadge($departmentId) {
    global $departmentColors;
    
    // If no department ID, return empty string
    if (empty($departmentId)) {
        return '';
    }
    
    // Default values if department not found
    $deptName = 'Unknown Department';
    $deptColor = '#757575'; // Grey
    $deptLightBg = 'rgba(117, 117, 117, 0.1)';
    $deptIcon = 'fas fa-building';
    
    // Get department details if available
    if (isset($departmentColors[$departmentId])) {
        $deptName = $departmentColors[$departmentId]['name'];
        $deptColor = $departmentColors[$departmentId]['color'];
        $deptLightBg = $departmentColors[$departmentId]['light_bg'];
        $deptIcon = $departmentColors[$departmentId]['icon'];
    } else {
        // Try to get department name from database if not in the color mapping
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "SELECT name FROM departments WHERE department_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $departmentId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $deptName = $row['name'];
            }
        } catch (PDOException $e) {
            // Silently fail and use default name
        }
    }
    
    // Return styled badge
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                  style="background-color: ' . $deptLightBg . '; color: ' . $deptColor . ';">
                <i class="' . $deptIcon . ' mr-1"></i> ' . $deptName . '
            </span>';
}


/**
 * Generate HTML for a role badge
 * @param int $roleId The role ID
 * @return string HTML for the role badge
 */
/**
 * Get a styled badge for a user role
 * 
 * @param int $roleId The role ID
 * @return string HTML for the badge
 */
function getRoleBadge($roleId) {
    global $roleColors;
    
    // Default values if role not found
    $roleName = 'Unknown Role';
    $roleColor = '#757575'; // Grey
    $roleLightBg = 'rgba(117, 117, 117, 0.1)';
    $roleIcon = 'fas fa-user';
    
    // Get role details if available
    if (isset($roleColors[$roleId])) {
        $roleName = $roleColors[$roleId]['name'];
        $roleColor = $roleColors[$roleId]['color'];
        $roleLightBg = $roleColors[$roleId]['light_bg'];
        $roleIcon = $roleColors[$roleId]['icon'];
    }
    
    // Return styled badge
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                  style="background-color: ' . $roleLightBg . '; color: ' . $roleColor . ';">
                <i class="' . $roleIcon . ' mr-1"></i> ' . $roleName . '
            </span>';
}

/**
 * Log user actions to the activity_logs table
 * 
 * @param int $user_id The user ID performing the action
 * @param string $action Description of the action
 * @param string $ip_address IP address (optional)
 * @return bool True if logged successfully, false otherwise
 */
function logAction($user_id, $action, $ip_address = null) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get IP address if not provided
        if ($ip_address === null) {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
        
        // Check if activity_logs table exists
        $tableExists = false;
        try {
            $checkTable = $conn->query("SHOW TABLES LIKE 'activity_logs'");
            $tableExists = ($checkTable->rowCount() > 0);
        } catch (PDOException $e) {
            // Table doesn't exist
            $tableExists = false;
        }
        
        // Create table if it doesn't exist
        if (!$tableExists) {
            $createTableSQL = "CREATE TABLE IF NOT EXISTS `activity_logs` (
                `log_id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `action` text NOT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`log_id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
            
            $conn->exec($createTableSQL);
        }
        
        // Insert log entry
        $query = "INSERT INTO activity_logs (user_id, action, ip_address, created_at) 
                  VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $action, PDO::PARAM_STR);
        $stmt->bindParam(3, $ip_address, PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error logging action: " . $e->getMessage());
        return false;
    }
}
