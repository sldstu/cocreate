<?php
// Turn off PHP's default error display and enable our custom error handler
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set content type header for JSON
header('Content-Type: application/json');

// Create a log file for debugging
$log_file = __DIR__ . '/../../../debug_log.txt';

// Log information about the request
file_put_contents($log_file, "===== JSON TEST LOG: " . date('Y-m-d H:i:s') . " =====\n", FILE_APPEND);
file_put_contents($log_file, "Request URI: " . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND);

// Start output buffering to catch any unexpected output
ob_start();

// Custom error handler that logs errors instead of displaying them
function json_error_handler($errno, $errstr, $errfile, $errline) {
    global $log_file;
    $error_msg = "Error [$errno]: $errstr in $errfile on line $errline\n";
    file_put_contents($log_file, $error_msg, FILE_APPEND);
    return true; // Don't execute PHP's internal error handler
}

// Set our custom error handler
set_error_handler("json_error_handler");

try {
    // Test database connection
    file_put_contents($log_file, "Testing database connection...\n", FILE_APPEND);
    require_once '../../../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    // Test session
    file_put_contents($log_file, "Testing session...\n", FILE_APPEND);
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $session_info = [];
    if (isset($_SESSION['user_id'])) {
        $session_info['user_id'] = $_SESSION['user_id'];
        $session_info['username'] = $_SESSION['username'] ?? 'unknown';
        $session_info['role_id'] = $_SESSION['role_id'] ?? 'unknown';
    } else {
        $session_info['status'] = 'No active session found';
    }
    
    // Output successful test result as JSON
    $response = [
        'success' => true,
        'message' => 'JSON test successful',
        'database_connected' => ($conn !== null),
        'session' => $session_info,
        'time' => date('Y-m-d H:i:s')
    ];
    
    // Clean the buffer
    ob_clean();
    
    // Output JSON
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log the exception
    file_put_contents($log_file, "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Clean the buffer
    ob_clean();
    
    // Output JSON error
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Get and log the output
$output = ob_get_contents();
file_put_contents($log_file, "Response Output: \n" . $output . "\n\n", FILE_APPEND);

// End output buffering and flush
ob_end_flush();
?>