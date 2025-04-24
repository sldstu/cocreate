<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');
?>

<div class="google-card p-5 mb-6 max-w-2xl mx-auto">
    <div class="text-center mb-6">
        <div class="rounded-full mx-auto p-6 mb-4" style="background-color: rgba(234, 67, 53, 0.1); width: fit-content;">
            <i class="fas fa-exclamation-triangle text-4xl" style="color: #EA4335;"></i>
        </div>
        <h2 class="text-xl font-medium mb-1" style="color: var(--color-text-primary);">Delete Task</h2>
        <p class="text-sm" style="color: var(--color-text-secondary);">
            Are you sure you want to delete this task? This action cannot be undone.
        </p>
    </div>

    <div class="p-4 mb-6 rounded-md" style="background-color: var(--color-hover);">
        <h3 class="text-md font-medium mb-2" style="color: var(--color-text-primary);">
            <?php echo htmlspecialchars($task['title']); ?>
        </h3>
        <?php if (!empty($task['description'])): ?>
        <p class="text-sm mb-3" style="color: var(--color-text-secondary);">
            <?php echo substr(htmlspecialchars($task['description']), 0, 150); ?>
            <?php echo strlen($task['description']) > 150 ? '...' : ''; ?>
        </p>
        <?php endif; ?>
        <div class="flex flex-wrap gap-2 mb-2">
            <?php 
            // Status badge
            $statusBg = '';
            $statusColor = '';
            $statusText = '';
            switch ($task['status']) {
                case 'to_do':
                    $statusBg = 'rgba(251, 188, 5, 0.1)';
                    $statusColor = '#FBBC05';
                    $statusText = 'To Do';
                    break;
                case 'in_progress':
                    $statusBg = 'rgba(66, 133, 244, 0.1)';
                    $statusColor = '#4285F4';
                    $statusText = 'In Progress';
                    break;
                case 'done':
                    $statusBg = 'rgba(52, 168, 83, 0.1)';
                    $statusColor = '#34A853';
                    $statusText = 'Completed';
                    break;
                default:
                    $statusBg = 'rgba(117, 117, 117, 0.1)';
                    $statusColor = '#757575';
                    $statusText = 'Unknown';
            }
            
            // Priority badge
            $priorityBg = '';
            $priorityColor = '';
            $priorityText = '';
            switch ($task['priority']) {
                case 'high':
                    $priorityBg = 'rgba(234, 67, 53, 0.1)';
                    $priorityColor = '#EA4335';
                    $priorityText = 'High Priority';
                    break;
                case 'medium':
                    $priorityBg = 'rgba(251, 188, 5, 0.1)';
                    $priorityColor = '#FBBC05';
                    $priorityText = 'Medium Priority';
                    break;
                case 'low':
                    $priorityBg = 'rgba(52, 168, 83, 0.1)';
                    $priorityColor = '#34A853';
                    $priorityText = 'Low Priority';
                    break;
                default:
                    $priorityBg = 'rgba(117, 117, 117, 0.1)';
                    $priorityColor = '#757575';
                    $priorityText = 'Unknown Priority';
            }
            ?>
            <span class="px-2 py-1 text-xs font-medium rounded-full" style="background-color: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>;">
                <?php echo $statusText; ?>
            </span>
            <span class="px-2 py-1 text-xs font-medium rounded-full" style="background-color: <?php echo $priorityBg; ?>; color: <?php echo $priorityColor; ?>;">
                <?php echo $priorityText; ?>
            </span>
            <?php if (!empty($task['deadline'])): ?>
            <?php 
                $isOverdue = ($task['status'] != 'done' && strtotime($task['deadline']) < time());
                $deadlineBg = $isOverdue ? 'rgba(234, 67, 53, 0.1)' : 'rgba(117, 117, 117, 0.1)';
                $deadlineColor = $isOverdue ? '#EA4335' : '#757575';
            ?>
            <span class="px-2 py-1 text-xs font-medium rounded-full" style="background-color: <?php echo $deadlineBg; ?>; color: <?php echo $deadlineColor; ?>;">
                Deadline: <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <form id="delete-task-form" method="post" action="views/lead/php/task_handler.php">
        <input type="hidden" name="action" value="delete_task">
        <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
        
        <div class="flex justify-between items-center">
            <a href="?page=lead_tasks" class="text-sm font-medium" style="color: var(--color-text-tertiary);">
                <i class="fas fa-arrow-left mr-1"></i> Cancel
            </a>
            <div class="flex space-x-3">
                <a href="?page=lead_tasks" class="px-4 py-2 border rounded-md text-sm font-medium transition-colors duration-200" 
                   style="border-color: var(--color-border-light); color: var(--color-text-secondary);">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 rounded-md text-sm font-medium text-white transition-colors duration-200" 
                        style="background-color: #EA4335;" id="confirm-delete-btn">
                    Delete Task
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.getElementById('delete-task-form');
    const deleteBtn = document.getElementById('confirm-delete-btn');
    
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Double confirm
            if (!confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
                return;
            }
            
            // Disable button to prevent multiple submissions
            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...';
            
            const formData = new FormData(this);
            
            fetch('views/lead/php/task_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message and redirect
                    alert(data.message);
                    window.location.href = '?page=lead_tasks';
                } else {
                    // Show error message
                    alert('Error: ' + data.message);
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = 'Delete Task';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request.');
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = 'Delete Task';
            });
        });
    }
});
</script>