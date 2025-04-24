<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Count RSVPs by status
$goingCount = $eventsManager->countEventRSVPs($event_id, 'going');
$interestedCount = $eventsManager->countEventRSVPs($event_id, 'interested');
$notGoingCount = $eventsManager->countEventRSVPs($event_id, 'not_going');
$totalRSVPs = $goingCount + $interestedCount + $notGoingCount;

// Determine status colors
$statusBg = '';
$statusColor = '';
switch ($event['status']) {
    case 'upcoming':
        $statusBg = 'rgba(52, 168, 83, 0.1)';
        $statusColor = '#34A853';
        break;
    case 'ongoing':
        $statusBg = 'rgba(66, 133, 244, 0.1)';
        $statusColor = '#4285F4';
        break;
    case 'completed':
        $statusBg = 'rgba(234, 67, 53, 0.1)';
        $statusColor = '#EA4335';
        break;
    case 'cancelled':
        $statusBg = 'rgba(234, 67, 53, 0.1)';
        $statusColor = '#EA4335';
        break;
    default:
        $statusBg = 'rgba(251, 188, 5, 0.1)';
        $statusColor = '#FBBC05';
}
?>

<div class="mb-4">
    <a href="?page=lead_events" class="text-sm font-medium flex items-center" style="color: var(--color-text-secondary);">
        <i class="fas fa-arrow-left mr-2"></i> Back to Events
    </a>
</div>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-normal" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($event['title']); ?></h1>
        
        <!-- Task completion status badge -->
        <div class="flex mt-2 space-x-2">
            <?php if (isset($event['completion_status'])): ?>
            <div class="px-3 py-1 text-xs font-medium rounded-full flex items-center" 
                style="background-color: <?php echo ($event['completion_status'] == 100) ? 'rgba(52, 168, 83, 0.1)' : 
                    (($event['completion_status'] >= 50) ? 'rgba(251, 188, 5, 0.1)' : 'rgba(234, 67, 53, 0.1)'); ?>; 
                    color: <?php echo ($event['completion_status'] == 100) ? '#34A853' : 
                    (($event['completion_status'] >= 50) ? '#FBBC05' : '#EA4335'); ?>;">
                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                    <div class="bg-current h-2 rounded-full" style="width: <?php echo $event['completion_status']; ?>%"></div>
                </div>
                <span><?php echo $event['completion_status']; ?>% Tasks Complete</span>
            </div>
            <?php endif; ?>
            
            <?php if (isset($event['ready_for_publish']) && $event['ready_for_publish'] == 1): ?>
            <div class="px-3 py-1 text-xs font-medium rounded-full" 
                 style="background-color: rgba(52, 168, 83, 0.1); color: #34A853;">
                <i class="fas fa-check-circle mr-1"></i> Ready for Publishing
            </div>
            <?php elseif (isset($event['completion_status'])): ?>
            <div class="px-3 py-1 text-xs font-medium rounded-full" 
                 style="background-color: rgba(251, 188, 5, 0.1); color: #FBBC05;">
                <i class="fas fa-clock mr-1"></i> Tasks Incomplete
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="flex space-x-2">
        <a href="?page=lead_events&action=edit&event_id=<?php echo $event['event_id']; ?>" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium">
            <i class="fas fa-edit mr-1"></i> Edit
        </a>
        <a href="javascript:void(0);" class="btn-danger py-2 px-4 rounded-md text-sm font-medium delete-event" data-id="<?php echo $event['event_id']; ?>">
            <i class="fas fa-trash mr-1"></i> Delete
        </a>
    </div>
</div>

