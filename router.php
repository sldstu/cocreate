<?php
session_start();
ob_start(); // Start output buffering to prevent premature output

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Load configuration and required files
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Initialize authentication
$auth = new Auth();

// Get current user
$currentUser = $auth->getCurrentUser();
$role_id = $currentUser['role_id'];
$username = $currentUser['username'];
$userEmail = $currentUser['email'];
$userInitials = strtoupper(substr($username, 0, 1));

// Store user data in session for topnav.php to access
$_SESSION['user'] = [
    'username' => $username,
    'email' => $userEmail,
    'role_id' => $role_id,
    'role' => getUserRoleName($role_id, true) // Pass true to get the role slug
];


// Define allowed pages and sidebar items based on role
$allowed_pages = [];
$sidebar_items = [];

// Update the getUserRoleName function to match new role names
if (!function_exists('getUserRoleName')) {
    function getUserRoleName($roleId, $returnSlug = false)
    {
        switch ($roleId) {
            case ROLE_ADMIN:
                return $returnSlug ? 'admin' : 'Administrator';
            case ROLE_DEPARTMENT_LEAD:
                return $returnSlug ? 'dep_lead' : 'Department Lead';
            case ROLE_OFFICER:
                return $returnSlug ? 'officer' : 'Officer'; // Changed from 'member' to 'officer'
            case ROLE_MEMBER:
                return $returnSlug ? 'member' : 'Member'; // Added case for ROLE_MEMBER
            default:
                return $returnSlug ? 'user' : 'Unknown Role';
        }
    }
}

// Add a new role constant for Member if it doesn't exist
if (!defined('ROLE_MEMBER')) {
    define('ROLE_MEMBER', 4);
}

// Modify the allowed pages section to include Member pages
if ($role_id == ROLE_ADMIN) {
    // Chapter Lead (Admin) pages - keep existing
    $allowed_pages = [
        'lead_dashboard' => 'views/lead/dashboard.php',
        'lead_users' => 'views/lead/users.php',
        'lead_departments' => 'views/lead/departments.php',
        'lead_events' => 'views/lead/events.php',
        'lead_tasks' => 'views/lead/tasks.php',
        'lead_reports' => 'views/lead/reports.php',
        'lead_settings' => 'views/lead/settings.php',
        'lead_profile' => 'views/lead/profile.php',
        'lead_notifications' => 'views/lead/notifications.php',
        'lead_logs' => 'views/lead/logs.php',
        '404' => 'views/404.php',
    ];

    $sidebar_items = [
        ['name' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'page' => 'lead_dashboard'],
        ['name' => 'Users', 'icon' => 'fas fa-users', 'page' => 'lead_users'],
        ['name' => 'Departments', 'icon' => 'fas fa-building', 'page' => 'lead_departments'],
        ['name' => 'Events', 'icon' => 'fas fa-calendar-alt', 'page' => 'lead_events'],
        ['name' => 'Tasks', 'icon' => 'fas fa-tasks', 'page' => 'lead_tasks'],
        ['name' => 'Reports', 'icon' => 'fas fa-chart-bar', 'page' => 'lead_reports'],
        ['name' => 'Notifications', 'icon' => 'fas fa-bell', 'page' => 'lead_notifications'],
        ['name' => 'System Logs', 'icon' => 'fas fa-history', 'page' => 'lead_logs'],
    ];

} elseif ($role_id == ROLE_DEPARTMENT_LEAD) {
    // Department Lead pages - keep existing
    $allowed_pages = [
        'dep_lead_dashboard' => 'views/dep_lead/dashboard.php',
        'dep_lead_events' => 'views/dep_lead/events.php',
        'dep_lead_tasks' => 'views/dep_lead/tasks.php',
        'dep_lead_members' => 'views/dep_lead/members.php',
        'dep_lead_reports' => 'views/dep_lead/reports.php',
        'dep_lead_settings' => 'views/dep_lead/settings.php',
        'dep_lead_profile' => 'views/dep_lead/profile.php',
        '404' => 'views/404.php',
    ];

    $sidebar_items = [
        ['name' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'page' => 'dep_lead_dashboard'],
        ['name' => 'Events', 'icon' => 'fas fa-calendar-alt', 'page' => 'dep_lead_events'],
        ['name' => 'Tasks', 'icon' => 'fas fa-tasks', 'page' => 'dep_lead_tasks'],
        ['name' => 'Department Members', 'icon' => 'fas fa-users', 'page' => 'dep_lead_members'],
        ['name' => 'Reports', 'icon' => 'fas fa-chart-bar', 'page' => 'dep_lead_reports'],
    ];
} elseif ($role_id == ROLE_OFFICER) {
    
    $allowed_pages = [
        'officer_dashboard' => 'views/officer/dashboard.php',
        'officer_events' => 'views/officer/events.php',
        'officer_tasks' => 'views/officer/tasks.php',
        'officer_profile' => 'views/officer/profile.php',
        'officer_settings' => 'views/officer/settings.php',
        '404' => 'views/404.php',
    ];

    $sidebar_items = [
        ['name' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'page' => 'officer_dashboard'],
        ['name' => 'Events', 'icon' => 'fas fa-calendar-alt', 'page' => 'officer_events'],
        ['name' => 'Tasks', 'icon' => 'fas fa-tasks', 'page' => 'officer_tasks'],
    ];


    // Keep existing sidebar items
} elseif ($role_id == ROLE_MEMBER) {
    // Add new Member pages
    $allowed_pages = [
        'member_dashboard' => 'views/member/dashboard.php',
        'member_events' => 'views/member/events.php',
        'member_profile' => 'views/member/profile.php',
        'member_settings' => 'views/member/settings.php',
        '404' => 'views/404.php',
    ];

    $sidebar_items = [
        ['name' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'page' => 'member_dashboard'],
        ['name' => 'Events', 'icon' => 'fas fa-calendar-alt', 'page' => 'member_events'],
    ];
} else {
    // Redirect to login if role is not recognized
    header('Location: login.php');
    exit();
}


