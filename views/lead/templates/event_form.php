<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Extract start and end date/time if editing
if ($isEditing) {
  $startDate = date('Y-m-d', strtotime($event['start_date']));
  $startTime = date('H:i', strtotime($event['start_date']));
  $endDate = date('Y-m-d', strtotime($event['end_date']));
  $endTime = date('H:i', strtotime($event['end_date']));
} else {
  // Default values for new event
  $startDate = date('Y-m-d');
  $startTime = '09:00';
  $endDate = date('Y-m-d');
  $endTime = '17:00';
}
?>

<div class="mb-4">
  <a href="?page=lead_events" class="text-sm font-medium flex items-center" style="color: var(--color-text-secondary);">
    <i class="fas fa-arrow-left mr-2"></i> Back to Events
  </a>
</div>

<div class="mb-6">
  <h1 class="text-2xl font-normal" style="color: var(--color-text-primary);"><?php echo $pageTitle; ?></h1>
  <p class="text-sm" style="color: var(--color-text-secondary);">
    <?php echo $isEditing ? 'Update the details of your event' : 'Create a new event for your community'; ?>
  </p>
</div>

<form method="POST" action="views/lead/php/<?php echo $isEditing ? 'edit_event.php' : 'create_event.php'; ?>" enctype="multipart/form-data" class="space-y-6">
  <?php if ($isEditing): ?>
    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
  <?php endif; ?>
  <input type="hidden" name="action" value="<?php echo $isEditing ? 'update_event' : 'createEvent'; ?>">

  <!-- Basic Information -->
  <div class="google-card p-5">
    <div class="mb-4">
      <h2 class="text-lg font-medium" style="color: var(--color-text-primary);">Basic Information</h2>
      <p class="text-sm" style="color: var(--color-text-secondary);">Enter the essential details about your event</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label for="title" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Event Title *</label>
        <input type="text" id="title" name="title" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo $isEditing ? htmlspecialchars($event['title']) : ''; ?>" required>
      </div>

      <div>
        <label for="type_id" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Event Type *</label>
        <select id="type_id" name="type_id" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
          <option value="">Select Type</option>
          <?php foreach ($eventTypes as $type): ?>
            <option value="<?php echo $type['type_id']; ?>" <?php echo ($isEditing && $event['type_id'] == $type['type_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($type['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="mt-4">
      <label for="description" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Event Description *</label>
      <textarea id="description" name="description" rows="5" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required><?php echo $isEditing ? htmlspecialchars($event['description']) : ''; ?></textarea>
    </div>
    
    <div class="mt-4">
      <label for="max_participants" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Maximum Participants</label>
      <input type="number" id="max_participants" name="max_participants" min="0" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo $isEditing && isset($event['max_participants']) ? htmlspecialchars($event['max_participants']) : ''; ?>" placeholder="Leave empty for unlimited">
      <p class="text-xs mt-1" style="color: var(--color-text-tertiary);">Set a limit for the number of participants, or leave empty for unlimited.</p>
    </div>
  </div>

  <!-- Date and Time -->
  <div class="google-card p-5">
    <div class="mb-4">
      <h2 class="text-lg font-medium" style="color: var(--color-text-primary);">Date and Time</h2>
      <p class="text-sm" style="color: var(--color-text-secondary);">When will your event take place?</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Start Date and Time *</label>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <input type="date" name="start_date" class="date-picker w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo $startDate; ?>" required>
          </div>
          <div>
            <input type="time" name="start_time" class="time-picker w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo $startTime; ?>" required>
          </div>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">End Date and Time *</label>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <input type="date" name="end_date" class="date-picker w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo $endDate; ?>" required>
          </div>
          <div>
            <input type="time" name="end_time" class="time-picker w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo $endTime; ?>" required>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Location -->
  <div class="google-card p-5">
    <div class="mb-4">
      <h2 class="text-lg font-medium" style="color: var(--color-text-primary);">Location</h2>
      <p class="text-sm" style="color: var(--color-text-secondary);">Where will your event take place?</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label for="location" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Location Name</label>
        <input type="text" id="location" name="location" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo $isEditing ? htmlspecialchars($event['location']) : ''; ?>">
      </div>
      <div>
        <label for="location_map_url" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Map URL (Google Maps)</label>
        <input type="url" id="location_map_url" name="location_map_url" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo $isEditing ? htmlspecialchars($event['location_map_url']) : ''; ?>">
      </div>
    </div>
  </div>

  <!-- Featured Image -->
  <div class="google-card p-5">
    <div class="mb-4">
      <h2 class="text-lg font-medium" style="color: var(--color-text-primary);">Featured Image</h2>
      <p class="text-sm" style="color: var(--color-text-secondary);">Upload an image to represent your event</p>
    </div>

    <div class="mb-3">
      <label for="featured_image" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Event Image</label>
      <input type="file" class="form-control" name="featured_image" id="featured_image" accept="image/*">
      <?php if ($isEditing && !empty($event['featured_image'])): ?>
        <div class="mt-2">
          <p class="text-sm" style="color: var(--color-text-secondary);">Current image:</p>
          <img src="<?php echo htmlspecialchars($event['featured_image']); ?>" alt="Current Event Image" class="mt-1 max-h-40">
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Additional Information -->
  <div class="google-card p-5">
    <div class="mb-4">
      <h2 class="text-lg font-medium" style="color: var(--color-text-primary);">Additional Information</h2>
      <p class="text-sm" style="color: var(--color-text-secondary);">Provide more details about your event</p>
    </div>

    <div class="mb-4">
      <label for="speakers" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Speakers/Presenters</label>
      <textarea id="speakers" name="speakers" rows="3" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);"><?php echo $isEditing ? htmlspecialchars($event['speakers']) : ''; ?></textarea>
      <p class="text-xs mt-1" style="color: var(--color-text-tertiary);">Enter one speaker per line. Include name, title, and organization if applicable.</p>
    </div>

    <div class="mb-4">
      <label for="attachments" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Attachments</label>
      <input type="file" id="attachments" name="attachments[]" multiple class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
      <p class="text-xs mt-1" style="color: var(--color-text-tertiary);">PDF, DOC, DOCX, PPT, PPTX (max. 5MB each)</p>
    </div>

    <?php if ($isEditing && !empty($attachments)): ?>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Current Attachments</label>
        <ul class="space-y-2">
          <?php foreach ($attachments as $attachment): ?>
            <li class="flex items-center justify-between p-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-hover);">
              <div class="flex items-center">
                <i class="fas fa-file mr-2" style="color: var(--color-text-secondary);"></i>
                <span class="text-sm" style="color: var(--color-text-secondary);">
                  <?php echo htmlspecialchars($attachment['file_name']); ?>
                  <span class="text-xs ml-2" style="color: var(--color-text-tertiary);">
                    (<?php echo round($attachment['file_size'] / 1024, 2); ?> KB)
                  </span>
                </span>
              </div>
              <div>
                <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank" class="text-sm mr-2" style="color: #4285F4;">
                  <i class="fas fa-download"></i>
                </a>
                <a href="javascript:void(0);" class="text-sm delete-attachment" data-id="<?php echo $attachment['attachment_id']; ?>" data-event-id="<?php echo $event['event_id']; ?>" style="color: #EA4335;">
                  <i class="fas fa-trash"></i>
                </a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>

  <!-- Event Status and Visibility -->
  <div class="google-card p-4 mb-6">
    <div class="card-header mb-4">
      <h2 class="text-lg font-medium" style="color: var(--color-text-primary);">Status and Visibility</h2>
      <p class="text-sm" style="color: var(--color-text-secondary);">Set the current status and visibility of your event</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label for="status" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Event Status *</label>
        <select id="status" name="status" class="text-xs p-2 w-full rounded-md border focus:ring-2 focus:ring-blue-500" style="background-color: var(--color-input-bg); color: var(--color-text-primary); border-color: var(--color-border-light);" required>
          <option value="upcoming" style="color: #34A853; background-color: rgba(52, 168, 83, 0.1);" <?php echo ($isEditing && $event['status'] == 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
          <option value="ongoing" style="color: #4285F4; background-color: rgba(66, 133, 244, 0.1);" <?php echo ($isEditing && $event['status'] == 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
          <option value="completed" style="color: #EA4335; background-color: rgba(234, 67, 53, 0.1);" <?php echo ($isEditing && $event['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
          <option value="cancelled" style="color: #757575; background-color: rgba(117, 117, 117, 0.1);" <?php echo ($isEditing && $event['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
        </select>
      </div>

      <div>
        <label for="visibility" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Visibility *</label>
        <select id="visibility" name="visibility" class="text-xs p-2 w-full rounded-md border focus:ring-2 focus:ring-blue-500" style="background-color: var(--color-input-bg); color: var(--color-text-primary); border-color: var(--color-border-light);" required>
          <option value="draft" style="color: #FBBC05; background-color: rgba(251, 188, 5, 0.1);" <?php echo ($isEditing && $event['visibility'] == 'draft') ? 'selected' : ''; ?>>Draft (visible to admins only)</option>
          <option value="private" style="color: #757575; background-color: rgba(117, 117, 117, 0.1);" <?php echo ($isEditing && $event['visibility'] == 'private') ? 'selected' : ''; ?>>Private (visible to members only)</option>
          <option value="unlisted" style="color: #673AB7; background-color: rgba(170, 136, 225, 0.1);" <?php echo ($isEditing && $event['visibility'] == 'unlisted') ? 'selected' : ''; ?>>Unlisted (accessible only via link)</option>
          <option value="public" style="color: #34A853; background-color: rgba(52, 168, 83, 0.1);" <?php echo ($isEditing && $event['visibility'] == 'public') ? 'selected' : ''; ?>>Public (visible to all)</option>
        </select>
      </div>
    </div>

    <?php if ($isEditing): ?>
    <div class="mt-6 border-t pt-4" style="border-color: var(--color-border-light);">
      <div class="mb-2">
        <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Task Completion Status</h3>
        <p class="text-sm" style="color: var(--color-text-secondary);">Event can only be published when all tasks are completed</p>
      </div>
      
      <div class="mb-4">
        <!-- Task Completion Progress -->
        <div class="w-full bg-gray-200 h-4 rounded-full overflow-hidden">
          <?php 
            $completionStatus = $event['completion_status'] ?? 0;
            $statusColor = '#FBBC05'; // Default yellow for in progress
            
            if ($completionStatus >= 100) {
                $statusColor = '#34A853'; // Green for complete
            } elseif ($completionStatus > 0) {
                $statusColor = '#4285F4'; // Blue for in progress
            } elseif ($completionStatus == 0) {
                $statusColor = '#EA4335'; // Red for not started
            }
          ?>
          <div class="h-full" style="width: <?php echo $completionStatus; ?>%; background-color: <?php echo $statusColor; ?>;"></div>
        </div>
        
        <div class="flex justify-between mt-1">
          <span class="text-xs font-medium" style="color: var(--color-text-tertiary);">
            <?php if ($completionStatus >= 100): ?>
              All tasks completed
            <?php elseif ($completionStatus > 0): ?>
              <?php echo $completionStatus; ?>% of tasks completed
            <?php else: ?>
              No tasks assigned yet
            <?php endif; ?>
          </span>
          <span class="text-xs">
            <?php if ($event['ready_for_publish'] == 1): ?>
              <span class="text-xs font-medium px-2 py-1 rounded-full" style="background-color: rgba(52, 168, 83, 0.1); color: #34A853;">
                Ready to Publish
              </span>
            <?php else: ?>
              <span class="text-xs font-medium px-2 py-1 rounded-full" style="background-color: rgba(251, 188, 5, 0.1); color: #FBBC05;">
                Not Ready to Publish
              </span>
            <?php endif; ?>
          </span>
        </div>
      </div>

      <div class="flex justify-end">
        <a href="?page=lead_tasks&event_id=<?php echo $event['event_id']; ?>" class="text-sm px-3 py-1 rounded-md font-medium mr-2" style="color: #4285F4; border: 1px solid #4285F4;">
          <i class="fas fa-tasks mr-1"></i> View Event Tasks
        </a>
        <button type="button" id="refresh-completion-status" data-id="<?php echo $event['event_id']; ?>" class="text-sm px-3 py-1 rounded-md font-medium" style="color: white; background-color: #4285F4;">
          <i class="fas fa-sync-alt mr-1"></i> Refresh Status
        </button>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Form Actions -->
  <div class="flex justify-between">
    <a href="?page=lead_events" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium">Cancel</a>
    <button type="submit" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
      <?php echo $isEditing ? 'Update Event' : 'Create Event'; ?>
    </button>
  </div>
</form>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Handle delete attachment confirmation
    const deleteAttachmentButtons = document.querySelectorAll('.delete-attachment');
    if (deleteAttachmentButtons.length > 0) {
      deleteAttachmentButtons.forEach(button => {
        button.addEventListener('click', function() {
          const attachmentId = this.dataset.id;
          const eventId = this.dataset.eventId || '';
          if (confirm('Are you sure you want to delete this attachment?')) {
            window.location.href = 'views/lead/php/event_handler.php?action=delete_attachment&attachment_id=' + attachmentId + '&event_id=' + eventId;
          }
        });
      });
    }

    // Display selected files
    const fileInput = document.getElementById('attachments');
    if (fileInput) {
      fileInput.addEventListener('change', function() {
        const fileList = document.createElement('div');
        fileList.className = 'mt-2';

        if (this.files.length > 0) {
          const list = document.createElement('ul');
          list.className = 'space-y-1 mt-2';

          for (let i = 0; i < this.files.length; i++) {
            const file = this.files[i];
            const item = document.createElement('li');
            item.className = 'text-xs flex items-center';
            item.style.color = 'var(--color-text-secondary)';

            const icon = document.createElement('i');
            icon.className = 'fas fa-file mr-2';

            const name = document.createElement('span');
            name.textContent = file.name;

            const size = document.createElement('span');
            size.className = 'ml-2';
            size.style.color = 'var(--color-text-tertiary)';
            size.textContent = `(${(file.size / 1024).toFixed(2)} KB)`;

            item.appendChild(icon);
            item.appendChild(name);
            item.appendChild(size);
            list.appendChild(item);
          }

          fileList.appendChild(list);

          // Insert after the input
          fileInput.parentNode.insertBefore(fileList, fileInput.nextSibling);
        }
      });
    }

    // Preview image before upload
    const imageInput = document.getElementById('featured_image');
    if (imageInput) {
      imageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
          const reader = new FileReader();

          reader.onload = function(e) {
            // Remove existing preview if any
            const existingPreview = document.querySelector('.image-preview');
            if (existingPreview) {
              existingPreview.remove();
            }

            // Create new preview
            const preview = document.createElement('div');
            preview.className = 'image-preview mt-2';

            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = 'Event Image Preview';
            img.className = 'max-h-40 mt-1';

            preview.appendChild(img);

            // Insert after the input
            imageInput.parentNode.insertBefore(preview, imageInput.nextSibling);
          };

          reader.readAsDataURL(this.files[0]);
        }
      });
    }
  });
</script>