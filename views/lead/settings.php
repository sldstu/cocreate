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
    'department_updates' => 1,
    'system_alerts' => 1,
    'security_notifications' => 1,
    'user_activity_reports' => 1
];

// Handle system settings
$system_settings = [
    'allow_user_registration' => 1,
    'require_email_verification' => 1,
    'maintenance_mode' => 0,
    'default_user_role' => ROLE_MEMBER,
    'session_timeout' => 120, // minutes
    'max_login_attempts' => 5,
    'password_expiry_days' => 90
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_notification_settings'])) {
        // Process notification settings update
        $email_notifications = isset($_POST['email_notifications']) && $_POST['email_notifications'] == '1' ? 1 : 0;
        $event_reminders = isset($_POST['event_reminders']) && $_POST['event_reminders'] == '1' ? 1 : 0;
        $task_notifications = isset($_POST['task_notifications']) && $_POST['task_notifications'] == '1' ? 1 : 0;
        $department_updates = isset($_POST['department_updates']) && $_POST['department_updates'] == '1' ? 1 : 0;
        $system_alerts = isset($_POST['system_alerts']) && $_POST['system_alerts'] == '1' ? 1 : 0;
        $security_notifications = isset($_POST['security_notifications']) && $_POST['security_notifications'] == '1' ? 1 : 0;
        $user_activity_reports = isset($_POST['user_activity_reports']) && $_POST['user_activity_reports'] == '1' ? 1 : 0;
        
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
                    `system_alerts` tinyint(1) DEFAULT 1,
                    `security_notifications` tinyint(1) DEFAULT 1,
                    `user_activity_reports` tinyint(1) DEFAULT 1,
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
                    
                    // Check and add admin-specific columns if they don't exist
                    $columnsToAdd = [
                        'system_alerts' => 'tinyint(1) DEFAULT 1',
                        'security_notifications' => 'tinyint(1) DEFAULT 1',
                        'user_activity_reports' => 'tinyint(1) DEFAULT 1'
                    ];
                    
                    foreach ($columnsToAdd as $column => $definition) {
                        if (!in_array($column, $columns)) {
                            $conn->exec("ALTER TABLE user_settings ADD COLUMN $column $definition");
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Error checking/adding columns: " . $e->getMessage());
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
                
                $columnMappings = [
                    'email_notifications' => $email_notifications,
                    'event_reminders' => $event_reminders,
                    'task_notifications' => $task_notifications,
                    'department_updates' => $department_updates,
                    'system_alerts' => $system_alerts,
                    'security_notifications' => $security_notifications,
                    'user_activity_reports' => $user_activity_reports
                ];
                
                foreach ($columnMappings as $column => $value) {
                    if (in_array($column, $columns)) {
                        $updateQuery .= ", $column = ?";
                        $params[] = $value;
                    }
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
                
                $columnMappings = [
                    'email_notifications' => $email_notifications,
                    'event_reminders' => $event_reminders,
                    'task_notifications' => $task_notifications,
                    'department_updates' => $department_updates,
                    'system_alerts' => $system_alerts,
                    'security_notifications' => $security_notifications,
                    'user_activity_reports' => $user_activity_reports
                ];
                
                foreach ($columnMappings as $column => $value) {
                    if (in_array($column, $columns)) {
                        $columnNames[] = $column;
                        $placeholders[] = '?';
                        $params[] = $value;
                    }
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
                $settings['system_alerts'] = $system_alerts;
                $settings['security_notifications'] = $security_notifications;
                $settings['user_activity_reports'] = $user_activity_reports;
            } else {
                $error_message = 'Failed to update notification settings.';
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error_message = 'An error occurred while updating settings: ' . $e->getMessage();
        }
    } elseif (isset($_POST['update_system_settings'])) {
        // Process system settings update
        $allow_user_registration = isset($_POST['allow_user_registration']) && $_POST['allow_user_registration'] == '1' ? 1 : 0;
        $require_email_verification = isset($_POST['require_email_verification']) && $_POST['require_email_verification'] == '1' ? 1 : 0;
        $maintenance_mode = isset($_POST['maintenance_mode']) && $_POST['maintenance_mode'] == '1' ? 1 : 0;
        $default_user_role = isset($_POST['default_user_role']) ? intval($_POST['default_user_role']) : ROLE_MEMBER;
        $session_timeout = isset($_POST['session_timeout']) ? intval($_POST['session_timeout']) : 120;
        $max_login_attempts = isset($_POST['max_login_attempts']) ? intval($_POST['max_login_attempts']) : 5;
        $password_expiry_days = isset($_POST['password_expiry_days']) ? intval($_POST['password_expiry_days']) : 90;
        
        try {
            // Check if system_settings table exists
            $tableExists = false;
            try {
                $checkTable = $conn->query("SHOW TABLES LIKE 'system_settings'");
                $tableExists = ($checkTable->rowCount() > 0);
            } catch (PDOException $e) {
                $tableExists = false;
            }
            
            // Create the table if it doesn't exist
            if (!$tableExists) {
                $createTableSQL = "CREATE TABLE IF NOT EXISTS `system_settings` (
                    `setting_id` int(11) NOT NULL AUTO_INCREMENT,
                    `setting_key` varchar(50) NOT NULL,
                    `setting_value` varchar(255) NOT NULL,
                    `created_at` timestamp NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`setting_id`),
                    UNIQUE KEY `setting_key` (`setting_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
                
                $conn->exec($createTableSQL);
            }
            
            // Update or insert system settings
            $settingsToUpdate = [
                'allow_user_registration' => $allow_user_registration,
                'require_email_verification' => $require_email_verification,
                'maintenance_mode' => $maintenance_mode,
                'default_user_role' => $default_user_role,
                'session_timeout' => $session_timeout,
                'max_login_attempts' => $max_login_attempts,
                'password_expiry_days' => $password_expiry_days
            ];
            
            foreach ($settingsToUpdate as $key => $value) {
                // Check if setting exists
                $query = "SELECT * FROM system_settings WHERE setting_key = ?";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(1, $key, PDO::PARAM_STR);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    // Update existing setting
                    $updateQuery = "UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bindParam(1, $value, PDO::PARAM_STR);
                    $stmt->bindParam(2, $key, PDO::PARAM_STR);
                } else {
                    // Insert new setting
                    $insertQuery = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)";
                    $stmt = $conn->prepare($insertQuery);
                    $stmt->bindParam(1, $key, PDO::PARAM_STR);
                    $stmt->bindParam(2, $value, PDO::PARAM_STR);
                }
                
                $stmt->execute();
            }
            
            $success_message = 'System settings updated successfully.';
            
            // Update the system_settings variable with new values
            $system_settings['allow_user_registration'] = $allow_user_registration;
            $system_settings['require_email_verification'] = $require_email_verification;
            $system_settings['maintenance_mode'] = $maintenance_mode;
            $system_settings['default_user_role'] = $default_user_role;
            $system_settings['session_timeout'] = $session_timeout;
            $system_settings['max_login_attempts'] = $max_login_attempts;
            $system_settings['password_expiry_days'] = $password_expiry_days;
            
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error_message = 'An error occurred while updating system settings: ' . $e->getMessage();
        }
    } elseif (isset($_POST['clear_system_cache'])) {
        // Simulate clearing system cache
        sleep(1); // Simulate processing time
        $success_message = 'System cache cleared successfully.';
    }
}

// Get current user settings
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
              foreach ($userSettings as $key => $value) {
                  if (isset($settings[$key])) {
                      $settings[$key] = $value;
                  }
              }
          }
      }
      
      // Get system settings
      $tableExists = false;
      try {
          $checkTable = $conn->query("SHOW TABLES LIKE 'system_settings'");
          $tableExists = ($checkTable->rowCount() > 0);
      } catch (PDOException $e) {
          $tableExists = false;
      }
      
      if ($tableExists) {
          $query = "SELECT setting_key, setting_value FROM system_settings";
          $stmt = $conn->prepare($query);
          $stmt->execute();
          $systemSettingsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
          
          foreach ($systemSettingsRows as $row) {
              if (isset($system_settings[$row['setting_key']])) {
                  $system_settings[$row['setting_key']] = $row['setting_value'];
              }
          }
      }
  } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      $error_message = 'An error occurred while fetching settings: ' . $e->getMessage();
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

// Get all roles for dropdown
$roles = [];
try {
  $query = "SELECT role_id, name FROM roles ORDER BY role_id";
  $stmt = $conn->prepare($query);
  $stmt->execute();
  $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("Database error: " . $e->getMessage());
}

// Get system statistics
$systemStats = [
  'total_users' => 0,
  'total_events' => 0,
  'total_tasks' => 0,
  'total_departments' => 0,
  'disk_usage' => '0 MB',
  'database_size' => '0 MB',
  'php_version' => phpversion(),
  'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
];

try {
  // Get total users
  $query = "SELECT COUNT(*) as count FROM users";
  $stmt = $conn->prepare($query);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $systemStats['total_users'] = $result['count'];
  
  // Get total events
  $query = "SELECT COUNT(*) as count FROM events";
  $stmt = $conn->prepare($query);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $systemStats['total_events'] = $result['count'];
  
  // Get total tasks
  $query = "SELECT COUNT(*) as count FROM tasks";
  $stmt = $conn->prepare($query);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $systemStats['total_tasks'] = $result['count'];
  
  // Get total departments
  $query = "SELECT COUNT(*) as count FROM departments";
  $stmt = $conn->prepare($query);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $systemStats['total_departments'] = $result['count'];
  
  // Simulate disk usage and database size
  $systemStats['disk_usage'] = rand(50, 500) . ' MB';
  $systemStats['database_size'] = rand(10, 100) . ' MB';
} catch (PDOException $e) {
  error_log("Database error: " . $e->getMessage());
}
?>

<div class="mb-6">
  <h1 class="text-2xl font-normal mb-1" style="color: var(--color-text-primary);">System Settings</h1>
  <p class="text-sm" style="color: var(--color-text-secondary);">
      Manage application settings and preferences
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- Left Column: Personal Settings -->
  <div class="lg:col-span-1">
      <!-- Personal Notification Settings -->
      <div class="google-card p-5 mb-6">
          <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Personal Notification Settings</h2>
          
          <form method="post" action="" id="personalSettingsForm">
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
                  
                  <!-- Admin-specific notification settings -->
                  <div class="flex items-center justify-between">
                      <div>
                          <h3 class="text-md font-medium" style="color: var(--color-text-primary);">System Alerts</h3>
                          <p class="text-sm" style="color: var(--color-text-secondary);">Receive notifications about system events and errors</p>
                      </div>
                      <div class="toggle-wrapper">
                          <div class="toggle-switch" onclick="toggleSetting('system_alerts')" title="Toggle system alerts">
                              <div class="toggle-track" id="track_system_alerts" data-active="<?php echo isset($settings['system_alerts']) && $settings['system_alerts'] ? 'true' : 'false'; ?>">
                                  <div class="toggle-thumb"></div>
                              </div>
                          </div>
                          <input type="hidden" name="system_alerts" id="system_alerts" value="<?php echo isset($settings['system_alerts']) && $settings['system_alerts'] ? '1' : '0'; ?>">
                      </div>
                  </div>
                  
                  <div class="flex items-center justify-between">
                      <div>
                          <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Security Notifications</h3>
                          <p class="text-sm" style="color: var(--color-text-secondary);">Receive alerts about security events (login attempts, etc.)</p>
                      </div>
                      <div class="toggle-wrapper">
                          <div class="toggle-switch" onclick="toggleSetting('security_notifications')" title="Toggle security notifications">
                              <div class="toggle-track" id="track_security_notifications" data-active="<?php echo isset($settings['security_notifications']) && $settings['security_notifications'] ? 'true' : 'false'; ?>">
                                  <div class="toggle-thumb"></div>
                              </div>
                          </div>
                          <input type="hidden" name="security_notifications" id="security_notifications" value="<?php echo isset($settings['security_notifications']) && $settings['security_notifications'] ? '1' : '0'; ?>">
                      </div>
                  </div>
                  
                  <div class="flex items-center justify-between">
                      <div>
                          <h3 class="text-md font-medium" style="color: var(--color-text-primary);">User Activity Reports</h3>
                          <p class="text-sm" style="color: var(--color-text-secondary);">Receive periodic reports on user activity</p>
                      </div>
                      <div class="toggle-wrapper">
                          <div class="toggle-switch" onclick="toggleSetting('user_activity_reports')" title="Toggle user activity reports">
                              <div class="toggle-track" id="track_user_activity_reports" data-active="<?php echo isset($settings['user_activity_reports']) && $settings['user_activity_reports'] ? 'true' : 'false'; ?>">
                                  <div class="toggle-thumb"></div>
                              </div>
                          </div>
                          <input type="hidden" name="user_activity_reports" id="user_activity_reports" value="<?php echo isset($settings['user_activity_reports']) && $settings['user_activity_reports'] ? '1' : '0'; ?>">
                      </div>
                  </div>
                  
                  <div class="mt-6">
                      <button type="submit" name="update_notification_settings" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                          Save Notification Settings
                      </button>
                  </div>
              </div>
          </form>
      </div>
      
      <!-- Display Preferences -->
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
                
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-md font-medium" style="color: var(--color-text-primary);">High Contrast Mode</h3>
                        <p class="text-sm" style="color: var(--color-text-secondary);">Increase contrast for better visibility</p>
                    </div>
                    <div class="toggle-wrapper">
                        <div class="toggle-switch" onclick="toggleHighContrast()" title="Toggle high contrast mode">
                            <div class="toggle-track" id="track_high_contrast" data-active="false">
                                <div class="toggle-thumb"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="google-card p-5">
            <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">System Information</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm" style="color: var(--color-text-secondary);">Total Users:</span>
                    <span class="text-sm font-medium" style="color: var(--color-text-primary);"><?php echo $systemStats['total_users']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm" style="color: var(--color-text-secondary);">Total Events:</span>
                    <span class="text-sm font-medium" style="color: var(--color-text-primary);"><?php echo $systemStats['total_events']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm" style="color: var(--color-text-secondary);">Total Tasks:</span>
                    <span class="text-sm font-medium" style="color: var(--color-text-primary);"><?php echo $systemStats['total_tasks']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm" style="color: var(--color-text-secondary);">Total Departments:</span>
                    <span class="text-sm font-medium" style="color: var(--color-text-primary);"><?php echo $systemStats['total_departments']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm" style="color: var(--color-text-secondary);">Disk Usage:</span>
                    <span class="text-sm font-medium" style="color: var(--color-text-primary);"><?php echo $systemStats['disk_usage']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm" style="color: var(--color-text-secondary);">Database Size:</span>
                    <span class="text-sm font-medium" style="color: var(--color-text-primary);"><?php echo $systemStats['database_size']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm" style="color: var(--color-text-secondary);">PHP Version:</span>
                    <span class="text-sm font-medium" style="color: var(--color-text-primary);"><?php echo $systemStats['php_version']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm" style="color: var(--color-text-secondary);">Server Software:</span>
                    <span class="text-sm font-medium" style="color: var(--color-text-primary);"><?php echo $systemStats['server_software']; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column: System Settings -->
    <div class="lg:col-span-2">
        <!-- System Configuration -->
        <div class="google-card p-5 mb-6">
            <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">System Configuration</h2>
            
            <form method="post" action="" id="systemSettingsForm">
                <div class="space-y-6">
                    <!-- User Registration Settings -->
                    <div>
                        <h3 class="text-md font-medium mb-3" style="color: var(--color-text-primary);">User Registration</h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm" style="color: var(--color-text-primary);">Allow User Registration</p>
                                    <p class="text-xs" style="color: var(--color-text-secondary);">Enable users to register accounts</p>
                                </div>
                                <div class="toggle-wrapper">
                                    <div class="toggle-switch" onclick="toggleSystemSetting('allow_user_registration')" title="Toggle user registration">
                                        <div class="toggle-track" id="track_allow_user_registration" data-active="<?php echo isset($system_settings['allow_user_registration']) && $system_settings['allow_user_registration'] ? 'true' : 'false'; ?>">
                                            <div class="toggle-thumb"></div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="allow_user_registration" id="allow_user_registration" value="<?php echo isset($system_settings['allow_user_registration']) && $system_settings['allow_user_registration'] ? '1' : '0'; ?>">
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm" style="color: var(--color-text-primary);">Require Email Verification</p>
                                    <p class="text-xs" style="color: var(--color-text-secondary);">Users must verify email before accessing the system</p>
                                </div>
                                <div class="toggle-wrapper">
                                    <div class="toggle-switch" onclick="toggleSystemSetting('require_email_verification')" title="Toggle email verification">
                                        <div class="toggle-track" id="track_require_email_verification" data-active="<?php echo isset($system_settings['require_email_verification']) && $system_settings['require_email_verification'] ? 'true' : 'false'; ?>">
                                            <div class="toggle-thumb"></div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="require_email_verification" id="require_email_verification" value="<?php echo isset($system_settings['require_email_verification']) && $system_settings['require_email_verification'] ? '1' : '0'; ?>">
                                </div>
                            </div>
                            
                            <div>
                                <label for="default_user_role" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Default User Role</label>
                                <select id="default_user_role" name="default_user_role" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                                    <?php foreach ($roles as $role): ?>
                                        <?php if ($role['role_id'] != ROLE_ADMIN): // Don't allow admin as default ?>
                                            <option value="<?php echo $role['role_id']; ?>" <?php echo $system_settings['default_user_role'] == $role['role_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($role['name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs mt-1" style="color: var(--color-text-secondary);">Role assigned to new users upon registration</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Settings -->
                    <div>
                        <h3 class="text-md font-medium mb-3" style="color: var(--color-text-primary);">Security Settings</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="session_timeout" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Session Timeout (minutes)</label>
                                <input type="number" id="session_timeout" name="session_timeout" min="5" max="1440" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo $system_settings['session_timeout']; ?>">
                                <p class="text-xs mt-1" style="color: var(--color-text-secondary);">Time before inactive users are automatically logged out</p>
                            </div>
                            
                            <div>
                                <label for="max_login_attempts" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Max Login Attempts</label>
                                <input type="number" id="max_login_attempts" name="max_login_attempts" min="1" max="10" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo $system_settings['max_login_attempts']; ?>">
                                <p class="text-xs mt-1" style="color: var(--color-text-secondary);">Number of failed login attempts before account lockout</p>
                            </div>
                            
                            <div>
                                <label for="password_expiry_days" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Password Expiry (days)</label>
                                <input type="number" id="password_expiry_days" name="password_expiry_days" min="0" max="365" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo $system_settings['password_expiry_days']; ?>">
                                <p class="text-xs mt-1" style="color: var(--color-text-secondary);">Days before users are required to change password (0 = never)</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Maintenance Settings -->
                    <div>
                        <h3 class="text-md font-medium mb-3" style="color: var(--color-text-primary);">Maintenance</h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm" style="color: var(--color-text-primary);">Maintenance Mode</p>
                                    <p class="text-xs" style="color: var(--color-text-secondary);">Put the system in maintenance mode (only admins can access)</p>
                                </div>
                                <div class="toggle-wrapper">
                                    <div class="toggle-switch" onclick="toggleSystemSetting('maintenance_mode')" title="Toggle maintenance mode">
                                        <div class="toggle-track" id="track_maintenance_mode" data-active="<?php echo isset($system_settings['maintenance_mode']) && $system_settings['maintenance_mode'] ? 'true' : 'false'; ?>">
                                            <div class="toggle-thumb"></div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="maintenance_mode" id="maintenance_mode" value="<?php echo isset($system_settings['maintenance_mode']) && $system_settings['maintenance_mode'] ? '1' : '0'; ?>">
                                </div>
                            </div>
                            
                            <div>
                                <button type="button" name="clear_system_cache" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium" onclick="clearSystemCache()">
                                    Clear System Cache
                                </button>
                                <p class="text-xs mt-1" style="color: var(--color-text-secondary);">Clear cached data to resolve potential issues</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <button type="submit" name="update_system_settings" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                            Save System Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Backup & Restore -->
        <div class="google-card p-5 mb-6">
            <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Backup & Restore</h2>
            
            <div class="space-y-6">
                <div>
                    <h3 class="text-md font-medium mb-3" style="color: var(--color-text-primary);">Database Backup</h3>
                    <p class="text-sm mb-3" style="color: var(--color-text-secondary);">Create a backup of the entire database</p>
                    
                    <div class="flex space-x-3">
                        <button type="button" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium" onclick="createBackup()">
                            Create Backup
                        </button>
                        <button type="button" class="btn-outline py-2 px-4 rounded-md text-sm font-medium" onclick="scheduleBackup()">
                            Schedule Backup
                        </button>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-md font-medium mb-3" style="color: var(--color-text-primary);">Restore Database</h3>
                    <p class="text-sm mb-3" style="color: var(--color-text-secondary);">Restore the database from a previous backup</p>
                    
                    <div class="mb-3">
                        <label for="backup_file" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Select Backup File</label>
                        
                        <div class="relative">
                            <input type="file" id="backup_file" name="backup_file" class="hidden" accept=".sql,.gz">
                            <label for="backup_file" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium w-full flex items-center justify-center cursor-pointer">
                                <i class="fas fa-upload mr-2"></i>
                                <span>Choose Backup File</span>
                            </label>
                        </div>
                        <p id="selected-backup-file" class="text-xs mt-1" style="color: var(--color-text-tertiary);">No file selected</p>
                    </div>
                    
                    <button type="button" class="btn-danger py-2 px-4 rounded-md text-sm font-medium" onclick="confirmRestore()">
                        Restore Database
                    </button>
                </div>
                
                <div>
                    <h3 class="text-md font-medium mb-3" style="color: var(--color-text-primary);">Previous Backups</h3>
                    
                    <div class="border rounded-md" style="border-color: var(--color-border-light);">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y" style="border-color: var(--color-border-light);">
                                <thead>
                                    <tr style="background-color: var(--color-surface-variant);">
                                        <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Backup Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Size</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Created By</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium" style="color: var(--color-text-secondary);">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" style="border-color: var(--color-border-light);">
                                    <tr>
                                        <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">2023-06-15 09:30:45</td>
                                        <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">4.2 MB</td>
                                        <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">System (Automated)</td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <button type="button" class="text-blue-600 hover:text-blue-800 mr-2" title="Download backup">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button type="button" class="text-red-600 hover:text-red-800" title="Delete backup">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">2023-06-01 14:15:22</td>
                                        <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">3.8 MB</td>
                                        <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">Admin User</td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <button type="button" class="text-blue-600 hover:text-blue-800 mr-2" title="Download backup">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button type="button" class="text-red-600 hover:text-red-800" title="Delete backup">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Logs -->
        <div class="google-card p-5">
            <h2 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">System Logs</h2>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-md font-medium" style="color: var(--color-text-primary);">Recent Activity Logs</h3>
                    <a href="?page=lead_logs" class="text-sm font-medium" style="color: var(--color-primary);">View All Logs</a>
                </div>
                
                <div class="border rounded-md" style="border-color: var(--color-border-light);">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y" style="border-color: var(--color-border-light);">
                            <thead>
                                <tr style="background-color: var(--color-surface-variant);">
                                    <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Timestamp</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">User</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Action</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">IP Address</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: var(--color-border-light);">
                                <tr>
                                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">2023-06-15 10:45:12</td>
                                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">admin@example.com</td>
                                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">User login</td>
                                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">192.168.1.1</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">2023-06-15 09:30:45</td>
                                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">john.doe@example.com</td>
                                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">Created event</td>
                                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">192.168.1.2</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">2023-06-15 08:15:33</td>
                                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">System</td>
                                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">Backup created</td>
                                    <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);">127.0.0.1</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium" onclick="downloadLogs()">
                        Download Logs
                    </button>
                    <button type="button" class="btn-outline py-2 px-4 rounded-md text-sm font-medium" onclick="clearLogs()">
                        Clear Logs
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div id="restoreModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-full" style="background-color: var(--color-surface); color: var(--color-text-primary);">
        <h3 class="text-lg font-medium mb-4">Confirm Database Restore</h3>
        <p class="mb-4" style="color: var(--color-text-secondary);">
            Are you sure you want to restore the database? This will replace all current data with the data from the backup file.
        </p>
        <div class="flex justify-end space-x-3">
            <button type="button" class="px-4 py-2 border rounded-md text-sm font-medium" style="border-color: var(--color-border-light); color: var(--color-text-primary);" onclick="hideRestoreModal()">
                Cancel
            </button>
            <button type="button" class="px-4 py-2 bg-red-500 text-white rounded-md text-sm font-medium hover:bg-red-600" onclick="restoreDatabase()">
                Restore Database
            </button>
        </div>
    </div>
</div>

<!-- Backup Schedule Modal -->
<div id="backupScheduleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-full" style="background-color: var(--color-surface); color: var(--color-text-primary);">
        <h3 class="text-lg font-medium mb-4">Schedule Automated Backups</h3>
        
        <div class="space-y-4 mb-4">
            <div>
                <label for="backup_frequency" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Backup Frequency</label>
                <select id="backup_frequency" name="backup_frequency" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>
            
            <div>
                <label for="backup_time" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Backup Time</label>
                <input type="time" id="backup_time" name="backup_time" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="03:00">
                <p class="text-xs mt-1" style="color: var(--color-text-tertiary);">Server time (24-hour format)</p>
            </div>
            
            <div>
                <label for="backup_retention" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Retention Period (days)</label>
                <input type="number" id="backup_retention" name="backup_retention" min="1" max="365" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="30">
                <p class="text-xs mt-1" style="color: var(--color-text-tertiary);">Number of days to keep backups before automatic deletion</p>
            </div>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button type="button" class="px-4 py-2 border rounded-md text-sm font-medium" style="border-color: var(--color-border-light); color: var(--color-text-primary);" onclick="hideBackupScheduleModal()">
                Cancel
            </button>
            <button type="button" class="px-4 py-2 bg-blue-500 text-white rounded-md text-sm font-medium hover:bg-blue-600" onclick="saveBackupSchedule()">
                Save Schedule
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
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.toggle-track[data-active="true"] .toggle-thumb {
    transform: translateX(20px);
}

/* Button Styles */
.btn-primary {
    background-color: var(--color-primary);
    color: white;
    transition: background-color 0.2s;
}

.btn-primary:hover {
    background-color: var(--color-primary-dark);
}

.btn-secondary {
    background-color: var(--color-surface-variant);
    color: var(--color-text-primary);
    transition: background-color 0.2s;
}

.btn-secondary:hover {
    background-color: var(--color-hover);
}

.btn-outline {
    border: 1px solid var(--color-border);
    color: var(--color-text-primary);
    transition: background-color 0.2s;
}

.btn-outline:hover {
    background-color: var(--color-hover);
}

.btn-danger {
    background-color: #ef4444;
    color: white;
    transition: background-color 0.2s;
}

.btn-danger:hover {
    background-color: #dc2626;
}
</style>

<script>
// Toggle personal notification settings
function toggleSetting(settingId) {
    const track = document.getElementById('track_' + settingId);
    const input = document.getElementById(settingId);
    
    const isActive = track.getAttribute('data-active') === 'true';
    track.setAttribute('data-active', !isActive);
    input.value = !isActive ? '1' : '0';
}

// Toggle system settings
function toggleSystemSetting(settingId) {
    const track = document.getElementById('track_' + settingId);
    const input = document.getElementById(settingId);
    
    const isActive = track.getAttribute('data-active') === 'true';
    track.setAttribute('data-active', !isActive);
    input.value = !isActive ? '1' : '0';
}

// Toggle dark mode
function toggleCompactView() {
    const track = document.getElementById('track_compact_view');
    const isActive = track.getAttribute('data-active') === 'true';
    track.setAttribute('data-active', !isActive);
    
    // Apply compact view
    document.body.classList.toggle('compact-view', !isActive);
    
    // Save preference to localStorage
    localStorage.setItem('compactView', !isActive ? 'true' : 'false');
}

// Toggle high contrast mode
function toggleHighContrast() {
    const track = document.getElementById('track_high_contrast');
    const isActive = track.getAttribute('data-active') === 'true';
    track.setAttribute('data-active', !isActive);
    
    // Apply high contrast mode
    document.documentElement.classList.toggle('high-contrast', !isActive);
    
    // Save preference to localStorage
    localStorage.setItem('highContrast', !isActive ? 'true' : 'false');
}

// Clear system cache
function clearSystemCache() {
    // Show loading indicator
    const button = document.querySelector('button[name="clear_system_cache"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Clearing...';
    button.disabled = true;
    
    // Simulate cache clearing with AJAX request
    setTimeout(() => {
        fetch('?page=lead_settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'clear_system_cache=1'
        })
        .then(response => response.text())
        .then(html => {
            // Show success message
            const successMessage = document.createElement('div');
            successMessage.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4';
            successMessage.innerHTML = '<span class="block sm:inline">System cache cleared successfully.</span>';
            
            const settingsForm = document.getElementById('systemSettingsForm');
            settingsForm.parentNode.insertBefore(successMessage, settingsForm);
            
            // Reset button
            button.innerHTML = originalText;
            button.disabled = false;
            
            // Remove success message after 3 seconds
            setTimeout(() => {
                successMessage.remove();
            }, 3000);
        })
        .catch(error => {
            console.error('Error:', error);
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }, 1000);
}

// Create database backup
function createBackup() {
    // Show loading indicator
    const button = document.querySelector('button[onclick="createBackup()"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creating Backup...';
    button.disabled = true;
    
    // Simulate backup creation with AJAX request
    setTimeout(() => {
        // Reset button
        button.innerHTML = originalText;
        button.disabled = false;
        
        // Show success message
        const successMessage = document.createElement('div');
        successMessage.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4';
        successMessage.innerHTML = '<span class="block sm:inline">Database backup created successfully.</span>';
        
        const backupSection = document.querySelector('.google-card:nth-of-type(3)');
        backupSection.insertBefore(successMessage, backupSection.firstChild);
        
        // Remove success message after 3 seconds
        setTimeout(() => {
            successMessage.remove();
        }, 3000);
        
        // Refresh the backup list (in a real implementation)
        // This would be replaced with actual code to update the table
    }, 2000);
}

// Schedule database backup
function scheduleBackup() {
    // Show the backup schedule modal
    document.getElementById('backupScheduleModal').classList.remove('hidden');
}

// Hide backup schedule modal
function hideBackupScheduleModal() {
    document.getElementById('backupScheduleModal').classList.add('hidden');
}

// Save backup schedule
function saveBackupSchedule() {
    const frequency = document.getElementById('backup_frequency').value;
    const time = document.getElementById('backup_time').value;
    const retention = document.getElementById('backup_retention').value;
    
    // In a real implementation, this would send the data to the server
    console.log('Backup Schedule:', { frequency, time, retention });
    
    // Hide the modal
    hideBackupScheduleModal();
    
    // Show success message
    const successMessage = document.createElement('div');
    successMessage.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4';
    successMessage.innerHTML = '<span class="block sm:inline">Backup schedule saved successfully.</span>';
    
    const backupSection = document.querySelector('.google-card:nth-of-type(3)');
    backupSection.insertBefore(successMessage, backupSection.firstChild);
    
    // Remove success message after 3 seconds
    setTimeout(() => {
        successMessage.remove();
    }, 3000);
}

// Confirm database restore
function confirmRestore() {
    // Check if a file has been selected
    const fileInput = document.getElementById('backup_file');
    if (fileInput.files.length === 0) {
        alert('Please select a backup file first.');
        return;
    }
    
    // Show the restore confirmation modal
    document.getElementById('restoreModal').classList.remove('hidden');
}

// Hide restore confirmation modal
function hideRestoreModal() {
    document.getElementById('restoreModal').classList.add('hidden');
}

// Restore database
function restoreDatabase() {
    // Hide the modal
    hideRestoreModal();
    
    // Show loading indicator
    const loadingMessage = document.createElement('div');
    loadingMessage.className = 'bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4';
    loadingMessage.innerHTML = '<span class="block sm:inline"><i class="fas fa-spinner fa-spin mr-2"></i> Restoring database... This may take a few minutes.</span>';
    
    const backupSection = document.querySelector('.google-card:nth-of-type(3)');
    backupSection.insertBefore(loadingMessage, backupSection.firstChild);
    
    // Simulate database restore
    setTimeout(() => {
        // Remove loading message
        loadingMessage.remove();
        
        // Show success message
        const successMessage = document.createElement('div');
        successMessage.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4';
        successMessage.innerHTML = '<span class="block sm:inline">Database restored successfully.</span>';
        
        backupSection.insertBefore(successMessage, backupSection.firstChild);
        
        // Remove success message after 5 seconds
        setTimeout(() => {
            successMessage.remove();
        }, 5000);
    }, 3000);
}

// Download logs
function downloadLogs() {
    // Simulate log download
    const link = document.createElement('a');
    link.href = '#'; // In a real implementation, this would be a URL to download the logs
    link.download = 'system_logs_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
}

// Clear logs
function clearLogs() {
    if (confirm('Are you sure you want to clear all system logs? This action cannot be undone.')) {
        // Show loading indicator
        const button = document.querySelector('button[onclick="clearLogs()"]');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Clearing...';
        button.disabled = true;
        
        // Simulate logs clearing
        setTimeout(() => {
            // Reset button
            button.innerHTML = originalText;
            button.disabled = false;
            
            // Show success message
            const successMessage = document.createElement('div');
            successMessage.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4';
            successMessage.innerHTML = '<span class="block sm:inline">System logs cleared successfully.</span>';
            
            const logsSection = document.querySelector('.google-card:nth-of-type(4)');
            logsSection.insertBefore(successMessage, logsSection.firstChild);
            
            // Remove success message after 3 seconds
            setTimeout(() => {
                successMessage.remove();
            }, 3000);
        }, 1500);
    }
}

// Update file name display for backup file
document.getElementById('backup_file').addEventListener('change', function(e) {
    const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
    document.getElementById('selected-backup-file').textContent = fileName;
});

// Initialize dark mode toggle based on current theme
document.addEventListener('DOMContentLoaded', function() {
    // Set dark mode toggle based on current theme
    const darkModeTrack = document.getElementById('track_dark_mode');
    const currentTheme = document.documentElement.getAttribute('data-theme');
    darkModeTrack.setAttribute('data-active', currentTheme === 'dark' ? 'true' : 'false');
    
    // Initialize compact view toggle
    const compactView = localStorage.getItem('compactView') === 'true';
    const compactViewTrack = document.getElementById('track_compact_view');
    compactViewTrack.setAttribute('data-active', compactView ? 'true' : 'false');
    if (compactView) {
        document.body.classList.add('compact-view');
    }
    
    // Initialize high contrast toggle
    const highContrast = localStorage.getItem('highContrast') === 'true';
    const highContrastTrack = document.getElementById('track_high_contrast');
    highContrastTrack.setAttribute('data-active', highContrast ? 'true' : 'false');
    if (highContrast) {
        document.documentElement.classList.add('high-contrast');
    }
});
</script>





