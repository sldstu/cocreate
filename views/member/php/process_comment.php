<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once 'config/config.php';
require_once 'config/database.php';

// Define APP_NAME to prevent direct access in included files
define('APP_NAME', true);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=member_events');
    exit;
}

// Validate required fields
if (empty($_POST['event_id']) || empty($_POST['user_id']) || empty($_POST['content'])) {
    $_SESSION['error'] = 'Missing required fields.';
    header('Location: index.php?page=member_events');
    exit;
}

// Get form data
$event_id = intval($_POST['event_id']);
$user_id = intval($_POST['user_id']);
$content = $_POST['content'];
$parent_comment_id = isset($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;

// Validate user ID matches session user
if ($user_id !== $_SESSION['user_id']) {
    $_SESSION['error'] = 'Invalid user ID.';
    header('Location: index.php?page=member_events');
    exit;
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Insert comment
    $query = "INSERT INTO event_comments (event_id, user_id, content, parent_comment_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $event_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
    $stmt->bindParam(3, $content);
    
    if ($parent_comment_id) {
        $stmt->bindParam(4, $parent_comment_id, PDO::PARAM_INT);
    } else {
        $stmt->bindParam(4, $parent_comment_id, PDO::PARAM_NULL);
    }
    
    $success = $stmt->execute();
    
    if ($success) {
        $_SESSION['success'] = 'Your comment has been posted successfully.';
    } else {
        $_SESSION['error'] = 'Failed to post comment.';
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while posting your comment.';
}

// Redirect back to the event page
header('Location: index.php?page=member_events&event_id=' . $event_id);
exit;
?>
