<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define APP_NAME to prevent direct access in included files
define('APP_NAME', true);

// Include necessary files
require_once __DIR__ . '../../../../config/config.php';
require_once __DIR__ . '../../../../config/database.php';
require_once 'events.class.php';

// Check if user is logged in and has correct role (using role_id instead of role)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    error_log("User not authorized. user_id: " . ($_SESSION['user_id'] ?? 'not set') . ", role_id: " . ($_SESSION['role_id'] ?? 'not set'));
    header('Location: ../../../login.php');
    exit;
}

// Enable detailed error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log the submission
error_log("Event creation attempt received");
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

try {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Test database connection
    $testQuery = "SELECT 1";
    $testStmt = $conn->query($testQuery);
    if (!$testStmt) {
        throw new Exception("Database connection failed");
    }
    
    // Verify user exists in the database
    $userQuery = "SELECT user_id FROM users WHERE user_id = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        throw new Exception("User ID " . $_SESSION['user_id'] . " does not exist in the database");
    }
    
    // Create instance of events manager
    $eventsManager = new EventsManager($conn);
    
    // Prepare event data - ENSURE created_by field uses the actual session user ID
    $eventData = [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'start_date' => $_POST['start_date'] . ' ' . ($_POST['start_time'] ?? '00:00:00'),
        'end_date' => $_POST['end_date'] . ' ' . ($_POST['end_time'] ?? '00:00:00'),
        'location' => $_POST['location'] ?? '',
        'location_map_url' => $_POST['location_map_url'] ?? '',
        'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : $_SESSION['department_id'] ?? null,
        'type_id' => !empty($_POST['type_id']) ? intval($_POST['type_id']) : null,
        'status' => $_POST['status'] ?? 'draft',
        'visibility' => $_POST['visibility'] ?? 'public',
        'speakers' => $_POST['speakers'] ?? '',
        'created_by' => (int)$_SESSION['user_id'], // IMPORTANT: Cast to integer and use the actual user ID
        'featured_image' => '' // Default empty
    ];
    
    error_log("Event data prepared: " . print_r($eventData, true));
    
    // Validate data
    if (empty($eventData['title'])) {
        throw new Exception('Event title is required.');
    }
    
    if (empty($eventData['type_id'])) {
        throw new Exception('Event type is required.');
    }
    
    // Handle featured image upload
    if(isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../uploads/event_images/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_tmp = $_FILES['featured_image']['tmp_name'];
        $file_name = $_FILES['featured_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed file extensions
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if(in_array($file_ext, $allowed_ext)) {
            // Generate unique filename
            $new_file_name = 'event_' . uniqid() . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;
            
            if(move_uploaded_file($file_tmp, $destination)) {
                $eventData['featured_image'] = 'uploads/event_images/' . $new_file_name;
            } else {
                error_log("Failed to move uploaded file: " . error_get_last()['message']);
            }
        }
    }
    
    // Create the event
    error_log("About to call createEvent method with user_id: " . $eventData['created_by']);
    $eventId = $eventsManager->createEvent($eventData);
    
    if ($eventId) {
        error_log("Event created successfully with ID: $eventId");
        
        // Handle attachments upload
        if(isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
            $upload_dir = '../../../uploads/event_attachments/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Process each attachment
            for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                if($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['attachments']['tmp_name'][$i];
                    $file_name = $_FILES['attachments']['name'][$i];
                    $file_size = $_FILES['attachments']['size'][$i];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Allowed file extensions
                    $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'png', 'jpg', 'jpeg', 'gif'];
                    
                    if(in_array($file_ext, $allowed_ext) && $file_size <= 5000000) { // 5MB limit
                        // Generate unique filename
                        $new_file_name = 'attachment_' . uniqid() . '.' . $file_ext;
                        $destination = $upload_dir . $new_file_name;
                        
                        if(move_uploaded_file($file_tmp, $destination)) {
                            // Save attachment to database
                            $attachmentData = [
                                'event_id' => $eventId,
                                'file_name' => $file_name,
                                'file_path' => 'uploads/event_attachments/' . $new_file_name,
                                'file_size' => $file_size
                            ];
                            $eventsManager->addEventAttachment($attachmentData);
                        }
                    }
                }
            }
        }
        
        $_SESSION['success'] = 'Event created successfully.';
        header('Location: ../../../index.php?page=lead_events');
        exit;
    } else {
        error_log("Failed to create event: Event ID is false or 0");
        throw new Exception('Failed to create event. Please check error logs for details.');
    }
    
} catch (Exception $e) {
    error_log("Exception in create_event.php: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
    header('Location: ../../../index.php?page=lead_events&action=add');
    exit;
}