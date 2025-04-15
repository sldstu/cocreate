<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Check if viewing a specific event
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if ($event_id > 0) {
    // Get specific event details
    try {
        $query = "SELECT e.*, d.name as department_name, t.name as type_name, t.color as type_color, 
                 u.first_name, u.last_name
                 FROM events e
                 LEFT JOIN departments d ON e.department_id = d.department_id
                 LEFT JOIN event_types t ON e.type_id = t.type_id
                 LEFT JOIN users u ON e.created_by = u.user_id
                 WHERE e.event_id = ? AND (e.visibility = 'public' OR e.department_id = ? OR e.created_by = ?)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $event_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $_SESSION['department_id'], PDO::PARAM_INT);
        $stmt->bindParam(3, $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            // Event not found or not accessible
            echo '<div class="alert alert-danger">Event not found or not available.</div>';
            exit;
        }
        
        // Get event comments
        $query = "SELECT c.*, u.first_name, u.last_name, u.username
                 FROM event_comments c
                 LEFT JOIN users u ON c.user_id = u.user_id
                 WHERE c.event_id = ? AND c.parent_comment_id IS NULL
                 ORDER BY c.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $event_id, PDO::PARAM_INT);
        $stmt->execute();
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get event attachments
        $query = "SELECT * FROM event_attachments WHERE event_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $event_id, PDO::PARAM_INT);
        $stmt->execute();
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get event RSVPs
        $query = "SELECT r.*, u.first_name, u.last_name, u.username
                 FROM event_rsvps r
                 LEFT JOIN users u ON r.user_id = u.user_id
                 WHERE r.event_id = ?
                 ORDER BY r.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $event_id, PDO::PARAM_INT);
        $stmt->execute();
        $rsvps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count RSVPs by status
        $rsvpCounts = [
            'going' => 0,
            'interested' => 0,
            'not_going' => 0
        ];
        
        foreach ($rsvps as $rsvp) {
            if (isset($rsvpCounts[$rsvp['status']])) {
                $rsvpCounts[$rsvp['status']]++;
            }
        }
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo '<div class="alert alert-danger">An error occurred while fetching event details.</div>';
        exit;
    }
    
    // Display event details
    ?>
    <div class="mb-4">
        <a href="?page=lead_events" class="text-sm font-medium flex items-center" style="color: var(--color-text-secondary);">
            <i class="fas fa-arrow-left mr-2"></i> Back to Events
        </a>
    </div>
    
    <div class="google-card p-5 mb-6">
        <?php if (!empty($event['featured_image'])): ?>
        <div class="mb-4">
            <img src="<?php echo htmlspecialchars($event['featured_image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="w-full h-64 object-cover rounded-lg">
        </div>
        <?php endif; ?>
        
        <div class="flex items-center mb-4">
            <span class="px-3 py-1 text-xs font-medium rounded-full mr-3" style="background-color: <?php echo htmlspecialchars($event['type_color']); ?>; color: white;">
                <?php echo htmlspecialchars($event['type_name']); ?>
            </span>
            <span class="text-sm" style="color: var(--color-text-secondary);">
                Organized by <?php echo htmlspecialchars($event['department_name'] ?? 'GDG on Campus WMSU'); ?>
            </span>
        </div>
        
        <h1 class="text-2xl font-medium mb-2" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($event['title']); ?></h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <p class="text-sm mb-2" style="color: var(--color-text-secondary);">
                    <i class="fas fa-calendar-alt mr-2"></i> 
                    <strong>Date:</strong> <?php echo date('F d, Y', strtotime($event['start_date'])); ?>
                    <?php if (date('Y-m-d', strtotime($event['start_date'])) != date('Y-m-d', strtotime($event['end_date']))): ?>
                    - <?php echo date('F d, Y', strtotime($event['end_date'])); ?>
                    <?php endif; ?>
                </p>
                
                <p class="text-sm mb-2" style="color: var(--color-text-secondary);">
                    <i class="fas fa-clock mr-2"></i> 
                    <strong>Time:</strong> <?php echo date('h:i A', strtotime($event['start_date'])); ?> - 
                    <?php echo date('h:i A', strtotime($event['end_date'])); ?>
                </p>
            </div>
            
            <div>
                <?php if (!empty($event['location'])): ?>
                <p class="text-sm mb-2" style="color: var(--color-text-secondary);">
                    <i class="fas fa-map-marker-alt mr-2"></i> 
                    <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                </p>
                <?php endif; ?>
                
                <?php if (!empty($event['location_map_url'])): ?>
                <p class="text-sm mb-2">
                    <a href="<?php echo htmlspecialchars($event['location_map_url']); ?>" target="_blank" class="text-blue-500 hover:underline">
                        <i class="fas fa-map mr-2"></i> View on Map
                    </a>
                </p>
                <?php endif; ?>
                
                <p class="text-sm" style="color: var(--color-text-secondary);">
                    <i class="fas fa-user mr-2"></i> 
                    <strong>Status:</strong> 
                    <span class="px-2 py-1 text-xs font-medium rounded-full" 
                          style="background-color: <?php 
                            echo $event['status'] == 'upcoming' ? 'rgba(52, 168, 83, 0.1)' : 
                                 ($event['status'] == 'ongoing' ? 'rgba(66, 133, 244, 0.1)' : 
                                 ($event['status'] == 'completed' ? 'rgba(234, 67, 53, 0.1)' : 'rgba(251, 188, 5, 0.1)')); 
                            ?>; 
                            color: <?php 
                            echo $event['status'] == 'upcoming' ? '#34A853' : 
                                 ($event['status'] == 'ongoing' ? '#4285F4' : 
                                 ($event['status'] == 'completed' ? '#EA4335' : '#FBBC05')); 
                            ?>;">
                        <?php echo ucfirst($event['status']); ?>
                    </span>
                </p>
            </div>
        </div>
        
        <div class="mb-6">
            <h3 class="text-lg font-medium mb-2" style="color: var(--color-text-primary);">Description</h3>
            <div class="text-sm" style="color: var(--color-text-secondary);">
                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
            </div>
        </div>
        
        <?php if (!empty($event['speakers'])): ?>
        <div class="mb-6">
            <h3 class="text-lg font-medium mb-2" style="color: var(--color-text-primary);">Speakers</h3>
            <div class="text-sm" style="color: var(--color-text-secondary);">
                <?php echo nl2br(htmlspecialchars($event['speakers'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($attachments)): ?>
        <div class="mb-6">
            <h3 class="text-lg font-medium mb-2" style="color: var(--color-text-primary);">Attachments</h3>
            <ul class="space-y-2">
                <?php foreach ($attachments as $attachment): ?>
                <li>
                    <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank" class="text-sm flex items-center" style="color: #4285F4;">
                        <i class="fas fa-file-download mr-2"></i>
                        <?php echo htmlspecialchars($attachment['file_name']); ?>
                        <?php if (!empty($attachment['file_size'])): ?>
                        <span class="ml-2 text-xs" style="color: var(--color-text-tertiary);">
                            (<?php echo round($attachment['file_size'] / 1024, 2); ?> KB)
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="pt-4 border-t" style="border-color: var(--color-border-light);">
            <div class="flex justify-between items-center">
                <div class="text-sm" style="color: var(--color-text-secondary);">
                    Posted by <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?>
                </div>
                
                <div class="flex space-x-2">
                    <?php if ($event['created_by'] == $_SESSION['user_id'] || $_SESSION['department_id'] == $event['department_id']): ?>
                    <a href="?page=lead_events&action=edit&event_id=<?php echo $event_id; ?>" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium inline-block">
                        Edit Event
                    </a>
                    <?php endif; ?>
                    
                    <a href="#rsvp-section" class="btn-primary py-2 px-4 rounded-md text-sm font-medium inline-block">
                        RSVP for this Event
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- RSVP Summary Section -->
    <div class="google-card p-5 mb-6">
        <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">RSVP Summary</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="p-4 rounded-lg text-center" style="background-color: rgba(52, 168, 83, 0.1);">
                <h3 class="text-2xl font-medium" style="color: #34A853;"><?php echo $rsvpCounts['going']; ?></h3>
                <p class="text-sm" style="color: var(--color-text-secondary);">Going</p>
            </div>
            
            <div class="p-4 rounded-lg text-center" style="background-color: rgba(66, 133, 244, 0.1);">
                <h3 class="text-2xl font-medium" style="color: #4285F4;"><?php echo $rsvpCounts['interested']; ?></h3>
                <p class="text-sm" style="color: var(--color-text-secondary);">Interested</p>
            </div>
            
            <div class="p-4 rounded-lg text-center" style="background-color: rgba(234, 67, 53, 0.1);">
                <h3 class="text-2xl font-medium" style="color: #EA4335;"><?php echo $rsvpCounts['not_going']; ?></h3>
                <p class="text-sm" style="color: var(--color-text-secondary);">Not Going</p>
            </div>
        </div>
        
        <?php if (!empty($rsvps)): ?>
        <div class="mt-4">
            <h3 class="text-md font-medium mb-2" style="color: var(--color-text-primary);">Recent RSVPs</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--color-border-light);">
                            <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Comment</th>
                            <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Date</th>
                            </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($rsvps, 0, 5) as $rsvp): ?>
                        <tr style="border-bottom: 1px solid var(--color-border-light);">
                            <td class="px-4 py-2 text-sm" style="color: var(--color-text-primary);">
                                <?php 
                                if (!empty($rsvp['first_name']) && !empty($rsvp['last_name'])) {
                                    echo htmlspecialchars($rsvp['first_name'] . ' ' . $rsvp['last_name']);
                                } elseif (!empty($rsvp['guest_name'])) {
                                    echo htmlspecialchars($rsvp['guest_name']) . ' (Guest)';
                                } else {
                                    echo 'Anonymous';
                                }
                                ?>
                            </td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full" 
                                      style="background-color: <?php 
                                        echo $rsvp['status'] == 'going' ? 'rgba(52, 168, 83, 0.1)' : 
                                             ($rsvp['status'] == 'interested' ? 'rgba(66, 133, 244, 0.1)' : 'rgba(234, 67, 53, 0.1)'); 
                                        ?>; 
                                        color: <?php 
                                        echo $rsvp['status'] == 'going' ? '#34A853' : 
                                             ($rsvp['status'] == 'interested' ? '#4285F4' : '#EA4335'); 
                                        ?>;">
                                    <?php echo ucfirst($rsvp['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm" style="color: var(--color-text-secondary);">
                                <?php echo !empty($rsvp['comment']) ? htmlspecialchars(substr($rsvp['comment'], 0, 50)) . (strlen($rsvp['comment']) > 50 ? '...' : '') : '-'; ?>
                            </td>
                            <td class="px-4 py-2 text-sm" style="color: var(--color-text-tertiary);">
                                <?php echo date('M d, Y', strtotime($rsvp['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (count($rsvps) > 5): ?>
            <div class="mt-4 text-center">
                <button type="button" id="view-all-rsvps" class="text-sm font-medium py-2 px-4 rounded-full" style="background-color: var(--color-hover); color: var(--color-text-primary);">
                    View All RSVPs
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="text-center py-4" style="color: var(--color-text-secondary);">No RSVPs yet.</p>
        <?php endif; ?>
    </div>
    
    <!-- RSVP Section -->
    <div id="rsvp-section" class="google-card p-5 mb-6">
        <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">RSVP for this Event</h2>
        
        <form method="post" action="process_rsvp.php" class="space-y-4">
            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
            <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
            
            <div>
                <label class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Will you attend?</label>
                <div class="flex space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="status" value="going" class="form-radio" checked>
                        <span class="ml-2 text-sm" style="color: var(--color-text-secondary);">Yes, I'll be there</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="status" value="interested" class="form-radio">
                        <span class="ml-2 text-sm" style="color: var(--color-text-secondary);">I'm interested</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="status" value="not_going" class="form-radio">
                        <span class="ml-2 text-sm" style="color: var(--color-text-secondary);">No, I can't make it</span>
                    </label>
                </div>
            </div>
            
            <div>
                <label for="comment" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Comment (Optional)</label>
                <textarea id="comment" name="comment" rows="3" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);"></textarea>
            </div>
            
            <div>
                <button type="submit" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">Submit RSVP</button>
            </div>
        </form>
    </div>
    
    <!-- Comments Section -->
    <div class="google-card p-5">
        <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Comments</h2>
        
        <form method="post" action="process_comment.php" class="mb-6">
            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
            <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
            
            <div class="mb-3">
                <label for="content" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Add a Comment</label>
                <textarea id="content" name="content" rows="3" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required></textarea>
            </div>
            
            <div>
                <button type="submit" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">Post Comment</button>
            </div>
        </form>
        
        <?php if (empty($comments)): ?>
        <p class="text-center py-4" style="color: var(--color-text-secondary);">No comments yet. Be the first to comment!</p>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($comments as $comment): ?>
            <div class="p-4 border rounded-lg" style="border-color: var(--color-border-light);">
                <div class="flex items-start">
                    <div class="rounded-full w-10 h-10 flex items-center justify-center mr-3" style="background-color: var(--color-primary); color: white;">
                        <?php 
                        $initials = '';
                        if (!empty($comment['first_name'])) {
                            $initials .= strtoupper(substr($comment['first_name'], 0, 1));
                        }
                        if (!empty($comment['last_name'])) {
                            $initials .= strtoupper(substr($comment['last_name'], 0, 1));
                        }
                        if (empty($initials) && !empty($comment['guest_name'])) {
                            $initials = strtoupper(substr($comment['guest_name'], 0, 1));
                        }
                        if (empty($initials)) {
                            $initials = 'A';
                        }
                        echo $initials;
                        ?>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <div class="font-medium" style="color: var(--color-text-primary);">
                                <?php 
                                if (!empty($comment['first_name']) && !empty($comment['last_name'])) {
                                    echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']);
                                } elseif (!empty($comment['guest_name'])) {
                                    echo htmlspecialchars($comment['guest_name']) . ' (Guest)';
                                } else {
                                    echo 'Anonymous';
                                }
                                ?>
                            </div>
                            <div class="text-xs" style="color: var(--color-text-tertiary);">
                                <?php echo date('M d, Y h:i A', strtotime($comment['created_at'])); ?>
                            </div>
                        </div>
                        <div class="text-sm" style="color: var(--color-text-secondary);">
                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // View all RSVPs button
        const viewAllRsvpsBtn = document.getElementById('view-all-rsvps');
        if (viewAllRsvpsBtn) {
            viewAllRsvpsBtn.addEventListener('click', function() {
                // Show modal with all RSVPs or expand the table
                alert('This feature will show all RSVPs in a modal or expanded view.');
            });
        }
    });
    </script>
<?php } else {
    // List all events
    try {
        // Get event types for filter
        $query = "SELECT * FROM event_types ORDER BY name";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $eventTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get department ID of the current user
        $department_id = $_SESSION['department_id'] ?? 0;
        
        // Get events with filtering options
        $whereConditions = [];
        $params = [];
        
        // Filter by department (show department's events and public events)
        $whereConditions[] = "(e.department_id = ? OR e.visibility = 'public')";
        $params[] = $department_id;
        
        // Filter by type if specified
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $whereConditions[] = "e.type_id = ?";
            $params[] = intval($_GET['type']);
        }
        
        // Filter by status if specified
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $whereConditions[] = "e.status = ?";
            $params[] = $_GET['status'];
        }
        
        // Filter by search term if specified
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $whereConditions[] = "(e.title LIKE ? OR e.description LIKE ?)";
            $searchTerm = '%' . $_GET['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Build the WHERE clause
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM events e $whereClause";
        $stmt = $conn->prepare($countQuery);
        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param);
        }
        $stmt->execute();
        $totalEvents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Pagination settings
        $eventsPerPage = 10;
        $totalPages = ceil($totalEvents / $eventsPerPage);
        $currentPage = isset($_GET['page_num']) ? max(1, min($totalPages, intval($_GET['page_num']))) : 1;
        $offset = ($currentPage - 1) * $eventsPerPage;
        
        // Get events with pagination
        $query = "SELECT e.*, d.name as department_name, t.name as type_name, t.color as type_color 
                  FROM events e
                  LEFT JOIN departments d ON e.department_id = d.department_id
                  LEFT JOIN event_types t ON e.type_id = t.type_id
                  $whereClause
                  ORDER BY e.start_date DESC
                  LIMIT $eventsPerPage OFFSET $offset";
        
        $stmt = $conn->prepare($query);
        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param);
        }
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo '<div class="alert alert-danger">An error occurred while fetching events.</div>';
        exit;
    }
    ?>
    
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <h1 class="text-2xl font-normal mb-2 md:mb-0" style="color: var(--color-text-primary);">Department Events</h1>
        
        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
            <a href="?page=lead_events&action=add" class="btn-primary py-2 px-4 rounded-md text-sm font-medium inline-flex items-center justify-center">
                <i class="fas fa-plus mr-2"></i> Create Event
            </a>
            
            <button type="button" onclick="toggleFilters()" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium inline-flex items-center justify-center">
                <i class="fas fa-filter mr-2"></i> Filters
            </button>
        </div>
    </div>
    
    <!-- Filters Section -->
    <div id="filters-section" class="google-card p-5 mb-6 <?php echo isset($_GET['type']) || isset($_GET['status']) || isset($_GET['search']) ? '' : 'hidden'; ?>">
    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="page" value="lead_events">
            
            <div>
                <label for="type" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Event Type</label>
                <select id="type" name="type" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                    <option value="">All Types</option>
                    <?php foreach ($eventTypes as $type): ?>
                    <option value="<?php echo $type['type_id']; ?>" <?php echo isset($_GET['type']) && $_GET['type'] == $type['type_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Status</label>
                <select id="status" name="status" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                    <option value="">All Statuses</option>
                    <option value="upcoming" <?php echo isset($_GET['status']) && $_GET['status'] == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="ongoing" <?php echo isset($_GET['status']) && $_GET['status'] == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                    <option value="completed" <?php echo isset($_GET['status']) && $_GET['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div>
                <label for="search" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Search</label>
                <input type="text" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" placeholder="Search events...">
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                    Apply Filters
                </button>
                
                <a href="?page=lead_events" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium inline-block">
                    Reset
                </a>
            </div>
        </form>
    </div>
    
    <?php if (empty($events)): ?>
    <div class="google-card p-5 text-center">
        <p class="text-lg mb-4" style="color: var(--color-text-secondary);">No events found</p>
        <a href="?page=lead_events&action=add" class="btn-primary py-2 px-4 rounded-md text-sm font-medium inline-block">
            Create New Event
        </a>
    </div>
    <?php else: ?>
    <!-- Events List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <?php foreach ($events as $event): ?>
        <div class="google-card overflow-hidden">
            <?php if (!empty($event['featured_image'])): ?>
            <div class="h-40 overflow-hidden">
                <img src="<?php echo htmlspecialchars($event['featured_image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="w-full h-full object-cover">
            </div>
            <?php endif; ?>
            
            <div class="p-5">
                <div class="flex items-center mb-2">
                    <span class="px-2 py-1 text-xs font-medium rounded-full mr-2" style="background-color: <?php echo htmlspecialchars($event['type_color']); ?>; color: white;">
                        <?php echo htmlspecialchars($event['type_name']); ?>
                    </span>
                    
                    <span class="px-2 py-1 text-xs font-medium rounded-full" 
                          style="background-color: <?php 
                            echo $event['status'] == 'upcoming' ? 'rgba(52, 168, 83, 0.1)' : 
                                 ($event['status'] == 'ongoing' ? 'rgba(66, 133, 244, 0.1)' : 
                                 ($event['status'] == 'completed' ? 'rgba(234, 67, 53, 0.1)' : 'rgba(251, 188, 5, 0.1)')); 
                            ?>; 
                            color: <?php 
                            echo $event['status'] == 'upcoming' ? '#34A853' : 
                                 ($event['status'] == 'ongoing' ? '#4285F4' : 
                                 ($event['status'] == 'completed' ? '#EA4335' : '#FBBC05')); 
                            ?>;">
                        <?php echo ucfirst($event['status']); ?>
                    </span>
                </div>
                
                <h3 class="text-lg font-medium mb-2" style="color: var(--color-text-primary);">
                    <?php echo htmlspecialchars($event['title']); ?>
                </h3>
                
                <p class="text-sm mb-3" style="color: var(--color-text-secondary);">
                    <?php 
                    $description = strip_tags($event['description']);
                    echo strlen($description) > 100 ? htmlspecialchars(substr($description, 0, 100)) . '...' : htmlspecialchars($description); 
                    ?>
                </p>
                
                <div class="text-sm mb-4" style="color: var(--color-text-secondary);">
                    <p class="flex items-center mb-1">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <?php echo date('M d, Y', strtotime($event['start_date'])); ?>
                    </p>
                    
                    <p class="flex items-center mb-1">
                        <i class="fas fa-clock mr-2"></i>
                        <?php echo date('h:i A', strtotime($event['start_date'])); ?>
                    </p>
                    
                    <?php if (!empty($event['location'])): ?>
                    <p class="flex items-center">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <?php echo htmlspecialchars($event['location']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-xs" style="color: var(--color-text-tertiary);">
                        <?php echo htmlspecialchars($event['department_name']); ?>
                    </span>
                    
                    <a href="?page=lead_events&event_id=<?php echo $event['event_id']; ?>" class="text-sm font-medium" style="color: var(--color-primary);">
                        View Details
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="google-card p-4 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-center">
            <div class="text-sm mb-3 sm:mb-0" style="color: var(--color-text-secondary);">
                Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $eventsPerPage, $totalEvents); ?> of <?php echo $totalEvents; ?> events
            </div>
            
            <div class="flex space-x-1">
                <?php if ($currentPage > 1): ?>
                <a href="?page=lead_events&page_num=<?php echo ($currentPage - 1); ?><?php echo isset($_GET['type']) ? '&type=' . htmlspecialchars($_GET['type']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?>" class="px-3 py-1 rounded-md text-sm" style="background-color: var(--color-hover); color: var(--color-text-primary);">
                    Previous
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                <a href="?page=lead_events&page_num=<?php echo $i; ?><?php echo isset($_GET['type']) ? '&type=' . htmlspecialchars($_GET['type']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?>" class="px-3 py-1 rounded-md text-sm <?php echo $i == $currentPage ? 'font-medium' : ''; ?>" style="<?php echo $i == $currentPage ? 'background-color: var(--color-primary); color: white;' : 'background-color: var(--color-hover); color: var(--color-text-primary);'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                <a href="?page=lead_events&page_num=<?php echo ($currentPage + 1); ?><?php echo isset($_GET['type']) ? '&type=' . htmlspecialchars($_GET['type']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?>" class="px-3 py-1 rounded-md text-sm" style="background-color: var(--color-hover); color: var(--color-text-primary);">
                    Next
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    
    <script>
    function toggleFilters() {
        const filtersSection = document.getElementById('filters-section');
        filtersSection.classList.toggle('hidden');
    }
    </script>
<?php } ?>

