<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Get current user information
$currentUser = $_SESSION['user'] ?? null;
$userRole = $currentUser['role'] ?? '';
$roleId = $currentUser['role_id'] ?? 0;

// Define role constants if not already defined
if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', 1);
if (!defined('ROLE_DEPARTMENT_LEAD')) define('ROLE_DEPARTMENT_LEAD', 2);
if (!defined('ROLE_OFFICER')) define('ROLE_OFFICER', 3);
if (!defined('ROLE_MEMBER')) define('ROLE_MEMBER', 4);

// Debug output
echo "<!-- DEBUG ROLE ID: " . $roleId . " -->";
echo "<!-- DEBUG USER ROLE: " . htmlspecialchars($userRole) . " -->";

// Determine the correct profile and settings pages based on role ID
if ($roleId == ROLE_ADMIN) {
    $profilePage = '?page=lead_profile';
    $settingsPage = '?page=lead_settings';
} elseif ($roleId == ROLE_DEPARTMENT_LEAD) {
    $profilePage = '?page=dep_lead_profile';
    $settingsPage = '?page=dep_lead_settings';
} elseif ($roleId == ROLE_OFFICER) {
    $profilePage = '?page=officer_profile';
    $settingsPage = '?page=officer_settings';
} elseif ($roleId == ROLE_MEMBER) {
    $profilePage = '?page=member_profile';
    $settingsPage = '?page=member_settings';
} else {
    // Default case
    echo "<!-- DEBUG: Default case hit for role ID: " . $roleId . " -->";
    $profilePage = '?page=member_profile';
    $settingsPage = '?page=member_settings';
}
?>


