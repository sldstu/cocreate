<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get current user's profile
$user_id = $_SESSION['user_id'];

// Handle profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Process profile update
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $username = trim($_POST['username'] ?? ''); // Added username field
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        // Validate input
        if (empty($first_name) || empty($last_name) || empty($email) || empty($username)) {
            $error_message = 'First name, last name, username, and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            try {
                // Check if email already exists for another user
                $query = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(1, $email, PDO::PARAM_STR);
                $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error_message = 'Email address is already in use by another account.';
                } else {
                    // Check if username already exists for another user
                    $query = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(1, $username, PDO::PARAM_STR);
                    $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $error_message = 'Username is already in use by another account.';
                    } else {
                        // Update profile
                        $query = "UPDATE users SET 
                                  first_name = ?, 
                                  last_name = ?, 
                                  username = ?,
                                  email = ?, 
                                  phone = ?, 
                                  bio = ?, 
                                  updated_at = NOW() 
                                  WHERE user_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(1, $first_name, PDO::PARAM_STR);
                        $stmt->bindParam(2, $last_name, PDO::PARAM_STR);
                        $stmt->bindParam(3, $username, PDO::PARAM_STR);
                        $stmt->bindParam(4, $email, PDO::PARAM_STR);
                        $stmt->bindParam(5, $phone, PDO::PARAM_STR);
                        $stmt->bindParam(6, $bio, PDO::PARAM_STR);
                        $stmt->bindParam(7, $user_id, PDO::PARAM_INT);
                        
                        if ($stmt->execute()) {
                            $success_message = 'Profile updated successfully.';
                            
                            // Update session data
                            $_SESSION['user']['email'] = $email;
                            $_SESSION['user']['username'] = $username;
                        } else {
                            $error_message = 'Failed to update profile.';
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error_message = 'An error occurred while updating profile.';
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Process password change
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
          $error_message = 'All password fields are required.';
      } elseif ($new_password !== $confirm_password) {
          $error_message = 'New passwords do not match.';
      } elseif (strlen($new_password) < 6) {
          $error_message = 'New password must be at least 6 characters long.';
      } else {
          try {
              // Verify current password
              $query = "SELECT password FROM users WHERE user_id = ?";
              $stmt = $conn->prepare($query);
              $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
              $stmt->execute();
              $user = $stmt->fetch(PDO::FETCH_ASSOC);
              
              if (!$user || !password_verify($current_password, $user['password'])) {
                  $error_message = 'Current password is incorrect.';
              } else {
                  // Update password
                  $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                  $query = "UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?";
                  $stmt = $conn->prepare($query);
                  $stmt->bindParam(1, $hashed_password, PDO::PARAM_STR);
                  $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
                  
                  if ($stmt->execute()) {
                      $success_message = 'Password changed successfully.';
                  } else {
                      $error_message = 'Failed to change password.';
                  }
              }
          } catch (PDOException $e) {
              error_log("Database error: " . $e->getMessage());
              $error_message = 'An error occurred while changing password.';
          }
      }
  } elseif (isset($_POST['update_profile_image'])) {
      // Process profile image update
      if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
          $file = $_FILES['profile_image'];
          $fileName = $file['name'];
          $fileTmpName = $file['tmp_name'];
          $fileSize = $file['size'];
          $fileError = $file['error'];
          
          // Get file extension
          $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
          
          // Allowed extensions
          $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
          
          if (in_array($fileExt, $allowedExts)) {
              if ($fileError === 0) {
                  if ($fileSize < 5000000) { // 5MB max
                      // Create unique filename
                      $fileNameNew = "user_" . $user_id . "_" . uniqid('', true) . "." . $fileExt;
                      $fileDestination = 'uploads/profile_images/' . $fileNameNew;
                      
                      // Create directory if it doesn't exist
                      if (!file_exists('uploads/profile_images/')) {
                          mkdir('uploads/profile_images/', 0777, true);
                      }
                      
                      if (move_uploaded_file($fileTmpName, $fileDestination)) {
                          try {
                              // Update profile image in database
                              $query = "UPDATE users SET profile_image = ?, updated_at = NOW() WHERE user_id = ?";
                              $stmt = $conn->prepare($query);
                              $stmt->bindParam(1, $fileDestination, PDO::PARAM_STR);
                              $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
                              
                              if ($stmt->execute()) {
                                  $success_message = 'Profile image updated successfully.';
                              } else {
                                  $error_message = 'Failed to update profile image in database.';
                              }
                          } catch (PDOException $e) {
                              error_log("Database error: " . $e->getMessage());
                              $error_message = 'An error occurred while updating profile image.';
                          }
                      } else {
                          $error_message = 'Failed to upload profile image.';
                      }
                  } else {
                      $error_message = 'File size is too large. Maximum size is 5MB.';
                  }
              } else {
                  $error_message = 'There was an error uploading your file.';
              }
          } else {
              $error_message = 'Invalid file type. Allowed types: JPG, JPEG, PNG, GIF.';
          }
      } else {
          $error_message = 'No file uploaded or an error occurred.';
      }
  }
}