<!-- Event Overview -->
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
            
            <p class="text-sm" style="color: var(--color-text-secondary);">
                <i class="fas fa-eye mr-2"></i> 
                <strong>Visibility:</strong>
                <span class="px-2 py-1 text-xs font-medium rounded-full ml-1" 
                      style="
                      <?php 
                      switch ($event['visibility']) {
                          case 'draft':
                              echo 'background-color: rgba(251, 188, 5, 0.1); color: #FBBC05;';
                              break;
                          case 'private':
                              echo 'background-color: rgba(117, 117, 117, 0.1); color: #757575;';
                              break;
                          case 'unlisted':
                              echo 'background-color: rgba(170, 136, 225, 0.1); color: #673AB7;';
                              break;
                          case 'public':
                              echo 'background-color: rgba(52, 168, 83, 0.1); color: #34A853;';
                              break;
                      }
                      ?>
                      ">
                    <?php echo ucfirst($event['visibility']); ?>
                </span>
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
                      style="background-color: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>;">
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
                Created by <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?> on <?php echo date('M d, Y', strtotime($event['created_at'])); ?>
            </div>
            
            <div>
                <a href="?page=lead_events&action=edit&event_id=<?php echo $event['event_id']; ?>" class="text-sm font-medium" style="color: #4285F4;">
                    <i class="fas fa-edit mr-1"></i> Edit Event
                </a>
            </div>
        </div>
    </div>
</div>

