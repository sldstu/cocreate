/**
 * User Management JavaScript
 */

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize user management
    initUserManagement();
    
    // Add event listeners for search and filters
    document.getElementById('search-input').addEventListener('input', debounce(function() {
        loadUsers(1);
    }, 500));
    
    document.getElementById('role-filter').addEventListener('change', function() {
        loadUsers(1);
    });
    
    document.getElementById('department-filter').addEventListener('change', function() {
        loadUsers(1);
    });
    
    document.getElementById('status-filter').addEventListener('change', function() {
        loadUsers(1);
    });
    
    // Add event listener for bulk action selection
    document.getElementById('bulk_action').addEventListener('change', function() {
        const action = this.value;
        document.getElementById('bulk_role_container').style.display = action === 'assign_role' ? 'block' : 'none';
        document.getElementById('bulk_department_container').style.display = action === 'assign_department' ? 'block' : 'none';
    });
    
    // Add event listener for apply bulk action button
    document.getElementById('apply_bulk_action').addEventListener('click', applyBulkAction);
    
    // Add event listeners for forms
    document.getElementById('addUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        addUser();
    });
    
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateUser();
    });
    
    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        resetPassword();
    });
    
    document.getElementById('deleteUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        deleteUser();
    });
});

/**
 * Initialize user management
 */
function initUserManagement() {
    // Add event listener for select all checkbox
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'select-all') {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = e.target.checked;
            });
        }
    });
}

/**
 * Load users with filters
 * @param {number} page - Page number
 */
function loadUsers(page = 1) {
    const searchQuery = document.getElementById('search-input').value;
    const roleFilter = document.getElementById('role-filter').value;
    const departmentFilter = document.getElementById('department-filter').value;
    const statusFilter = document.getElementById('status-filter').value;
    
    // Show loading indicator
    document.getElementById('loading').classList.remove('hidden');
    document.getElementById('users-table-container').classList.add('opacity-50');
    
    // Build query string
    const params = new URLSearchParams({
        ajax_action: 'get_users',
        search: searchQuery,
        role: roleFilter,
        department: departmentFilter,
        is_active: statusFilter,
        page: page
    });
    
    // Fetch users
    fetch('?view=users&' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update table
                document.getElementById('users-table-container').innerHTML = data.html;
                
                // Update counters
                document.getElementById('total-users-count').textContent = data.total_users;
                document.getElementById('active-users-count').textContent = data.active_users;
                document.getElementById('inactive-users-count').textContent = data.inactive_users;
                
                // Reinitialize select all functionality
                initUserManagement();
            } else {
                showAlert('error', data.message || 'Error loading users');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while loading users');
        })
        .finally(() => {
            // Hide loading indicator
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('users-table-container').classList.remove('opacity-50');
        });
}

/**
 * Add a new user
 */
function addUser() {
    const formData = new FormData(document.getElementById('addUserForm'));
    formData.append('add_user', '1');
    
    // Validate form
    if (!validateUserForm(formData)) {
        return;
    }
    
    // Submit form
    fetch('?view=users', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideAddUserModal();
            showAlert('success', data.message);
            loadUsers();
            document.getElementById('addUserForm').reset();
        } else {
            showAlert('error', data.message, 'addUserModal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while adding the user', 'addUserModal');
    });
}

/**
 * Update an existing user
 */
function updateUser() {
    const formData = new FormData(document.getElementById('editUserForm'));
    
    // Validate form
    if (!validateUserForm(formData, true)) {
        return;
    }
    
    // Submit form
    fetch('?view=users', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideEditUserModal();
            showAlert('success', data.message);
            loadUsers();
        } else {
            showAlert('error', data.message, 'editUserModal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while updating the user', 'editUserModal');
    });
}

/**
 * Reset a user's password
 */