// Get user profile data
try {
  $query = "SELECT u.*, d.name as department_name, r.name as role_name 
            FROM users u 
            LEFT JOIN departments d ON u.department_id = d.department_id 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            WHERE u.user_id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $profile = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$profile) {
      $error_message = 'User profile not found.';
  }
} catch (PDOException $e) {
  error_log("Database error: " . $e->getMessage());
  $error_message = 'An error occurred while fetching profile data.';
  $profile = [];
}

// Get user activity
try {
  // Get recent events created by user
  $query = "SELECT e.event_id, e.title, e.start_date, e.status 
            FROM events e 
            WHERE e.created_by = ? 
            ORDER BY e.created_at DESC LIMIT 5";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $userEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Get recent tasks assigned to user
  $query = "SELECT t.task_id, t.title, t.status, t.deadline 
            FROM tasks t 
            JOIN task_assignments ta ON t.task_id = ta.task_id 
            WHERE ta.user_id = ? 
            ORDER BY ta.assigned_at DESC LIMIT 5";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $userTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("Database error: " . $e->getMessage());
  $userEvents = [];
  $userTasks = [];
}
?>

<div class="mb-6">
  <h1 class="text-2xl font-normal mb-1" style="color: var(--color-text-primary);">My Profile</h1>
  <p class="text-sm" style="color: var(--color-text-secondary);">
      Manage your personal information and account settings
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
  <!-- Profile Summary Card -->
  <div class="google-card p-5">
      <div class="flex flex-col items-center text-center mb-6">
          <?php if (!empty($profile['profile_image'])): ?>
          <img src="<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="Profile Image" class="w-24 h-24 rounded-full object-cover mb-4">
          <?php else: ?>
          <div class="w-24 h-24 rounded-full flex items-center justify-center text-2xl font-medium mb-4" style="background-color: var(--color-primary); color: white;">
              <?php 
              $initials = '';
              if (!empty($profile['first_name'])) {
                  $initials .= strtoupper(substr($profile['first_name'], 0, 1));
              }
              if (!empty($profile['last_name'])) {
                  $initials .= strtoupper(substr($profile['last_name'], 0, 1));
              }
              echo $initials ?: strtoupper(substr($profile['username'], 0, 1));
              ?>
          </div>
          <?php endif; ?>
          
          <h2 class="text-xl font-medium" style="color: var(--color-text-primary);">
              <?php echo !empty($profile['first_name']) && !empty($profile['last_name']) ? 
                  htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) : 
                  htmlspecialchars($profile['username']); ?>
          </h2>
          
          <!-- Use the new role badge function -->
          <div class="mt-2 mb-2">
              <?php echo getRoleBadge($profile['role_id']); ?>
          </div>
          
          <!-- Use the new department badge function -->
          <div>
              <?php echo getDepartmentBadge($profile['department_id']); ?>
          </div>
          
          <form method="post" action="" enctype="multipart/form-data" class="mt-4 w-full">
              <div class="mb-3">
                  <label for="profile_image" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Update Profile Image</label>
                  
                  <!-- Improved file upload button -->
                  <div class="relative">
                      <input type="file" id="profile_image" name="profile_image" class="hidden" accept="image/*" required onchange="updateFileNameDisplay(this)">
                      <label for="profile_image" class="btn-secondary py-2 px-4 rounded-md text-sm font-medium w-full flex items-center justify-center cursor-pointer">
                          <i class="fas fa-upload mr-2"></i>
                          <span id="file-name-display">Choose File</span>
                      </label>
                      </div>
                  <p id="selected-file-name" class="text-xs mt-1" style="color: var(--color-text-tertiary);">No file selected</p>
              </div>
              
              <button type="submit" name="update_profile_image" class="btn-primary py-2 px-4 rounded-md text-sm font-medium w-full">
                  Update Profile Image
              </button>
          </form>
      </div>
      
      <div class="border-t pt-4" style="border-color: var(--color-border-light);">
          <h3 class="text-md font-medium mb-3" style="color: var(--color-text-primary);">Contact Information</h3>
          
          <div class="space-y-2">
              <div class="flex items-start">
                  <i class="fas fa-envelope mt-1 mr-3" style="color: var(--color-text-secondary);"></i>
                  <div>
                      <p class="text-sm font-medium" style="color: var(--color-text-primary);">Email</p>
                      <p class="text-sm" style="color: var(--color-text-secondary);"><?php echo htmlspecialchars($profile['email'] ?? 'Not set'); ?></p>
                  </div>
              </div>
              
              <div class="flex items-start">
                  <i class="fas fa-phone-alt mt-1 mr-3" style="color: var(--color-text-secondary);"></i>
                  <div>
                      <p class="text-sm font-medium" style="color: var(--color-text-primary);">Phone</p>
                      <p class="text-sm" style="color: var(--color-text-secondary);"><?php echo !empty($profile['phone']) ? htmlspecialchars($profile['phone']) : 'Not set'; ?></p>
                  </div>
              </div>
              
              <div class="flex items-start">
                  <i class="fas fa-calendar-alt mt-1 mr-3" style="color: var(--color-text-secondary);"></i>
                  <div>
                      <p class="text-sm font-medium" style="color: var(--color-text-primary);">Member Since</p>
                      <p class="text-sm" style="color: var(--color-text-secondary);"><?php echo !empty($profile['created_at']) ? date('F d, Y', strtotime($profile['created_at'])) : 'Unknown'; ?></p>
                  </div>
              </div>
              
              <div class="flex items-start">
                  <i class="fas fa-clock mt-1 mr-3" style="color: var(--color-text-secondary);"></i>
                  <div>
                      <p class="text-sm font-medium" style="color: var(--color-text-primary);">Last Login</p>
                      <p class="text-sm" style="color: var(--color-text-secondary);"><?php echo !empty($profile['last_login']) ? date('F d, Y H:i', strtotime($profile['last_login'])) : 'Never'; ?></p>
                  </div>
              </div>
          </div>
      </div>
  </div>
  
  <!-- Profile Edit Form -->
  <div class="google-card p-5 lg:col-span-2">
      <h3 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Edit Profile</h3>
      
      <form method="post" action="" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                  <label for="first_name" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">First Name</label>
                  <input type="text" id="first_name" name="first_name" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>" required>
              </div>
              
              <div>
                  <label for="last_name" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Last Name</label>
                  <input type="text" id="last_name" name="last_name" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>" required>
              </div>
          </div>
          
          <div>
              <label for="username" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Username</label>
              <input type="text" id="username" name="username" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo htmlspecialchars($profile['username'] ?? ''); ?>" required>
          </div>
          
          <div>
              <label for="email" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Email Address</label>
              <input type="email" id="email" name="email" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
          </div>
          
          <div>
              <label for="phone" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Phone Number</label>
              <input type="tel" id="phone" name="phone" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
          </div>
          
          <div>
              <label for="bio" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Bio</label>
              <textarea id="bio" name="bio" rows="4" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
          </div>
          
          <div>
              <button type="submit" name="update_profile" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                  Save Changes
              </button>
          </div>
      </form>
      
      <div class="mt-8 pt-6 border-t" style="border-color: var(--color-border-light);">
          <h3 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Change Password</h3>
          
          <form method="post" action="" class="space-y-4">
              <div>
                  <label for="current_password" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Current Password</label>
                  <input type="password" id="current_password" name="current_password" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
              </div>
              
              <div>
                  <label for="new_password" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">New Password</label>
                  <input type="password" id="new_password" name="new_password" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
              </div>
              
              <div>
                  <label for="confirm_password" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Confirm New Password</label>
                  <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border rounded-md" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);" required>
              </div>
              
              <div>
                  <button type="submit" name="change_password" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                      Change Password
                  </button>
              </div>
          </form>
      </div>
  </div>
