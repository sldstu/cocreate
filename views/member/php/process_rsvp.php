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
if (empty($_POST['event_id']) || empty($_POST['user_id']) || empty($_POST['status'])) {
    $_SESSION['error'] = 'Missing required fields.';
    header('Location: index.php?page=member_events');
    exit;
}

// Get form data
$event_id = intval($_POST['event_id']);
$user_id = intval($_POST['user_id']);
$status = $_POST['status'];
$comment = isset($_POST['comment']) ? $_POST['comment'] : '';

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
    // Check if user already has an RSVP for this event
    $query = "SELECT rsvp_id FROM event_rsvps WHERE event_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $event_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Update existing RSVP
        $rsvp_id = $stmt->fetchColumn();
        $query = "UPDATE event_rsvps SET status = ?, comment = ?, updated_at = NOW() WHERE rsvp_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->bindParam(2, $comment);
        $stmt->bindParam(3, $rsvp_id, PDO::PARAM_INT);
    } else {
        // Create new RSVP
        $query = "INSERT INTO event_rsvps (event_id, user_id, status, comment) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $event_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $status);
        $stmt->bindParam(4, $comment);
    }
    
    $success = $stmt->execute();
    
    if ($success) {
        $_SESSION['success'] = 'Your RSVP has been submitted successfully.';
    } else {
        $_SESSION['error'] = 'Failed to submit RSVP.';
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while processing your RSVP.';
}

// Redirect back to the event page
header('Location: index.php?page=member_events&event_id=' . $event_id);
exit;
?>
