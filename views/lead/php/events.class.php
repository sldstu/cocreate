<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

class EventsManager
{
  protected $conn;

  public function __construct($conn)
  {
    $this->conn = $conn;
    
    // Make sure functions.php is included to have access to logAction
    if (!function_exists('logAction')) {
      require_once __DIR__ . '../../../../includes/functions.php';
    }
  }

  /**
   * Get all events with optional filtering
   * 
   * @param string $status Filter by status (optional)
   * @param string $search Search term (optional)
   * @param array $filters Additional filters (date_from, date_to, type, visibility)
   * @return array Array of events
   */
  public function getAllEvents($status = '', $search = '', $filters = [])
  {
    try {
      $query = "SELECT e.*, t.name as type_name, t.color as type_color
                 FROM events e
                 LEFT JOIN event_types t ON e.type_id = t.type_id
                 WHERE 1=1";

      $params = [];

      // Filter by status
      if (!empty($status) && $status !== 'all') {
        // Handle "draft" status which is actually a visibility
        if ($status === 'draft') {
          $query .= " AND e.visibility = :visibility";
          $params[':visibility'] = 'draft';
        } else {
          $query .= " AND e.status = :status";
          $params[':status'] = $status;
        }
      }

      // Filter by search term
      if (!empty($search)) {
        $query .= " AND (e.title LIKE :search1 OR e.description LIKE :search2 OR e.location LIKE :search3)";
        $searchTerm = "%$search%";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
      }

      // Apply date range filters
      if (!empty($filters['date_from'])) {
        $query .= " AND DATE(e.start_date) >= :date_from";
        $params[':date_from'] = $filters['date_from'];
      }

      if (!empty($filters['date_to'])) {
        $query .= " AND DATE(e.start_date) <= :date_to";
        $params[':date_to'] = $filters['date_to'];
      }

      // Filter by event type
      if (!empty($filters['type'])) {
        $query .= " AND e.type_id = :type_id";
        $params[':type_id'] = $filters['type'];
      }

      // Filter by visibility
      if (!empty($filters['visibility'])) {
        $query .= " AND e.visibility = :visibility_filter";
        $params[':visibility_filter'] = $filters['visibility'];
      }

      // Filter by additional status (from dropdown)
      if (!empty($filters['status'])) {
        $query .= " AND e.status = :status_filter";
        $params[':status_filter'] = $filters['status'];
      }

      // Filter by completion status
      if (isset($filters['completion_status'])) {
        if ($filters['completion_status'] == 'completed') {
          $query .= " AND e.completion_status = 100";
        } elseif ($filters['completion_status'] == 'in_progress') {
          $query .= " AND e.completion_status > 0 AND e.completion_status < 100";
        } elseif ($filters['completion_status'] == 'not_started') {
          $query .= " AND (e.completion_status = 0 OR e.completion_status IS NULL)";
        }
      }

      // Filter by ready for publish
      if (isset($filters['ready_for_publish'])) {
        $query .= " AND e.ready_for_publish = :ready_for_publish";
        $params[':ready_for_publish'] = $filters['ready_for_publish'] ? 1 : 0;
      }

      $query .= " ORDER BY e.start_date DESC";

      $stmt = $this->conn->prepare($query);

      foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
      }

      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Database error in getAllEvents: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Get event by ID
   * 
   * @param int $event_id Event ID
   * @return array|false Event data or false if not found
   */
  public function getEventById($event_id)
  {
    try {
      $query = "SELECT e.*, t.name as type_name, t.color as type_color, 
                 u.first_name, u.last_name
                 FROM events e
                 LEFT JOIN event_types t ON e.type_id = t.type_id
                 LEFT JOIN users u ON e.created_by = u.user_id
                 WHERE e.event_id = :event_id";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
      $stmt->execute();
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Database error in getEventById: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Get event comments
   * 
   * @param int $event_id Event ID
   * @return array Comments
   */
  public function getEventComments($event_id)
  {
    try {
      $query = "SELECT c.*, u.first_name, u.last_name, u.username
                     FROM event_comments c
                     LEFT JOIN users u ON c.user_id = u.user_id
                     WHERE c.event_id = ? AND c.parent_comment_id IS NULL
                     ORDER BY c.created_at DESC";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(1, $event_id, PDO::PARAM_INT);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Database error in getEventComments: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Get event attachments
   * 
   * @param int $event_id Event ID
   * @return array Attachments
   */
  public function getEventAttachments($event_id)
  {
    try {
      $query = "SELECT * FROM event_attachments WHERE event_id = ?";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(1, $event_id, PDO::PARAM_INT);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Database error in getEventAttachments: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Get event RSVPs
   * 
   * @param int $event_id Event ID
   * @return array RSVPs
   */
  public function getEventRSVPs($event_id)
  {
    try {
      $query = "SELECT r.*, u.first_name, u.last_name, u.username, u.email
                     FROM event_rsvps r
                     LEFT JOIN users u ON r.user_id = u.user_id
                     WHERE r.event_id = ?
                     ORDER BY r.created_at DESC";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(1, $event_id, PDO::PARAM_INT);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Database error in getEventRSVPs: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Count RSVPs by status
   * 
   * @param int $event_id Event ID
   * @param string $status RSVP status (going, interested, not_going)
   * @return int Count
   */
  public function countEventRSVPs($event_id, $status = '')
  {
    try {
      $query = "SELECT COUNT(*) FROM event_rsvps WHERE event_id = ?";
      $params = [$event_id];

      if (!empty($status)) {
        $query .= " AND status = ?";
        $params[] = $status;
      }

      $stmt = $this->conn->prepare($query);

      for ($i = 0; $i < count($params); $i++) {
        $stmt->bindParam($i + 1, $params[$i]);
      }

      $stmt->execute();
      return $stmt->fetchColumn();
    } catch (PDOException $e) {
      error_log("Database error in countEventRSVPs: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Count total events
   * 
   * @return int Count
   */
  public function countEvents()
  {
    try {
      $query = "SELECT COUNT(*) FROM events";
      $stmt = $this->conn->prepare($query);
      $stmt->execute();
      return $stmt->fetchColumn();
    } catch (PDOException $e) {
      error_log("Database error in countEvents: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Count events by status
   * 
   * @param string $status Event status
   * @return int Count
   */
  public function countEventsByStatus($status)
  {
    try {
      $query = "SELECT COUNT(*) FROM events WHERE status = ?";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(1, $status);
      $stmt->execute();
      return $stmt->fetchColumn();
    } catch (PDOException $e) {
      error_log("Database error in countEventsByStatus: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Create a new event
   * 
   * @param array $eventData Event data
   * @return int|false New event ID or false on failure
   */
  public function createEvent($eventData)
  {
    // Debug log to verify function is called
    error_log("Creating event: " . ($eventData['title'] ?? 'Unknown Title'));
    
    try {
      $query = "INSERT INTO events (
    title, description, start_date, end_date, location, location_map_url,
    type_id, status, visibility, featured_image, speakers, created_by,
    max_participants, completion_status, ready_for_publish
) VALUES (
    :title, :description, :start_date, :end_date, :location, :location_map_url,
    :type_id, :status, :visibility, :featured_image, :speakers, :created_by,
    :max_participants, :completion_status, :ready_for_publish
)";

      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':title', $eventData['title']);
      $stmt->bindParam(':description', $eventData['description']);
      $stmt->bindParam(':start_date', $eventData['start_date']);
      $stmt->bindParam(':end_date', $eventData['end_date']);
      $stmt->bindParam(':location', $eventData['location']);
      $stmt->bindParam(':location_map_url', $eventData['location_map_url']);
      $stmt->bindParam(':type_id', $eventData['type_id'], PDO::PARAM_INT);
      $stmt->bindParam(':status', $eventData['status']);
      $stmt->bindParam(':visibility', $eventData['visibility']);
      $stmt->bindParam(':featured_image', $eventData['featured_image']);
      $stmt->bindParam(':speakers', $eventData['speakers']);
      $stmt->bindParam(':created_by', $eventData['created_by'], PDO::PARAM_INT);
      
      // New fields
      $maxParticipants = isset($eventData['max_participants']) ? $eventData['max_participants'] : null;
      $completionStatus = isset($eventData['completion_status']) ? $eventData['completion_status'] : 0;
      $readyForPublish = isset($eventData['ready_for_publish']) ? $eventData['ready_for_publish'] : 0;
      
      $stmt->bindParam(':max_participants', $maxParticipants, PDO::PARAM_INT);
      $stmt->bindParam(':completion_status', $completionStatus, PDO::PARAM_INT);
      $stmt->bindParam(':ready_for_publish', $readyForPublish, PDO::PARAM_INT);

      $success = $stmt->execute();
      
      if ($success) {
        $eventId = $this->conn->lastInsertId();
        
        // Log the event creation
        if (function_exists('logAction') && isset($eventData['created_by'])) {
          error_log("Logging event creation: Event ID " . $eventId);
          logAction($eventData['created_by'], 'Created new event: ' . $eventData['title'] . ' (Event ID: ' . $eventId . ')');
        } else {
          error_log("logAction function not available for event creation");
        }
        
        return $eventId;
      }
      
      return false;
    } catch (PDOException $e) {
      error_log("Database error in createEvent: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Update an existing event
   * 
   * @param int $event_id Event ID
   * @param array $eventData Event data
   * @return bool Success or failure
   */
  public function updateEvent($event_id, $eventData)
  {
    // Debug log to verify function is called
    error_log("Updating event ID " . $event_id . ": " . ($eventData['title'] ?? 'Unknown Title'));
    
    try {
      $query = "UPDATE events SET
                  title = :title, 
                  description = :description, 
                  start_date = :start_date, 
                  end_date = :end_date, 
                  location = :location, 
                  location_map_url = :location_map_url, 
                  type_id = :type_id, 
                  status = :status, 
                  visibility = :visibility, 
                  featured_image = :featured_image, 
                  speakers = :speakers,
                  max_participants = :max_participants,
                  updated_at = NOW()
                  WHERE event_id = :event_id";

      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':title', $eventData['title']);
      $stmt->bindParam(':description', $eventData['description']);
      $stmt->bindParam(':start_date', $eventData['start_date']);
      $stmt->bindParam(':end_date', $eventData['end_date']);
      $stmt->bindParam(':location', $eventData['location']);
      $stmt->bindParam(':location_map_url', $eventData['location_map_url']);
      $stmt->bindParam(':type_id', $eventData['type_id'], PDO::PARAM_INT);
      $stmt->bindParam(':status', $eventData['status']);
      $stmt->bindParam(':visibility', $eventData['visibility']);
      $stmt->bindParam(':featured_image', $eventData['featured_image']);
      $stmt->bindParam(':speakers', $eventData['speakers']);
      
      // New field
      $maxParticipants = isset($eventData['max_participants']) ? $eventData['max_participants'] : null;
      $stmt->bindParam(':max_participants', $maxParticipants, PDO::PARAM_INT);
      
      $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);

      $success = $stmt->execute();
      
      if ($success) {
        // Log the event update
        if (function_exists('logAction') && isset($_SESSION['user_id'])) {
          error_log("Logging event update: Event ID " . $event_id);
          logAction($_SESSION['user_id'], 'Updated event: ' . $eventData['title'] . ' (Event ID: ' . $event_id . ')');
        } else {
          error_log("logAction function not available for event update or user_id not found");
        }
      }
      
      return $success;
    } catch (PDOException $e) {
      error_log("Database error in updateEvent: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Delete an event
   * 
   * @param int $event_id Event ID
   * @return bool Success or failure
   */
  public function deleteEvent($event_id)
  {
    // Debug log to verify function is called
    error_log("Deleting event ID " . $event_id);
    
    try {
      // Get event details for logging before deletion
      $event = $this->getEventById($event_id);
      $eventTitle = $event ? $event['title'] : 'Unknown Event';
      
      // Begin transaction to ensure all related data is deleted
      $this->conn->beginTransaction();

      // Delete attachments
      $query = "DELETE FROM event_attachments WHERE event_id = :event_id";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
      $stmt->execute();

      // Delete RSVPs
      $query = "DELETE FROM event_rsvps WHERE event_id = :event_id";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
      $stmt->execute();

      // Delete comments
      $query = "DELETE FROM event_comments WHERE event_id = :event_id";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
      $stmt->execute();

      // Delete the event
      $query = "DELETE FROM events WHERE event_id = :event_id";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
      $success = $stmt->execute();

      // Commit the transaction
      $this->conn->commit();
      
      if ($success) {
        // Log the event deletion
        if (function_exists('logAction') && isset($_SESSION['user_id'])) {
          error_log("Logging event deletion: Event ID " . $event_id);
          logAction($_SESSION['user_id'], 'Deleted event: ' . $eventTitle . ' (Event ID: ' . $event_id . ')');
        } else {
          error_log("logAction function not available for event deletion or user_id not found");
        }
      }
      
      return $success;
    } catch (PDOException $e) {
      // Rollback the transaction on error
      $this->conn->rollBack();
      error_log("Database error in deleteEvent: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Get all event types
   * 
   * @return array Event types
   */
  public function getEventTypes()
  {
    try {
      $query = "SELECT * FROM event_types ORDER BY name";
      $stmt = $this->conn->prepare($query);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Database error in getEventTypes: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Get all departments
   * 
   * @return array Departments
   */
  public function getDepartments()
  {
    try {
      $query = "SELECT * FROM departments ORDER BY name";
      $stmt = $this->conn->prepare($query);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Database error in getDepartments: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Add attachment to event
   * 
   * @param array $attachmentData Attachment data (event_id, file_name, file_path, file_size)
   * @return bool Success or failure
   */
  public function addEventAttachment($attachmentData)
  {
    try {
      $query = "INSERT INTO event_attachments (event_id, file_name, file_path, file_size) 
                VALUES (:event_id, :file_name, :file_path, :file_size)";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':event_id', $attachmentData['event_id'], PDO::PARAM_INT);
      $stmt->bindParam(':file_name', $attachmentData['file_name']);
      $stmt->bindParam(':file_path', $attachmentData['file_path']);
      $stmt->bindParam(':file_size', $attachmentData['file_size'], PDO::PARAM_INT);
      return $stmt->execute();
    } catch (PDOException $e) {
      error_log("Database error in addEventAttachment: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Delete event attachment
   * 
   * @param int $attachment_id Attachment ID
   * @return bool Success or failure
   */
  public function deleteEventAttachment($attachment_id)
  {
    try {
      // Get attachment details for logging
      $query = "SELECT a.*, e.title as event_title 
                FROM event_attachments a 
                JOIN events e ON a.event_id = e.event_id 
                WHERE a.attachment_id = :attachment_id";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':attachment_id', $attachment_id, PDO::PARAM_INT);
      $stmt->execute();
      $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
      
      // Delete the attachment
      $query = "DELETE FROM event_attachments WHERE attachment_id = :attachment_id";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':attachment_id', $attachment_id, PDO::PARAM_INT);
      $success = $stmt->execute();
      
      if ($success && $attachment) {
        // Log the attachment deletion
        $fileName = basename($attachment['file_path'] ?? 'unknown');
        $eventTitle = $attachment['event_title'] ?? 'Unknown Event';
        $eventId = $attachment['event_id'] ?? 0;
        
        if (function_exists('logAction') && isset($_SESSION['user_id'])) {
          error_log("Logging attachment deletion: Attachment ID " . $attachment_id);
          logAction($_SESSION['user_id'], 'Deleted attachment: ' . $fileName . ' from event: ' . $eventTitle . ' (Event ID: ' . $eventId . ')');
        } else {
          error_log("logAction function not available for attachment deletion or user_id not found");
        }
      }
      
      return $success;
    } catch (PDOException $e) {
      error_log("Database error in deleteEventAttachment: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Get event statistics
   * 
   * @return array Statistics
   */
  public function getEventStatistics()
  {
    try {
      $stats = [
        'total' => $this->countEvents(),
        'upcoming' => $this->countEventsByStatus('upcoming'),
        'ongoing' => $this->countEventsByStatus('ongoing'),
        'completed' => $this->countEventsByStatus('completed'),
        'draft' => $this->countEventsByStatus('draft')
      ];

      // Get total RSVPs
      $query = "SELECT COUNT(*) FROM event_rsvps";
      $stmt = $this->conn->prepare($query);
      $stmt->execute();
      $stats['total_rsvps'] = $stmt->fetchColumn();

      // Get RSVPs by status
      $query = "SELECT status, COUNT(*) as count FROM event_rsvps GROUP BY status";
      $stmt = $this->conn->prepare($query);
      $stmt->execute();
      $rsvp_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

      $stats['rsvps_going'] = $rsvp_stats['going'] ?? 0;
      $stats['rsvps_interested'] = $rsvp_stats['interested'] ?? 0;
      $stats['rsvps_not_going'] = $rsvp_stats['not_going'] ?? 0;

      return $stats;
    } catch (PDOException $e) {
      error_log("Database error in getEventStatistics: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Get tasks associated with an event
   * 
   * @param int $event_id Event ID
   * @return array Array of tasks
   */
  public function getEventTasks($event_id)
  {
    try {
      $query = "SELECT t.*, 
                CASE WHEN t.status = 'done' THEN 100
                     WHEN t.status = 'in_progress' THEN 50
                     ELSE 0 END as completion_percentage
                FROM tasks t
                WHERE t.event_id = :event_id
                ORDER BY t.deadline ASC, t.priority DESC";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Database error in getEventTasks: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Calculate event completion status based on associated tasks
   * 
   * @param int $event_id Event ID
   * @return int Completion percentage (0-100)
   */
  public function calculateEventCompletionStatus($event_id)
  {
    try {
      $tasks = $this->getEventTasks($event_id);
      
      if (empty($tasks)) {
        return 0;
      }
      
      $totalTasks = count($tasks);
      $completedTasks = 0;
      $inProgressTasks = 0;
      
      foreach ($tasks as $task) {
        if ($task['status'] === 'done') {
          $completedTasks++;
        } else if ($task['status'] === 'in_progress') {
          $inProgressTasks++;
        }
      }
      
      $completionPercentage = ($completedTasks * 100 + $inProgressTasks * 50) / $totalTasks;
      return round($completionPercentage);
    } catch (Exception $e) {
      error_log("Error in calculateEventCompletionStatus: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Update event completion status
   * 
   * @param int $event_id Event ID
   * @return bool Success or failure
   */
  public function updateEventCompletionStatus($event_id)
  {
    // Debug log to verify function is called
    error_log("Updating completion status for event ID " . $event_id);
    
    try {
      $completionStatus = $this->calculateEventCompletionStatus($event_id);
      $readyForPublish = ($completionStatus == 100) ? 1 : 0;
      
      $query = "UPDATE events SET 
                completion_status = :completion_status,
                ready_for_publish = :ready_for_publish,
                updated_at = NOW()
                WHERE event_id = :event_id";
                
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':completion_status', $completionStatus, PDO::PARAM_INT);
      $stmt->bindParam(':ready_for_publish', $readyForPublish, PDO::PARAM_INT);
      $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
      
      $success = $stmt->execute();
      
      if ($success) {
        // Get event details for log
        $event = $this->getEventById($event_id);
        $eventTitle = $event ? $event['title'] : 'Unknown Event';
        
        // Log the status update
        if (function_exists('logAction') && isset($_SESSION['user_id'])) {
          error_log("Logging completion status update: Event ID " . $event_id);
          logAction($_SESSION['user_id'], 'Updated completion status for event: ' . $eventTitle . ' to ' . $completionStatus . '% (Event ID: ' . $event_id . ')');
        } else {
          error_log("logAction function not available for completion status update or user_id not found");
        }
      }
      
      return $success;
    } catch (PDOException $e) {
      error_log("Database error in updateEventCompletionStatus: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Update event visibility status (publish/unpublish)
   * 
   * @param int $event_id Event ID
   * @param string $visibility New visibility status
   * @return bool Success or failure
   */
  public function updateEventVisibility($event_id, $visibility)
  {
    // Debug log to verify function is called
    error_log("Updating event visibility for event ID " . $event_id . " to " . $visibility);
    
    try {
      // Get event details for logging
      $event = $this->getEventById($event_id);
      $eventTitle = $event ? $event['title'] : 'Unknown Event';
      
      // Only allow publishing if event is ready (all tasks complete)
      if ($visibility === 'public') {
        if (!$event || $event['ready_for_publish'] != 1) {
          return false;
        }
      }
      
      $query = "UPDATE events SET 
                visibility = :visibility,
                updated_at = NOW()
                WHERE event_id = :event_id";
                
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':visibility', $visibility);
      $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
      
      $success = $stmt->execute();
      
      if ($success) {
        // Log the visibility change
        if (function_exists('logAction') && isset($_SESSION['user_id'])) {
          error_log("Logging visibility change: Event ID " . $event_id);
          logAction($_SESSION['user_id'], 'Changed visibility to ' . $visibility . ' for event: ' . $eventTitle . ' (Event ID: ' . $event_id . ')');
        } else {
          error_log("logAction function not available for visibility change or user_id not found");
        }
      }
      
      return $success;
    } catch (PDOException $e) {
      error_log("Database error in updateEventVisibility: " . $e->getMessage());
      return false;
    }
  }
}