function resetPassword() {
    const formData = new FormData(document.getElementById('resetPasswordForm'));
    
    // Validate passwords
    const newPassword = formData.get('new_password');
    const confirmPassword = formData.get('confirm_password');
    
    if (newPassword.length < 6) {
        showAlert('error', 'Password must be at least 6 characters long', 'resetPasswordModal');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showAlert('error', 'Passwords do not match', 'resetPasswordModal');
        return;
    }
    
    // Submit form
    fetch('?view=users', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideResetPasswordModal();
            showAlert('success', data.message);
        } else {
            showAlert('error', data.message, 'resetPasswordModal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while resetting the password', 'resetPasswordModal');
    });
}

/**
 * Delete a user
 */
function deleteUser() {
    const formData = new FormData(document.getElementById('deleteUserForm'));
    
    // Submit form
    fetch('?view=users', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideDeleteUserModal();
            showAlert('success', data.message);
            loadUsers();
        } else {
            showAlert('error', data.message, 'deleteUserModal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while deleting the user', 'deleteUserModal');
    });
}

/**
 * Apply bulk action to selected users
 */
function applyBulkAction() {
    const action = document.getElementById('bulk_action').value;
    const selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
    
    if (!action) {
        showAlert('error', 'Please select an action');
        return;
    }
    
    if (selectedUsers.length === 0) {
        showAlert('error', 'Please select at least one user');
        return;
    }
    
    // For role or department assignment, validate selection
    if (action === 'assign_role' && !document.getElementById('bulk_role_id').value) {
        showAlert('error', 'Please select a role to assign');
        return;
    }
    
    if (action === 'assign_department' && !document.getElementById('bulk_department_id').value) {
        showAlert('error', 'Please select a department to assign');
        return;
    }
    
    // Confirm delete action
    if (action === 'delete' && !confirm('Are you sure you want to delete the selected users? This action cannot be undone.')) {
        return;
    }
    
    // Prepare form data
    const formData = new FormData();
    formData.append('bulk_action', action);
    selectedUsers.forEach(userId => {
        formData.append('selected_users[]', userId);
    });
    
    if (action === 'assign_role') {
        formData.append('bulk_role_id', document.getElementById('bulk_role_id').value);
    }
    
    if (action === 'assign_department') {
        formData.append('bulk_department_id', document.getElementById('bulk_department_id').value);
    }
    
    // Submit form
    fetch('?view=users', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            loadUsers();
            
            // Reset bulk action form
            document.getElementById('bulk_action').value = '';
            document.getElementById('bulk_role_container').style.display = 'none';
            document.getElementById('bulk_department_container').style.display = 'none';
            
            // Uncheck select all
            const selectAllCheckbox = document.getElementById('select-all');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while applying the bulk action');
    });
}

/**
 * Validate user form
 * @param {FormData} formData - Form data
 * @param {boolean} isEdit - Whether this is an edit form
 * @returns {boolean} - Whether the form is valid
 */
function validateUserForm(formData, isEdit = false) {
    // Get form values
    const username = formData.get('username');
    const email = formData.get('email');
    const firstName = formData.get('first_name');
    const lastName = formData.get('last_name');
    const roleId = formData.get('role_id');
    
    // Check required fields
    if (!username || !email || !firstName || !lastName || !roleId) {
        showAlert('error', 'Please fill in all required fields', isEdit ? 'editUserModal' : 'addUserModal');
        return false;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showAlert('error', 'Please enter a valid email address', isEdit ? 'editUserModal' : 'addUserModal');
        return false;
    }
    
    // For new users, validate password
    if (!isEdit) {
        const password = formData.get('password');
        const confirmPassword = formData.get('confirm_password');
        
        if (!password || password.length < 6) {
            showAlert('error', 'Password must be at least 6 characters long', 'addUserModal');
            return false;
        }
        
        if (password !== confirmPassword) {
            showAlert('error', 'Passwords do not match', 'addUserModal');
            return false;
        }
    }
    
    return true;
}

/**
 * Show add user modal
 */
function showAddUserModal() {
    document.getElementById('addUserModal').classList.remove('hidden');
    document.getElementById('username').focus();
}

