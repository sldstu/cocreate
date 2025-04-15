<?php
/**
 * Check if a user has permission to access a feature
 * 
 * @param int $user_id The user ID
 * @param string $permission The permission to check
 * @return bool True if user has permission, false otherwise
 */
function user_has_permission($user_id, $permission) {
    global $conn;
    
    // Get user role
    $stmt = $conn->prepare("SELECT r.permissions FROM users u 
                           JOIN roles r ON u.role_id = r.role_id 
                           WHERE u.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $permissions = json_decode($row['permissions'], true);
        
        // Chapter Lead (Admin) has all permissions
        if (isset($permissions['all']) && $permissions['all'] === true) {
            return true;
        }
        
        // Check specific permission
        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }
    
    return false;
}

/**
 * Check if user belongs to a department
 * 
 * @param int $user_id The user ID
 * @param int $department_id The department ID
 * @return bool True if user belongs to department, false otherwise
 */
function user_in_department($user_id, $department_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT department_id FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['department_id'] == $department_id;
    }
    
    return false;
}

/**
 * Get user role name
 * 
 * @param int $user_id The user ID
 * @return string The role name
 */
function get_user_role($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT r.name FROM users u 
                           JOIN roles r ON u.role_id = r.role_id 
                           WHERE u.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['name'];
    }
    
    return 'Unknown';
}
?>
