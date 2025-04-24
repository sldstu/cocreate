<?php
// Simple debugging file to log AJAX requests and responses
// This will help identify what's causing the JSON parsing error

// Enable error reporting for proper debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to client, just log them

// Define the app name to allow access to this file
define('APP_NAME', 'CoCreate');

// Create a log file
$log_file = __DIR__ . '/../../../debug_log.txt';

// Log the request information
$time = date('Y-m-d H:i:s');
$request_method = $_SERVER['REQUEST_METHOD'];
$request_url = $_SERVER['REQUEST_URI'];
$post_data = print_r($_POST, true);

// Write to log file
file_put_contents($log_file, "===== DEBUG LOG: $time =====\n", FILE_APPEND);
file_put_contents($log_file, "Request Method: $request_method\n", FILE_APPEND);
file_put_contents($log_file, "Request URL: $request_url\n", FILE_APPEND);
file_put_contents($log_file, "POST Data: \n$post_data\n", FILE_APPEND);

// Capture errors
function capture_error($errno, $errstr, $errfile, $errline) {
    global $log_file;
    $error_msg = "PHP Error [$errno]: $errstr in $errfile on line $errline\n";
    file_put_contents($log_file, $error_msg, FILE_APPEND);
    return true; // Don't execute PHP's internal error handler
}

// Set error handler
set_error_handler("capture_error");

// Process the original request by including the task_handler.php file
// Start output buffering to capture the output
ob_start();

// Set proper content type header for JSON
header('Content-Type: application/json');

try {
    // Include the task handler
    include(__DIR__ . '/task_handler.php');
} catch (Exception $e) {
    // Log the exception
    file_put_contents($log_file, "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Output JSON error
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Get the output
$output = ob_get_clean();

// Log the output
file_put_contents($log_file, "Response: \n$output\n\n", FILE_APPEND);

// Check if the output appears to be valid JSON
if (!empty($output)) {
    $first_char = substr(trim($output), 0, 1);
    if ($first_char != '{' && $first_char != '[') {
        file_put_contents($log_file, "WARNING: Output doesn't appear to be valid JSON\n", FILE_APPEND);
    } else {
        // Attempt to parse the JSON to verify
        json_decode($output);
        if (json_last_error() !== JSON_ERROR_NONE) {
            file_put_contents($log_file, "JSON Parse Error: " . json_last_error_msg() . "\n", FILE_APPEND);
        }
    }
}

// Send the response back to the client
echo $output;