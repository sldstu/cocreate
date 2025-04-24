<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Log the data received
error_log("Test file accessed");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Form data received successfully',
    'data' => [
        'post' => $_POST,
        'files' => $_FILES
    ]
]);
?>