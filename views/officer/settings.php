<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get current user's settings
$user_id = $_SESSION['user_id'];

// Handle settings update
$success_message = '';
$error_message = '';

// Initialize settings with defaults
$settings = [
    'email_notifications' => 1,
    'event_reminders' => 1,
    'task_notifications' => 1,
    'department_updates' => 1
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_notification_settings'])) {
        // Process notification settings update
        $email_notifications = isset($_POST['email_notifications']) && $_POST['email_notifications'] == '1' ? 1 : 0;
        $event_reminders = isset($_POST['event_reminders']) && $_POST['event_reminders'] == '1' ? 1 : 0;
        $task_notifications = isset($_POST['task_notifications']) && $_POST['task_notifications'] == '1' ? 1 : 0;
        $department_updates = isset($_POST['department_updates']) && $_POST['department_updates'] == '1' ? 1 : 0;
        
        try {
            // First, check if the user_settings table exists
            $tableExists = false;
            try {
                $checkTable = $conn->query("SHOW TABLES LIKE 'user_settings'");
                $tableExists = ($checkTable->rowCount() > 0);
            } catch (PDOException $e) {
                // Table doesn't exist, we'll create it
                $tableExists = false;
            }
            
            // Create the table if it doesn't exist
            if (!$tableExists) {
                $createTableSQL = "CREATE TABLE IF NOT EXISTS `user_settings` (
                    `setting_id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `email_notifications` tinyint(1) DEFAULT 1,
                    `event_reminders` tinyint(1) DEFAULT 1,
                    `task_notifications` tinyint(1) DEFAULT 1,
                    `department_updates` tinyint(1) DEFAULT 1,
                    `created_at` timestamp NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`setting_id`),
                    UNIQUE KEY `user_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
                
                $conn->exec($createTableSQL);
            } else {
                // Check if the required columns exist, and add them if they don't
                try {
                    // Get column information
                    $columnsQuery = $conn->query("SHOW COLUMNS FROM user_settings");
                    $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN, 0);
                    
                    // Check and add task_notifications column if it doesn't exist
                    if (!in_array('task_notifications', $columns)) {
                        $conn->exec("ALTER TABLE user_settings ADD COLUMN task_notifications tinyint(1) DEFAULT 1");
                    }
                    
                    // Check and add department_updates column if it doesn't exist
                    if (!in_array('department_updates', $columns)) {
                        $conn->exec("ALTER TABLE user_settings ADD COLUMN department_updates tinyint(1) DEFAULT 1");
                    }
                } catch (PDOException $e) {
                    error_log("Error checking/adding columns: " . $e->getMessage());
                    // Continue anyway, we'll handle any issues in the next steps
                }
            }
            
            // Check if settings exist for this user
            $query = "SELECT * FROM user_settings WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Update existing settings
                // First, check which columns actually exist in the table
                $columnsQuery = $conn->query("SHOW COLUMNS FROM user_settings");
                $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN, 0);
                
                // Build the update query dynamically based on existing columns
                $updateQuery = "UPDATE user_settings SET updated_at = NOW()";
                $params = [];
                
                if (in_array('email_notifications', $columns)) {
                    $updateQuery .= ", email_notifications = ?";
                    $params[] = $email_notifications;
                }
                
                if (in_array('event_reminders', $columns)) {
                    $updateQuery .= ", event_reminders = ?";
                    $params[] = $event_reminders;
                }
                
                if (in_array('task_notifications', $columns)) {
                    $updateQuery .= ", task_notifications = ?";
                    $params[] = $task_notifications;
                }
                
                if (in_array('department_updates', $columns)) {
                    $updateQuery .= ", department_updates = ?";
                    $params[] = $department_updates;
                }
                
                $updateQuery .= " WHERE user_id = ?";
                $params[] = $user_id;
                
                $stmt = $conn->prepare($updateQuery);
                for ($i = 0; $i < count($params); $i++) {
                    $stmt->bindParam($i + 1, $params[$i]);
                }
            } else {
                // Insert new settings
                // First, check which columns actually exist in the table
                $columnsQuery = $conn->query("SHOW COLUMNS FROM user_settings");
                $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN, 0);
                
                // Build the insert query dynamically based on existing columns
                $columnNames = ['user_id'];
                $placeholders = ['?'];
                $params = [$user_id];
                
                if (in_array('email_notifications', $columns)) {
                    $columnNames[] = 'email_notifications';
                    $placeholders[] = '?';
                    $params[] = $email_notifications;
                }
                
                if (in_array('event_reminders', $columns)) {
                    $columnNames[] = 'event_reminders';
                    $placeholders[] = '?';
                    $params[] = $event_reminders;
                }
                
                if (in_array('task_notifications', $columns)) {
                    $columnNames[] = 'task_notifications';
                    $placeholders[] = '?';
                    $params[] = $task_notifications;
                }
                
                if (in_array('department_updates', $columns)) {
                    $columnNames[] = 'department_updates';
                    $placeholders[] = '?';
                    $params[] = $department_updates;
                }
                
                $columnNames[] = 'created_at';
                $placeholders[] = 'NOW()';
                
                $columnNames[] = 'updated_at';
                $placeholders[] = 'NOW()';
                
                $insertQuery = "INSERT INTO user_settings (" . implode(', ', $columnNames) . ") VALUES (" . implode(', ', $placeholders) . ")";
                
                $stmt = $conn->prepare($insertQuery);
                for ($i = 0; $i < count($params); $i++) {
                    $stmt->bindParam($i + 1, $params[$i]);
                }
            }
            
            if ($stmt->execute()) {
                $success_message = 'Notification settings updated successfully.';
                
                // Update the settings variable with new values
                $settings['email_notifications'] = $email_notifications;
                $settings['event_reminders'] = $event_reminders;
                $settings['task_notifications'] = $task_notifications;
                $settings['department_updates'] = $department_updates;
            } else {
                $error_message = 'Failed to update notification settings.';
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error_message = 'An error occurred while updating settings: ' . $e->getMessage();
        }
    }
}

