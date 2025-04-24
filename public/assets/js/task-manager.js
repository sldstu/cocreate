/**
 * Task Manager JS - Implements SortableJS drag-and-drop functionality
 * For EMS Co-Create Task Board with Todoist-like features
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize SortableJS for each task list
    const taskLists = document.querySelectorAll('.task-list');
    
    // Setup sortable instances for each task column
    const sortableInstances = [];
    
    taskLists.forEach(taskList => {
        const sortable = new Sortable(taskList, {
            group: 'tasks', // Set the same group for all lists to enable cross-list dragging
            animation: 250,
            delay: 50,
            ghostClass: 'task-card-ghost', // Class for the ghost element
            chosenClass: 'task-card-chosen', // Class for the chosen element
            dragClass: 'task-card-drag', // Class for the dragging element
            forceFallback: true, // Force fallback for better cross-browser compatibility
            fallbackTolerance: 5, // Tolerance before drag is initiated
            fallbackClass: 'sortable-fallback',
            
            // Event triggered when a task is moved
            onEnd: function(evt) {
                const taskId = evt.item.dataset.taskId;
                const newStatus = evt.to.dataset.status;
                const oldStatus = evt.from.dataset.status;
                
                // Update counters
                updateTaskCounters();
                
                if (newStatus !== oldStatus) {
                    updateTaskStatus(taskId, newStatus);
                }
            },
            
            // Improved visual feedback during drag operations
            onStart: function(evt) {
                document.body.classList.add('dragging');
                
                // Add a class to indicate potential drop targets
                document.querySelectorAll('.task-list').forEach(list => {
                    list.classList.add('potential-drop-target');
                });
            },
            
            onMove: function(evt, originalEvent) {
                // Clear previous highlight
                document.querySelectorAll('.task-list').forEach(list => {
                    list.classList.remove('active-drop-target');
                });
                
                // Highlight current list
                if (evt.to) {
                    evt.to.classList.add('active-drop-target');
                }
                
                return true;
            },
            
            onEnd: function(evt) {
                document.body.classList.remove('dragging');
                
                // Remove all drop target indicators
                document.querySelectorAll('.task-list').forEach(list => {
                    list.classList.remove('potential-drop-target');
                    list.classList.remove('active-drop-target');
                });
                
                const taskId = evt.item.dataset.taskId;
                const newStatus = evt.to.dataset.status;
                const oldStatus = evt.from.dataset.status;
                
                // Update counters
                updateTaskCounters();
                
                if (newStatus !== oldStatus) {
                    updateTaskStatus(taskId, newStatus);
                }
            }
        });
        
        sortableInstances.push(sortable);
    });
    
    // Update task counters for all columns
    function updateTaskCounters() {
        document.querySelectorAll('.status-column').forEach(column => {
            const statusKey = column.dataset.status;
            const taskCount = column.querySelector('.task-list').querySelectorAll('.task-card').length;
            const countElement = column.querySelector('.task-count');
            if (countElement) {
                countElement.textContent = taskCount;
            }
        });
    }
    
    // Function to update task status via AJAX
    function updateTaskStatus(taskId, newStatus) {
        // Show a loading indicator
        const loadingToast = showToast('Updating task...', 'info');
        
        // Make AJAX request to update task status
        const formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('status', newStatus);
        formData.append('action', 'update_status');
        
        fetch('views/lead/php/task_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Hide the loading indicator
            hideToast(loadingToast);
            
            if (data.success) {
                // Show success message
                showToast('Task status updated successfully!', 'success');
                
                // Update the task card UI if needed
                const taskCard = document.querySelector(`.task-card[data-task-id="${taskId}"]`);
                if (taskCard) {
                    // Update any visual indicators for the new status if needed
                    const statusColumns = document.querySelectorAll('.status-column');
                    statusColumns.forEach(column => {
                        const statusKey = column.querySelector('.task-list').dataset.status;
                        if (statusKey === newStatus) {
                            const statusColor = getComputedStyle(column.querySelector('.rounded-full')).backgroundColor;
                            taskCard.style.borderLeftColor = statusColor;
                        }
                    });
                }
            } else {
                // Show error message
                showToast('Failed to update task status: ' + data.message, 'error');
                
                // Revert the drag if there was an error
                location.reload();
            }
        })
        .catch(error => {
            // Hide the loading indicator
            hideToast(loadingToast);
            
            // Show error message
            showToast('Error: ' + error.message, 'error');
            
            // Revert the drag
            location.reload();
        });
    }
    
    // Quick Add Task functionality (Todoist-like)
    document.querySelectorAll('.add-task-btn').forEach(button => {
        button.addEventListener('click', function() {
            const statusColumn = this.closest('.status-column');
            const statusKey = statusColumn.dataset.status;
            const taskList = statusColumn.querySelector('.task-list');
            
            // Create and append quick add form
            const quickAddForm = document.createElement('div');
            quickAddForm.className = 'quick-add-task';
            quickAddForm.innerHTML = `
                <div class="quick-add-task-form google-card p-3">
                    <input type="text" class="new-task-title w-full p-2 mb-2" placeholder="Task title" autocomplete="off" 
                           style="background: var(--color-background-variant); border: 1px solid var(--color-border-light); border-radius: 4px; color: var(--color-text-primary);">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <button class="task-priority-btn" data-priority="low">
                                <i class="fas fa-flag" style="color: #34A853;"></i>
                            </button>
                            <button class="task-priority-btn" data-priority="medium">
                                <i class="fas fa-flag" style="color: #FBBC05;"></i>
                            </button>
                            <button class="task-priority-btn" data-priority="high">
                                <i class="fas fa-flag" style="color: #EA4335;"></i>
                            </button>
                            <button class="task-calendar-btn">
                                <i class="far fa-calendar-alt" style="color: var(--color-text-secondary);"></i>
                            </button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button class="task-cancel-btn text-sm py-1 px-3" 
                                    style="background: var(--color-hover); color: var(--color-text-secondary); border-radius: 4px;">Cancel</button>
                            <button class="task-save-btn text-sm py-1 px-3 text-white" 
                                    style="background: var(--color-primary); border-radius: 4px;">Add task</button>
                        </div>
                    </div>
                    <input type="hidden" class="new-task-priority" value="low">
                    <input type="hidden" class="new-task-deadline" value="">
                </div>
            `;
            
            statusColumn.insertBefore(quickAddForm, taskList);
            
            // Focus the input field
            quickAddForm.querySelector('.new-task-title').focus();
            
            // Setup calendar picker (simplified)
            quickAddForm.querySelector('.task-calendar-btn').addEventListener('click', function() {
                const dateField = quickAddForm.querySelector('.new-task-deadline');
                const today = new Date().toISOString().split('T')[0];
                const newDate = prompt('Enter deadline (YYYY-MM-DD):', today);
                if (newDate) {
                    dateField.value = newDate;
                    this.innerHTML = `<i class="far fa-calendar-check" style="color: #4285F4;"></i>`;
                }
            });
            
            // Setup priority buttons
            quickAddForm.querySelectorAll('.task-priority-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const priority = this.dataset.priority;
                    quickAddForm.querySelector('.new-task-priority').value = priority;
                    
                    // Reset all buttons
                    quickAddForm.querySelectorAll('.task-priority-btn i').forEach(icon => {
                        icon.style.color = icon.parentElement.dataset.priority === 'low' ? '#34A853' : 
                                          (icon.parentElement.dataset.priority === 'medium' ? '#FBBC05' : '#EA4335');
                    });
                    
                    // Highlight selected button
                    this.querySelector('i').style.color = '#4285F4';
                });
            });
            
            // Cancel button action
            quickAddForm.querySelector('.task-cancel-btn').addEventListener('click', function() {
                quickAddForm.remove();
            });
            
            // Save button action
            quickAddForm.querySelector('.task-save-btn').addEventListener('click', function() {
                const titleField = quickAddForm.querySelector('.new-task-title');
                const title = titleField.value.trim();
                
                if (!title) {
                    titleField.classList.add('border-red-500');
                    titleField.focus();
                    return;
                }
                
                const priority = quickAddForm.querySelector('.new-task-priority').value;
                const deadline = quickAddForm.querySelector('.new-task-deadline').value;
                
                // Show loading toast
                const loadingToast = showToast('Creating new task...', 'info');
                
                // Create form data
                const formData = new FormData();
                formData.append('action', 'quick_add_task');
                formData.append('title', title);
                formData.append('status', statusKey);
                formData.append('priority', priority);
                if (deadline) {
                    formData.append('deadline', deadline);
                }
                
                // Send AJAX request
                fetch('views/lead/php/task_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideToast(loadingToast);
                    
                    if (data.success) {
                        showToast('Task created successfully!', 'success');
                        
                        // Create a new task card and insert it
                        const taskCard = createTaskCard(data.task);
                        taskList.insertBefore(taskCard, taskList.firstChild);
                        
                        // Update task counter
                        updateTaskCounters();
                        
                        // Remove quick add form
                        quickAddForm.remove();
                    } else {
                        showToast('Failed to create task: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    hideToast(loadingToast);
                    showToast('Error: ' + error.message, 'error');
                });
            });
        });
    });
    
    // Function to create a new task card element
    function createTaskCard(task) {
        // Get the appropriate status color
        let priorityColor, priorityBg;
        
        switch (task.priority) {
            case 'high':
                priorityColor = '#EA4335';
                priorityBg = 'rgba(234, 67, 53, 0.1)';
                break;
            case 'medium':
                priorityColor = '#FBBC05';
                priorityBg = 'rgba(251, 188, 5, 0.1)';
                break;
            case 'low':
                priorityColor = '#34A853';
                priorityBg = 'rgba(52, 168, 83, 0.1)';
                break;
            default:
                priorityColor = '#757575';
                priorityBg = 'rgba(117, 117, 117, 0.1)';
        }
        
        // Create task card element
        const taskCard = document.createElement('div');
        taskCard.className = 'task-card mb-3 p-3 rounded-md shadow-sm cursor-grab task-item';
        taskCard.dataset.taskId = task.task_id;
        taskCard.dataset.status = task.status;
        taskCard.style.backgroundColor = 'var(--color-surface)';
        taskCard.style.borderLeft = `3px solid ${task.status_color || '#4285F4'}`;
        
        // Add task content
        taskCard.innerHTML = `
            <div class="flex justify-between items-start mb-2">
                <h4 class="text-sm font-medium" style="color: var(--color-text-primary);">
                    ${task.title}
                </h4>
                <div class="task-actions">
                    <a href="?page=lead_tasks&action=view&task_id=${task.task_id}" class="text-xs" style="color: #4285F4;">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
            ${task.deadline ? `
            <div class="flex items-center mb-2">
                <i class="far fa-calendar-alt text-xs mr-1" style="color: var(--color-text-tertiary);"></i>
                <span class="text-xs" style="color: var(--color-text-tertiary);">
                    ${new Date(task.deadline).toLocaleDateString('en-US', {month: 'short', day: 'numeric'})}
                </span>
            </div>
            ` : ''}
            <div class="flex justify-between items-center">
                <span class="px-2 py-0.5 text-xs font-medium rounded-full" 
                      style="background-color: ${priorityBg}; color: ${priorityColor};">
                    ${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
                </span>
                ${task.assigned_name ? `
                <div class="flex items-center">
                    <div class="w-5 h-5 rounded-full flex items-center justify-center" style="background-color: var(--color-hover);">
                        <span class="text-xs" style="color: var(--color-text-primary);">
                            ${task.assigned_name.split(' ').map(n => n[0]).join('').toUpperCase()}
                        </span>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
        
        // Add double-click to edit functionality
        taskCard.addEventListener('dblclick', function() {
            window.location.href = `?page=lead_tasks&action=edit&task_id=${task.task_id}`;
        });
        
        return taskCard;
    }
    
    // Add Column functionality
    const addColumnBtn = document.getElementById('add-column-btn');
    if (addColumnBtn) {
        addColumnBtn.addEventListener('click', function() {
            const columnName = prompt('Enter the name for the new column:');
            if (columnName && columnName.trim() !== '') {
                createNewColumn(columnName.trim());
            }
        });
    }
    
    function createNewColumn(columnName) {
        // Create a status key from the column name
        const statusKey = columnName.toLowerCase().replace(/\s+/g, '_');
        
        // Show loading toast
        const loadingToast = showToast('Creating new column...', 'info');
        
        // Make AJAX request to create new column
        const formData = new FormData();
        formData.append('action', 'create_column');
        formData.append('name', columnName);
        formData.append('key', statusKey);
        
        fetch('views/lead/php/task_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideToast(loadingToast);
            
            if (data.success) {
                showToast('New column created!', 'success');
                // Reload page to show the new column
                location.reload();
            } else {
                showToast('Failed to create column: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideToast(loadingToast);
            showToast('Error: ' + error.message, 'error');
        });
    }
    
    // Helper function to show toast notifications
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        
        // Animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        return toast;
    }
    
    // Helper function to hide toast
    function hideToast(toast) {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
    
    // Filter tasks by category/tag
    const filterSelect = document.getElementById('filter-category');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            const category = this.value;
            const taskCards = document.querySelectorAll('.task-card');
            
            if (category === 'all') {
                taskCards.forEach(card => card.style.display = 'block');
            } else {
                taskCards.forEach(card => {
                    if (card.dataset.category === category) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
            
            // Update counters
            updateTaskCounters();
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('task-search');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            const searchTerm = this.value.toLowerCase();
            const taskCards = document.querySelectorAll('.task-card');
            
            taskCards.forEach(card => {
                const taskTitle = card.querySelector('h4').textContent.toLowerCase();
                if (taskTitle.includes(searchTerm) || searchTerm === '') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Update counters
            updateTaskCounters();
        }, 300));
    }
    
    // Utility function for debouncing
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }
    
    // Add CSS for enhanced interactions
    const style = document.createElement('style');
    style.textContent = `
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 16px;
            border-radius: 8px;
            z-index: 9999;
            transform: translateY(-100%);
            opacity: 0;
            transition: all 0.3s ease;
            color: white;
            max-width: 300px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .toast-success {
            background-color: #34A853;
        }
        
        .toast-error {
            background-color: #EA4335;
        }
        
        .toast-info {
            background-color: #4285F4;
        }
        
        .toast-content {
            display: flex;
            align-items: center;
        }
        
        .toast-content i {
            margin-right: 8px;
        }
        
        /* Enhanced drag and drop styles */
        .task-card-ghost {
            opacity: 0.5;
            background: var(--color-hover) !important;
            border: 1px dashed var(--color-border) !important;
        }
        
        .task-card-chosen {
            box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
            transform: scale(1.03) !important;
            z-index: 10;
        }
        
        .task-card-drag {
            transform: rotate(2deg);
        }
        
        .sortable-fallback {
            transform: rotate(2deg);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .potential-drop-target {
            transition: background-color 0.2s ease;
        }
        
        .active-drop-target {
            background-color: var(--color-hover);
        }
        
        /* Quick add task form */
        .quick-add-task {
            margin-bottom: 16px;
            opacity: 0;
            transform: translateY(-10px);
            animation: slide-in 0.2s ease forwards;
        }
        
        @keyframes slide-in {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .quick-add-task-form {
            border: 1px solid var(--color-border);
        }
        
        .task-priority-btn, .task-calendar-btn {
            padding: 5px;
            background: transparent;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .task-priority-btn:hover, .task-calendar-btn:hover {
            background: var(--color-hover);
        }
        
        /* Task card hover effects */
        .task-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-left-width: 4px;
        }
        
        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .task-actions {
            opacity: 0.5;
            transition: opacity 0.2s;
        }
        
        .task-card:hover .task-actions {
            opacity: 1;
        }
        
        /* Smooth column animations */
        .status-column {
            transition: background-color 0.3s;
        }
        
        body.dragging {
            cursor: grabbing !important;
        }
    `;
    document.head.appendChild(style);
    
    // Initialize task counters on page load
    updateTaskCounters();
});