</div>

<!-- Recent Activity Section -->
<div class="mt-8">
  <h2 class="text-xl font-normal mb-4" style="color: var(--color-text-primary);">Recent Activity</h2>
  
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Recent Events -->
      <div class="google-card p-5">
          <h3 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Events Created</h3>
          
          <?php if (empty($userEvents)): ?>
          <p class="text-center py-4" style="color: var(--color-text-secondary);">No events found.</p>
          <?php else: ?>
          <div class="space-y-4">
              <?php foreach ($userEvents as $event): ?>
              <div class="flex items-start">
                  <div class="rounded-full p-2 mr-3" style="background-color: rgba(251, 188, 5, 0.1);">
                      <i class="fas fa-calendar-alt" style="color: #FBBC05;"></i>
                  </div>
                  <div>
                      <p class="text-sm font-medium" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($event['title']); ?></p>
                      <p class="text-xs" style="color: var(--color-text-secondary);">
                          <?php echo date('M d, Y', strtotime($event['start_date'])); ?> • 
                          <span class="px-1 py-0.5 rounded-full text-xs" style="background-color: <?php 
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
              <?php endforeach; ?>
          </div>
          <?php endif; ?>
      </div>
      
      <!-- Recent Tasks -->
      <div class="google-card p-5">
          <h3 class="text-lg font-medium mb-4" style="color: var(--color-text-primary);">Assigned Tasks</h3>
          
          <?php if (empty($userTasks)): ?>
          <p class="text-center py-4" style="color: var(--color-text-secondary);">No tasks found.</p>
          <?php else: ?>
          <div class="space-y-4">
              <?php foreach ($userTasks as $task): ?>
              <div class="flex items-start">
                  <div class="rounded-full p-2 mr-3" style="background-color: <?php 
                      echo $task['status'] == 'to_do' ? 'rgba(251, 188, 5, 0.1)' : 
                          ($task['status'] == 'in_progress' ? 'rgba(66, 133, 244, 0.1)' : 'rgba(52, 168, 83, 0.1)'); 
                      ?>;">
                      <i class="fas fa-tasks" style="color: <?php 
                          echo $task['status'] == 'to_do' ? '#FBBC05' : 
                              ($task['status'] == 'in_progress' ? '#4285F4' : '#34A853'); 
                          ?>;"></i>
                  </div>
                  <div>
                      <p class="text-sm font-medium" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($task['title']); ?></p>
                      <p class="text-xs" style="color: var(--color-text-secondary);">
                          <?php echo !empty($task['deadline']) ? 'Due: ' . date('M d, Y', strtotime($task['deadline'])) : 'No deadline'; ?> • 
                          <span class="px-1 py-0.5 rounded-full text-xs" style="background-color: <?php 
                              echo $task['status'] == 'to_do' ? 'rgba(251, 188, 5, 0.1)' : 
                                  ($task['status'] == 'in_progress' ? 'rgba(66, 133, 244, 0.1)' : 'rgba(52, 168, 83, 0.1)'); 
                              ?>; 
                              color: <?php 
                              echo $task['status'] == 'to_do' ? '#FBBC05' : 
                                  ($task['status'] == 'in_progress' ? '#4285F4' : '#34A853'); 
                              ?>;">
                              <?php echo $task['status'] == 'to_do' ? 'To Do' : ($task['status'] == 'in_progress' ? 'In Progress' : 'Done'); ?>
                          </span>
                      </p>
                  </div
                  </div>
              </div>
              <?php endforeach; ?>
          </div>
          <?php endif; ?>
      </div>
  </div>
