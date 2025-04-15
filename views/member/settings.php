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
    'community_updates' => 1
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_notification_settings'])) {
        // Process notification settings update
        $email_notifications = isset($_POST['email_notifications']) && $_POST['email_notifications'] == '1' ? 1 : 0;
        $event_reminders = isset($_POST['event_reminders']) && $_POST['event_reminders'] == '1' ? 1 : 0;
        $community_updates = isset($_POST['community_updates']) && $_POST['community_updates'] == '1' ? 1 : 0;
        
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
                    `community_updates` tinyint(1) DEFAULT 1,
                    `created_at` timestamp NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`setting_id`),
                    UNIQUE KEY `user_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
                
                $conn->exec($createTableSQL);
            }
            
            // Check if settings exist for this user
            $query = "SELECT * FROM user_settings WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Update existing settings
                $query = "UPDATE user_settings SET 
                          email_notifications = ?, 
                          event_reminders = ?, 
                          community_updates = ?, 
                          updated_at = NOW() 
                          WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(1, $email_notifications, PDO::PARAM_INT);
                $stmt->bindParam(2, $event_reminders, PDO::PARAM_INT);
                $stmt->bindParam(3, $community_updates, PDO::PARAM_INT);
                $stmt->bindParam(4, $user_id, PDO::PARAM_INT);
            } else {
                // Insert new settings
                $query = "INSERT INTO user_settings (user_id, email_notifications, event_reminders, community_updates, created_at, updated_at) 
                          VALUES (?, ?, ?, ?, NOW(), NOW())";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                $stmt->bindParam(2, $email_notifications, PDO::PARAM_INT);
                $stmt->bindParam(3, $event_reminders, PDO::PARAM_INT);
                $stmt->bindParam(4, $community_updates, PDO::PARAM_INT);
            }
            
            if ($stmt->execute()) {
                $success_message = 'Notification settings updated successfully.';
                
                // Update the settings variable with new values
                $settings['email_notifications'] = $email_notifications;
                $settings['event_reminders'] = $event_reminders;
                $settings['community_updates'] = $community_updates;
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
                    <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Community Updates</h3>
                    <p class="text-sm" style="color: var(--color-text-secondary);">Receive updates about GDG on Campus WMSU</p>
                </div>
                <div class="toggle-wrapper">
                    <div class="toggle-switch" onclick="toggleSetting('community_updates')" title="Toggle community updates">
                        <div class="toggle-track" id="track_community_updates" data-active="<?php echo isset($settings['community_updates']) && $settings['community_updates'] ? 'true' : 'false'; ?>">
                            <div class="toggle-thumb"></div>
                        </div>
                    </div>
                    <input type="hidden" name="community_updates" id="community_updates" value="<?php echo isset($settings['community_updates']) && $settings['community_updates'] ? '1' : '0'; ?>">
                </div>
            </div>
            
            <div class="pt-4">
                <button type="submit" name="update_notification_settings" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">Save Settings</button>
            </div>
        </div>
    </form>
</div>

<!-- Theme Settings -->
<div class="google-card p-5 mb-6">
    <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Theme Settings</h2>
    
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Dark Mode</h3>
                <p class="text-sm" style="color: var(--color-text-secondary);">Toggle between light and dark theme</p>
            </div>
            <div class="theme-toggle-wrapper">
                <button type="button" class="theme-toggle-switch" onclick="toggleTheme()" title="Toggle theme">
                    <div class="theme-toggle-track">
                        <div class="theme-toggle-icons">
                            <i class="fas fa-sun theme-icon-sun" style="color: #f6e05e;"></i>
                            <i class="fas fa-moon theme-icon-moon" style="color: #a0aec0;"></i>
                        </div>
                        <div class="theme-toggle-thumb"></div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Account Settings -->
<div class="google-card p-5">
    <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Account Settings</h2>
    
    <div class="space-y-4">
        <div>
            <a href="?page=member_profile" class="text-sm font-medium flex items-center" style="color: #4285F4;">
                <i class="fas fa-user-edit mr-2"></i> Edit Profile
            </a>
        </div>
        
        <div>
            <a href="?page=member_profile#password" class="text-sm font-medium flex items-center" style="color: #4285F4;">
                <i class="fas fa-key mr-2"></i> Change Password
            </a>
        </div>
        
        <div class="pt-4 border-t" style="border-color: var(--color-border-light);">
            <a href="logout.php" class="text-sm font-medium flex items-center" style="color: #EA4335;">
                <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
            </a>
        </div>
    </div>
</div>

<style>
/* Common toggle switch styles */
.toggle-wrapper, .theme-toggle-wrapper {
    display: inline-block;
}

.toggle-switch, .theme-toggle-switch {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    outline: none;
}

.toggle-track, .theme-toggle-track {
    position: relative;
    width: 50px;
    height: 24px;
    border-radius: 12px;
    background-color: #ccc;
    transition: background-color 0.3s;
    display: flex;
    align-items: center;
    padding: 0 4px;
}

.toggle-track[data-active="true"], .theme-toggle-track {
    background-color: #4285F4;
}

.toggle-thumb, .theme-toggle-thumb {
    position: absolute;
    left: 2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s;
}

.toggle-track[data-active="true"] .toggle-thumb, 
[data-theme="dark"] .theme-toggle-thumb {
    transform: translateX(26px);
}

/* Theme-specific styles */
.theme-toggle-icons {
    position: absolute;
    width: 100%;
    display: flex;
    justify-content: space-between;
    padding: 0 6px;
    box-sizing: border-box;
}

[data-theme="dark"] .theme-toggle-track {
    background-color: #555;
}

.theme-icon-moon, .theme-icon-sun {
    font-size: 12px;
    transition: opacity 0.3s;
}

.theme-icon-moon {
    opacity: 1;
}

.theme-icon-sun {
    opacity: 0.5;
}

[data-theme="dark"] .theme-icon-moon {
    opacity: 0.5;
}

[data-theme="dark"] .theme-icon-sun {
    opacity: 1;
}
</style>

<script>
// Function to toggle settings
function toggleSetting(settingId) {
    // Get the track element
    const track = document.getElementById('track_' + settingId);
    // Get the hidden input
    const input = document.getElementById(settingId);
    
    // Toggle the active state
    const isActive = track.getAttribute('data-active') === 'true';
    const newState = !isActive;
    
    // Update the visual state
    track.setAttribute('data-active', newState ? 'true' : 'false');
    
    // Update the background color directly
    track.style.backgroundColor = newState ? '#4285F4' : '#ccc';
    
    // Update the thumb position
    const thumb = track.querySelector('.toggle-thumb');
    if (thumb) {
        thumb.style.transform = newState ? 'translateX(26px)' : 'translateX(0)';
    }
    
    // Update the hidden input value
    input.value = newState ? '1' : '0';
    
    console.log('Setting ' + settingId + ' changed to ' + input.value);
}

// Initialize toggle states on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification toggles
    document.querySelectorAll('.toggle-track').forEach(track => {
        const isActive = track.getAttribute('data-active') === 'true';
        
        // Set initial background color
        track.style.backgroundColor = isActive ? '#4285F4' : '#ccc';
        
        // Set initial thumb position
        const thumb = track.querySelector('.toggle-thumb');
        if (thumb) {
            thumb.style.transform = isActive ? 'translateX(26px)' : 'translateX(0)';
        }
    });
    
    // Debug: Log form values before submission
    const form = document.getElementById('settingsForm');
    if (form) {
        form.addEventListener('submit', function() {
            console.log('Form submitted with values:');
            console.log('email_notifications: ' + document.getElementById('email_notifications').value);
            console.log('event_reminders: ' + document.getElementById('event_reminders').value);
            console.log('community_updates: ' + document.getElementById('community_updates').value);
        });
    }
});
</script>
