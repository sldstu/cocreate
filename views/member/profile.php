<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get current user's profile data
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
    $query = "SELECT u.*, r.name as role_name, d.name as department_name 
              FROM users u 
              LEFT JOIN roles r ON u.role_id = r.role_id 
              LEFT JOIN departments d ON u.department_id = d.department_id 
              WHERE u.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        $error_message = 'User profile not found.';
    }
    
    // Get user's RSVPs
    $query = "SELECT er.*, e.title, e.start_date, e.location 
              FROM event_rsvps er
              JOIN events e ON er.event_id = e.event_id
              WHERE er.user_id = ?
              ORDER BY e.start_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $rsvps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = 'An error occurred while fetching user data.';
    $profile = [];
    $rsvps = [];
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
          
          <!-- Display role badge -->
          <div class="mt-2 mb-2">
              <?php echo getRoleBadge($profile['role_id']); ?>
          </div>
          
          <!-- Only display department badge for officers and above (role_id < 4) -->
          <?php if (isset($profile['role_id']) && $profile['role_id'] < ROLE_MEMBER && !empty($profile['department_id'])): ?>
          <div>
              <?php echo getDepartmentBadge($profile['department_id']); ?>
          </div>
          <?php endif; ?>
          
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

<!-- Event RSVPs Section -->
<div class="mt-8">
  <h2 class="text-xl font-normal mb-4" style="color: var(--color-text-primary);">My Event RSVPs</h2>
  
  <div class="google-card p-5">
      <?php if (empty($rsvps)): ?>
      <p class="text-center py-4" style="color: var(--color-text-secondary);">You haven't RSVP'd to any events yet.</p>
      <?php else: ?>
      <div class="overflow-x-auto">
          <table class="min-w-full">
              <thead>
                  <tr style="border-bottom: 1px solid var(--color-border-light);">
                      <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Event</th>
                      <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Date</th>
                      <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Location</th>
                      <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Status</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach ($rsvps as $rsvp): ?>
                  <tr style="border-bottom: 1px solid var(--color-border-light);">
                      <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($rsvp['title']); ?></td>
                      <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);"><?php echo date('M d, Y', strtotime($rsvp['start_date'])); ?></td>
                      <td class="px-4 py-3 text-sm" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($rsvp['location'] ?? 'N/A'); ?></td>
                      <td class="px-4 py-3 text-sm">
                          <span class="px-2 py-1 rounded-full text-xs font-medium" style="background-color: <?php 
                              echo $rsvp['status'] == 'going' ? 'rgba(52, 168, 83, 0.1)' : 
                                  ($rsvp['status'] == 'interested' ? 'rgba(251, 188, 5, 0.1)' : 'rgba(234, 67, 53, 0.1)'); 
                              ?>; 
                              color: <?php 
                              echo $rsvp['status'] == 'going' ? '#34A853' : 
                                  ($rsvp['status'] == 'interested' ? '#FBBC05' : '#EA4335'); 
                              ?>;">
                              <?php echo $rsvp['status'] == 'going' ? 'Going' : ($rsvp['status'] == 'interested' ? 'Interested' : 'Not Going'); ?>
                          </span>
                      </td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
      </div>
      <?php endif; ?>
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