<!-- RSVP Statistics -->
<div class="google-card p-5 mb-6">
    <div class="mb-4">
        <h2 class="text-lg font-medium" style="color: var(--color-text-primary);">RSVP Statistics</h2>
        <p class="text-sm" style="color: var(--color-text-secondary);">
            Overview of attendee responses
        </p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="p-4 border rounded-lg" style="border-color: var(--color-border-light);">
            <div class="text-2xl font-medium mb-1" style="color: var(--color-text-primary);"><?php echo $totalRSVPs; ?></div>
            <div class="text-sm" style="color: var(--color-text-secondary);">Total Responses</div>
        </div>
        
        <div class="p-4 border rounded-lg" style="border-color: var(--color-border-light);">
            <div class="text-2xl font-medium mb-1" style="color: #34A853;"><?php echo $goingCount; ?></div>
            <div class="text-sm" style="color: var(--color-text-secondary);">Going</div>
        </div>
        
        <div class="p-4 border rounded-lg" style="border-color: var(--color-border-light);">
            <div class="text-2xl font-medium mb-1" style="color: #FBBC05;"><?php echo $interestedCount; ?></div>
            <div class="text-sm" style="color: var(--color-text-secondary);">Interested</div>
        </div>
        
        <div class="p-4 border rounded-lg" style="border-color: var(--color-border-light);">
            <div class="text-2xl font-medium mb-1" style="color: #EA4335;"><?php echo $notGoingCount; ?></div>
            <div class="text-sm" style="color: var(--color-text-secondary);">Not Going</div>
        </div>
    </div>
    
    <?php if (!empty($rsvps)): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Comment</th>
                    <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rsvps as $rsvp): ?>
                <tr class="border-t" style="border-color: var(--color-border-light);">
                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">
                        <?php echo htmlspecialchars($rsvp['first_name'] . ' ' . $rsvp['last_name']); ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs font-medium rounded-full" 
                              style="background-color: <?php 
                                echo $rsvp['status'] == 'going' ? 'rgba(52, 168, 83, 0.1)' : 
                                     ($rsvp['status'] == 'interested' ? 'rgba(251, 188, 5, 0.1)' : 'rgba(234, 67, 53, 0.1)'); 
                                ?>; 
                                color: <?php 
                                echo $rsvp['status'] == 'going' ? '#34A853' : 
                                     ($rsvp['status'] == 'interested' ? '#FBBC05' : '#EA4335'); 
                                ?>;">
                            <?php echo ucfirst($rsvp['status']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-secondary);">
                        <?php echo !empty($rsvp['comment']) ? htmlspecialchars($rsvp['comment']) : '-'; ?>
                    </td>
                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-tertiary);">
                        <?php echo date('M d, Y h:i A', strtotime($rsvp['created_at'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center py-4">
        <p class="text-sm" style="color: var(--color-text-secondary);">No RSVPs yet.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Event Tasks Section -->
<div class="google-card p-5 mb-6">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-lg font-medium" style="color: var(--color-text-primary);">Event Tasks</h2>
            <p class="text-sm" style="color: var(--color-text-secondary);">
                Items that need to be completed for this event
            </p>
        </div>
        <div>
            <a href="?page=lead_tasks&action=create&event_id=<?php echo $event_id; ?>" class="btn-primary py-2 px-3 rounded-md text-sm font-medium">
                <i class="fas fa-plus mr-1"></i> Add Task
            </a>
        </div>
    </div>
    
    <?php 
    $tasks = $eventsManager->getEventTasks($event_id);
    $taskStatusColors = [
        'todo' => ['bg' => 'rgba(117, 117, 117, 0.1)', 'text' => '#757575'],
        'in_progress' => ['bg' => 'rgba(251, 188, 5, 0.1)', 'text' => '#FBBC05'],
        'done' => ['bg' => 'rgba(52, 168, 83, 0.1)', 'text' => '#34A853'],
        'blocked' => ['bg' => 'rgba(234, 67, 53, 0.1)', 'text' => '#EA4335']
    ];
    $priorityLabels = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent'
    ];
    $priorityColors = [
        'low' => '#757575',
        'medium' => '#4285F4',
        'high' => '#FBBC05',
        'urgent' => '#EA4335'
    ];
    ?>
    
    <?php if (empty($tasks)): ?>
    <div class="text-center py-4 border rounded-lg" style="border-color: var(--color-border-light);">
        <p class="text-sm mb-2" style="color: var(--color-text-secondary);">No tasks have been created for this event yet.</p>
        <a href="?page=lead_tasks&action=create&event_id=<?php echo $event_id; ?>" class="text-sm font-medium" style="color: #4285F4;">
            <i class="fas fa-plus mr-1"></i> Create the first task
        </a>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Task</th>
                    <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Priority</th>
                    <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Deadline</th>
                    <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Assigned To</th>
                    <th class="px-4 py-2 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                <tr class="border-t" style="border-color: var(--color-border-light);">
                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">
                        <?php echo htmlspecialchars($task['title']); ?>
                        <?php if (!empty($task['description'])): ?>
                        <div class="text-xs mt-1" style="color: var(--color-text-tertiary);">
                            <?php echo htmlspecialchars(substr($task['description'], 0, 50)) . (strlen($task['description']) > 50 ? '...' : ''); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs font-medium rounded-full" 
                              style="background-color: <?php echo $taskStatusColors[$task['status']]['bg']; ?>; 
                                     color: <?php echo $taskStatusColors[$task['status']]['text']; ?>;">
                            <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium" style="color: <?php echo $priorityColors[$task['priority']]; ?>;">
                            <?php echo $priorityLabels[$task['priority']]; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-secondary);">
                        <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                    </td>
                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-secondary);">
                        <?php echo $task['assigned_to_name'] ?? 'Unassigned'; ?>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <div class="flex space-x-2">
                            <a href="?page=lead_tasks&action=edit&task_id=<?php echo $task['task_id']; ?>" class="text-sm" style="color: #4285F4;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="javascript:void(0);" class="text-sm delete-task" data-id="<?php echo $task['task_id']; ?>" style="color: #EA4335;">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Comments Section -->
<div class="google-card p-5">
    <div class="mb-4">
        <h2 class="text-lg font-medium" style="color: var(--color-text-primary);">Comments</h2>
        <p class="text-sm" style="color: var(--color-text-secondary);">
            Discussions about this event
        </p>
    </div>
    
    <?php if (empty($comments)): ?>
    <div class="text-center py-4">
        <p class="text-sm" style="color: var(--color-text-secondary);">No comments yet.</p>
    </div>
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
                    <div class="flex items-center justify-between mb-1">
                        <div>
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
                        <div>
                            <a href="javascript:void(0);" class="text-sm delete-comment" data-id="<?php echo $comment['comment_id']; ?>" data-event-id="<?php echo $event['event_id']; ?>" style="color: #EA4335;">
                                <i class="fas fa-trash"></i>
                            </a>
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

<script src="views/lead/js/events.js"></script>
