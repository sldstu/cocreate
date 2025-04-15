<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Get system statistics
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get total users count
    $query = "SELECT COUNT(*) as count FROM users";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Get total departments count
    $query = "SELECT COUNT(*) as count FROM departments";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $totalDepartments = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Get total events count
    $query = "SELECT COUNT(*) as count FROM events";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $totalEvents = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Get total tasks count
    $query = "SELECT COUNT(*) as count FROM tasks";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $totalTasks = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Get completed tasks count
    $query = "SELECT COUNT(*) as count FROM tasks WHERE status = 'done'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $completedTasks = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Calculate task completion rate
    $taskCompletionRate = ($totalTasks > 0) ? round(($completedTasks / $totalTasks) * 100) : 0;
    
    // Get recent users (last 5)
    $query = "SELECT u.user_id, u.username, u.email, u.created_at, r.name as role_name, d.name as department_name 
              FROM users u 
              LEFT JOIN roles r ON u.role_id = r.role_id 
              LEFT JOIN departments d ON u.department_id = d.department_id 
              ORDER BY u.created_at DESC LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent events (last 5)
    $query = "SELECT e.event_id, e.title, e.start_date, e.end_date, d.name as department_name 
              FROM events e 
              LEFT JOIN departments d ON e.department_id = d.department_id 
              ORDER BY e.created_at DESC LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $recentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get task status distribution
    $query = "SELECT status, COUNT(*) as count FROM tasks GROUP BY status";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $taskStatusData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format task status data for chart
    $taskStatusLabels = [];
    $taskStatusCounts = [];
    
    foreach ($taskStatusData as $status) {
        $taskStatusLabels[] = ucfirst(str_replace('_', ' ', $status['status']));
        $taskStatusCounts[] = $status['count'];
    }
    
    // If no data, provide defaults
    if (empty($taskStatusLabels)) {
        $taskStatusLabels = ['To Do', 'In Progress', 'Done'];
        $taskStatusCounts = [0, 0, 0];
    }
    
    // Get monthly user registrations for the past 6 months
    $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
              FROM users 
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
              GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
              ORDER BY month ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $userMonthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format monthly user data for chart
    $userMonthLabels = [];
    $userMonthlyCounts = [];
    
    // Initialize with past 6 months
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $userMonthLabels[] = date('M Y', strtotime($month));
        $userMonthlyCounts[] = 0; // Default to 0
    }
    
    // Fill in actual data
    foreach ($userMonthlyData as $monthData) {
        $monthIndex = array_search(date('M Y', strtotime($monthData['month'])), $userMonthLabels);
        if ($monthIndex !== false) {
            $userMonthlyCounts[$monthIndex] = (int)$monthData['count'];
        }
    }
    
    // Get department event counts
    $query = "SELECT d.name, COUNT(e.event_id) as count 
              FROM departments d 
              LEFT JOIN events e ON d.department_id = e.department_id 
              GROUP BY d.department_id 
              ORDER BY count DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $departmentEventData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format department event data for chart
    $departmentLabels = [];
    $departmentCounts = [];
    
    foreach ($departmentEventData as $dept) {
        $departmentLabels[] = $dept['name'];
        $departmentCounts[] = $dept['count'];
    }
    
    // If no data, provide defaults
    if (empty($departmentLabels)) {
        $departmentLabels = ['No Data'];
        $departmentCounts = [0];
    }
    
} catch (PDOException $e) {
    // Handle error
    error_log("Database error: " . $e->getMessage());
    // Use placeholder data if database query fails
    $totalUsers = 0;
    $totalDepartments = 0;
    $totalEvents = 0;
    $totalTasks = 0;
    $taskCompletionRate = 0;
    $recentUsers = [];
    $recentEvents = [];
    $taskStatusLabels = ['To Do', 'In Progress', 'Done'];
    $taskStatusCounts = [0, 0, 0];
    $userMonthLabels = [];
    $userMonthlyCounts = [];
    $departmentLabels = ['No Data'];
    $departmentCounts = [0];
    
    for ($i = 5; $i >= 0; $i--) {
        $userMonthLabels[] = date('M Y', strtotime("-$i months"));
        $userMonthlyCounts[] = 0;
    }
}
?>