</div>

<!-- Officer-specific section: Department Activities -->
<div class="mt-8">
  <h2 class="text-xl font-normal mb-4" style="color: var(--color-text-primary);">Department Activities</h2>
  
  <?php
  // Get department information if the officer is assigned to a department
  $departmentInfo = null;
  $departmentEvents = [];
  
  if (!empty($profile['department_id'])) {
      try {
          // Get department details
          $query = "SELECT * FROM departments WHERE department_id = ?";
          $stmt = $conn->prepare($query);
          $stmt->bindParam(1, $profile['department_id'], PDO::PARAM_INT);
          $stmt->execute();
          $departmentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
          
          // Get upcoming department events
          $query = "SELECT e.* FROM events e 
                    WHERE e.department_id = ? 
                    AND e.status = 'upcoming' 
                    ORDER BY e.start_date ASC LIMIT 3";
          $stmt = $conn->prepare($query);
          $stmt->bindParam(1, $profile['department_id'], PDO::PARAM_INT);
          $stmt->execute();
          $departmentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
          error_log("Database error: " . $e->getMessage());
      }
  }
  ?>
  
  <?php if ($departmentInfo): ?>
  <div class="google-card p-5">
      <div class="flex items-center mb-4">
          <div class="rounded-full p-2 mr-3" style="background-color: rgba(66, 133, 244, 0.1);">
              <i class="fas fa-building" style="color: #4285F4;"></i>
          </div>
          <h3 class="text-lg font-medium" style="color: var(--color-text-primary);">
              <?php echo htmlspecialchars($departmentInfo['name']); ?> Department
          </h3>
      </div>
      
      <p class="mb-4" style="color: var(--color-text-secondary);">
          <?php echo htmlspecialchars($departmentInfo['description'] ?? 'No description available.'); ?>
      </p>
      
      <div class="mt-6">
          <h4 class="text-md font-medium mb-3" style="color: var(--color-text-primary);">Upcoming Department Events</h4>
          
          <?php if (empty($departmentEvents)): ?>
          <p class="text-center py-2" style="color: var(--color-text-secondary);">No upcoming events.</p>
          <?php else: ?>
          <div class="space-y-3">
              <?php foreach ($departmentEvents as $event): ?>
              <div class="flex items-start p-3 rounded-lg" style="background-color: var(--color-hover);">
                  <div class="rounded-full p-2 mr-3" style="background-color: rgba(251, 188, 5, 0.1);">
                      <i class="fas fa-calendar-alt" style="color: #FBBC05;"></i>
                  </div>
                  <div>
                      <p class="text-sm font-medium" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($event['title']); ?></p>
                      <p class="text-xs" style="color: var(--color-text-secondary);">
                          <?php echo date('M d, Y', strtotime($event['start_date'])); ?> at <?php echo date('h:i A', strtotime($event['start_date'])); ?>
                      </p>
                      <p class="text-xs mt-1" style="color: var(--color-text-secondary);">
                          <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($event['location'] ?? 'Location TBD'); ?>
                      </p>
                  </div>
              </div>
              <?php endforeach; ?>
          </div>
          <?php endif; ?>
      </div>
  </div>
  <?php else: ?>
  <div class="google-card p-5 text-center">
      <i class="fas fa-info-circle text-2xl mb-2" style="color: var(--color-text-secondary);"></i>
      <p style="color: var(--color-text-secondary);">You are not currently assigned to any department.</p>
  </div>
  <?php endif; ?>
