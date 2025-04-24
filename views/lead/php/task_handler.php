<?php
// Prevent any output before headers
ob_start();

// Turn off PHP's default error display
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set content type header for JSON
header('Content-Type: application/json');

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Create a log file for debugging
$log_file = __DIR__ . '/../../../debug_log.txt';
file_put_contents($log_file, "===== TASK HANDLER LOG: " . date('Y-m-d H:i:s') . " =====\n", FILE_APPEND);

try {
    // Include required files
    require_once '../../../config/database.php';
    require_once '../../../includes/functions.php';
    require_once '../../../includes/auth.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("You must be logged in to perform this action");
    }
    
    // Check if user has lead role
    if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) { // Assuming role_id 1 is 'lead'
        throw new Exception("You don't have permission to perform this action");
    }
    
    // Get action parameter
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if (empty($action)) {
        throw new Exception("No action specified");
    }

    // Set up database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Log the action being performed
    file_put_contents($log_file, "Action: $action\n", FILE_APPEND);
    
    // Process based on action
    switch ($action) {
        case 'create_task':
            createTask($conn, $log_file);
            break;
        
        case 'update_task':
            updateTask($conn, $log_file);
            break;
        
        case 'delete_task':
            deleteTask($conn, $log_file);
            break;
        
        case 'update_status':
            updateTaskStatus($conn, $log_file);
            break;
        
        case 'update_priority':
            updateTaskPriority($conn, $log_file);
            break;
        
        case 'add_comment':
            addTaskComment($conn, $log_file);
            break;
        
        case 'quick_add_task':
            quickAddTask($conn, $log_file);
            break;
        
        default:
            throw new Exception("Invalid action: $action");
    }
} catch (Exception $e) {
    // Log the error
    file_put_contents($log_file, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($log_file, "Trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    // Return error response as JSON
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering and send response
ob_end_flush();

/**
 * Create a new task
 * 
 * @param PDO $conn Database connection
 * @param string $log_file Path to the log file
 */
function createTask($conn, $log_file = null) {
    try {
        // Get required fields
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        
        if (empty($title)) {
            throw new Exception("Task title is required");
        }
        
        // Get other fields with defaults
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $status = isset($_POST['status']) ? $_POST['status'] : 'to_do';
        $priority = isset($_POST['priority']) ? $_POST['priority'] : 'medium';
        $deadline = !empty($_POST['deadline']) ? date('Y-m-d H:i:s', strtotime($_POST['deadline'])) : null;
        $event_id = !empty($_POST['event_id']) ? intval($_POST['event_id']) : null;
        $assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        $created_by = $_SESSION['user_id'];
        
        // Log the task creation details
        if ($log_file) {
            file_put_contents($log_file, "Creating task: $title\n", FILE_APPEND);
            file_put_contents($log_file, "Status: $status, Priority: $priority\n", FILE_APPEND);
            if ($deadline) file_put_contents($log_file, "Deadline: $deadline\n", FILE_APPEND);
            if ($assigned_to) file_put_contents($log_file, "Assigned to: $assigned_to\n", FILE_APPEND);
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Insert into tasks table
        $query = "INSERT INTO tasks (title, description, status, priority, deadline, event_id, created_by, created_at) 
                  VALUES (:title, :description, :status, :priority, :deadline, :event_id, :created_by, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':priority', $priority);
        $stmt->bindParam(':deadline', $deadline);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':created_by', $created_by);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create task");
        }
        
        $task_id = $conn->lastInsertId();
        
        // If there's an assigned user, create task assignment
        if ($assigned_to) {
            $assign_query = "INSERT INTO task_assignments (task_id, user_id, assigned_by, assigned_at) 
                            VALUES (:task_id, :user_id, :assigned_by, NOW())";
            
            $assign_stmt = $conn->prepare($assign_query);
            $assign_stmt->bindParam(':task_id', $task_id);
            $assign_stmt->bindParam(':user_id', $assigned_to);
            $assign_stmt->bindParam(':assigned_by', $created_by);
            
            if (!$assign_stmt->execute()) {
                // Rollback if assignment fails
                $conn->rollBack();
                throw new Exception("Failed to assign task");
            }
        }
        
        // Commit the transaction
        $conn->commit();
        
        // Log success
        if ($log_file) {
            file_put_contents($log_file, "Task created successfully with ID: $task_id\n", FILE_APPEND);
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Task created successfully',
            'task_id' => $task_id
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction if active
        if ($conn && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Log error
        if ($log_file) {
            file_put_contents($log_file, "Task creation failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        // Throw the exception up to the main handler
        throw $e;
    }
}

/**
 * Quick add task (Todoist-like functionality)
 * 
 * @param PDO $conn Database connection
 * @param string $log_file Path to the log file
 */
function quickAddTask($conn, $log_file = null) {
    try {
        // Get required fields
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        
        if (empty($title)) {
            throw new Exception("Task title is required");
        }
        
        // Get other fields with defaults
        $status = isset($_POST['status']) ? $_POST['status'] : 'to_do';
        $priority = isset($_POST['priority']) ? $_POST['priority'] : 'low';
        $deadline = !empty($_POST['deadline']) ? date('Y-m-d', strtotime($_POST['deadline'])) : null;
        $created_by = $_SESSION['user_id'];
        $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;
        
        // Log the task creation details
        if ($log_file) {
            file_put_contents($log_file, "Quick adding task: $title\n", FILE_APPEND);
            file_put_contents($log_file, "Status: $status, Priority: $priority\n", FILE_APPEND);
            if ($deadline) file_put_contents($log_file, "Deadline: $deadline\n", FILE_APPEND);
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Check if department_id column exists
        $checkDeptColumn = $conn->query("SHOW COLUMNS FROM tasks LIKE 'department_id'");
        $departmentColumnExists = $checkDeptColumn->rowCount() > 0;
        
        // Insert into tasks table
        if ($departmentColumnExists && $department_id) {
            $query = "INSERT INTO tasks (title, status, priority, deadline, created_by, created_at, department_id) 
                      VALUES (:title, :status, :priority, :deadline, :created_by, NOW(), :department_id)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':department_id', $department_id);
        } else {
            $query = "INSERT INTO tasks (title, status, priority, deadline, created_by, created_at) 
                      VALUES (:title, :status, :priority, :deadline, :created_by, NOW())";
            
            $stmt = $conn->prepare($query);
        }
        
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':priority', $priority);
        $stmt->bindParam(':deadline', $deadline);
        $stmt->bindParam(':created_by', $created_by);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create task");
        }
        
        $task_id = $conn->lastInsertId();
        
        // Commit the transaction
        $conn->commit();
        
        // Get the task color for the UI
        $statusColor = '#FBBC05'; // Default yellow for to_do
        switch ($status) {
            case 'in_progress':
                $statusColor = '#4285F4'; // Blue
                break;
            case 'done':
                $statusColor = '#34A853'; // Green
                break;
        }
        
        // Get task details for response
        $getTaskQuery = "SELECT t.*, 
                          u.username as assigned_username, 
                          CONCAT(u.first_name, ' ', u.last_name) as assigned_name
                        FROM tasks t
                        LEFT JOIN task_assignments ta ON t.task_id = ta.task_id
                        LEFT JOIN users u ON ta.user_id = u.user_id
                        WHERE t.task_id = :task_id";
        
        $getTaskStmt = $conn->prepare($getTaskQuery);
        $getTaskStmt->bindParam(':task_id', $task_id);
        $getTaskStmt->execute();
        $task = $getTaskStmt->fetch(PDO::FETCH_ASSOC);
        
        // Add status color to task
        $task['status_color'] = $statusColor;
        
        // Log success
        if ($log_file) {
            file_put_contents($log_file, "Task created successfully with ID: $task_id\n", FILE_APPEND);
        }
        
        // Return success response with task data for UI updating
        echo json_encode([
            'success' => true,
            'message' => 'Task created successfully',
            'task' => $task
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction if active
        if ($conn && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Log error
        if ($log_file) {
            file_put_contents($log_file, "Task creation failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        // Throw the exception up to the main handler
        throw $e;
    }
}

/**
 * Update an existing task
 */
function updateTask($conn, $log_file = null) {
    try {
        // Get required fields
        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        
        if ($task_id <= 0 || empty($title)) {
            throw new Exception("Task ID and title are required");
        }
        
        // Log the update
        if ($log_file) {
            file_put_contents($log_file, "Updating task ID: $task_id\n", FILE_APPEND);
        }
        
        // Get other fields
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $status = isset($_POST['status']) ? $_POST['status'] : 'to_do';
        $priority = isset($_POST['priority']) ? $_POST['priority'] : 'medium';
        $deadline = !empty($_POST['deadline']) ? date('Y-m-d H:i:s', strtotime($_POST['deadline'])) : null;
        $event_id = !empty($_POST['event_id']) ? intval($_POST['event_id']) : null;
        $assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        $updated_by = $_SESSION['user_id'];
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Update task
        $query = "UPDATE tasks SET 
                  title = :title, 
                  description = :description, 
                  status = :status, 
                  priority = :priority, 
                  deadline = :deadline, 
                  event_id = :event_id, 
                  updated_at = NOW() 
                  WHERE task_id = :task_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':task_id', $task_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':priority', $priority);
        $stmt->bindParam(':deadline', $deadline);
        $stmt->bindParam(':event_id', $event_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update task");
        }
        
        // Handle assignment changes
        if ($assigned_to) {
            // Check if there's an existing assignment
            $check_query = "SELECT assignment_id FROM task_assignments WHERE task_id = :task_id";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bindParam(':task_id', $task_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                // Update existing assignment
                $assignment = $check_stmt->fetch(PDO::FETCH_ASSOC);
                $assignment_id = $assignment['assignment_id'];
                
                $update_query = "UPDATE task_assignments 
                                SET user_id = :user_id, assigned_by = :assigned_by, assigned_at = NOW() 
                                WHERE assignment_id = :assignment_id";
                
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bindParam(':user_id', $assigned_to);
                $update_stmt->bindParam(':assigned_by', $updated_by);
                $update_stmt->bindParam(':assignment_id', $assignment_id);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update task assignment");
                }
            } else {
                // Create new assignment
                $assign_query = "INSERT INTO task_assignments (task_id, user_id, assigned_by, assigned_at) 
                                VALUES (:task_id, :user_id, :assigned_by, NOW())";
                
                $assign_stmt = $conn->prepare($assign_query);
                $assign_stmt->bindParam(':task_id', $task_id);
                $assign_stmt->bindParam(':user_id', $assigned_to);
                $assign_stmt->bindParam(':assigned_by', $updated_by);
                
                if (!$assign_stmt->execute()) {
                    throw new Exception("Failed to create task assignment");
                }
            }
        } else {
            // Remove any existing assignments
            $delete_query = "DELETE FROM task_assignments WHERE task_id = :task_id";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bindParam(':task_id', $task_id);
            $delete_stmt->execute();
        }
        
        // Commit the transaction
        $conn->commit();
        
        // Log success
        if ($log_file) {
            file_put_contents($log_file, "Task updated successfully\n", FILE_APPEND);
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Task updated successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction if active
        if ($conn && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Log error
        if ($log_file) {
            file_put_contents($log_file, "Task update failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        // Throw the exception up to the main handler
        throw $e;
    }
}

/**
 * Delete a task
 */
function deleteTask($conn, $log_file = null) {
    try {
        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        
        if ($task_id <= 0) {
            throw new Exception("Task ID is required");
        }
        
        // Log the deletion
        if ($log_file) {
            file_put_contents($log_file, "Deleting task ID: $task_id\n", FILE_APPEND);
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Delete task assignments
        $assign_query = "DELETE FROM task_assignments WHERE task_id = :task_id";
        $assign_stmt = $conn->prepare($assign_query);
        $assign_stmt->bindParam(':task_id', $task_id);
        $assign_stmt->execute();
        
        // Delete task comments
        $comment_query = "DELETE FROM task_comments WHERE task_id = :task_id";
        $comment_stmt = $conn->prepare($comment_query);
        $comment_stmt->bindParam(':task_id', $task_id);
        $comment_stmt->execute();
        
        // Delete the task
        $query = "DELETE FROM tasks WHERE task_id = :task_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':task_id', $task_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete task");
        }
        
        // Commit the transaction
        $conn->commit();
        
        // Log success
        if ($log_file) {
            file_put_contents($log_file, "Task deleted successfully\n", FILE_APPEND);
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Task deleted successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction if active
        if ($conn && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Log error
        if ($log_file) {
            file_put_contents($log_file, "Task deletion failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        // Throw the exception up to the main handler
        throw $e;
    }
}

/**
 * Update task status
 */
function updateTaskStatus($conn, $log_file = null) {
    try {
        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        
        if ($task_id <= 0 || empty($status)) {
            throw new Exception("Task ID and status are required");
        }
        
        // Validate status
        $valid_statuses = ['to_do', 'in_progress', 'done'];
        if (!in_array($status, $valid_statuses)) {
            throw new Exception("Invalid status value");
        }
        
        // Log the status update
        if ($log_file) {
            file_put_contents($log_file, "Updating status for task ID $task_id to $status\n", FILE_APPEND);
        }
        
        // Update the status
        $query = "UPDATE tasks SET status = :status, updated_at = NOW() WHERE task_id = :task_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':task_id', $task_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update task status");
        }
        
        // Log success
        if ($log_file) {
            file_put_contents($log_file, "Task status updated successfully\n", FILE_APPEND);
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Task status updated successfully'
        ]);
        
    } catch (Exception $e) {
        // Log error
        if ($log_file) {
            file_put_contents($log_file, "Task status update failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        // Throw the exception up to the main handler
        throw $e;
    }
}

/**
 * Update task priority
 */
function updateTaskPriority($conn, $log_file = null) {
    try {
        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $priority = isset($_POST['priority']) ? $_POST['priority'] : '';
        
        if ($task_id <= 0 || empty($priority)) {
            throw new Exception("Task ID and priority are required");
        }
        
        // Validate priority
        $valid_priorities = ['low', 'medium', 'high'];
        if (!in_array($priority, $valid_priorities)) {
            throw new Exception("Invalid priority value");
        }
        
        // Log the priority update
        if ($log_file) {
            file_put_contents($log_file, "Updating priority for task ID $task_id to $priority\n", FILE_APPEND);
        }
        
        // Update the priority
        $query = "UPDATE tasks SET priority = :priority, updated_at = NOW() WHERE task_id = :task_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':priority', $priority);
        $stmt->bindParam(':task_id', $task_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update task priority");
        }
        
        // Log success
        if ($log_file) {
            file_put_contents($log_file, "Task priority updated successfully\n", FILE_APPEND);
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Task priority updated successfully'
        ]);
        
    } catch (Exception $e) {
        // Log error
        if ($log_file) {
            file_put_contents($log_file, "Task priority update failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        // Throw the exception up to the main handler
        throw $e;
    }
}

/**
 * Add a comment to a task
 */
function addTaskComment($conn, $log_file = null) {
    try {
        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        
        if ($task_id <= 0 || empty($content)) {
            throw new Exception("Task ID and comment content are required");
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Log the comment addition
        if ($log_file) {
            file_put_contents($log_file, "Adding comment to task ID $task_id\n", FILE_APPEND);
        }
        
        // Insert the comment
        $query = "INSERT INTO task_comments (task_id, user_id, content, created_at) 
                  VALUES (:task_id, :user_id, :content, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':task_id', $task_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':content', $content);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to add comment");
        }
        
        $comment_id = $conn->lastInsertId();
        
        // Log success
        if ($log_file) {
            file_put_contents($log_file, "Comment added successfully with ID: $comment_id\n", FILE_APPEND);
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Comment added successfully',
            'comment_id' => $comment_id
        ]);
        
    } catch (Exception $e) {
        // Log error
        if ($log_file) {
            file_put_contents($log_file, "Adding comment failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        // Throw the exception up to the main handler
        throw $e;
    }
}
?>