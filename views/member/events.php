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
                 WHERE e.event_id = ? AND e.visibility = 'public'";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $event_id, PDO::PARAM_INT);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            // Event not found or not public
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
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo '<div class="alert alert-danger">An error occurred while fetching event details.</div>';
        exit;
    }
    
    // Display event details
    ?>
    <div class="mb-4">
        <a href="?page=member_events" class="text-sm font-medium flex items-center" style="color: var(--color-text-secondary);">
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
                
                <div>
                    <a href="#rsvp-section" class="btn-primary py-2 px-4 rounded-md text-sm font-medium inline-block">
                        RSVP for this Event
                    </a>
                </div>
            </div>
        </div>
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
                        if (empty($initials) && !empty($comment['username'])) {
                            $initials = strtoupper(substr($comment['username'], 0, 1));
                        }
                        echo $initials;
                        ?>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center mb-1">
                            <span class="font-medium text-sm" style="color: var(--color-text-primary);">
                                <?php 
                                if (!empty($comment['first_name']) && !empty($comment['last_name'])) {
                                    echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']);
                                } else {
                                    echo htmlspecialchars($comment['username'] ?? 'Anonymous');
                                }
                                ?>
                            </span>
                            <span class="text-xs ml-2" style="color: var(--color-text-tertiary);">
                                <?php echo date('M d, Y h:i A', strtotime($comment['created_at'])); ?>
                            </span>
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
    <?php
} else {
    // List all public events
    try {
        $query = "SELECT e.*, d.name as department_name, t.name as type_name, t.color as type_color
                 FROM events e
                 LEFT JOIN departments d ON e.department_id = d.department_id
                 LEFT JOIN event_types t ON e.type_id = t.type_id
                 WHERE e.visibility = 'public'
                 ORDER BY e.start_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo '<div class="alert alert-danger">An error occurred while fetching events.</div>';
        exit;
    }
    ?>
    
    <div class="mb-6">
        <h1 class="text-2xl font-normal mb-1" style="color: var(--color-text-primary);">Community Events</h1>
        <p class="text-sm" style="color: var(--color-text-secondary);">
            Discover and participate in upcoming events from GDG on Campus WMSU
        </p>
    </div>
    
    <!-- Filter and Search Section -->
    <div class="google-card p-4 mb-6">
        <div class="flex flex-wrap items-center justify-between">
            <div class="flex space-x-2 mb-2 sm:mb-0">
                <button class="text-sm font-medium px-3 py-1 rounded-full" style="background-color: var(--color-hover); color: var(--color-text-primary);">All Events</button>
                <button class="text-sm font-medium px-3 py-1 rounded-full" style="color: var(--color-text-secondary);">Upcoming</button>
                <button class="text-sm font-medium px-3 py-1 rounded-full" style="color: var(--color-text-secondary);">Past</button>
            </div>
            
            <div class="relative">
                <input type="text" placeholder="Search events..." class="pl-10 pr-4 py-2 border rounded-md w-full sm:w-64" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                <div class="absolute left-3 top-2.5">
                    <i class="fas fa-search" style="color: var(--color-text-tertiary);"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Events Grid -->
    <?php if (empty($events)): ?>
    <div class="text-center py-8">
        <div class="rounded-full mx-auto p-4 mb-4" style="background-color: rgba(66, 133, 244, 0.1); width: fit-content;">
            <i class="fas fa-calendar-alt text-2xl" style="color: #4285F4;"></i>
        </div>
        <h3 class="text-lg font-medium mb-2" style="color: var(--color-text-primary);">No Events Found</h3>
        <p class="text-sm" style="color: var(--color-text-secondary);">There are no public events available at this time.</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($events as $event): ?>
        <div class="google-card overflow-hidden">
            <?php if (!empty($event['featured_image'])): ?>
            <div class="h-40 overflow-hidden">
                <img src="<?php echo htmlspecialchars($event['featured_image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="w-full h-full object-cover">
            </div>
            <?php endif; ?>
            
            <div class="p-4">
                <div class="flex items-center mb-2">
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full mr-2" style="background-color: <?php echo htmlspecialchars($event['type_color']); ?>; color: white;">
                        <?php echo htmlspecialchars($event['type_name']); ?>
                    </span>
                    <span class="text-xs" style="color: var(--color-text-tertiary);">
                        <?php echo htmlspecialchars($event['department_name'] ?? 'GDG on Campus WMSU'); ?>
                    </span>
                </div>
                
                <h3 class="text-md font-medium mb-2" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($event['title']); ?></h3>
                
                <div class="mb-3">
                    <p class="text-xs mb-1" style="color: var(--color-text-secondary);">
                        <i class="fas fa-calendar-alt mr-1"></i> 
                        <?php echo date('F d, Y', strtotime($event['start_date'])); ?>
                        <?php if (date('Y-m-d', strtotime($event['start_date'])) != date('Y-m-d', strtotime($event['end_date']))): ?>
                        - <?php echo date('F d, Y', strtotime($event['end_date'])); ?>
                        <?php endif; ?>
                    </p>
                    
                    <p class="text-xs mb-1" style="color: var(--color-text-secondary);">
                        <i class="fas fa-clock mr-1"></i> 
                        <?php echo date('h:i A', strtotime($event['start_date'])); ?> - 
                        <?php echo date('h:i A', strtotime($event['end_date'])); ?>
                    </p>
                    
                    <?php if (!empty($event['location'])): ?>
                    <p class="text-xs" style="color: var(--color-text-secondary);">
                        <i class="fas fa-map-marker-alt mr-1"></i> 
                        <?php echo htmlspecialchars($event['location']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="flex justify-between items-center">
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
                    
                    <a href="?page=member_events&event_id=<?php echo $event['event_id']; ?>" class="text-sm font-medium" style="color: #4285F4;">View Details</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php
}
?>

