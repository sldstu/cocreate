<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Get upcoming events
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get upcoming events
    $query = "SELECT e.event_id, e.title, e.start_date, e.end_date, e.location, d.name as department_name 
              FROM events e 
              LEFT JOIN departments d ON e.department_id = d.department_id 
              WHERE e.start_date >= CURDATE() AND e.visibility = 'public'
              ORDER BY e.start_date ASC LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Handle error
    error_log("Database error: " . $e->getMessage());
    $upcomingEvents = [];
}
?>

<!-- Welcome Section -->
<div class="mb-6">
    <h1 class="text-2xl font-normal mb-1" style="color: var(--color-text-primary);">Welcome, <?php echo htmlspecialchars($currentUser['username']); ?></h1>
    <p class="text-sm" style="color: var(--color-text-secondary);">
        Stay updated with upcoming events and community activities
    </p>
</div>

<!-- Upcoming Events Section -->
<div class="google-card p-5 mb-8">
    <h2 class="text-lg font-normal mb-4" style="color: var(--color-text-primary);">Upcoming Events</h2>
    
    <?php if (empty($upcomingEvents)): ?>
        <p class="text-center py-4" style="color: var(--color-text-secondary);">No upcoming events at this time.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($upcomingEvents as $event): ?>
                <div class="p-4 border rounded-lg" style="border-color: var(--color-border-light);">
                    <div class="flex items-start">
                        <div class="rounded-full p-3 mr-4" style="background-color: rgba(66, 133, 244, 0.1);">
                            <i class="fas fa-calendar-alt text-lg" style="color: #4285F4;"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-md font-medium" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p class="text-sm mt-1" style="color: var(--color-text-secondary);">
                                <i class="fas fa-clock mr-1"></i> 
                                <?php echo date('M d, Y h:i A', strtotime($event['start_date'])); ?> - 
                                <?php echo date('M d, Y h:i A', strtotime($event['end_date'])); ?>
                            </p>
                            <?php if (!empty($event['location'])): ?>
                            <p class="text-sm mt-1" style="color: var(--color-text-secondary);">
                                <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($event['location']); ?>
                            </p>
                            <?php endif; ?>
                            <p class="text-sm mt-1" style="color: var(--color-text-tertiary);">
                                Organized by: <?php echo htmlspecialchars($event['department_name'] ?? 'GDG on Campus WMSU'); ?>
                            </p>
                            <div class="mt-3">
                                <a href="?page=member_events&event_id=<?php echo $event['event_id']; ?>" class="text-sm font-medium" style="color: #4285F4;">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-4 text-center">
            <a href="?page=member_events" class="text-sm font-medium py-2 px-4 rounded-full" style="background-color: var(--color-hover); color: var(--color-text-primary);">
                View All Events
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Community Updates Section -->
<div class="google-card p-5 mb-8">
    <h2 class="text-lg font-normal mb-4" style="color: var(--color-text-primary);">Community Updates</h2>
    
    <div class="space-y-4">
        <div class="p-4 border rounded-lg" style="border-color: var(--color-border-light);">
            <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Welcome to GDG on Campus WMSU!</h3>
            <p class="text-sm mt-2" style="color: var(--color-text-secondary);">
                Thank you for joining our community. As a member, you'll have access to events, workshops, and networking opportunities.
            </p>
        </div>
        
        <div class="p-4 border rounded-lg" style="border-color: var(--color-border-light);">
            <h3 class="text-md font-medium" style="color: var(--color-text-primary);">How to Get Involved</h3>
            <p class="text-sm mt-2" style="color: var(--color-text-secondary);">
                Attend our events, participate in discussions, and connect with other members. If you're interested in becoming an Officer, reach out to your Department Lead.
            </p>
        </div>
    </div>
</div>

<!-- Resources Section -->
<div class="google-card p-5">
    <h2 class="text-lg font-normal mb-4" style="color: var(--color-text-primary);">Resources</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="https://developers.google.com/community/gdg" target="_blank" class="p-4 border rounded-lg hover:shadow-md transition-shadow" style="border-color: var(--color-border-light); text-decoration: none;">
            <div class="flex items-center mb-2">
                <div class="rounded-full p-2 mr-3" style="background-color: rgba(66, 133, 244, 0.1);">
                    <i class="fas fa-info-circle" style="color: #4285F4;"></i>
                </div>
                <h3 class="text-md font-medium" style="color: var(--color-text-primary);">About GDG</h3>
            </div>
            <p class="text-sm" style="color: var(--color-text-secondary);">Learn more about Google Developer Groups</p>
        </a>
        
        <a href="https://developers.google.com/community/gdsc" target="_blank" class="p-4 border rounded-lg hover:shadow-md transition-shadow" style="border-color: var(--color-border-light); text-decoration: none;">
            <div class="flex items-center mb-2">
                <div class="rounded-full p-2 mr-3" style="background-color: rgba(52, 168, 83, 0.1);">
                    <i class="fas fa-graduation-cap" style="color: #34A853;"></i>
                </div>
                <h3 class="text-md font-medium" style="color: var(--color-text-primary);">GDSC Program</h3>
            </div>
            <p class="text-sm" style="color: var(--color-text-secondary);">Explore Google Developer Student Clubs</p>
        </a>
        
        <a href="https://developers.google.com/learn" target="_blank" class="p-4 border rounded-lg hover:shadow-md transition-shadow" style="border-color: var(--color-border-light); text-decoration: none;">
            <div class="flex items-center mb-2">
                <div class="rounded-full p-2 mr-3" style="background-color: rgba(234, 67, 53, 0.1);">
                    <i class="fas fa-code" style="color: #EA4335;"></i>
                </div>
                <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Learning Resources</h3>
            </div>
            <p class="text-sm" style="color: var(--color-text-secondary);">Access Google Developers learning materials</p>
        </a>
    </div>
</div>