/**
 * Hide add user modal
 */
function hideAddUserModal() {
    document.getElementById('addUserModal').classList.add('hidden');
    document.getElementById('addUserForm').reset();
}

/**
 * Show edit user modal
 * @param {number} userId - User ID
 * @param {string} username - Username
 * @param {string} email - Email
 * @param {string} firstName - First name
 * @param {string} lastName - Last name
 * @param {number} roleId - Role ID
 * @param {number|null} departmentId - Department ID
 * @param {number} isActive - Active status
 */
function showEditUserModal(userId, username, email, firstName, lastName, roleId, departmentId, isActive) {
    // Set form values
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_first_name').value = firstName;
    document.getElementById('edit_last_name').value = lastName;
    document.getElementById('edit_role_id').value = roleId;
    document.getElementById('edit_department_id').value = departmentId || '';
    document.getElementById('edit_is_active').value = isActive;
    
    // Show modal
    document.getElementById('editUserModal').classList.remove('hidden');
    document.getElementById('edit_username').focus();
}

/**
 * Hide edit user modal
 */
function hideEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
    document.getElementById('editUserForm').reset();
}

/**
 * Show reset password modal
 * @param {number} userId - User ID
 * @param {string} username - Username
 */
function showResetPasswordModal(userId, username) {
    // Set form values
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username').textContent = username;
    
    // Show modal
    document.getElementById('resetPasswordModal').classList.remove('hidden');
    document.getElementById('new_password').focus();
}

/**
 * Hide reset password modal
 */
function hideResetPasswordModal() {
    document.getElementById('resetPasswordModal').classList.add('hidden');
    document.getElementById('resetPasswordForm').reset();
}

/**
 * Show delete user modal
 * @param {number} userId - User ID
 * @param {string} username - Username
 */
function showDeleteUserModal(userId, username) {
    // Set form values
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('delete_username').textContent = username;
    
    // Show modal
    document.getElementById('deleteUserModal').classList.remove('hidden');
}

/**
 * Hide delete user modal
 */
function hideDeleteUserModal() {
    document.getElementById('deleteUserModal').classList.add('hidden');
    document.getElementById('deleteUserForm').reset();
}

/**
 * Show alert message
 * @param {string} type - Alert type (success, error, warning, info)
 * @param {string} message - Alert message
 * @param {string} modalId - ID of modal to show alert in (optional)
 */
function showAlert(type, message, modalId = null) {
    const alertClass = type === 'success' ? 'bg-green-100 text-green-800' :
                      type === 'error' ? 'bg-red-100 text-red-800' :
                      type === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-blue-100 text-blue-800';
    
    const alertIcon = type === 'success' ? 'fa-check-circle' :
                     type === 'error' ? 'fa-exclamation-circle' :
                     type === 'warning' ? 'fa-exclamation-triangle' :
                     'fa-info-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass} px-4 py-3 rounded relative mb-4" role="alert">
            <div class="flex items-center">
                <i class="fas ${alertIcon} mr-2"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="absolute top-0 right-0 mt-2 mr-2 text-sm" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    if (modalId) {
        // Show alert in modal
        const modalAlertContainer = document.querySelector(`#${modalId} form`);
        if (modalAlertContainer) {
            const alertElement = document.createElement('div');
            alertElement.innerHTML = alertHtml;
            modalAlertContainer.prepend(alertElement.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                const alert = modalAlertContainer.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    } else {
        // Show alert in main container
        const alertContainer = document.getElementById('alert-container');
        if (alertContainer) {
            const alertElement = document.createElement('div');
            alertElement.innerHTML = alertHtml;
            alertContainer.appendChild(alertElement.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                const alerts = alertContainer.querySelectorAll('.alert');
                if (alerts.length > 0) {
                    alerts[0].remove();
                }
            }, 5000);
        }
    }
}

/**
 * Debounce function to limit how often a function can be called
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} - Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}