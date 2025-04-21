<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Initialize variables
$success_message = '';
$error_message = '';
$users = [];
$roles = [];
$departments = [];
$total_users = 0;
$active_users = 0;
$inactive_users = 0;
$search_query = '';
$current_page = 1;
$users_per_page = 10;
$total_pages = 1;
$role_filter = '';
$department_filter = '';
$is_active_filter = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $role_id = intval($_POST['role_id'] ?? 0);
        $department_id = intval($_POST['department_id'] ?? 0);
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        // Validate input
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error_message = 'Username, email, and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long.';
        } else {
            try {
                // Check if username or email already exists
                $query = "SELECT user_id FROM users WHERE username = ? OR email = ?";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(1, $username, PDO::PARAM_STR);
                $stmt->bindParam(2, $email, PDO::PARAM_STR);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error_message = 'Username or email already exists.';
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    $query = "INSERT INTO users (username, email, first_name, last_name, password, role_id, department_id, is_active, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(1, $username, PDO::PARAM_STR);
                    $stmt->bindParam(2, $email, PDO::PARAM_STR);
                    $stmt->bindParam(3, $first_name, PDO::PARAM_STR);
                    $stmt->bindParam(4, $last_name, PDO::PARAM_STR);
                    $stmt->bindParam(5, $hashed_password, PDO::PARAM_STR);
                    $stmt->bindParam(6, $role_id, PDO::PARAM_INT);
                    $stmt->bindParam(7, $department_id, PDO::PARAM_INT);
                    
                    if ($stmt->execute()) {
                        $success_message = 'User added successfully.';
                        
                        // Log the action
                        logAction($_SESSION['user_id'], 'Added new user: ' . $username);
                    } else {
                        $error_message = 'Failed to add user.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error_message = 'An error occurred while adding user.';
            }
        }
    }
    
    // Update user
    elseif (isset($_POST['update_user'])) {
        $user_id = intval($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $role_id = intval($_POST['role_id'] ?? 0);
        $department_id = intval($_POST['department_id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 1);
        
        // Validate input
        if (empty($username) || empty($email)) {
            $error_message = 'Username and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            try {
                // Check if username or email already exists for another user
                $query = "SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(1, $username, PDO::PARAM_STR);
                $stmt->bindParam(2, $email, PDO::PARAM_STR);
                $stmt->bindParam(3, $user_id, PDO::PARAM_INT);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error_message = 'Username or email already exists for another user.';
                } else {
                    // Update user
                    $query = "UPDATE users SET 
                              username = ?, 
                              email = ?, 
                              first_name = ?, 
                              last_name = ?, 
                              role_id = ?, 
                              department_id = ?, 
                              is_active = ?,
                              updated_at = NOW() 
                              WHERE user_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(1, $username, PDO::PARAM_STR);
                    $stmt->bindParam(2, $email, PDO::PARAM_STR);
                    $stmt->bindParam(3, $first_name, PDO::PARAM_STR);
                    $stmt->bindParam(4, $last_name, PDO::PARAM_STR);
                    $stmt->bindParam(5, $role_id, PDO::PARAM_INT);
                    $stmt->bindParam(6, $department_id, PDO::PARAM_INT);
                    $stmt->bindParam(7, $is_active, PDO::PARAM_INT);
                    $stmt->bindParam(8, $user_id, PDO::PARAM_INT);
                    
                    if ($stmt->execute()) {
                        $success_message = 'User updated successfully.';
                        
                        // Log the action
                        logAction($_SESSION['user_id'], 'Updated user: ' . $username);
                    } else {
                        $error_message = 'Failed to update user.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error_message = 'An error occurred while updating user.';
            }
        }
    }
    
    // Reset password
    elseif (isset($_POST['reset_password'])) {
        $user_id = intval($_POST['user_id'] ?? 0);
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        // Validate input
        if (empty($new_password) || empty($confirm_password)) {
            $error_message = 'New password and confirmation are required.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'Password must be at least 6 characters long.';
        } else {
            try {
                // Hash password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $query = "UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(1, $hashed_password, PDO::PARAM_STR);
                $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $success_message = 'Password reset successfully.';
                    
                    // Log the action
                    logAction($_SESSION['user_id'], 'Reset password for user ID: ' . $user_id);
                } else {
                    $error_message = 'Failed to reset password.';
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error_message = 'An error occurred while resetting password.';
            }
        }
    }
    
    // Delete user
    elseif (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id'] ?? 0);
        
        // Prevent deleting self
        if ($user_id === intval($_SESSION['user_id'])) {
            $error_message = 'You cannot delete your own account.';
        } else {
            try {
                // Get username for logging
                $query = "SELECT username FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $username = $user ? $user['username'] : 'Unknown';
                
                // Delete user
                $query = "DELETE FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $success_message = 'User deleted successfully.';
                    
                    // Log the action
                    logAction($_SESSION['user_id'], 'Deleted user: ' . $username);
                } else {
                    $error_message = 'Failed to delete user.';
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error_message = 'An error occurred while deleting user.';
            }
        }
    }
    
    // Bulk action
    elseif (isset($_POST['bulk_action']) && isset($_POST['selected_users'])) {
        $action = $_POST['bulk_action'];
        $selected_users = $_POST['selected_users'];
        
        if (empty($selected_users)) {
            $error_message = 'No users selected.';
        } else {
            try {
                switch ($action) {
                    case 'activate':
                        $query = "UPDATE users SET is_active = 1, updated_at = NOW() WHERE user_id IN (" . implode(',', array_map('intval', $selected_users)) . ")";
                        $stmt = $conn->prepare($query);
                        if ($stmt->execute()) {
                            $success_message = count($selected_users) . ' users activated successfully.';
                            logAction($_SESSION['user_id'], 'Activated ' . count($selected_users) . ' users');
                        } else {
                            $error_message = 'Failed to activate users.';
                        }
                        break;
                        
                    case 'deactivate':
                        // Prevent deactivating self
                        if (in_array($_SESSION['user_id'], $selected_users)) {
                            $error_message = 'You cannot deactivate your own account.';
                        } else {
                            $query = "UPDATE users SET is_active = 0, updated_at = NOW() WHERE user_id IN (" . implode(',', array_map('intval', $selected_users)) . ")";
                            $stmt = $conn->prepare($query);
                            if ($stmt->execute()) {
                                $success_message = count($selected_users) . ' users deactivated successfully.';
                                logAction($_SESSION['user_id'], 'Deactivated ' . count($selected_users) . ' users');
                            } else {
                                $error_message = 'Failed to deactivate users.';
                            }
                        }
                        break;
                        
                    case 'delete':
                        // Prevent deleting self
                        if (in_array($_SESSION['user_id'], $selected_users)) {
                            $error_message = 'You cannot delete your own account.';
                        } else {
                            $query = "DELETE FROM users WHERE user_id IN (" . implode(',', array_map('intval', $selected_users)) . ")";
                            $stmt = $conn->prepare($query);
                            if ($stmt->execute()) {
                                $success_message = count($selected_users) . ' users deleted successfully.';
                                logAction($_SESSION['user_id'], 'Deleted ' . count($selected_users) . ' users');
                            } else {
                                $error_message = 'Failed to delete users.';
                            }
                        }
                        break;
                        
                    case 'assign_role':
                        $role_id = intval($_POST['bulk_role_id'] ?? 0);
                        if ($role_id > 0) {
                            $query = "UPDATE users SET role_id = ?, updated_at = NOW() WHERE user_id IN (" . implode(',', array_map('intval', $selected_users)) . ")";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(1, $role_id, PDO::PARAM_INT);
                            if ($stmt->execute()) {
                                $success_message = 'Role assigned to ' . count($selected_users) . ' users successfully.';
                                logAction($_SESSION['user_id'], 'Assigned role ID ' . $role_id . ' to ' . count($selected_users) . ' users');
                            } else {
                                $error_message = 'Failed to assign role to users.';
                            }
                        } else {
                            $error_message = 'Invalid role selected.';
                        }
                        break;
                        
                    case 'assign_department':
                        $department_id = intval($_POST['bulk_department_id'] ?? 0);
                        if ($department_id > 0) {
                            $query = "UPDATE users SET department_id = ?, updated_at = NOW() WHERE user_id IN (" . implode(',', array_map('intval', $selected_users)) . ")";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(1, $department_id, PDO::PARAM_INT);
                            if ($stmt->execute()) {
                                $success_message = 'Department assigned to ' . count($selected_users) . ' users successfully.';
                                logAction($_SESSION['user_id'], 'Assigned department ID ' . $department_id . ' to ' . count($selected_users) . ' users');
                            } else {
                                $error_message = 'Failed to assign department to users.';
                            }
                        } else {
                            $error_message = 'Invalid department selected.';
                        }
                        break;
                        
                    default:
                        $error_message = 'Invalid action selected.';
                        break;
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error_message = 'An error occurred while performing bulk action.';
            }
        }
    }
}

// Get search and filter parameters
$search_query = $_GET['search'] ?? '';
$role_filter = isset($_GET['role']) && $_GET['role'] !== '' ? intval($_GET['role']) : '';
$department_filter = isset($_GET['department']) && $_GET['department'] !== '' ? intval($_GET['department']) : '';
$is_active_filter = $_GET['is_active'] ?? '';
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page < 1) $current_page = 1;

// Get roles
try {
    $query = "SELECT * FROM roles ORDER BY role_id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Get departments
try {
    $query = "SELECT * FROM departments ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Get user statistics
try {
    // Total users
    $query = "SELECT COUNT(*) as count FROM users";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active users
    $query = "SELECT COUNT(*) as count FROM users WHERE is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $active_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Inactive users
    $query = "SELECT COUNT(*) as count FROM users WHERE is_active = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $inactive_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total departments
    $query = "SELECT COUNT(*) as count FROM departments";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $total_departments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Get users with filters
try {
    // Build query
    $query = "SELECT u.*, r.name as role_name, d.name as department_name 
              FROM users u 
              LEFT JOIN roles r ON u.role_id = r.role_id 
              LEFT JOIN departments d ON u.department_id = d.department_id";
    
    $where_clauses = [];
    $params = [];
    
    if (!empty($search_query)) {
        $where_clauses[] = "(u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if ($role_filter !== '') {
        $where_clauses[] = "u.role_id = ?";
        $params[] = $role_filter;
    }
    
    if ($department_filter !== '') {
        $where_clauses[] = "u.department_id = ?";
        $params[] = $department_filter;
    }
    
    if ($is_active_filter !== '') {
        $where_clauses[] = "u.is_active = ?";
        $params[] = $is_active_filter;
    }
    
    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Count total filtered users
    $count_query = "SELECT COUNT(*) as count FROM ($query) as filtered_users";
    $stmt = $conn->prepare($count_query);
    foreach ($params as $i => $param) {
        $stmt->bindValue($i + 1, $param);
    }
    $stmt->execute();
    $total_filtered_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Calculate pagination
    $total_pages = ceil($total_filtered_users / $users_per_page);
    if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
    $offset = ($current_page - 1) * $users_per_page;
    
    // Get paginated users
    $query .= " ORDER BY u.username ASC LIMIT $offset, $users_per_page";
    $stmt = $conn->prepare($query);
    foreach ($params as $i => $param) {
        $stmt->bindValue($i + 1, $param);
    }
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>