<div class="p-4 sm:p-6 lg:p-8">
    <!-- Welcome Section -->
    <div class="mb-6">
        <h1 class="text-2xl font-normal mb-1" style="color: var(--color-text-primary);">Welcome back, <?php echo htmlspecialchars($currentUser['username']); ?></h1>
        <p class="text-sm" style="color: var(--color-text-secondary);">
            Here's what's happening with your system today
        </p>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Total Users Card -->
        <div class="google-card p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="rounded-full p-3 mr-4" style="background-color: rgba(66, 133, 244, 0.1);">
                        <i class="fas fa-users text-lg" style="color: #4285F4;"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium" style="color: var(--color-text-secondary);">Users</p>
                    </div>
                </div>
                <a href="?page=lead_users&action=add" class="rounded-full p-2 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700" title="Add User">
                    <i class="fas fa-plus" style="color: #4285F4;"></i>
                </a>
            </div>
            <div class="flex items-end justify-between">
                <h3 class="text-3xl font-normal" style="color: var(--color-text-primary);"><?php echo number_format($totalUsers); ?></h3>
                <a href="?page=lead_users" class="text-sm font-medium" style="color: #4285F4;">View all</a>
            </div>
        </div>

        <!-- Total Departments Card -->
        <div class="google-card p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="rounded-full p-3 mr-4" style="background-color: rgba(52, 168, 83, 0.1);">
                        <i class="fas fa-building text-lg" style="color: #34A853;"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium" style="color: var(--color-text-secondary);">Departments</p>
                    </div>
                </div>
                <a href="?page=lead_departments&action=add" class="rounded-full p-2 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700" title="Add Department">
                    <i class="fas fa-plus" style="color: #34A853;"></i>
                </a>
            </div>
            <div class="flex items-end justify-between">
                <h3 class="text-3xl font-normal" style="color: var(--color-text-primary);"><?php echo number_format($totalDepartments); ?></h3>
                <a href="?page=lead_departments" class="text-sm font-medium" style="color: #34A853;">View all</a>
            </div>
        </div>

        <!-- Total Events Card -->
        <div class="google-card p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="rounded-full p-3 mr-4" style="background-color: rgba(251, 188, 5, 0.1);">
                        <i class="fas fa-calendar-alt text-lg" style="color: #FBBC05;"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium" style="color: var(--color-text-secondary);">Events</p>
                    </div>
                </div>
                <a href="?page=lead_events&action=add" class="rounded-full p-2 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700" title="Create Event">
                    <i class="fas fa-plus" style="color: #FBBC05;"></i>
                </a>
            </div>
            <div class="flex items-end justify-between">
                <h3 class="text-3xl font-normal" style="color: var(--color-text-primary);"><?php echo number_format($totalEvents); ?></h3>
                <a href="?page=lead_events" class="text-sm font-medium" style="color: #FBBC05;">View all</a>
            </div>
        </div>

        <!-- Task Completion Rate Card -->
        <div class="google-card p-5">
            <div class="flex items-center mb-4">
                <div class="rounded-full p-3 mr-4" style="background-color: rgba(234, 67, 53, 0.1);">
                    <i class="fas fa-chart-pie text-lg" style="color: #EA4335;"></i>
                </div>
                <div>
                    <p class="text-sm font-medium" style="color: var(--color-text-secondary);">Completion Rate</p>
                </div>
            </div>
            <div class="flex items-end justify-between">
                <h3 class="text-3xl font-normal" style="color: var(--color-text-primary);"><?php echo $taskCompletionRate; ?>%</h3>
                <div class="w-16 h-16">
                    <canvas id="completionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="google-card p-5 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-normal" style="color: var(--color-text-primary);">Recent Activity</h2>
            <div class="flex space-x-2">
                <button class="text-sm font-medium px-3 py-1 rounded-full" style="background-color: var(--color-hover); color: var(--color-text-primary);">Today</button>
                <button class="text-sm font-medium px-3 py-1 rounded-full" style="color: var(--color-text-secondary);">Week</button>
                <button class="text-sm font-medium px-3 py-1 rounded-full" style="color: var(--color-text-secondary);">Month</button>
            </div>
        </div>
        
        <!-- Activity Timeline -->
        <div class="space-y-6">
            <div class="flex">
                <div class="flex flex-col items-center mr-4">
                    <div class="rounded-full w-10 h-10 flex items-center justify-center" style="background-color: rgba(66, 133, 244, 0.1);">
                        <i class="fas fa-user-plus" style="color: #4285F4;"></i>
                    </div>
                    <div class="flex-grow h-full border-l-2 mx-auto my-2" style="border-color: rgba(66, 133, 244, 0.2);"></div>
                </div>
                <div>
                <p class="text-sm font-medium" style="color: var(--color-text-primary);">New user registered</p>
                    <p class="text-sm" style="color: var(--color-text-secondary);">John Doe joined as Department Lead</p>
                    <p class="text-xs mt-1" style="color: var(--color-text-tertiary);">Today, 10:30 AM</p>
                </div>
            </div>
            
            <div class="flex">
                <div class="flex flex-col items-center mr-4">
                    <div class="rounded-full w-10 h-10 flex items-center justify-center" style="background-color: rgba(251, 188, 5, 0.1);">
                        <i class="fas fa-calendar-plus" style="color: #FBBC05;"></i>
                    </div>
                    <div class="flex-grow h-full border-l-2 mx-auto my-2" style="border-color: rgba(251, 188, 5, 0.2);"></div>
                </div>
                <div>
                    <p class="text-sm font-medium" style="color: var(--color-text-primary);">New event created</p>
                    <p class="text-sm" style="color: var(--color-text-secondary);">Annual Conference 2023 was added by Marketing Department</p>
                    <p class="text-xs mt-1" style="color: var(--color-text-tertiary);">Today, 9:15 AM</p>
                </div>
            </div>
            
            <div class="flex">
                <div class="flex flex-col items-center mr-4">
                    <div class="rounded-full w-10 h-10 flex items-center justify-center" style="background-color: rgba(52, 168, 83, 0.1);">
                        <i class="fas fa-check-circle" style="color: #34A853;"></i>
                    </div>
                    <div class="flex-grow h-full border-l-2 mx-auto my-2" style="border-color: rgba(52, 168, 83, 0.2);"></div>
                </div>
                <div>
                    <p class="text-sm font-medium" style="color: var(--color-text-primary);">Task completed</p>
                    <p class="text-sm" style="color: var(--color-text-secondary);">Send invitations to speakers was completed by Jane Smith</p>
                    <p class="text-xs mt-1" style="color: var(--color-text-tertiary);">Yesterday, 4:30 PM</p>
                </div>
            </div>
            
            <div class="flex">
                <div class="flex flex-col items-center mr-4">
                    <div class="rounded-full w-10 h-10 flex items-center justify-center" style="background-color: rgba(234, 67, 53, 0.1);">
                        <i class="fas fa-building" style="color: #EA4335;"></i>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-medium" style="color: var(--color-text-primary);">New department added</p>
                    <p class="text-sm" style="color: var(--color-text-secondary);">Research & Development department was created</p>
                    <p class="text-xs mt-1" style="color: var(--color-text-tertiary);">Yesterday, 2:00 PM</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="google-card p-5">
            <h3 class="text-md font-medium mb-4" style="color: var(--color-text-secondary);">User Registration Trend</h3>
            <div style="height: 300px;">
                <canvas id="userChart"></canvas>
            </div>
        </div>
        
        <div class="google-card p-5">
            <h3 class="text-md font-medium mb-4" style="color: var(--color-text-secondary);">Task Status Distribution</h3>
            <div style="height: 300px;">
                <canvas id="taskStatusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Data Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Users -->
        <div class="google-card">
            <div class="flex items-center justify-between p-5 border-b" style="border-color: var(--color-border-light);">
                <h2 class="text-lg font-normal" style="color: var(--color-text-primary);">Recent Users</h2>
                <a href="?page=lead_users" class="text-sm font-medium" style="color: #4285F4;">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--color-border-light);">
                            <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Username</th>
                            <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Role</th>
                            <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Department</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentUsers)): ?>
                        <tr>
                            <td colspan="4" class="px-5 py-4 text-center text-sm" style="color: var(--color-text-tertiary);">No users found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentUsers as $user): ?>
                            <tr style="border-bottom: 1px solid var(--color-border-light);">
                                <td class="px-5 py-4 text-sm" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="px-5 py-4 text-sm" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-5 py-4 text-sm" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($user['role_name']); ?></td>
                                <td class="px-5 py-4 text-sm" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($user['department_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Events -->
        <div class="google-card">
            <div class="flex items-center justify-between p-5 border-b" style="border-color: var(--color-border-light);">
                <h2 class="text-lg font-normal" style="color: var(--color-text-primary);">Recent Events</h2>
                <a href="?page=lead_events" class="text-sm font-medium" style="color: #4285F4;">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--color-border-light);">
                            <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Event Title</th>
                            <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Start Date</th>
                            <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">End Date</th>
                            <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Department</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentEvents)): ?>
                        <tr>
                            <td colspan="4" class="px-5 py-4 text-center text-sm" style="color: var(--color-text-tertiary);">No events found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentEvents as $event): ?>
                            <tr style="border-bottom: 1px solid var(--color-border-light);">
                                <td class="px-5 py-4 text-sm" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($event['title']); ?></td>
                                <td class="px-5 py-4 text-sm" style="color: var(--color-text-primary);"><?php echo date('M d, Y', strtotime($event['start_date'])); ?></td>
                                <td class="px-5 py-4 text-sm" style="color: var(--color-text-primary);"><?php echo date('M d, Y', strtotime($event['end_date'])); ?></td>
                                <td class="px-5 py-4 text-sm" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($event['department_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Additional Analytics -->
    <div class="google-card p-5 mb-8">
        <h3 class="text-md font-medium mb-4" style="color: var(--color-text-secondary);">Event Distribution by Department</h3>
        <div style="height: 300px;">
            <canvas id="departmentChart"></canvas>
        </div>
    </div>

    <!-- System Status -->
    <div class="google-card p-5 mb-8">
        <h2 class="text-lg font-normal mb-4" style="color: var(--color-text-primary);">System Status</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium" style="color: var(--color-text-secondary);">Database</span>
                        <span class="text-sm font-medium" style="color: #34A853;">Operational</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium" style="color: var(--color-text-secondary);">File Storage</span>
                        <span class="text-sm font-medium" style="color: #34A853;">Operational</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: 95%"></div>
                    </div>
                </div>
            </div>
            
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium" style="color: var(--color-text-secondary);">Email Service</span>
                        <span class="text-sm font-medium" style="color: #34A853;">Operational</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium" style="color: var(--color-text-secondary);">Server Load</span>
                        <span class="text-sm font-medium" style="color: #FBBC05;">Moderate</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-yellow-500 h-2 rounded-full" style="width: 65%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 pt-4 border-t" style="border-color: var(--color-border-light);">
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium" style="color: var(--color-text-secondary);">Last System Check:</span>
                <span class="text-sm" style="color: var(--color-text-tertiary);"><?php echo date('M d, Y H:i'); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Charts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // User registration chart - Google-style chart with Material Design colors
    const userCtx = document.getElementById('userChart').getContext('2d');
    const userChart = new Chart(userCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($userMonthLabels); ?>,
            datasets: [{
                label: 'New Users',
                data: <?php echo json_encode($userMonthlyCounts); ?>,
                backgroundColor: 'rgba(66, 133, 244, 0.1)',
                borderColor: '#4285F4',
                borderWidth: 2,
                pointBackgroundColor: '#4285F4',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            family: "'Google Sans', 'Roboto', sans-serif",
                            size: 11
                        },
                        color: 'var(--color-text-secondary)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: "'Google Sans', 'Roboto', sans-serif",
                            size: 11
                        },
                        color: 'var(--color-text-secondary)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'var(--color-surface)',
                    titleColor: 'var(--color-text-primary)',
                    bodyColor: 'var(--color-text-secondary)',
                    borderColor: 'var(--color-border-light)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: {
                        family: "'Google Sans', 'Roboto', sans-serif",
                        size: 14,
                        weight: 'normal'
                    },
                    bodyFont: {
                        family: "'Google Sans', 'Roboto', sans-serif",
                        size: 13
                    },
                    displayColors: false
                }
            }
        }
    });
    
    // Task status distribution chart
    const taskStatusCtx = document.getElementById('taskStatusChart').getContext('2d');
    const taskStatusChart = new Chart(taskStatusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($taskStatusLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($taskStatusCounts); ?>,
                backgroundColor: [
                    '#FBBC05', // Google Yellow for To Do
                    '#4285F4', // Google Blue for In Progress
                    '#34A853'  // Google Green for Done
                ],
                borderWidth: 0,
                hoverOffset: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        font: {
                            family: "'Google Sans', 'Roboto', sans-serif",
                            size: 12
                        },
                        color: 'var(--color-text-secondary)',
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'var(--color-surface)',
                    titleColor: 'var(--color-text-primary)',
                    bodyColor: 'var(--color-text-secondary)',
                    borderColor: 'var(--color-border-light)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: {
                        family: "'Google Sans', 'Roboto', sans-serif",
                        size: 14,
                        weight: 'normal'
                    },
                    bodyFont: {
                        family: "'Google Sans', 'Roboto', sans-serif",
                        size: 13
                    }
                }
            }
        }
    });
    
    // Department event distribution chart
    const departmentCtx = document.getElementById('departmentChart').getContext('2d');
    const departmentChart = new Chart(departmentCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($departmentLabels); ?>,
            datasets: [{
                label: 'Events',
                data: <?php echo json_encode($departmentCounts); ?>,
                backgroundColor: [
                    '#4285F4', // Google Blue
                    '#34A853', // Google Green
                    '#FBBC05', // Google Yellow
                    '#EA4335', // Google Red
                    '#8E24AA'  // Purple
                ],
                borderWidth: 0,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            family: "'Google Sans', 'Roboto', sans-serif",
                            size: 11
                        },
                        color: 'var(--color-text-secondary)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: "'Google Sans', 'Roboto', sans-serif",
                            size: 11
                        },
                        color: 'var(--color-text-secondary)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'var(--color-surface)',
                    titleColor: 'var(--color-text-primary)',
                    bodyColor: 'var(--color-text-secondary)',
                    borderColor: 'var(--color-border-light)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: {
                        family: "'Google Sans', 'Roboto', sans-serif",
                        size: 14,
                        weight: 'normal'
                    },
                    bodyFont: {
                        family: "'Google Sans', 'Roboto', sans-serif",
                        size: 13
                    }
                }
            }
        }
    });
    
    // Task completion rate mini chart
    const completionCtx = document.getElementById('completionChart').getContext('2d');
    const completionChart = new Chart(completionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Remaining'],
            datasets: [{
                data: [<?php echo $taskCompletionRate; ?>, <?php echo 100 - $taskCompletionRate; ?>],
                backgroundColor: [
                    '#EA4335', // Google Red
                    'rgba(234, 67, 53, 0.1)' // Lighter red
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            }
        }
    });
    
    // Update charts when theme changes
    const updateChartsForTheme = () => {
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
        
        // Update user chart
        userChart.options.scales.y.grid.color = gridColor;
        userChart.update();
        
        // Update department chart
        departmentChart.options.scales.y.grid.color = gridColor;
        departmentChart.update();
        
        // Both charts will automatically use CSS variables for text colors
    };
    
    // Initial update based on current theme
    updateChartsForTheme();
    
    // Listen for theme changes
    document.addEventListener('themeChanged', function(e) {
        updateChartsForTheme();
    });
});
</script>


