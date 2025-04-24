<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');
?>

<div class="google-card p-5 mb-6">
    <div class="mb-4">
        <h2 class="text-xl font-normal mb-1" style="color: var(--color-text-primary);"><?php echo $pageTitle; ?></h2>
        <p class="text-sm" style="color: var(--color-text-secondary);">
            <?php echo $isEditing ? 'Edit task details and save changes.' : 'Create a new task by filling in the details below.'; ?>
        </p>
    </div>

    <form id="task-form" method="post" action="views/lead/php/task_handler.php">
        <input type="hidden" name="action" value="<?php echo $isEditing ? 'update_task' : 'create_task'; ?>">
        <?php if ($isEditing): ?>
        <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
        <?php endif; ?>

        <!-- Task Title -->
        <div class="mb-5">
            <label for="title" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">Task Title <span class="text-red-500">*</span></label>
            <input type="text" id="title" name="title" value="<?php echo $isEditing ? htmlspecialchars($task['title']) : ''; ?>" required 
                   class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                   style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
        </div>
        
        <!-- Task Description -->
        <div class="mb-5">
            <label for="description" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">Description</label>
            <textarea id="description" name="description" rows="4" 
                      class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                      style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);"><?php echo $isEditing ? htmlspecialchars($task['description']) : ''; ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <!-- Task Status -->
            <div>
                <label for="status" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">Status <span class="text-red-500">*</span></label>
                <select id="status" name="status" required 
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                    <option value="to_do" <?php echo $isEditing && $task['status'] == 'to_do' ? 'selected' : ''; ?>>To Do</option>
                    <option value="in_progress" <?php echo $isEditing && $task['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="done" <?php echo $isEditing && $task['status'] == 'done' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            
            <!-- Task Priority -->
            <div>
                <label for="priority" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">Priority <span class="text-red-500">*</span></label>
                <select id="priority" name="priority" required 
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                    <option value="low" <?php echo $isEditing && $task['priority'] == 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $isEditing && $task['priority'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $isEditing && $task['priority'] == 'high' ? 'selected' : ''; ?>>High</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <!-- Deadline -->
            <div>
                <label for="deadline" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">Deadline</label>
                <input type="datetime-local" id="deadline" name="deadline" 
                       value="<?php echo $isEditing && !empty($task['deadline']) ? date('Y-m-d\TH:i', strtotime($task['deadline'])) : ''; ?>" 
                       class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
            </div>
            
            <!-- Assigned To -->
            <div>
                <label for="assigned_to" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">Assign To</label>
                <select id="assigned_to" name="assigned_to" 
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                    <option value="">-- Select User --</option>
                    <?php foreach ($users as $user): 
                        $displayName = !empty($user['first_name']) && !empty($user['last_name']) ? 
                            $user['first_name'] . ' ' . $user['last_name'] : 
                            $user['username'];
                    ?>
                    <option value="<?php echo $user['user_id']; ?>" <?php echo $isEditing && $task['assigned_to'] == $user['user_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($displayName); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <!-- Department -->
            <div>
                <label for="department_id" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">Department</label>
                <select id="department_id" name="department_id" 
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                    <option value="">-- Select Department --</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['department_id']; ?>" <?php echo $isEditing && $task['department_id'] == $dept['department_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Related Event -->
            <div>
                <label for="event_id" class="block text-sm font-medium mb-1" style="color: var(--color-text-secondary);">Related Event</label>
                <select id="event_id" name="event_id" 
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                    <option value="">-- Select Event --</option>
                    <?php foreach ($events as $event): ?>
                    <option value="<?php echo $event['event_id']; ?>" <?php echo $isEditing && $task['event_id'] == $event['event_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($event['title']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-between items-center mt-8">
            <a href="?page=lead_tasks" class="text-sm font-medium" style="color: var(--color-text-tertiary);">
                <i class="fas fa-arrow-left mr-1"></i> Back to Tasks
            </a>
            <div class="flex space-x-3">
                <button type="reset" class="px-4 py-2 border rounded-md text-sm font-medium transition-colors duration-200" 
                        style="border-color: var(--color-border-light); color: var(--color-text-secondary);">
                    Reset
                </button>
                <button type="submit" class="px-4 py-2 rounded-md text-sm font-medium text-white transition-colors duration-200" 
                        style="background-color: var(--color-primary);" id="submit-btn">
                    <?php echo $isEditing ? 'Update Task' : 'Create Task'; ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const taskForm = document.getElementById('task-form');
    const submitBtn = document.getElementById('submit-btn');
    
    if (taskForm) {
        taskForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Disable button to prevent multiple submissions
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> <?php echo $isEditing ? 'Updating...' : 'Creating...'; ?>';
            
            const formData = new FormData(this);
            
            // Debug: Log form data being sent
            console.log('Sending form data:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            fetch('views/lead/php/task_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if the response is ok (status in the range 200-299)
                if (!response.ok) {
                    throw new Error('Server returned ' + response.status + ' ' + response.statusText);
                }
                
                // Get content type to determine how to process the response
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        return { type: 'json', data: data };
                    });
                } else {
                    return response.text().then(text => {
                        return { type: 'text', data: text };
                    });
                }
            })
            .then(result => {
                if (result.type === 'json') {
                    // Process JSON response
                    const data = result.data;
                    if (data.success) {
                        // Show success message and redirect
                        alert(data.message);
                        window.location.href = '?page=lead_tasks';
                    } else {
                        // Show error message from JSON response
                        alert('Error: ' + data.message);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<?php echo $isEditing ? 'Update Task' : 'Create Task'; ?>';
                    }
                } else {
                    // Handle non-JSON response (likely HTML error page)
                    console.error('Server returned non-JSON response:', result.data);
                    alert('Server Error: The server returned an unexpected response format. Please check the browser console for details.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<?php echo $isEditing ? 'Update Task' : 'Create Task'; ?>';
                }
            })
            .catch(error => {
                console.error('Request Error:', error);
                alert('Error: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<?php echo $isEditing ? 'Update Task' : 'Create Task'; ?>';
            });
        });
    }
    
    // Handle department selection to filter users
    const departmentSelect = document.getElementById('department_id');
    const userSelect = document.getElementById('assigned_to');
    
    if (departmentSelect && userSelect) {
        departmentSelect.addEventListener('change', function() {
            const departmentId = this.value;
            
            // Keep track of the current selection
            const currentUser = userSelect.value;
            
            // Store all user options
            if (!window.allUsers) {
                window.allUsers = Array.from(userSelect.options).map(option => ({
                    value: option.value,
                    text: option.text,
                    departmentId: option.getAttribute('data-department-id')
                }));
            }
            
            // Reset users dropdown
            userSelect.innerHTML = '<option value="">-- Select User --</option>';
            
            // If a department is selected, filter users
            if (departmentId) {
                window.allUsers.forEach(user => {
                    if (!user.departmentId || user.departmentId === departmentId) {
                        const option = document.createElement('option');
                        option.value = user.value;
                        option.text = user.text;
                        option.setAttribute('data-department-id', user.departmentId);
                        
                        if (user.value === currentUser) {
                            option.selected = true;
                        }
                        
                        userSelect.appendChild(option);
                    }
                });
            } else {
                // If no department selected, show all users
                window.allUsers.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.value;
                    option.text = user.text;
                    option.setAttribute('data-department-id', user.departmentId);
                    
                    if (user.value === currentUser) {
                        option.selected = true;
                    }
                    
                    userSelect.appendChild(option);
                });
            }
        });
    }
});
</script>