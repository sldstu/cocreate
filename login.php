<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

// Initialize authentication
$auth = new Auth();

// Check if user is already logged in
if ($auth->isLoggedIn()) {
    // Redirect to router
    header('Location: router.php');
    exit;
}

// Check if it's an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Please enter your email.';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Please enter your password.';
    }
    
    if (empty($errors)) {
        $result = $auth->login($email, $password);
        
    // In the AJAX response section
if ($result['success']) {
    if ($isAjax) {
        // Return JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Login successful!',
            'redirect' => 'router.php'  // Ensure this is router.php, not dashboard.php
        ]);
        exit;
    } else {
        // Redirect for traditional form
        header('Location: router.php');
        exit;
    }
}
 else {
            if ($isAjax) {
                // Return JSON response for AJAX
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $result['message']
                ]);
                exit;
            } else {
                $error = $result['message'];
            }
        }
    } else {
        if ($isAjax) {
            // Return JSON response for AJAX
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Please correct the errors below.',
                'errors' => $errors
            ]);
            exit;
        } else {
            $error = 'Please enter both email and password.';
        }
    }
}

// If it's an AJAX request but not POST, return error
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$error = $error ?? '';
$success = $success ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Login</title>
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
        
        .login-card {
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
    <!-- Messages container for AJAX responses -->
    <div id="messages-container" class="fixed top-4 right-4 z-50 w-80"></div>
    
    <div class="max-w-md w-full login-card">
        <div class="py-8 px-8">
            <div class="flex items-center justify-center mb-6">
                <img src="public/assets/img/brand/CoCreate-v2.png" alt="CoCreate Logo" class="h-10 w-auto mr-2">
                <h2 class="text-2xl font-bold" style="color: var(--color-text-primary);"><?php echo APP_NAME; ?></h2>
            </div>
            <p class="text-center text-lg mb-6" style="color: var(--color-text-secondary);">Sign in to your account</p>
            
            <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" class="ajax-form">
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope" style="color: var(--color-text-tertiary);"></i>
                        </div>
                        <input type="email" id="email" name="email" class="input-field pl-10 w-full py-2 px-3 rounded-md focus:outline-none" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary);">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock" style="color: var(--color-text-tertiary);"></i>
                        </div>
                        <input type="password" id="password" name="password" class="input-field pl-10 w-full py-2 px-3 rounded-md focus:outline-none" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="h-4 w-4 rounded">
                        <label for="remember" class="ml-2 block text-sm" style="color: var(--color-text-secondary);">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="text-sm link-primary">Forgot Password?</a>
                </div>
                
                <div class="mb-6">
                    <button type="submit" class="btn-primary w-full py-2 px-4 rounded-md font-medium focus:outline-none">
                        Sign In
                    </button>
                </div>
                
                <div class="text-center">
                    <p class="text-sm" style="color: var(--color-text-secondary);">
                        Don't have an account? <a href="register.php" class="link-primary">Create an account</a>
                    </p>
                </div>
            </form>
            
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
    <!-- AJAX Utils -->
    <script src="public/assets/js/ajax-utils.js"></script>
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
