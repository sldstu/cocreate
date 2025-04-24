<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Determine status and priority colors
$statusBg = '';
$statusColor = '';
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

$priorityBg = '';
$priorityColor = '';
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

// Check if task is overdue
$isOverdue = (!empty($task['deadline']) && $task['status'] != 'done' && strtotime($task['deadline']) < time());
?>

<div class="mb-4">
    <div class="flex items-center mb-2">
        <a href="?page=lead_tasks" class="text-sm font-medium mr-2" style="color: var(--color-text-tertiary);">
            <i class="fas fa-arrow-left mr-1"></i> Back to Tasks
        </a>
    </div>
    <h1 class="text-2xl font-normal" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($task['title']); ?></h1>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Task Details Card -->
    <div class="md:col-span-2">
        <div class="google-card p-5 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center">
                    <div class="rounded-full p-2 mr-3" style="background-color: <?php echo $statusBg; ?>;">
                        <i class="fas <?php echo $task['status'] == 'to_do' ? 'fa-clipboard-list' : ($task['status'] == 'in_progress' ? 'fa-spinner' : 'fa-check-circle'); ?>" style="color: <?php echo $statusColor; ?>;"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-medium" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($task['title']); ?></h2>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full" style="background-color: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>;">
                                <?php echo $statusText; ?>
                            </span>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full" style="background-color: <?php echo $priorityBg; ?>; color: <?php echo $priorityColor; ?>;">
                                <?php echo $priorityText; ?>
                            </span>
                            <?php if ($isOverdue): ?>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full" style="background-color: rgba(234, 67, 53, 0.1); color: #EA4335;">
                                Overdue
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="?page=lead_tasks&action=edit&task_id=<?php echo $task['task_id']; ?>" class="px-3 py-1 rounded-md text-sm transition-colors duration-200" style="background-color: var(--color-hover); color: var(--color-text-primary);">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                    <a href="javascript:void(0);" onclick="confirmDelete()" class="px-3 py-1 rounded-md text-sm text-white transition-colors duration-200" style="background-color: #EA4335;">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </a>
                </div>
            </div>
            
            <?php if (!empty($task['description'])): ?>
            <div class="mb-6">
                <h3 class="text-sm font-medium mb-2" style="color: var(--color-text-secondary);">Description</h3>
                <div class="text-sm p-4 rounded-md" style="background-color: var(--color-hover); color: var(--color-text-primary);">
                    <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium mb-3" style="color: var(--color-text-secondary);">Task Details</h3>
                    <div class="space-y-3">
                        <?php if (!empty($task['deadline'])): ?>
                        <div class="flex items-start">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3" style="background-color: <?php echo $isOverdue ? 'rgba(234, 67, 53, 0.1)' : 'rgba(66, 133, 244, 0.1)'; ?>;">
                                <i class="fas fa-calendar-alt" style="color: <?php echo $isOverdue ? '#EA4335' : '#4285F4'; ?>;"></i>
                            </div>
                            <div>
                                <p class="text-xs" style="color: var(--color-text-secondary);">Deadline</p>
                                <p class="text-sm font-medium" style="color: <?php echo $isOverdue ? '#EA4335' : 'var(--color-text-primary)'; ?>;">
                                    <?php echo date('F d, Y - h:i A', strtotime($task['deadline'])); ?>
                                    <?php echo $isOverdue ? ' (Overdue)' : ''; ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($task['department_name'])): ?>
                        <div class="flex items-start">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3" style="background-color: rgba(66, 133, 244, 0.1);">
                                <i class="fas fa-building" style="color: #4285F4;"></i>
                            </div>
                            <div>
                                <p class="text-xs" style="color: var(--color-text-secondary);">Department</p>
                                <p class="text-sm font-medium" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($task['department_name']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($task['event_title'])): ?>
                        <div class="flex items-start">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3" style="background-color: rgba(251, 188, 5, 0.1);">
                                <i class="fas fa-calendar-check" style="color: #FBBC05;"></i>
                            </div>
                            <div>
                                <p class="text-xs" style="color: var(--color-text-secondary);">Related Event</p>
                                <p class="text-sm font-medium" style="color: var(--color-text-primary);">
                                    <a href="?page=lead_events&event_id=<?php echo $task['event_id']; ?>" style="color: #4285F4;">
                                        <?php echo htmlspecialchars($task['event_title']); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium mb-3" style="color: var(--color-text-secondary);">Assignment Information</h3>
                    <div class="space-y-3">
                        <?php if (!empty($task['assigned_name']) || !empty($task['assigned_username'])): ?>
                        <div class="flex items-start">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3" style="background-color: rgba(52, 168, 83, 0.1);">
                                <i class="fas fa-user" style="color: #34A853;"></i>
                            </div>
                            <div>
                                <p class="text-xs" style="color: var(--color-text-secondary);">Assigned To</p>
                                <p class="text-sm font-medium" style="color: var(--color-text-primary);">
                                    <?php echo !empty($task['assigned_name']) ? htmlspecialchars($task['assigned_name']) : htmlspecialchars($task['assigned_username']); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($task['created_name']) || !empty($task['created_username'])): ?>
                        <div class="flex items-start">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3" style="background-color: rgba(66, 133, 244, 0.1);">
                                <i class="fas fa-user-plus" style="color: #4285F4;"></i>
                            </div>
                            <div>
                                <p class="text-xs" style="color: var(--color-text-secondary);">Created By</p>
                                <p class="text-sm font-medium" style="color: var(--color-text-primary);">
                                    <?php echo !empty($task['created_name']) ? htmlspecialchars($task['created_name']) : htmlspecialchars($task['created_username']); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-start">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3" style="background-color: rgba(117, 117, 117, 0.1);">
                                <i class="fas fa-clock" style="color: #757575;"></i>
                            </div>
                            <div>
                                <p class="text-xs" style="color: var(--color-text-secondary);">Created On</p>
                                <p class="text-sm font-medium" style="color: var(--color-text-primary);">
                                    <?php echo date('F d, Y - h:i A', strtotime($task['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if (!empty($task['updated_at'])): ?>
                        <div class="flex items-start">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3" style="background-color: rgba(117, 117, 117, 0.1);">
                                <i class="fas fa-edit" style="color: #757575;"></i>
                            </div>
                            <div>
                                <p class="text-xs" style="color: var(--color-text-secondary);">Last Updated</p>
                                <p class="text-sm font-medium" style="color: var(--color-text-primary);">
                                    <?php echo date('F d, Y - h:i A', strtotime($task['updated_at'])); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Update Actions -->
            <div class="mt-8 pt-6 border-t" style="border-color: var(--color-border-light);">
                <h3 class="text-sm font-medium mb-3" style="color: var(--color-text-secondary);">Quick Actions</h3>
                <div class="flex flex-wrap gap-2">
                    <div class="form-group mr-4">
                        <label class="text-xs mb-1 block" style="color: var(--color-text-tertiary);">Update Status</label>
                        <div class="flex space-x-2">
                            <button onclick="updateTaskStatus('to_do')" class="px-3 py-1 text-xs rounded-full <?php echo $task['status'] == 'to_do' ? 'text-white' : ''; ?>" 
                                    style="background-color: <?php echo $task['status'] == 'to_do' ? '#FBBC05' : 'rgba(251, 188, 5, 0.1)'; ?>; color: <?php echo $task['status'] == 'to_do' ? 'white' : '#FBBC05'; ?>;">
                                To Do
                            </button>
                            <button onclick="updateTaskStatus('in_progress')" class="px-3 py-1 text-xs rounded-full <?php echo $task['status'] == 'in_progress' ? 'text-white' : ''; ?>" 
                                    style="background-color: <?php echo $task['status'] == 'in_progress' ? '#4285F4' : 'rgba(66, 133, 244, 0.1)'; ?>; color: <?php echo $task['status'] == 'in_progress' ? 'white' : '#4285F4'; ?>;">
                                In Progress
                            </button>
                            <button onclick="updateTaskStatus('done')" class="px-3 py-1 text-xs rounded-full <?php echo $task['status'] == 'done' ? 'text-white' : ''; ?>" 
                                    style="background-color: <?php echo $task['status'] == 'done' ? '#34A853' : 'rgba(52, 168, 83, 0.1)'; ?>; color: <?php echo $task['status'] == 'done' ? 'white' : '#34A853'; ?>;">
                                Completed
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="text-xs mb-1 block" style="color: var(--color-text-tertiary);">Update Priority</label>
                        <div class="flex space-x-2">
                            <button onclick="updateTaskPriority('low')" class="px-3 py-1 text-xs rounded-full <?php echo $task['priority'] == 'low' ? 'text-white' : ''; ?>" 
                                    style="background-color: <?php echo $task['priority'] == 'low' ? '#34A853' : 'rgba(52, 168, 83, 0.1)'; ?>; color: <?php echo $task['priority'] == 'low' ? 'white' : '#34A853'; ?>;">
                                Low
                            </button>
                            <button onclick="updateTaskPriority('medium')" class="px-3 py-1 text-xs rounded-full <?php echo $task['priority'] == 'medium' ? 'text-white' : ''; ?>" 
                                    style="background-color: <?php echo $task['priority'] == 'medium' ? '#FBBC05' : 'rgba(251, 188, 5, 0.1)'; ?>; color: <?php echo $task['priority'] == 'medium' ? 'white' : '#FBBC05'; ?>;">
                                Medium
                            </button>
                            <button onclick="updateTaskPriority('high')" class="px-3 py-1 text-xs rounded-full <?php echo $task['priority'] == 'high' ? 'text-white' : ''; ?>" 
                                    style="background-color: <?php echo $task['priority'] == 'high' ? '#EA4335' : 'rgba(234, 67, 53, 0.1)'; ?>; color: <?php echo $task['priority'] == 'high' ? 'white' : '#EA4335'; ?>;">
                                High
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Task Comments Section -->
        <div class="google-card p-5">
            <h3 class="text-md font-medium mb-4" style="color: var(--color-text-primary);">Comments</h3>
            
            <!-- Add Comment Form -->
            <div class="mb-6">
                <form id="comment-form" class="relative">
                    <textarea id="comment-text" class="w-full px-3 py-2 pr-24 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" 
                              rows="2" placeholder="Add a comment..." 
                              style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);"></textarea>
                    <button type="submit" class="absolute right-2 bottom-2 px-3 py-1 rounded-md text-xs font-medium text-white transition-colors duration-200" 
                            style="background-color: var(--color-primary);" id="add-comment-btn">
                        Comment
                    </button>
                </form>
            </div>
            
            <!-- Comments List -->
            <div id="comments-container">
                <?php if (empty($comments)): ?>
                <div class="text-center py-6" id="no-comments-message">
                    <div class="rounded-full mx-auto p-3 mb-3" style="background-color: rgba(117, 117, 117, 0.1); width: fit-content;">
                        <i class="fas fa-comment-alt" style="color: #757575;"></i>
                    </div>
                    <p class="text-sm" style="color: var(--color-text-secondary);">No comments yet. Be the first to comment!</p>
                </div>
                <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                <div class="comment-item border-b pb-4 mb-4" style="border-color: var(--color-border-light);">
                    <div class="flex items-start">
                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mr-3" style="background-color: var(--color-hover);">
                            <span class="text-sm font-medium" style="color: var(--color-text-primary);">
                                <?php 
                                    $initials = '';
                                    if (!empty($comment['first_name'])) $initials .= strtoupper(substr($comment['first_name'], 0, 1));
                                    if (!empty($comment['last_name'])) $initials .= strtoupper(substr($comment['last_name'], 0, 1));
                                    if (empty($initials)) $initials = strtoupper(substr($comment['username'], 0, 1));
                                    echo $initials;
                                ?>
                            </span>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-medium" style="color: var(--color-text-primary);">
                                    <?php echo !empty($comment['first_name']) && !empty($comment['last_name']) ? 
                                        htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) : 
                                        htmlspecialchars($comment['username']); ?>
                                </span>
                                <span class="text-xs" style="color: var(--color-text-tertiary);">
                                    <?php echo date('M d, Y - h:i A', strtotime($comment['created_at'])); ?>
                                </span>
                            </div>
                            <p class="text-sm" style="color: var(--color-text-secondary);">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Side Panel -->
    <div class="md:col-span-1">
        <!-- Task Activity -->
        <div class="google-card p-5 mb-6">
            <h3 class="text-md font-medium mb-4" style="color: var(--color-text-primary);">Task Activity</h3>
            
            <div class="space-y-4">
                <div class="flex">
                    <div class="flex flex-col items-center mr-3">
                        <div class="rounded-full w-8 h-8 flex items-center justify-center" style="background-color: rgba(66, 133, 244, 0.1);">
                            <i class="fas fa-plus-circle" style="color: #4285F4;"></i>
                        </div>
                        <div class="flex-grow h-full border-l-2 mx-auto my-2" style="border-color: rgba(66, 133, 244, 0.2);"></div>
                    </div>
                    <div>
                        <p class="text-sm font-medium" style="color: var(--color-text-primary);">Task Created</p>
                        <p class="text-xs" style="color: var(--color-text-tertiary);">
                            <?php echo date('M d, Y - h:i A', strtotime($task['created_at'])); ?>
                        </p>
                    </div>
                </div>
                
                <?php if (!empty($task['updated_at']) && $task['updated_at'] != $task['created_at']): ?>
                <div class="flex">
                    <div class="flex flex-col items-center mr-3">
                        <div class="rounded-full w-8 h-8 flex items-center justify-center" style="background-color: rgba(251, 188, 5, 0.1);">
                            <i class="fas fa-edit" style="color: #FBBC05;"></i>
                        </div>
                        <div class="flex-grow h-full border-l-2 mx-auto my-2" style="border-color: rgba(251, 188, 5, 0.2);"></div>
                    </div>
                    <div>
                        <p class="text-sm font-medium" style="color: var(--color-text-primary);">Task Updated</p>
                        <p class="text-xs" style="color: var(--color-text-tertiary);">
                            <?php echo date('M d, Y - h:i A', strtotime($task['updated_at'])); ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="flex">
                    <div class="flex flex-col items-center mr-3">
                        <div class="rounded-full w-8 h-8 flex items-center justify-center" style="background-color: <?php echo $statusBg; ?>;">
                            <i class="fas <?php echo $task['status'] == 'to_do' ? 'fa-clipboard-list' : ($task['status'] == 'in_progress' ? 'fa-spinner' : 'fa-check-circle'); ?>" style="color: <?php echo $statusColor; ?>;"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium" style="color: var(--color-text-primary);">Current Status: <?php echo $statusText; ?></p>
                        <p class="text-xs" style="color: var(--color-text-tertiary);">
                            <?php echo $task['status'] == 'done' ? 'Completed' : ($task['status'] == 'in_progress' ? 'In progress' : 'Not started'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="google-card p-5">
            <h3 class="text-md font-medium mb-4" style="color: var(--color-text-primary);">Quick Links</h3>
            
            <div class="space-y-3">
                <a href="?page=lead_tasks&action=edit&task_id=<?php echo $task['task_id']; ?>" class="flex items-center p-2 rounded-md transition-colors duration-200 hover:bg-gray-100">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3" style="background-color: rgba(251, 188, 5, 0.1);">
                        <i class="fas fa-edit" style="color: #FBBC05;"></i>
                    </div>
                    <span class="text-sm" style="color: var(--color-text-primary);">Edit Task</span>
                </a>
                
                <?php if (!empty($task['event_id'])): ?>
                <a href="?page=lead_events&event_id=<?php echo $task['event_id']; ?>" class="flex items-center p-2 rounded-md transition-colors duration-200 hover:bg-gray-100">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3" style="background-color: rgba(66, 133, 244, 0.1);">
                        <i class="fas fa-calendar-check" style="color: #4285F4;"></i>
                    </div>
                    <span class="text-sm" style="color: var(--color-text-primary);">View Related Event</span>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($task['department_id'])): ?>
                <a href="?page=lead_departments&department_id=<?php echo $task['department_id']; ?>" class="flex items-center p-2 rounded-md transition-colors duration-200 hover:bg-gray-100">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3" style="background-color: rgba(52, 168, 83, 0.1);">
                        <i class="fas fa-building" style="color: #34A853;"></i>
                    </div>
                    <span class="text-sm" style="color: var(--color-text-primary);">View Department</span>
                </a>
                <?php endif; ?>
                
                <a href="javascript:void(0);" onclick="confirmDelete()" class="flex items-center p-2 rounded-md transition-colors duration-200 hover:bg-gray-100">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3" style="background-color: rgba(234, 67, 53, 0.1);">
                        <i class="fas fa-trash" style="color: #EA4335;"></i>
                    </div>
                    <span class="text-sm" style="color: var(--color-text-primary);">Delete Task</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const commentForm = document.getElementById('comment-form');
    const commentText = document.getElementById('comment-text');
    const commentsContainer = document.getElementById('comments-container');
    const noCommentsMessage = document.getElementById('no-comments-message');
    const addCommentBtn = document.getElementById('add-comment-btn');
    
    // Handle comment submission
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!commentText.value.trim()) {
                alert('Please enter a comment.');
                return;
            }
            
            // Disable button to prevent multiple submissions
            addCommentBtn.disabled = true;
            addCommentBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            const formData = new FormData();
            formData.append('action', 'add_comment');
            formData.append('task_id', '<?php echo $task['task_id']; ?>');
            formData.append('content', commentText.value.trim());
            
            fetch('views/lead/php/task_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create a new comment element
                    if (noCommentsMessage) {
                        noCommentsMessage.style.display = 'none';
                    }
                    
                    const commentElement = document.createElement('div');
                    commentElement.className = 'comment-item border-b pb-4 mb-4';
                    commentElement.style.borderColor = 'var(--color-border-light)';
                    
                    // Get user's initials for the avatar
                    const username = '<?php echo $_SESSION['username'] ?? ''; ?>';
                    const firstName = '<?php echo $_SESSION['first_name'] ?? ''; ?>';
                    const lastName = '<?php echo $_SESSION['last_name'] ?? ''; ?>';
                    
                    let initials = '';
                    if (firstName) initials += firstName.charAt(0).toUpperCase();
                    if (lastName) initials += lastName.charAt(0).toUpperCase();
                    if (!initials && username) initials = username.charAt(0).toUpperCase();
                    
                    // Format the comment HTML
                    commentElement.innerHTML = `
                        <div class="flex items-start">
                            <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mr-3" style="background-color: var(--color-hover);">
                                <span class="text-sm font-medium" style="color: var(--color-text-primary);">${initials}</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium" style="color: var(--color-text-primary);">
                                        ${firstName && lastName ? `${firstName} ${lastName}` : username}
                                    </span>
                                    <span class="text-xs" style="color: var(--color-text-tertiary);">
                                        Just now
                                    </span>
                                </div>
                                <p class="text-sm" style="color: var(--color-text-secondary);">
                                    ${commentText.value.trim().replace(/\n/g, '<br>')}
                                </p>
                            </div>
                        </div>
                    `;
                    
                    // Add the new comment at the top of the list
                    commentsContainer.insertBefore(commentElement, commentsContainer.firstChild);
                    
                    // Reset the form
                    commentText.value = '';
                } else {
                    alert('Error adding comment: ' + data.message);
                }
                
                // Re-enable the button
                addCommentBtn.disabled = false;
                addCommentBtn.innerHTML = 'Comment';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding your comment.');
                addCommentBtn.disabled = false;
                addCommentBtn.innerHTML = 'Comment';
            });
        });
    }
});

// Confirm task deletion
function confirmDelete() {
    if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
        window.location.href = '?page=lead_tasks&action=delete&task_id=<?php echo $task['task_id']; ?>';
    }
}

// Update task status
function updateTaskStatus(status) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('task_id', '<?php echo $task['task_id']; ?>');
    formData.append('status', status);
    
    fetch('views/lead/php/task_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Server returned ' + response.status + ' ' + response.statusText);
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Reload the page to show the updated status
            window.location.reload();
        } else {
            alert('Error updating status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the task status.');
    });
}

// Update task priority
function updateTaskPriority(priority) {
    const formData = new FormData();
    formData.append('action', 'update_priority');
    formData.append('task_id', '<?php echo $task['task_id']; ?>');
    formData.append('priority', priority);
    
    fetch('views/lead/php/task_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Server returned ' + response.status + ' ' + response.statusText);
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Reload the page to show the updated priority
            window.location.reload();
        } else {
            alert('Error updating priority: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the task priority.');
    });
}
</script>