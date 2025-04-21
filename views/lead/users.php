<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Include the user controller
require_once(dirname(__FILE__) . '/php/users.class.php');
?>

<div class="mb-6">
    <h1 class="text-2xl font-normal mb-1" style="color: var(--color-text-primary);">User Management</h1>
    <p class="text-sm" style="color: var(--color-text-secondary);">
        Manage users, roles, and permissions
    </p>
</div>

<!-- Alert Messages -->
<div id="alert-container">
    <?php if (!empty($success_message)): ?>
    <div class="alert bg-green-100 text-green-800 px-4 py-3 rounded relative mb-4" role="alert">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
        <button type="button" class="absolute top-0 right-0 mt-2 mr-2 text-sm" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="alert bg-red-100 text-red-800 px-4 py-3 rounded relative mb-4" role="alert">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
        <button type="button" class="absolute top-0 right-0 mt-2 mr-2 text-sm" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- User Statistics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Users -->
    <div class="google-card p-5">
        <div class="flex items-center">
            <div class="rounded-full p-3 mr-4" style="background-color: rgba(66, 133, 244, 0.1);">
                <i class="fas fa-users text-xl" style="color: #4285F4;"></i>
            </div>
            <div>
                <p class="text-sm font-medium" style="color: var(--color-text-secondary);">Total Users</p>
                <p class="text-2xl font-medium" style="color: var(--color-text-primary);" id="total-users-count"><?php echo $total_users; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Active Users -->
    <div class="google-card p-5">
        <div class="flex items-center">
            <div class="rounded-full p-3 mr-4" style="background-color: rgba(52, 168, 83, 0.1);">
                <i class="fas fa-user-check text-xl" style="color: #34A853;"></i>
            </div>
            <div>
                <p class="text-sm font-medium" style="color: var(--color-text-secondary);">Active Users</p>
                <p class="text-2xl font-medium" style="color: var(--color-text-primary);" id="active-users-count"><?php echo $active_users; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Inactive Users -->
    <div class="google-card p-5">
        <div class="flex items-center">
            <div class="rounded-full p-3 mr-4" style="background-color: rgba(234, 67, 53, 0.1);">
                <i class="fas fa-user-times text-xl" style="color: #EA4335;"></i>
            </div>
            <div>
                <p class="text-sm font-medium" style="color: var(--color-text-secondary);">Inactive Users</p>
                <p class="text-2xl font-medium" style="color: var(--color-text-primary);" id="inactive-users-count"><?php echo $inactive_users; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Departments -->
    <div class="google-card p-5">
        <div class="flex items-center">
            <div class="rounded-full p-3 mr-4" style="background-color: rgba(251, 188, 5, 0.1);">
                <i class="fas fa-building text-xl" style="color: #FBBC05;"></i>
            </div>
            <div>
                <p class="text-sm font-medium" style="color: var(--color-text-secondary);">Departments</p>
                <p class="text-2xl font-medium" style="color: var(--color-text-primary);"><?php echo $total_departments; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="google-card p-5 mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
        <h2 class="text-lg font-medium mb-4 md:mb-0" style="color: var(--color-text-primary);">Search & Filters</h2>
        
        <div class="relative">
            <form id="search-form" method="get" action="">
                <input type="hidden" name="view" value="users">
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($role_filter); ?>">
                <input type="hidden" name="department" value="<?php echo htmlspecialchars($department_filter); ?>">
                <input type="hidden" name="is_active" value="<?php echo htmlspecialchars($is_active_filter); ?>">
                
                <input type="text" id="search-input" name="search" placeholder="Search users..." class="w-full md:w-64 pl-10 pr-4 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo htmlspecialchars($search_query); ?>">
                <div class="absolute left-3 top-2.5" style="color: var(--color-text-secondary);">
                    <i class="fas fa-search"></i>
                </div>
            </form>
        </div>
    </div>
    
    <div class="border-t pt-4" style="border-color: var(--color-border-light);">
        <form id="filter-form" method="get" action="">
            <input type="hidden" name="view" value="users">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="role-filter" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Role</label>
                    <select id="role-filter" name="role" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['role_id']; ?>" <?php echo $role_filter === intval($role['role_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="department-filter" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Department</label>
                    <select id="department-filter" name="department" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $department): ?>
                        <option value="<?php echo $department['department_id']; ?>" <?php echo $department_filter === intval($department['department_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($department['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="status-filter" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Status</label>
                    <select id="status-filter" name="is_active" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                        <option value="">All Status</option>
                        <option value="1" <?php echo $is_active_filter === '1' ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $is_active_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-4 flex justify-end">
                <button type="submit" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="google-card p-5 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium" style="color: var(--color-text-primary);">Users</h2>
        
        <button type="button" id="add-user-btn" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
            <i class="fas fa-plus mr-2"></i> Add User
        </button>
    </div>
    
    <?php if (empty($users)): ?>
    <div class="text-center py-8" style="color: var(--color-text-secondary);">
        <i class="fas fa-users text-4xl mb-3 opacity-50"></i>
        <p>No users found. Try adjusting your search or filters.</p>
    </div>
    <?php else: ?>
    <form id="bulk-action-form" method="post" action="?view=users">
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
        <input type="hidden" name="role" value="<?php echo htmlspecialchars($role_filter); ?>">
        <input type="hidden" name="department" value="<?php echo htmlspecialchars($department_filter); ?>">
        <input type="hidden" name="is_active" value="<?php echo htmlspecialchars($is_active_filter); ?>">
        <input type="hidden" name="page" value="<?php echo $current_page; ?>">
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b" style="border-color: var(--color-border-light);">
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" id="select-all-checkbox" class="rounded" style="color: var(--color-primary);">
                        </th>
                        <th class="px-4 py-3 text-left" style="color: var(--color-text-secondary);">User</th>
                        <th class="px-4 py-3 text-left" style="color: var(--color-text-secondary);">Role</th>
                        <th class="px-4 py-3 text-left" style="color: var(--color-text-secondary);">Department</th>
                        <th class="px-4 py-3 text-left" style="color: var(--color-text-secondary);">Status</th>
                        <th class="px-4 py-3 text-left" style="color: var(--color-text-secondary);">Last Login</th>
                        <th class="px-4 py-3 text-left" style="color: var(--color-text-secondary);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="border-b hover:bg-gray-50" style="border-color: var(--color-border-light); <?php echo $user['is_active'] ? '' : 'background-color: var(--color-inactive-bg);'; ?>">
                        <td class="px-4 py-3">
                            <input type="checkbox" name="selected_users[]" value="<?php echo $user['user_id']; ?>" class="user-checkbox rounded" style="color: var(--color-primary);">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center" style="background-color: var(--color-surface-variant);">
                                    <span class="text-lg font-medium" style="color: var(--color-text-primary);">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium" style="color: var(--color-text-primary);">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                    <div class="text-sm" style="color: var(--color-text-secondary);">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">
                            <?php echo htmlspecialchars($user['role_name']); ?>
                        </td>
                        <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">
                            <?php echo htmlspecialchars($user['department_name'] ?? 'Not Assigned'); ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?php if ($user['is_active']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">
                            <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <div class="flex space-x-2 justify-end">
                                <button type="button" class="text-blue-600 hover:text-blue-800 edit-user-btn" 
                                        data-user-id="<?php echo $user['user_id']; ?>"
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                        data-first-name="<?php echo htmlspecialchars($user['first_name']); ?>"
                                        data-last-name="<?php echo htmlspecialchars($user['last_name']); ?>"
                                        data-role-id="<?php echo $user['role_id']; ?>"
                                        data-department-id="<?php echo $user['department_id'] ?? ''; ?>"
                                        data-is-active="<?php echo $user['is_active']; ?>"
                                        title="Edit user">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="text-indigo-600 hover:text-indigo-800 reset-password-btn" 
                                        data-user-id="<?php echo $user['user_id']; ?>"
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                        title="Reset password">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                <button type="button" class="text-red-600 hover:text-red-800 delete-user-btn" 
                                        data-user-id="<?php echo $user['user_id']; ?>"
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                        title="Delete user">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Bulk Actions -->
        <div class="mt-4 border-t pt-4" style="border-color: var(--color-border-light);">
            <div class="flex flex-col md:flex-row md:items-center">
                <div class="flex-1 mb-4 md:mb-0">
                    <div class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
                        <div>
                            <label for="bulk_action" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Bulk Action</label>
                            <select id="bulk_action" name="bulk_action" class="w-full md:w-48 px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                                <option value="">Select Action</option>
                                <option value="activate">Activate</option>
                                <option value="deactivate">Deactivate</option>
                                <option value="assign_role">Assign Role</option>
                                <option value="assign_department">Assign Department</option>
                                <option value="delete">Delete</option>
                            </select>
                        </div>
                        
                        <div id="bulk_role_container" class="hidden">
                            <label for="bulk_role_id" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Role</label>
                            <select id="bulk_role_id" name="bulk_role_id" class="w-full md:w-48 px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="bulk_department_container" class="hidden">
                            <label for="bulk_department_id" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Department</label>
                            <select id="bulk_department_id" name="bulk_department_id" class="w-full md:w-48 px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo $department['department_id']; ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <button type="submit" id="apply_bulk_action" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                                Apply
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="flex justify-center md:justify-end">
                    <nav class="flex items-center space-x-1">
                        <?php if ($current_page > 1): ?>
                        <a href="?view=users&search=<?php echo urlencode($search_query); ?>&role=<?php echo urlencode($role_filter); ?>&department=<?php echo urlencode($department_filter); ?>&is_active=<?php echo urlencode($is_active_filter); ?>&page=<?php echo $current_page - 1; ?>" class="px-3 py-1 rounded-md mr-2" style="background-color: var(--color-surface-variant); color: var(--color-text-primary);">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <div class="flex space-x-1">
                            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                <a href="?view=users&search=<?php echo urlencode($search_query); ?>&role=<?php echo urlencode($role_filter); ?>&department=<?php echo urlencode($department_filter); ?>&is_active=<?php echo urlencode($is_active_filter); ?>&page=<?php echo $i; ?>" class="px-3 py-1 rounded-md <?php echo $i === $current_page ? 'font-medium' : ''; ?>" style="background-color: <?php echo $i === $current_page ? 'var(--color-primary)' : 'var(--color-surface-variant)'; ?>; color: <?php echo $i === $current_page ? 'white' : 'var(--color-text-primary)'; ?>;">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if ($current_page < $total_pages): ?>
                        <a href="?view=users&search=<?php echo urlencode($search_query); ?>&role=<?php echo urlencode($role_filter); ?>&department=<?php echo urlencode($department_filter); ?>&is_active=<?php echo urlencode($is_active_filter); ?>&page=<?php echo $current_page + 1; ?>" class="px-3 py-1 rounded-md ml-2" style="background-color: var(--color-surface-variant); color: var(--color-text-primary);">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<!-- Add User Modal -->
<div id="add-user-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 overflow-hidden" style="background-color: var(--color-surface); color: var(--color-text-primary);">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Add New User</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" id="close-add-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="add-user-form" method="post" action="?view=users">
                <input type="hidden" name="add_user" value="1">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium mb-1">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium mb-1">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                        </div>
                    </div>
                    
                    <div>
                        <label for="username" class="block text-sm font-medium mb-1">Username</label>
                        <input type="text" id="username" name="username" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" id="email" name="email" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                    </div>
                    
                    <div>
                        <label for="role_id" class="block text-sm font-medium mb-1">Role</label>
                        <select id="role_id" name="role_id" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="department_id" class="block text-sm font-medium mb-1">Department</label>
                        <select id="department_id" name="department_id" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['department_id']; ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium mb-1">Password</label>
                        <input type="password" id="password" name="password" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                    </div>
                    
                    <div>
                        <label for="add_confirm_password" class="block text-sm font-medium mb-1">Confirm Password</label>
                        <input type="password" id="add_confirm_password" name="confirm_password" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium" id="cancel-add-user">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                            Add User
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="edit-user-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 overflow-hidden" style="background-color: var(--color-surface); color: var(--color-text-primary);">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Edit User</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" id="close-edit-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="edit-user-form" method="post" action="?view=users">
                <input type="hidden" name="update_user" value="1">
                <input type="hidden" id="edit_user_id" name="user_id" value="">
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit_first_name" class="block text-sm font-medium mb-1">First Name</label>
                            <input type="text" id="edit_first_name" name="first_name" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                        </div>
                        <div>
                            <label for="edit_last_name" class="block text-sm font-medium mb-1">Last Name</label>
                            <input type="text" id="edit_last_name" name="last_name" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                        </div>
                    </div>
                    
                    <div>
                        <label for="edit_username" class="block text-sm font-medium mb-1">Username</label>
                        <input type="text" id="edit_username" name="username" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                    </div>
                    
                    <div>
                        <label for="edit_email" class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" id="edit_email" name="email" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                    </div>
                    
                    <div>
                        <label for="edit_role_id" class="block text-sm font-medium mb-1">Role</label>
                        <select id="edit_role_id" name="role_id" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="edit_department_id" class="block text-sm font-medium mb-1">Department</label>
                        <select id="edit_department_id" name="department_id" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['department_id']; ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="edit_is_active" class="block text-sm font-medium mb-1">Status</label>
                        <select id="edit_is_active" name="is_active" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium" id="cancel-edit-user">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                            Update User
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="reset-password-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 overflow-hidden" style="background-color: var(--color-surface); color: var(--color-text-primary);">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Reset Password</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" id="close-reset-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="reset-password-form" method="post" action="?view=users">
                <input type="hidden" name="reset_password" value="1">
                <input type="hidden" id="reset_user_id" name="user_id" value="">
                
                <p class="mb-4" style="color: var(--color-text-secondary);">
                    Reset password for user: <span id="reset_username" class="font-medium" style="color: var(--color-text-primary);"></span>
                </p>
                
                <div class="space-y-4">
                <div>
                        <label for="new_password" class="block text-sm font-medium mb-1">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                    </div>
                    
                    <div>
                        <label for="reset_confirm_password" class="block text-sm font-medium mb-1">Confirm New Password</label>
                        <input type="password" id="reset_confirm_password" name="confirm_password" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium" id="cancel-reset-password">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                            Reset Password
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div id="delete-user-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 overflow-hidden" style="background-color: var(--color-surface); color: var(--color-text-primary);">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Delete User</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" id="close-delete-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mb-6">
                <div class="flex items-center justify-center mb-4 text-red-500">
                    <i class="fas fa-exclamation-triangle text-4xl"></i>
                </div>
                <p class="text-center mb-2" style="color: var(--color-text-primary);">
                    Are you sure you want to delete the user <span id="delete_username" class="font-medium"></span>?
                </p>
                <p class="text-center text-sm" style="color: var(--color-text-secondary);">
                    This action cannot be undone. All data associated with this user will be permanently removed.
                </p>
            </div>
            
            <form id="delete-user-form" method="post" action="?view=users">
                <input type="hidden" name="delete_user" value="1">
                <input type="hidden" id="delete_user_id" name="user_id" value="">
                
                <div class="flex justify-center space-x-3">
                    <button type="button" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium" id="cancel-delete-user">
                        Cancel
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-md text-sm font-medium">
                        Delete User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide bulk action options based on selection
    const bulkActionSelect = document.getElementById('bulk_action');
    const bulkRoleContainer = document.getElementById('bulk_role_container');
    const bulkDepartmentContainer = document.getElementById('bulk_department_container');
    
    if (bulkActionSelect) {
        bulkActionSelect.addEventListener('change', function() {
            if (this.value === 'assign_role') {
                bulkRoleContainer.classList.remove('hidden');
                bulkDepartmentContainer.classList.add('hidden');
            } else if (this.value === 'assign_department') {
                bulkRoleContainer.classList.add('hidden');
                bulkDepartmentContainer.classList.remove('hidden');
            } else {
                bulkRoleContainer.classList.add('hidden');
                bulkDepartmentContainer.classList.add('hidden');
            }
        });
    }
    
    // Select all checkboxes
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Add User Modal
    const addUserBtn = document.getElementById('add-user-btn');
    const addUserModal = document.getElementById('add-user-modal');
    const closeAddModal = document.getElementById('close-add-modal');
    const cancelAddUser = document.getElementById('cancel-add-user');
    
    if (addUserBtn) {
        addUserBtn.addEventListener('click', function() {
            addUserModal.classList.remove('hidden');
        });
    }
    
    if (closeAddModal) {
        closeAddModal.addEventListener('click', function() {
            addUserModal.classList.add('hidden');
        });
    }
    
    if (cancelAddUser) {
        cancelAddUser.addEventListener('click', function() {
            addUserModal.classList.add('hidden');
        });
    }
    
    // Edit User Modal
    const editUserBtns = document.querySelectorAll('.edit-user-btn');
    const editUserModal = document.getElementById('edit-user-modal');
    const closeEditModal = document.getElementById('close-edit-modal');
    const cancelEditUser = document.getElementById('cancel-edit-user');
    
    editUserBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            const email = this.getAttribute('data-email');
            const firstName = this.getAttribute('data-first-name');
            const lastName = this.getAttribute('data-last-name');
            const roleId = this.getAttribute('data-role-id');
            const departmentId = this.getAttribute('data-department-id');
            const isActive = this.getAttribute('data-is-active');
            
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_first_name').value = firstName;
            document.getElementById('edit_last_name').value = lastName;
            document.getElementById('edit_role_id').value = roleId;
            document.getElementById('edit_department_id').value = departmentId;
            document.getElementById('edit_is_active').value = isActive;
            
            editUserModal.classList.remove('hidden');
        });
    });
    
    if (closeEditModal) {
        closeEditModal.addEventListener('click', function() {
            editUserModal.classList.add('hidden');
        });
    }
    
    if (cancelEditUser) {
        cancelEditUser.addEventListener('click', function() {
            editUserModal.classList.add('hidden');
        });
    }
    
    // Reset Password Modal
    const resetPasswordBtns = document.querySelectorAll('.reset-password-btn');
    const resetPasswordModal = document.getElementById('reset-password-modal');
    const closeResetModal = document.getElementById('close-reset-modal');
    const cancelResetPassword = document.getElementById('cancel-reset-password');
    
    resetPasswordBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_username').textContent = username;
            
            resetPasswordModal.classList.remove('hidden');
        });
    });
    
    if (closeResetModal) {
        closeResetModal.addEventListener('click', function() {
            resetPasswordModal.classList.add('hidden');
        });
    }
    
    if (cancelResetPassword) {
        cancelResetPassword.addEventListener('click', function() {
            resetPasswordModal.classList.add('hidden');
        });
    }
    
    // Delete User Modal
    const deleteUserBtns = document.querySelectorAll('.delete-user-btn');
    const deleteUserModal = document.getElementById('delete-user-modal');
    const closeDeleteModal = document.getElementById('close-delete-modal');
    const cancelDeleteUser = document.getElementById('cancel-delete-user');
    
    deleteUserBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_username').textContent = username;
            
            deleteUserModal.classList.remove('hidden');
        });
    });
    
    if (closeDeleteModal) {
        closeDeleteModal.addEventListener('click', function() {
            deleteUserModal.classList.add('hidden');
        });
    }
    
    if (cancelDeleteUser) {
        cancelDeleteUser.addEventListener('click', function() {
            deleteUserModal.classList.add('hidden');
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 1s';
            setTimeout(() => {
                alert.remove();
            }, 1000);
        }, 5000);
    });
    
    // Form validation
    const addUserForm = document.getElementById('add-user-form');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('add_confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    }
    
    const resetPasswordForm = document.getElementById('reset-password-form');
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('reset_confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    }
    
    // Bulk action form validation
    const bulkActionForm = document.getElementById('bulk-action-form');
    if (bulkActionForm) {
        bulkActionForm.addEventListener('submit', function(e) {
            const selectedAction = document.getElementById('bulk_action').value;
            const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
            
            if (selectedAction === '') {
                e.preventDefault();
                alert('Please select an action');
                return;
            }
            
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one user');
                return;
            }
            
            if (selectedAction === 'assign_role' && document.getElementById('bulk_role_id').value === '') {
                e.preventDefault();
                alert('Please select a role to assign');
                return;
            }
            
            if (selectedAction === 'assign_department' && document.getElementById('bulk_department_id').value === '') {
                e.preventDefault();
                alert('Please select a department to assign');
                return;
            }
            
            if (selectedAction === 'delete') {
                if (!confirm('Are you sure you want to delete the selected users? This action cannot be undone.')) {
                    e.preventDefault();
                }
            }
        });
    }
});
</script>