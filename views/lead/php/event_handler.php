<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '../../../../config/config.php';
require_once __DIR__ . '../../../../config/database.php';
require_once __DIR__ . '../../../../includes/functions.php';
require_once 'events.class.php';

// Define APP_NAME to prevent direct access in included files
define('APP_NAME', true);

// Check if user is logged in and has lead role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lead') {
    header('Location: ../../../index.php?page=login');
    exit;
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();
$eventsManager = new EventsManager($conn);

// Enable error logging
error_log("Action received: " . ($_POST['action'] ?? $_GET['action'] ?? 'none'));

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    error_log("POST Action received: $action");

    switch ($action) {
        case 'createEvent':
            // Debug log for received data
            error_log("Received POST data for createEvent: " . print_r($_POST, true));
            
            // Prepare base event data
            $eventData = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'start_date' => $_POST['start_date'] . ' ' . ($_POST['start_time'] ?? '00:00:00'),
                'end_date' => $_POST['end_date'] . ' ' . ($_POST['end_time'] ?? '00:00:00'),
                'location' => $_POST['location'] ?? '',
                'location_map_url' => $_POST['location_map_url'] ?? '',
                'type_id' => $_POST['type_id'] ?? null,
                'status' => $_POST['status'] ?? 'draft',
                'visibility' => $_POST['visibility'] ?? 'public',
                'speakers' => $_POST['speakers'] ?? '',
                'max_participants' => !empty($_POST['max_participants']) ? $_POST['max_participants'] : null,
                'created_by' => $_SESSION['user_id'] ?? 0,
                'featured_image' => '', // Default empty
                'completion_status' => 0, // New event starts at 0% completion
                'ready_for_publish' => 0 // New event starts as not ready
            ];

            // Validate data
            if (empty($eventData['title'])) {
                error_log("Validation error: Title is required");
                $_SESSION['error'] = 'Event title is required.';
                header('Location: ../../../index.php?page=lead_events&action=add');
                exit;
            }
            
            if (empty($eventData['type_id'])) {
                error_log("Validation error: Event type is required");
                $_SESSION['error'] = 'Event type is required.';
                header('Location: ../../../index.php?page=lead_events&action=add');
                exit;
            }
            
            // Handle featured image upload
            if(isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                error_log("Processing featured image upload");
                $upload_dir = '../../../uploads/event_images/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                    error_log("Created directory: $upload_dir");
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
                        error_log("Featured image uploaded: " . $eventData['featured_image']);
                    } else {
                        error_log("Failed to move uploaded file from $file_tmp to $destination");
                    }
                } else {
                    error_log("Invalid file type: $file_ext");
                }
            } else {
                error_log("No featured image uploaded or upload error: " . ($_FILES['featured_image']['error'] ?? 'not set'));
            }

            error_log("Event data for creation: " . print_r($eventData, true));

            try {
                $eventId = $eventsManager->createEvent($eventData);
                if ($eventId) {
                    error_log("Event successfully created with ID: $eventId");
                    
                    // Log the event creation action
                    logAction($_SESSION['user_id'], 'Created new event: ' . $eventData['title'] . ' (Event ID: ' . $eventId . ')');
                    
                    // Handle attachments upload
                    if(isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
                        $upload_dir = '../../../uploads/event_attachments/';
                        
                        // Create directory if it doesn't exist
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Process each attachment
                        $attachments_files = reArrayFiles($_FILES['attachments']);
                        foreach($attachments_files as $file) {
                            if($file['error'] === UPLOAD_ERR_OK) {
                                $file_tmp = $file['tmp_name'];
                                $file_name = $file['name'];
                                $file_size = $file['size'];
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
                    $_SESSION['error'] = 'Failed to create event. Please check error logs for details.';
                    header('Location: ../../../index.php?page=lead_events&action=add');
                    exit;
                }
            } catch (Exception $e) {
                error_log("Exception while creating event: " . $e->getMessage());
                $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
                header('Location: ../../../index.php?page=lead_events&action=add');
                exit;
            }
            break; // Add break statement to prevent fall-through to next case

        case 'update_event':
            $eventId = $_POST['event_id'] ?? 0;
            $eventData = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'start_date' => $_POST['start_date'] . ' ' . ($_POST['start_time'] ?? '00:00:00'),
                'end_date' => $_POST['end_date'] . ' ' . ($_POST['end_time'] ?? '00:00:00'),
                'location' => $_POST['location'] ?? '',
                'location_map_url' => $_POST['location_map_url'] ?? '',
                'type_id' => $_POST['type_id'] ?? null,
                'status' => $_POST['status'] ?? 'draft',
                'visibility' => $_POST['visibility'] ?? 'public',
                'speakers' => $_POST['speakers'] ?? '',
                'max_participants' => !empty($_POST['max_participants']) ? $_POST['max_participants'] : null
            ];

            // Handle featured image upload for update
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
                        // Get current image to delete later
                        $currentEvent = $eventsManager->getEventById($eventId);
                        $oldImage = $currentEvent['featured_image'] ?? '';
                        
                        $eventData['featured_image'] = 'uploads/event_images/' . $new_file_name;
                        
                        // Delete old image if exists
                        if(!empty($oldImage) && file_exists('../../../' . $oldImage)) {
                            @unlink('../../../' . $oldImage);
                        }
                    }
                }
            }

            error_log("Event data for update: " . print_r($eventData, true));

            $success = $eventsManager->updateEvent($eventId, $eventData);
            
            if ($success) {
                // Log the event update action
                logAction($_SESSION['user_id'], 'Updated event: ' . $eventData['title'] . ' (Event ID: ' . $eventId . ')');
                
                // Handle attachments upload for update
                if(isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
                    $upload_dir = '../../../uploads/event_attachments/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Process each attachment
                    $attachments_files = reArrayFiles($_FILES['attachments']);
                    foreach($attachments_files as $file) {
                        if($file['error'] === UPLOAD_ERR_OK) {
                            $file_tmp = $file['tmp_name'];
                            $file_name = $file['name'];
                            $file_size = $file['size'];
                            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            
                            // Allowed file extensions
                            $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];
                            
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
                
                $_SESSION['success'] = 'Event updated successfully.';
                header('Location: ../../../index.php?page=lead_events');
                exit;
            } else {
                $_SESSION['error'] = 'Failed to update event. Please try again.';
                header('Location: ../../../index.php?page=lead_events&action=edit&event_id=' . $eventId);
                exit;
            }

        case 'update_completion_status':
            $event_id = $_POST['event_id'] ?? 0;
            
            if (!$event_id) {
                echo json_encode(['success' => false, 'message' => 'Event ID is required']);
                exit;
            }
            
            $success = $eventsManager->updateEventCompletionStatus($event_id);
            
            if ($success) {
                // Get the updated event details
                $event = $eventsManager->getEventById($event_id);
                
                // Log completion status update
                logAction($_SESSION['user_id'], 'Updated completion status for event ID: ' . $event_id . 
                    ' (New status: ' . ($event['completion_status'] ?? 0) . '%, Ready: ' . 
                    (($event['ready_for_publish'] ?? 0) ? 'Yes' : 'No') . ')');
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Event completion status updated', 
                    'completion_status' => $event['completion_status'] ?? 0,
                    'ready_for_publish' => $event['ready_for_publish'] ?? 0
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update event status']);
            }
            exit;
        
        case 'update_visibility':
            $event_id = $_POST['event_id'] ?? 0;
            $visibility = $_POST['visibility'] ?? '';
            
            if (!$event_id || !in_array($visibility, ['draft', 'private', 'unlisted', 'public'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
                exit;
            }
            
            $success = $eventsManager->updateEventVisibility($event_id, $visibility);
            
            if ($success) {
                // Get event title for logging
                $event = $eventsManager->getEventById($event_id);
                $eventTitle = $event['title'] ?? 'Unknown Event';
                
                // Log visibility change
                logAction($_SESSION['user_id'], 'Changed visibility to ' . $visibility . ' for event: ' . $eventTitle . ' (Event ID: ' . $event_id . ')');
                
                echo json_encode(['success' => true, 'message' => 'Event visibility updated to ' . $visibility]);
            } else {
                $message = ($visibility === 'public') ? 
                    'Cannot publish event: All tasks must be completed first' : 
                    'Failed to update event visibility';
                    
                echo json_encode(['success' => false, 'message' => $message]);
            }
            exit;

        default:
            $_SESSION['error'] = 'Invalid action.';
            header('Location: ../../../index.php?page=lead_events');
            exit;
    }
} else {
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
    error_log("GET Action received: $action");

    switch ($action) {
        case 'delete_event':
            handleDeleteEvent($eventsManager);
            break;
            
        case 'delete_attachment':
            handleDeleteAttachment($eventsManager);
            break;
            
        case 'delete_comment':
            handleDeleteComment($eventsManager, $conn);
            break;
            
        default:
            // Invalid action
            $_SESSION['error'] = 'Invalid action.';
            header('Location: ../../../index.php?page=lead_events');
            exit;
    }
}

/**
* Handle event deletion
*/
function handleDeleteEvent($eventsManager) {
  if (empty($_GET['event_id'])) {
      $_SESSION['error'] = 'Event ID is required.';
      header('Location: ../../../index.php?page=lead_events');
      exit;
  }
  
  $event_id = $_GET['event_id'];
  
  // Get event details to delete image file
  $event = $eventsManager->getEventById($event_id);
  $eventTitle = $event['title'] ?? 'Unknown Event';
  
  // Delete event
  $success = $eventsManager->deleteEvent($event_id);
  
  if ($success) {
      // Log event deletion
      logAction($_SESSION['user_id'], 'Deleted event: ' . $eventTitle . ' (Event ID: ' . $event_id . ')');
      
      // Delete featured image file if exists
      if (!empty($event['featured_image']) && file_exists('../../../' . $event['featured_image'])) {
          @unlink('../../../' . $event['featured_image']);
      }
      
      // Get attachments to delete files
      $attachments = $eventsManager->getEventAttachments($event_id);
      foreach ($attachments as $attachment) {
          if (!empty($attachment['file_path']) && file_exists('../../../' . $attachment['file_path'])) {
              @unlink('../../../' . $attachment['file_path']);
          }
      }
      
      $_SESSION['success'] = 'Event deleted successfully.';
  } else {
      $_SESSION['error'] = 'Failed to delete event.';
  }
  
  header('Location: ../../../index.php?page=lead_events');
  exit;
}

/**
* Handle attachment deletion
*/
function handleDeleteAttachment($eventsManager) {
  if (empty($_GET['attachment_id']) || empty($_GET['event_id'])) {
      $_SESSION['error'] = 'Attachment ID and Event ID are required.';
      header('Location: ../../../index.php?page=lead_events');
      exit;
  }
  
  $attachment_id = $_GET['attachment_id'];
  $event_id = $_GET['event_id'];
  
  // Get attachment details to delete file
  $query = "SELECT file_path FROM event_attachments WHERE attachment_id = ?";
  $stmt = $eventsManager->conn->prepare($query);
  $stmt->bindParam(1, $attachment_id, PDO::PARAM_INT);
  $stmt->execute();
  $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
  
  // Delete attachment
  $success = $eventsManager->deleteEventAttachment($attachment_id);
  
  if ($success) {
      // Log attachment deletion
      $fileName = basename($attachment['file_path'] ?? 'unknown');
      logAction($_SESSION['user_id'], 'Deleted attachment: ' . $fileName . ' from Event ID: ' . $event_id);
      
      // Delete file if exists
      if (!empty($attachment['file_path']) && file_exists('../../../' . $attachment['file_path'])) {
          @unlink('../../../' . $attachment['file_path']);
      }
      
      $_SESSION['success'] = 'Attachment deleted successfully.';
  } else {
      $_SESSION['error'] = 'Failed to delete attachment.';
  }
  
  header('Location: ../../../index.php?page=lead_events&action=edit&event_id=' . $event_id);
  exit;
}

/**
* Handle comment deletion
*/
function handleDeleteComment($eventsManager, $conn) {
  if (empty($_GET['comment_id']) || empty($_GET['event_id'])) {
      $_SESSION['error'] = 'Comment ID and Event ID are required.';
      header('Location: ../../../index.php?page=lead_events');
      exit;
  }
  
  $comment_id = $_GET['comment_id'];
  $event_id = $_GET['event_id'];
  
  try {
      // Get event title for the log
      $event = $eventsManager->getEventById($event_id);
      $eventTitle = $event['title'] ?? 'Unknown Event';
      
      // Delete comment
      $query = "DELETE FROM event_comments WHERE comment_id = ?";
      $stmt = $conn->prepare($query);
      $stmt->bindParam(1, $comment_id, PDO::PARAM_INT);
      $success = $stmt->execute();
      
      if ($success) {
          // Log comment deletion
          logAction($_SESSION['user_id'], 'Deleted comment (ID: ' . $comment_id . ') from event: ' . $eventTitle);
          
          $_SESSION['success'] = 'Comment deleted successfully.';
      } else {
          $_SESSION['error'] = 'Failed to delete comment.';
      }
  } catch (PDOException $e) {
      error_log("Database error in handleDeleteComment: " . $e->getMessage());
      $_SESSION['error'] = 'Database error occurred while deleting comment.';
  }
  
  header('Location: ../../../index.php?page=lead_events&event_id=' . $event_id);
  exit;
}

/**
* Re-arrange $_FILES array for multiple file uploads
*/
function reArrayFiles($file_post) {
  $file_array = array();
  $file_count = count($file_post['name']);
  $file_keys = array_keys($file_post);
  
  for ($i = 0; $i < $file_count; $i++) {
      foreach ($file_keys as $key) {
          $file_array[$i][$key] = $file_post[$key][$i];
      }
  }
  
  return $file_array;
}
?>