// Determine the current page based on the URL parameter or session
$page = $_GET['page'] ?? ($_SESSION['last_page'] ?? array_key_first($allowed_pages));

// Check if the requested page is allowed for this role
if (!isset($allowed_pages[$page])) {
    $page = '404';
}

// Save the current page in the session to persist between refreshes
$_SESSION['last_page'] = $page;
$file_to_include = $allowed_pages[$page];

// Handle AJAX requests
if (
    (isset($_GET['ajax']) && $_GET['ajax'] === 'true') ||
    isset($_GET['ajax_action']) // detect ajax_action param
) {
    if (file_exists($file_to_include)) {
        include_once $file_to_include;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'File not found.']);
    }
    exit(); // Stop further execution for AJAX requests
}


ob_end_flush(); // Flush the buffered output after headers

// Get page title
$pageTitle = ucwords(str_replace('_', ' ', str_replace(['lead_', 'officer_'], '', $page)));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@300;400;500;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="public/assets/css/theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Google Sans', 'Roboto', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text-primary);
            height: 100vh;
            overflow: hidden;
        }

        /* Google-style sidebar */
        .sidebar {
            width: 256px;
            background-color: var(--color-surface);
            border-right: 1px solid var(--color-border-light);
            transition: transform 0.3s ease;
            z-index: 40;
        }

        /* Google-style sidebar item */
        .sidebar-item {
            border-radius: 0 16px 16px 0;
            margin-right: 12px;
            transition: background-color 0.2s;
        }

        .sidebar-item:hover {
            background-color: var(--color-hover);
        }

        .sidebar-item.active {
            background-color: var(--color-item-active);
            color: var(--color-primary);
        }

        .sidebar-item.active i {
            color: var(--color-primary);
        }

        /* Google-style avatar */
        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--color-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 500;
        }

        /* Google-style content area */
        .content-area {
            background-color: var(--color-background);
            overflow-y: auto;
        }

        /* Google-style card */
        .google-card {
            background-color: var(--color-surface);
            border-radius: 8px;
            border: 1px solid var(--color-border-light);
            box-shadow: var(--shadow-elevation-1);
            transition: box-shadow 0.2s;
        }

        .google-card:hover {
            box-shadow: var(--shadow-elevation-2);
        }

        /* Google-style button */
        .google-button {
            border-radius: 4px;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        /* Mobile sidebar handling */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 35;
                display: none;
            }

            .overlay.active {
                display: block;
            }
        }
    </style>