</div>

<!-- Officer Skills Section -->
<div class="mt-8 mb-8">
  <h2 class="text-xl font-normal mb-4" style="color: var(--color-text-primary);">My Skills & Expertise</h2>
  
  <div class="google-card p-5">
      <div class="flex items-center mb-4">
          <div class="rounded-full p-2 mr-3" style="background-color: rgba(52, 168, 83, 0.1);">
              <i class="fas fa-lightbulb" style="color: #34A853;"></i>
          </div>
          <h3 class="text-lg font-medium" style="color: var(--color-text-primary);">Skills</h3>
      </div>
      
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
          <?php
          // Example skills based on department
          $skills = [];
          if (!empty($profile['department_id'])) {
              if ($profile['department_id'] == 1) { // Operations
                  $skills = ['Event Planning', 'Budget Management', 'Team Coordination', 'Strategic Planning', 'Resource Allocation', 'Logistics'];
              } elseif ($profile['department_id'] == 2) { // Marketing
                  $skills = ['Social Media', 'Content Creation', 'Graphic Design', 'Digital Marketing', 'Brand Strategy', 'Analytics'];
              } elseif ($profile['department_id'] == 3) { // Technical
                  $skills = ['Web Development', 'Audio-Visual', 'IT Support', 'Programming', 'Technical Documentation', 'System Administration'];
              }
          }
          
          // Display skills
          if (!empty($skills)):
              foreach ($skills as $skill):
          ?>
          <div class="px-3 py-2 rounded-full text-sm" style="background-color: var(--color-hover); color: var(--color-text-primary);">
              <?php echo htmlspecialchars($skill); ?>
          </div>
          <?php 
              endforeach;
          else:
          ?>
          <p class="col-span-3 text-center py-2" style="color: var(--color-text-secondary);">No skills listed.</p>
          <?php endif; ?>
      </div>
      
      <div class="mt-6 text-center">
          <p class="text-sm" style="color: var(--color-text-secondary);">
              <i class="fas fa-info-circle mr-1"></i> Skills are automatically assigned based on your department. Contact an administrator to update your skills.
          </p>
      </div>
  </div>
</div>

<script>
// Function to update the file name display
function updateFileNameDisplay(input) {
    const fileNameDisplay = document.getElementById('file-name-display');
    const selectedFileName = document.getElementById('selected-file-name');
    
    if (input.files && input.files[0]) {
        const fileName = input.files[0].name;
        fileNameDisplay.textContent = 'File Selected';
        selectedFileName.textContent = fileName;
    } else {
        fileNameDisplay.textContent = 'Choose File';
        selectedFileName.textContent = 'No file selected';
    }
}
</script>