// Get current settings
if (empty($error_message)) {
    try {
        // First, check if the user_settings table exists
        $tableExists = false;
        try {
            $checkTable = $conn->query("SHOW TABLES LIKE 'user_settings'");
            $tableExists = ($checkTable->rowCount() > 0);
        } catch (PDOException $e) {
            // Table doesn't exist
            $tableExists = false;
        }
        
        if ($tableExists) {
            $query = "SELECT * FROM user_settings WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $userSettings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If settings exist, update our defaults
            if ($userSettings) {
                $settings = $userSettings;
            }
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error_message = 'An error occurred while fetching settings: ' . $e->getMessage();
        // We'll use the default settings initialized at the top
    }
}

// Get user department information
$departmentInfo = null;
try {
    $query = "SELECT u.department_id, d.name as department_name 
              FROM users u 
              LEFT JOIN departments d ON u.department_id = d.department_id 
              WHERE u.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $departmentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>

<div class="mb-6">
    <h1 class="text-2xl font-normal mb-1" style="color: var(--color-text-primary);">Settings</h1>
    <p class="text-sm" style="color: var(--color-text-secondary);">
        Manage your account preferences and notifications
    </p>
</div>

<?php if (!empty($success_message)): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
    <span class="block sm:inline"><?php echo $success_message; ?></span>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
    <span class="block sm:inline"><?php echo $error_message; ?></span>
</div>
<?php endif; ?>

<!-- Notification Settings -->
<div class="google-card p-5 mb-6">
    <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Notification Settings</h2>
    
    <form method="post" action="" id="settingsForm">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Email Notifications</h3>
                    <p class="text-sm" style="color: var(--color-text-secondary);">Receive notifications via email</p>
                </div>
                <div class="toggle-wrapper">
                    <div class="toggle-switch" onclick="toggleSetting('email_notifications')" title="Toggle email notifications">
                        <div class="toggle-track" id="track_email_notifications" data-active="<?php echo isset($settings['email_notifications']) && $settings['email_notifications'] ? 'true' : 'false'; ?>">
                            <div class="toggle-thumb"></div>
                        </div>
                    </div>
                    <input type="hidden" name="email_notifications" id="email_notifications" value="<?php echo isset($settings['email_notifications']) && $settings['email_notifications'] ? '1' : '0'; ?>">
                </div>
            </div>
            
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Event Reminders</h3>
                    <p class="text-sm" style="color: var(--color-text-secondary);">Receive reminders about upcoming events</p>
                </div>
                <div class="toggle-wrapper">
                    <div class="toggle-switch" onclick="toggleSetting('event_reminders')" title="Toggle event reminders">
                        <div class="toggle-track" id="track_event_reminders" data-active="<?php echo isset($settings['event_reminders']) && $settings['event_reminders'] ? 'true' : 'false'; ?>">
                            <div class="toggle-thumb"></div>
                        </div>
                    </div>
                    <input type="hidden" name="event_reminders" id="event_reminders" value="<?php echo isset($settings['event_reminders']) && $settings['event_reminders'] ? '1' : '0'; ?>">
                </div>
            </div>
            
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Task Notifications</h3>
                    <p class="text-sm" style="color: var(--color-text-secondary);">Receive notifications about task assignments and updates</p>
                </div>
                <div class="toggle-wrapper">
                    <div class="toggle-switch" onclick="toggleSetting('task_notifications')" title="Toggle task notifications">
                        <div class="toggle-track" id="track_task_notifications" data-active="<?php echo isset($settings['task_notifications']) && $settings['task_notifications'] ? 'true' : 'false'; ?>">
                            <div class="toggle-thumb"></div>
                        </div>
                    </div>
                    <input type="hidden" name="task_notifications" id="task_notifications" value="<?php echo isset($settings['task_notifications']) && $settings['task_notifications'] ? '1' : '0'; ?>">
                </div>
            </div>
            
            <?php if ($departmentInfo && $departmentInfo['department_id']): ?>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Department Updates</h3>
                    <p class="text-sm" style="color: var(--color-text-secondary);">Receive updates about your department (<?php echo htmlspecialchars($departmentInfo['department_name']); ?>)</p>
                </div>
                <div class="toggle-wrapper">
                <div class="toggle-switch" onclick="toggleSetting('department_updates')" title="Toggle department updates">
                        <div class="toggle-track" id="track_department_updates" data-active="<?php echo isset($settings['department_updates']) && $settings['department_updates'] ? 'true' : 'false'; ?>">
                            <div class="toggle-thumb"></div>
                        </div>
                    </div>
                    <input type="hidden" name="department_updates" id="department_updates" value="<?php echo isset($settings['department_updates']) && $settings['department_updates'] ? '1' : '0'; ?>">
                </div>
            </div>
            <?php endif; ?>
            
            <div class="mt-6">
                <button type="submit" name="update_notification_settings" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                    Save Notification Settings
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Officer-specific Settings: Display Preferences -->
<div class="google-card p-5 mb-6">
    <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Display Preferences</h2>
    
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Dark Mode</h3>
                <p class="text-sm" style="color: var(--color-text-secondary);">Switch between light and dark theme</p>
            </div>
            <div class="toggle-wrapper">
                <div class="toggle-switch" onclick="toggleTheme()" title="Toggle dark mode">
                    <div class="toggle-track" id="track_dark_mode" data-active="false">
                        <div class="toggle-thumb"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Compact View</h3>
                <p class="text-sm" style="color: var(--color-text-secondary);">Show more content with less spacing</p>
            </div>
            <div class="toggle-wrapper">
                <div class="toggle-switch" onclick="toggleCompactView()" title="Toggle compact view">
                    <div class="toggle-track" id="track_compact_view" data-active="false">
                        <div class="toggle-thumb"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Officer Task Preferences -->
<div class="google-card p-5 mb-6">
    <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Task Preferences</h2>
    
    <div class="space-y-4">
        <div>
            <h3 class="text-md font-medium mb-2" style="color: var(--color-text-primary);">Default Task View</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="radio-card">
                    <input type="radio" name="task_view" id="task_view_list" class="hidden" checked>
                    <label for="task_view_list" class="block p-3 border rounded-lg cursor-pointer transition-all" style="border-color: var(--color-border-light); background-color: var(--color-surface);">
                        <div class="flex items-center">
                            <div class="w-5 h-5 rounded-full border-2 mr-3 flex items-center justify-center radio-circle" style="border-color: var(--color-primary);">
                                <div class="w-3 h-3 rounded-full radio-dot" style="background-color: var(--color-primary);"></div>
                            </div>
                            <div>
                                <span class="font-medium" style="color: var(--color-text-primary);">List View</span>
                            </div>
                        </div>
                    </label>
                </div>
                
                <div class="radio-card">
                    <input type="radio" name="task_view" id="task_view_board" class="hidden">
                    <label for="task_view_board" class="block p-3 border rounded-lg cursor-pointer transition-all" style="border-color: var(--color-border-light); background-color: var(--color-surface);">
                        <div class="flex items-center">
                            <div class="w-5 h-5 rounded-full border-2 mr-3 flex items-center justify-center radio-circle" style="border-color: var(--color-primary);">
                                <div class="w-3 h-3 rounded-full radio-dot" style="background-color: var(--color-primary);"></div>
                            </div>
                            <div>
                                <span class="font-medium" style="color: var(--color-text-primary);">Board View</span>
                            </div>
                        </div>
                    </label>
                </div>
                
                <div class="radio-card">
                    <input type="radio" name="task_view" id="task_view_calendar" class="hidden">
                    <label for="task_view_calendar" class="block p-3 border rounded-lg cursor-pointer transition-all" style="border-color: var(--color-border-light); background-color: var(--color-surface);">
                        <div class="flex items-center">
                            <div class="w-5 h-5 rounded-full border-2 mr-3 flex items-center justify-center radio-circle" style="border-color: var(--color-primary);">
                                <div class="w-3 h-3 rounded-full radio-dot" style="background-color: var(--color-primary);"></div>
                            </div>
                            <div>
                                <span class="font-medium" style="color: var(--color-text-primary);">Calendar View</span>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        
        <div>
            <h3 class="text-md font-medium mb-2" style="color: var(--color-text-primary);">Task Sorting</h3>
            <select class="w-full md:w-1/2 px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                <option value="deadline_asc">Deadline (Earliest First)</option>
                <option value="deadline_desc">Deadline (Latest First)</option>
                <option value="priority_desc">Priority (Highest First)</option>
                <option value="priority_asc">Priority (Lowest First)</option>
                <option value="title_asc">Title (A-Z)</option>
                <option value="title_desc">Title (Z-A)</option>
            </select>
        </div>
    </div>
</div>

<!-- Account Deactivation -->
<div class="google-card p-5 mb-6 border border-red-200" style="border-color: rgba(234, 67, 53, 0.3);">
    <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Account Actions</h2>
    
    <div class="space-y-4">
        <div>
            <h3 class="text-md font-medium" style="color: #EA4335;">Request Account Deactivation</h3>
            <p class="text-sm mb-4" style="color: var(--color-text-secondary);">
                If you wish to deactivate your account, please contact an administrator. Your data will be preserved but your account will be inactive.
            </p>
            <button type="button" class="px-4 py-2 border border-red-500 text-red-500 rounded-md hover:bg-red-50 text-sm font-medium" onclick="showDeactivationModal()">
                Request Deactivation
            </button>
        </div>
    </div>
</div>

<!-- Deactivation Request Modal -->
<div id="deactivationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-full" style="background-color: var(--color-surface); color: var(--color-text-primary);">
        <h3 class="text-lg font-medium mb-4">Request Account Deactivation</h3>
        <p class="mb-4" style="color: var(--color-text-secondary);">
            Please provide a reason for your deactivation request. An administrator will review your request.
        </p>
        <textarea id="deactivationReason" rows="4" class="w-full px-3 py-2 border rounded-md mb-4" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" placeholder="Reason for deactivation..."></textarea>
        <div class="flex justify-end space-x-3">
            <button type="button" class="px-4 py-2 border rounded-md text-sm font-medium" style="border-color: var(--color-border-light); color: var(--color-text-primary);" onclick="hideDeactivationModal()">
                Cancel
            </button>
            <button type="button" class="px-4 py-2 bg-red-500 text-white rounded-md text-sm font-medium hover:bg-red-600" onclick="submitDeactivationRequest()">
                Submit Request
            </button>
        </div>
    </div>
</div>

<style>
/* Toggle Switch Styles */
.toggle-wrapper {
    position: relative;
    display: inline-block;
}

.toggle-switch {
    cursor: pointer;
}

.toggle-track {
    width: 40px;
    height: 20px;
    border-radius: 10px;
    background-color: #ccc;
    position: relative;
    transition: background-color 0.3s;
}

.toggle-track[data-active="true"] {
    background-color: var(--color-primary);
}

.toggle-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: white;
    transition: transform 0.3s;
}

.toggle-track[data-active="true"] .toggle-thumb {
    transform: translateX(20px);
}

/* Radio Button Styles */
.radio-card input[type="radio"]:checked + label {
    border-color: var(--color-primary);
    background-color: rgba(66, 133, 244, 0.05);
}

.radio-card input[type="radio"]:not(:checked) + label .radio-dot {
    display: none;
}
</style>

<script>
// Function to toggle settings
function toggleSetting(settingId) {
    const track = document.getElementById('track_' + settingId);
    const input = document.getElementById(settingId);
    
    const isActive = track.getAttribute('data-active') === 'true';
    track.setAttribute('data-active', !isActive);
    input.value = !isActive ? '1' : '0';
}

// Function to toggle compact view
function toggleCompactView() {
    const track = document.getElementById('track_compact_view');
    const isActive = track.getAttribute('data-active') === 'true';
    track.setAttribute('data-active', !isActive);
    
    // Apply compact view styles
    document.body.classList.toggle('compact-view', !isActive);
    
    // Save preference to localStorage
    localStorage.setItem('compactView', !isActive);
}

// Function to show deactivation modal
function showDeactivationModal() {
    document.getElementById('deactivationModal').classList.remove('hidden');
}

// Function to hide deactivation modal
function hideDeactivationModal() {
    document.getElementById('deactivationModal').classList.add('hidden');
}

// Function to submit deactivation request
function submitDeactivationRequest() {
    const reason = document.getElementById('deactivationReason').value.trim();
    
    if (!reason) {
        alert('Please provide a reason for your deactivation request.');
        return;
    }
    
    // Here you would typically send an AJAX request to submit the deactivation request
    // For now, we'll just show a confirmation message
    alert('Your deactivation request has been submitted. An administrator will review your request.');
    hideDeactivationModal();
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    // Set dark mode toggle based on current theme
    const darkModeTrack = document.getElementById('track_dark_mode');
    const currentTheme = document.documentElement.getAttribute('data-theme');
    darkModeTrack.setAttribute('data-active', currentTheme === 'dark');
    
    // Set compact view toggle based on saved preference
    const compactViewTrack = document.getElementById('track_compact_view');
    const isCompactView = localStorage.getItem('compactView') === 'true';
    compactViewTrack.setAttribute('data-active', isCompactView);
    
    if (isCompactView) {
        document.body.classList.add('compact-view');
    }
    
    // Initialize radio buttons
    const taskViewRadios = document.querySelectorAll('input[name="task_view"]');
    taskViewRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Save preference to localStorage
            localStorage.setItem('taskView', this.id.replace('task_view_', ''));
        });
    });
    
    // Set initial task view based on saved preference
    const savedTaskView = localStorage.getItem('taskView');
    if (savedTaskView) {
        const radioToCheck = document.getElementById('task_view_' + savedTaskView);
        if (radioToCheck) {
            radioToCheck.checked = true;
        }
    }
});

// Listen for theme changes
document.addEventListener('themeChanged', function(e) {
    const darkModeTrack = document.getElementById('track_dark_mode');
    darkModeTrack.setAttribute('data-active', e.detail.theme === 'dark');
});
</script>