</head>

<body>
    <!-- Mobile sidebar overlay -->
    <div class="overlay" id="overlay"></div>

    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="sidebar h-full flex flex-col" id="sidebar">
            <!-- App Logo and Name -->
            <div class="px-6 py-4 flex items-center">
                <img src="public/assets/img/brand/CoCreate-v2.png" alt="<?php echo APP_NAME; ?>" class="h-8 w-auto mr-2">
                <h1 class="text-xl font-medium" style="color: var(--color-text-primary);"><?php echo APP_NAME; ?></h1>
            </div>

            <!-- Sidebar Navigation -->
            <nav class="mt-2 flex-grow">
                <ul class="px-2 space-y-1">
                    <?php foreach ($sidebar_items as $item): ?>
                        <li>
                            <a href="?page=<?php echo $item['page']; ?>" class="sidebar-item flex items-center px-4 py-3 text-sm font-medium <?php echo ($page === $item['page']) ? 'active' : ''; ?>" style="color: var(--color-text-primary);">
                                <i class="<?php echo $item['icon']; ?> w-5 h-5 mr-3" style="color: var(--color-text-secondary);"></i>
                                <?php echo $item['name']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- User Info at Bottom
            <div class="p-4 mt-auto border-t" style="border-color: var(--color-border-light);">
                <div class="flex items-center">
                    <div class="avatar mr-3">
                        <?php echo $userInitials; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate" style="color: var(--color-text-primary);">
                            <?php echo htmlspecialchars($username); ?>
                        </p>
                        <p class="text-xs truncate" style="color: var(--color-text-secondary);">
                            <?php echo getUserRoleName($role_id); ?>
                        </p>
                    </div>
                    <a href="logout.php" class="p-2 rounded-full hover:bg-gray-200" style="color: var(--color-text-secondary);" title="Sign out">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div> -->
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation - Include the topnav.php file -->
            <?php include_once 'includes/topnav.php'; ?>

            <!-- Page Content -->
            <main class="content-area flex-1 p-6">
                <?php
                if (file_exists($file_to_include)) {
                    include_once $file_to_include;
                } else {
                    echo "Error: File not found.";
                }
                ?>
            </main>
        </div>
    </div>

    <!-- Theme JS -->
    <script src="public/assets/js/theme.js"></script>
    <script>
        // Mobile menu toggle
        document.getElementById('overlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('active');
        });

        // Handle sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }

        // Update active sidebar item based on current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = '<?php echo $page; ?>';
            const sidebarItems = document.querySelectorAll('.sidebar-item');

            sidebarItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href && href.includes('page=' + currentPage)) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });

            // Initialize any page-specific scripts
            if (typeof initPage === 'function') {
                initPage();
            }
        });

        // Handle theme changes for dynamic content
        function updateThemeForDynamicContent() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            // Update any theme-dependent elements that might have been loaded dynamically

            // Dispatch a custom event that page-specific scripts can listen for
            document.dispatchEvent(new CustomEvent('themeChanged', {
                detail: {
                    theme: currentTheme
                }
            }));
        }

        // Override the toggleTheme function to also update dynamic content
        const originalToggleTheme = window.toggleTheme;
        window.toggleTheme = function() {
            originalToggleTheme();
            updateThemeForDynamicContent();
        };
    </script>
</body>

</html>