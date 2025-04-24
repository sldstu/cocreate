<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');
?>

<div class="mb-4">
    <a href="?page=lead_events" class="text-sm font-medium flex items-center" style="color: var(--color-text-secondary);">
        <i class="fas fa-arrow-left mr-2"></i> Back to Events
    </a>
</div>

<div class="google-card p-5 max-w-lg mx-auto">
    <div class="text-center mb-6">
        <div class="rounded-full mx-auto p-4 mb-4" style="background-color: rgba(234, 67, 53, 0.1); width: fit-content;">
            <i class="fas fa-exclamation-triangle text-2xl" style="color: #EA4335;"></i>
        </div>
        <h2 class="text-xl font-medium mb-2" style="color: var(--color-text-primary);">Delete Event</h2>
        <p class="text-sm" style="color: var(--color-text-secondary);">
            Are you sure you want to delete the event "<strong><?php echo htmlspecialchars($event['title']); ?></strong>"?
            This action cannot be undone.
        </p>
    </div>
    
    <div class="border-t border-b py-4 mb-6" style="border-color: var(--color-border-light);">
        <div class="text-sm" style="color: var(--color-text-secondary);">
            <p class="mb-2"><strong>Event Details:</strong></p>
            <p class="mb-1">
                <i class="fas fa-calendar-alt mr-2"></i> 
                <?php echo date('F d, Y', strtotime($event['start_date'])); ?>
                <?php if (date('Y-m-d', strtotime($event['start_date'])) != date('Y-m-d', strtotime($event['end_date']))): ?>
                - <?php echo date('F d, Y', strtotime($event['end_date'])); ?>
                <?php endif; ?>
            </p>
            <p class="mb-1">
                <i class="fas fa-clock mr-2"></i> 
                <?php echo date('h:i A', strtotime($event['start_date'])); ?> - 
                <?php echo date('h:i A', strtotime($event['end_date'])); ?>
            </p>
            <?php if (!empty($event['location'])): ?>
            <p>
                <i class="fas fa-map-marker-alt mr-2"></i> 
                <?php echo htmlspecialchars($event['location']); ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="flex justify-between">
        <a href="?page=lead_events&event_id=<?php echo $event['event_id']; ?>" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium">
            Cancel
        </a>
        <a href="views/lead/php/event_handler.php?action=delete_event&event_id=<?php echo $event['event_id']; ?>" class="btn-danger py-2 px-4 rounded-md text-sm font-medium">
            Delete Event
        </a>
    </div>
</div>
