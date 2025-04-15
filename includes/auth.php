<?php
/**
 * Authentication class for the Event Management System
 */
class Auth {
    private $conn;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize database connection
        require_once 'config/database.php';
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Login a user
     * 
     * @param string $email The user's email
     * @param string $password The user's password
     * @return array Result with success status and message
     */
    public function login($email, $password) {
        try {
            // Find user by email
            $query = "SELECT * FROM users WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['department_id'] = $user['department_id'];
                    
                    // Update last login
                    $updateQuery = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
                    $updateStmt = $this->conn->prepare($updateQuery);
                    $updateStmt->bindParam(':user_id', $user['user_id']);
                    $updateStmt->execute();
                    
                    return [
                        'success' => true,
                        'message' => 'Login successful'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Invalid password'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Register a new user
     * 
     * @param string $username The username
     * @param string $email The email
     * @param string $password The password
     * @param string $firstName The first name
     * @param string $lastName The last name
     * @param int $departmentId The department ID
     * @return array Result with success status and message
     */
// Update the register method to accept role_id parameter
public function register($username, $email, $password, $firstName, $lastName, $departmentId, $roleId = 4) {
    try {
        // Check if username already exists
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email already exists
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $query = "INSERT INTO users (username, email, password, first_name, last_name, role_id, department_id, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->bindParam(2, $email);
        $stmt->bindParam(3, $hashedPassword);
        $stmt->bindParam(4, $firstName);
        $stmt->bindParam(5, $lastName);
        $stmt->bindParam(6, $roleId, PDO::PARAM_INT);
        $stmt->bindParam(7, $departmentId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Registration successful'];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during registration'];
    }
}

    
    /**
     * Check if a user is logged in
     * 
     * @return bool True if logged in, false otherwise
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get the current logged-in user
     * 
     * @return array|null The user data or null if not logged in
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $query = "SELECT * FROM users WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            // Handle error
            error_log("Database error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Logout the current user
     */
    public function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
    }
}
