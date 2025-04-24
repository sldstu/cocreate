<?php
// Prevent PHP errors from being displayed in JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit;
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Handle different actions
$action = $_GET['action'] ?? '';

// Ensure all responses are JSON
header('Content-Type: application/json');

switch ($action) {
    case 'get':
        // Get notifications for the current user
        try {
            $query = "SELECT * FROM notifications 
                      WHERE user_id = ? 
                      ORDER BY created_at DESC 
                      LIMIT 10";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format dates for display
            foreach ($notifications as &$notification) {
                $notification['created_at'] = date('M d, Y h:i A', strtotime($notification['created_at']));
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'count':
        // Count unread notifications
        try {
            $query = "SELECT COUNT(*) as count FROM notifications 
                      WHERE user_id = ? AND is_read = 0";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['count'] ?? 0;
            
            echo json_encode([
                'success' => true,
                'count' => (int)$count
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'mark_all_read':
        // Mark all notifications as read
        try {
            $query = "UPDATE notifications 
                      SET is_read = 1 
                      WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}
