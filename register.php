<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Initialize authentication
$auth = new Auth();

// Check if user is already logged in
if ($auth->isLoggedIn()) {
    // Redirect to dashboard
    header('Location: router.php');
    exit;
}

// Handle registration form submission
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        isset($_POST['username']) && 
        isset($_POST['email']) && 
        isset($_POST['password']) && 
        isset($_POST['confirm_password']) && 
        isset($_POST['first_name']) && 
        isset($_POST['last_name'])
    ) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $departmentId = 1; // Default department ID since we're removing the selection
        
        // Set default role to Member (role_id = 4)
        $roleId = 4; // Member role
        
        // Validate input
        if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
            $error = 'All fields are required';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } else {
            // Register user with Member role
            $result = $auth->register($username, $email, $password, $firstName, $lastName, $departmentId, $roleId);
            
            if ($result['success']) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = $result['message'];
            }
        }
    } else {
        $error = 'All fields are required';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@300;400;500;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Theme CSS -->
    <link rel="stylesheet" href="public/assets/css/theme.css">
    <!-- Prevent caching -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        body {
            font-family: 'Google Sans', 'Roboto', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text-primary);
        }
        
        .register-card {
            background-color: var(--color-surface);
            border: 1px solid var(--color-border-light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-elevation-2);
        }
        
        .btn-primary {
            background-color: #1a73e8; /* Google's blue */
            color: white;
            transition: background-color 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #1765cc; /* Darker blue on hover */
        }
        
        .input-field {
            border: 1px solid var(--color-border-light);
            background-color: var(--color-input-bg);
            color: var(--color-text-primary);
        }
        
        .input-field:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
        }
        
        .link-primary {
            color: #1a73e8;
            transition: color 0.3s;
        }
        
        .link-primary:hover {
            color: #1765cc;
        }
        
        /* Theme toggle switch */
        .theme-toggle-wrapper {
            display: inline-block;
        }

        .theme-toggle-switch {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            outline: none;
        }

        .theme-toggle-track {
            position: relative;
            width: 50px;
            height: 24px;
            border-radius: 12px;
            background-color: #4285F4;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            padding: 0 4px;
        }

        [data-theme="dark"] .theme-toggle-track {
            background-color: #555;
        }

        .theme-toggle-icons {
            position: absolute;
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 6px;
            box-sizing: border-box;
        }

        .theme-toggle-thumb {
            position: absolute;
            left: 2px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s;
        }

        [data-theme="dark"] .theme-toggle-thumb {
            transform: translateX(26px);
        }

        /* Icon visibility based on theme */
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
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full register-card">
        <div class="py-8 px-8">
            <div class="flex items-center justify-center mb-6">
                <img src="public/assets/img/brand/CoCreate-v2.png" alt="CoCreate Logo" class="h-10 w-auto mr-2">
                <h2 class="text-2xl font-bold" style="color: var(--color-text-primary);"><?php echo APP_NAME; ?></h2>
            </div>
            <p class="text-center text-lg mb-6" style="color: var(--color-text-secondary);">Create your account</p>
            
            <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
                <p class="mt-2">
                    <a href="login.php" class="font-bold text-green-700 hover:underline">Go to Login</a>
                </p>
            </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label for="first_name" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">
                                First Name
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user" style="color: var(--color-text-tertiary);"></i>
                                </div>
                                <input type="text" id="first_name" name="first_name" class="input-field pl-10 w-full py-2 px-3 rounded-md focus:outline-none" placeholder="First Name" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="last_name" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">
                                Last Name
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user" style="color: var(--color-text-tertiary);"></i>
                                </div>
                                <input type="text" id="last_name" name="last_name" class="input-field pl-10 w-full py-2 px-3 rounded-md focus:outline-none" placeholder="Last Name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">
                            Username
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user-tag" style="color: var(--color-text-tertiary);"></i>
                            </div>
                            <input type="text" id="username" name="username" class="input-field pl-10 w-full py-2 px-3 rounded-md focus:outline-none" placeholder="Username" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">
                            Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope" style="color: var(--color-text-tertiary);"></i>
                            </div>
                            <input type="email" id="email" name="email" class="input-field pl-10 w-full py-2 px-3 rounded-md focus:outline-none" placeholder="Email" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock" style="color: var(--color-text-tertiary);"></i>
                            </div>
                            <input type="password" id="password" name="password" class="input-field pl-10 w-full py-2 px-3 rounded-md focus:outline-none" placeholder="Password" required>
                        </div>
                        <p class="text-xs mt-1" style="color: var(--color-text-tertiary);">Password must be at least 6 characters long</p>
                    </div>
                    
                    <div class="mb-6">
                        <label for="confirm_password" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">
                            Confirm Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock" style="color: var(--color-text-tertiary);"></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" class="input-field pl-10 w-full py-2 px-3 rounded-md focus:outline-none" placeholder="Confirm Password" required>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <button type="submit" class="btn-primary w-full py-2 px-4 rounded-md font-medium focus:outline-none">
                            Register
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <p class="text-sm" style="color: var(--color-text-secondary);">
                            Already have an account? <a href="login.php" class="link-primary">Sign in</a>
                        </p>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="mt-8 pt-6 border-t relative" style="border-color: var(--color-border-light);">
                <div class="flex justify-between items-center">
                    <a href="landing.php" class="text-sm link-primary flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Home
                    </a>
                    
                    <!-- Theme toggle switch -->
                    <div class="theme-toggle-wrapper">
                        <button class="theme-toggle-switch" onclick="toggleTheme()" title="Toggle theme">
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
    </div>
    
    <!-- Theme JS -->
    <script src="public/assets/js/theme.js"></script>
    <script>
        // Update theme toggle appearance based on current theme
        function updateThemeToggle() {
            // The CSS handles most of the visual changes through the data-theme attribute
            // This function can be used for any additional JavaScript-based updates if needed
        }

        // Initial toggle update
        document.addEventListener('DOMContentLoaded', function() {
            updateThemeToggle();
        });

        // Override the toggleTheme function
        const originalToggleTheme = window.toggleTheme;
        window.toggleTheme = function() {
            originalToggleTheme();
            updateThemeToggle();
        };
    </script>
</body>
</html>
