<?php
// Function to create a notification
function createNotification($user_id, $title, $content, $type, $reference_id = null) {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $query = "INSERT INTO notifications (user_id, title, content, type, reference_id, is_read, created_at) 
                  VALUES (?, ?, ?, ?, ?, 0, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $title, PDO::PARAM_STR);
        $stmt->bindParam(3, $content, PDO::PARAM_STR);
        $stmt->bindParam(4, $type, PDO::PARAM_STR);
        $stmt->bindParam(5, $reference_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

// Function to notify all users of a specific role
function notifyUsersByRole($role_id, $title, $content, $type, $reference_id = null) {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        // Get all users with the specified role
        $query = "SELECT user_id FROM users WHERE role_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $role_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create notification for each user
        foreach ($users as $user) {
            createNotification($user['user_id'], $title, $content, $type, $reference_id);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error notifying users by role: " . $e->getMessage());
        return false;
    }
}

// Function to notify all users in a department
function notifyUsersByDepartment($department_id, $title, $content, $type, $reference_id = null) {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        // Get all users in the specified department
        $query = "SELECT user_id FROM users WHERE department_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $department_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create notification for each user
        foreach ($users as $user) {
            createNotification($user['user_id'], $title, $content, $type, $reference_id);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error notifying users by department: " . $e->getMessage());
        return false;
    }
}

// Function to notify all users
function notifyAllUsers($title, $content, $type, $reference_id = null) {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        // Get all active users
        $query = "SELECT user_id FROM users WHERE is_active = 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create notification for each user
        foreach ($users as $user) {
            createNotification($user['user_id'], $title, $content, $type, $reference_id);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error notifying all users: " . $e->getMessage());
        return false;
    }
}

function sendEmailNotification($user_email, $subject, $body) {
  require 'vendor/autoload.php'; // If using Composer
  
  $mail = new PHPMailer\PHPMailer\PHPMailer(true);
  
  try {
      // Server settings
      $mail->isSMTP();
      $mail->Host = SMTP_HOST; // Define these in config
      $mail->SMTPAuth = true;
      $mail->Username = SMTP_USERNAME;
      $mail->Password = SMTP_PASSWORD;
      $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = SMTP_PORT;
      
      // Recipients
      $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
      $mail->addAddress($user_email);
      
      // Content
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body = $body;
      
      $mail->send();
      return true;
  } catch (Exception $e) {
      error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
      return false;
  }
}