<header class="flex-shrink-0 border-b" style="border-color: var(--color-border-light); background-color: var(--color-surface);">
    <div class="flex items-center justify-between p-4">
        <!-- Left side: Mobile menu button and search -->
        <div class="flex items-center space-x-3">
            <!-- Mobile menu button -->
            <button id="sidebar-toggle" class="p-2 rounded-md lg:hidden" style="color: var(--color-text-secondary);">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Search bar -->
            <div class="relative hidden md:block">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3" style="color: var(--color-text-tertiary);">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" class="py-2 pl-10 pr-4 rounded-full w-64"
                    style="background-color: var(--color-hover); color: var(--color-text-primary);"
                    placeholder="Search...">
            </div>
        </div>

        <!-- Right side: User actions -->
        <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <div class="relative">
                <button id="notifications-button" class="p-2 rounded-full" style="color: var(--color-text-secondary);">
                    <i class="fas fa-bell"></i>
                    <span id="notification-indicator" class="absolute top-0 right-0 w-2 h-2 rounded-full" style="background-color: #EA4335; display: none;"></span>
                </button>

                <!-- Notifications panel -->
                <div id="notifications-panel" class="absolute right-0 mt-2 w-80 rounded-md shadow-lg py-1 hidden z-10"
                    style="background-color: var(--color-surface); border: 1px solid var(--color-border-light);">
                    <div class="px-4 py-2 border-b flex justify-between items-center" style="border-color: var(--color-border-light);">
                        <h3 class="text-sm font-medium" style="color: var(--color-text-primary);">Notifications</h3>
                        <button id="mark-all-read" class="text-xs" style="color: var(--color-primary);">Mark all as read</button>
                    </div>

                    <div id="notifications-container" class="max-h-80 overflow-y-auto">
                        <!-- Notifications will be loaded here via AJAX -->
                        <div class="px-4 py-3 text-center text-sm" style="color: var(--color-text-tertiary);">
                            Loading notifications...
                        </div>
                    </div>

                    <div class="px-4 py-2 border-t text-center" style="border-color: var(--color-border-light);">
                        <a href="?page=notifications" class="text-xs" style="color: var(--color-primary);">View all notifications</a>
                    </div>
                </div>
            </div>

            <?php if ($userRole === 'admin' || $userRole === 'lead'): ?>
                <!-- Admin/Lead specific actions -->
                <div class="relative">
                    <button class="p-2 rounded-full" style="color: var(--color-text-secondary);">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- User profile dropdown -->
            <div class="relative">
                <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none">
                    <div class="w-8 h-8 rounded-full overflow-hidden bg-gray-200">
                        <?php if (!empty($profile['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="Profile Image" class="w-24 h-24 rounded-full object-cover mb-4">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center" style="background-color: #4285F4; color: white;">
                                <span><?php echo strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="hidden md:block text-left">
                        <p class="text-sm font-medium" style="color: var(--color-text-primary);">
                            <?php echo htmlspecialchars($currentUser['username'] ?? 'User'); ?>
                        </p>
                        <p class="text-xs" style="color: var(--color-text-secondary);">
                            <?php
                            // Get role_id directly from session
                            $role_id = $currentUser['role_id'] ?? 0;

                            // Simple mapping of role_id to display name
                            $roleDisplay = 'User';
                            if ($role_id == 1) {
                                $roleDisplay = 'Chapter Lead';
                            } elseif ($role_id == 2) {
                                $roleDisplay = 'Department Lead';
                            } elseif ($role_id == 3) {
                                $roleDisplay = 'Officer';
                            } elseif ($role_id == 4) {
                                $roleDisplay = 'Member';
                            }

                            echo htmlspecialchars($roleDisplay);
                            ?>
                        </p>
                    </div>

                    <i class="fas fa-chevron-down text-xs ml-1 hidden md:block" style="color: var(--color-text-tertiary);"></i>
                </button>

                <!-- Dropdown menu -->
                <div id="user-dropdown" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 hidden z-10"
                    style="background-color: var(--color-surface); border: 1px solid var(--color-border-light);">

                    <!-- Profile link based on role -->
                    <a href="<?php echo $profilePage; ?>" class="block px-4 py-2 text-sm" style="color: var(--color-text-primary);">
                        <i class="fas fa-user mr-2"></i> Your Profile
                    </a>
                    <a href="<?php echo $settingsPage; ?>" class="block px-4 py-2 text-sm" style="color: var(--color-text-primary);">
                        <i class="fas fa-cog mr-2"></i> Settings
                    </a>

                    <!-- Theme toggle added to dropdown -->
                    <button onclick="toggleTheme()" class="w-full text-left block px-4 py-2 text-sm" style="color: var(--color-text-primary);">
                        <i class="fas fa-moon dark:hidden mr-2"></i>
                        <i class="fas fa-sun hidden dark:inline mr-2"></i>
                        <span class="dark:hidden">Dark Mode</span>
                        <span class="hidden dark:inline">Light Mode</span>
                    </button>

                    <?php if ($userRole === 'admin'): ?>
                        <a href="?page=admin_dashboard" class="block px-4 py-2 text-sm" style="color: var(--color-text-primary);">
                            <i class="fas fa-tachometer-alt mr-2"></i> Admin Dashboard
                        </a>
                    <?php elseif ($userRole === 'lead'): ?>
                        <a href="?page=lead_dashboard" class="block px-4 py-2 text-sm" style="color: var(--color-text-primary);">
                            <i class="fas fa-tachometer-alt mr-2"></i> System Lead Dashboard
                        </a>
                    <?php elseif ($userRole === 'dep_lead'): ?>
                        <a href="?page=dep_lead_dashboard" class="block px-4 py-2 text-sm" style="color: var(--color-text-primary);">
                            <i class="fas fa-tachometer-alt mr-2"></i> Department Dashboard
                        </a>
                    <?php endif; ?>

                    <div class="border-t my-1" style="border-color: var(--color-border-light);"></div>

                    <a href="logout.php" class="block px-4 py-2 text-sm" style="color: var(--color-text-primary);">
                        <i class="fas fa-sign-out-alt mr-2"></i> Sign out
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // User dropdown toggle
        const userMenuButton = document.getElementById('user-menu-button');
        const userDropdown = document.getElementById('user-dropdown');

        if (userMenuButton && userDropdown) {
            userMenuButton.addEventListener('click', function() {
                userDropdown.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
                    userDropdown.classList.add('hidden');
                }
            });
        }

        // Notifications panel toggle
        const notificationsButton = document.getElementById('notifications-button');
        const notificationsPanel = document.getElementById('notifications-panel');

        if (notificationsButton && notificationsPanel) {
            notificationsButton.addEventListener('click', function() {
                notificationsPanel.classList.toggle('hidden');

                // Load notifications via AJAX when panel is opened
                if (!notificationsPanel.classList.contains('hidden')) {
                    loadNotifications();
                }
            });

            // Close notifications panel when clicking outside
            document.addEventListener('click', function(event) {
                if (!notificationsButton.contains(event.target) && !notificationsPanel.contains(event.target)) {
                    notificationsPanel.classList.add('hidden');
                }
            });
        }

        // Mark all notifications as read
        const markAllReadButton = document.getElementById('mark-all-read');
        if (markAllReadButton) {
            markAllReadButton.addEventListener('click', function() {
                markAllNotificationsAsRead();
            });
        }

        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
            });
        }

        // Initial load of notifications count
        checkNewNotifications();

        // Set up periodic checking for new notifications
        setInterval(checkNewNotifications, 60000); // Check every minute
    });

    // Function to load notifications via AJAX
    function loadNotifications() {
        const container = document.getElementById('notifications-container');
        if (!container) return;

        // Show loading state
        container.innerHTML = '<div class="px-4 py-3 text-center text-sm" style="color: var(--color-text-tertiary);">Loading notifications...</div>';

        // Make AJAX request
        fetch('api/notifications.php?action=get')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.notifications.length > 0) {
                        let html = '';
                        data.notifications.forEach(notification => {
                            const isRead = notification.is_read ? '' : 'font-bold';
                            const readIndicator = notification.is_read ? '' : '<span class="w-2 h-2 rounded-full bg-blue-500 mr-2"></span>';

                            html += `
                        <div class="px-4 py-3 border-b hover:bg-gray-50 dark:hover:bg-gray-800 flex items-start" 
                             style="border-color: var(--color-border-light);">
                            ${readIndicator}
                            <div class="${isRead}">
                                <p class="text-sm" style="color: var(--color-text-primary);">${notification.title}</p>
                                <p class="text-xs mt-1" style="color: var(--color-text-tertiary);">${notification.created_at}</p>
                            </div>
                        </div>`;
                        });
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = '<div class="px-4 py-3 text-center text-sm" style="color: var(--color-text-tertiary);">No notifications</div>';
                    }
                } else {
                    container.innerHTML = '<div class="px-4 py-3 text-center text-sm" style="color: var(--color-text-tertiary);">Error loading notifications</div>';
                }
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
                container.innerHTML = '<div class="px-4 py-3 text-center text-sm" style="color: var(--color-text-tertiary);">Error loading notifications</div>';
            });
    }

    // Function to check for new notifications
    function checkNewNotifications() {
        fetch('api/notifications.php?action=count')
            .then(response => response.json())
            .then(data => {
                const indicator = document.getElementById('notification-indicator');
                if (data.success && data.count > 0) {
                    indicator.style.display = 'block';
                } else {
                    indicator.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error checking notifications:', error);
            });
    }

    // Function to mark all notifications as read
    function markAllNotificationsAsRead() {
        fetch('api/notifications.php?action=mark_all_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload notifications to show updated read status
                    loadNotifications();

                    // Update the notification indicator
                    document.getElementById('notification-indicator').style.display = 'none';
                } else {
                    console.error('Error marking notifications as read:', data.message);
                }
            })
            .catch(error => {
                console.error('Error marking notifications as read:', error);
            });
    }

    // Function to handle theme toggle in the dropdown
    function handleThemeToggle() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        // Update the theme
        document.documentElement.setAttribute('data-theme', newTheme);

        // Save preference to localStorage
        localStorage.setItem('theme', newTheme);

        // Update UI elements that depend on theme
        updateThemeElements();
    }

    // Function to update UI elements based on current theme
    function updateThemeElements() {
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';

        // Update theme toggle text and icon in dropdown
        const darkModeText = document.querySelector('.dark\\:hidden');
        const lightModeText = document.querySelector('.hidden.dark\\:inline');
        const darkModeIcon = document.querySelector('.fas.fa-moon');
        const lightModeIcon = document.querySelector('.fas.fa-sun');

        if (isDarkMode) {
            darkModeText.classList.add('hidden');
            lightModeText.classList.remove('hidden');
            darkModeIcon.classList.add('hidden');
            lightModeIcon.classList.remove('hidden');
        } else {
            darkModeText.classList.remove('hidden');
            lightModeText.classList.add('hidden');
            darkModeIcon.classList.remove('hidden');
            lightModeIcon.classList.add('hidden');
        }
    }

    // Initialize theme on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Set initial theme based on localStorage or system preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        } else {
            // Check system preference
            const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', prefersDarkMode ? 'dark' : 'light');
        }

        // Update UI elements based on current theme
        updateThemeElements();
    });
</script>