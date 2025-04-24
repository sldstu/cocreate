<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Get department information and statistics
try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get current user's department
    $query = "SELECT d.* FROM departments d 
              JOIN users u ON d.department_id = u.department_id 
              WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get department members count
    $query = "SELECT COUNT(*) as count FROM users WHERE department_id = :department_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':department_id', $department['department_id']);
    $stmt->execute();
    $totalMembers = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Get department events count
    $query = "SELECT COUNT(*) as count FROM events WHERE department_id = :department_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':department_id', $department['department_id']);
    $stmt->execute();
    $totalEvents = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Get department tasks count
    $query = "SELECT COUNT(*) as count FROM tasks WHERE department_id = :department_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':department_id', $department['department_id']);
    $stmt->execute();
    $totalTasks = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Get completed tasks count
    $query = "SELECT COUNT(*) as count FROM tasks WHERE department_id = :department_id AND status = 'completed'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':department_id', $department['department_id']);
    $stmt->execute();
    $completedTasks = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Calculate task completion rate
    $taskCompletionRate = ($totalTasks > 0) ? round(($completedTasks / $totalTasks) * 100) : 0;
} catch (PDOException $e) {
    // Handle error
    error_log("Database error: " . $e->getMessage());
    $department = ['name' => 'Unknown Department'];
    $totalMembers = 0;
    $totalEvents = 0;
    $totalTasks = 0;
    $taskCompletionRate = 0;
}

// Get upcoming events - COPIED DIRECTLY FROM MEMBER DASHBOARD
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get upcoming events
    $query = "SELECT e.event_id, e.title, e.start_date, e.end_date, e.location, d.name as department_name 
              FROM events e 
              LEFT JOIN departments d ON e.department_id = d.department_id 
              WHERE e.start_date >= CURDATE() AND e.visibility = 'public'
              ORDER BY e.start_date ASC LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Handle error
    error_log("Database error: " . $e->getMessage());
    $upcomingEvents = [];
}

// Get recent tasks (last 5)
try {
    $query = "SELECT t.task_id, t.title, t.description, t.due_date, t.status, u.username as assigned_to 
              FROM tasks t
              LEFT JOIN task_assignments ta ON t.task_id = ta.task_id
              LEFT JOIN users u ON ta.user_id = u.user_id
              WHERE t.department_id = :department_id 
              ORDER BY t.created_at DESC LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':department_id', $department['department_id']);
    $stmt->execute();
    $recentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $recentTasks = [];
}

// Get department members
try {
    $query = "SELECT u.user_id, u.username, u.email, r.name as role_name 
              FROM users u 
              JOIN roles r ON u.role_id = r.role_id
              WHERE u.department_id = :department_id 
              ORDER BY u.username ASC LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':department_id', $department['department_id']);
    $stmt->execute();
    $departmentMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $departmentMembers = [];
}

// Get task status distribution
try {
    $query = "SELECT status, COUNT(*) as count FROM tasks 
              WHERE department_id = :department_id 
              GROUP BY status";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':department_id', $department['department_id']);
    $stmt->execute();
    $taskStatusData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format task status data for chart
    $taskStatusLabels = [];
    $taskStatusCounts = [];
    
    foreach ($taskStatusData as $status) {
        $taskStatusLabels[] = ucfirst(str_replace('_', ' ', $status['status']));
        $taskStatusCounts[] = $status['count'];
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $taskStatusLabels = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
    $taskStatusCounts = [0, 0, 0, 0];
}

// Get monthly event counts for the past 6 months
try {
    $query = "SELECT DATE_FORMAT(start_date, '%Y-%m') as month, COUNT(*) as count 
              FROM events 
              WHERE department_id = :department_id 
              AND start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
              GROUP BY DATE_FORMAT(start_date, '%Y-%m') 
              ORDER BY month ASC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':department_id', $department['department_id']);
    $stmt->execute();
    $eventMonthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format monthly event data for chart
    $eventMonthLabels = [];
    $eventMonthlyCounts = [];
    
    // Initialize with past 6 months
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $eventMonthLabels[] = date('M Y', strtotime($month));
        $eventMonthlyCounts[] = 0; // Default to 0
    }
    
    // Fill in actual data
    foreach ($eventMonthlyData as $monthData) {
        $monthIndex = array_search(date('M Y', strtotime($monthData['month'])), $eventMonthLabels);
        if ($monthIndex !== false) {
            $eventMonthlyCounts[$monthIndex] = (int)$monthData['count'];
        }
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $eventMonthLabels = [];
    $eventMonthlyCounts = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $eventMonthLabels[] = date('M Y', strtotime("-$i months"));
        $eventMonthlyCounts[] = 0;
    }
}
?>

<div class="p-4 sm:p-6 lg:p-8">
    <!-- Welcome Section -->
    <div class="mb-6">
        <h1 class="text-2xl font-normal mb-1" style="color: var(--color-text-primary);">
            <?php echo htmlspecialchars($department['name']); ?> Dashboard
        </h1>
        <p class="text-sm" style="color: var(--color-text-secondary);">
            Welcome back, <?php echo htmlspecialchars($currentUser['username']); ?>. Here's an overview of your department's activities.
        </p>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Department Members Card -->
        <div class="google-card p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="rounded-full p-3 mr-4" style="background-color: rgba(66, 133, 244, 0.1);">
                        <i class="fas fa-users text-lg" style="color: #4285F4;"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium" style="color: var(--color-text-secondary);">Members</p>
                    </div>
                </div>
                <a href="?page=lead_members&action=invite" class="rounded-full p-2 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700" title="Add Member">
                    <i class="fas fa-plus" style="color: #4285F4;"></i>
                </a>
            </div>
            <div class="flex items-end justify-between">
                <h3 class="text-3xl font-normal" style="color: var(--color-text-primary);"><?php echo number_format($totalMembers); ?></h3>
                <a href="?page=lead_members" class="text-sm font-medium" style="color: #4285F4;">View all</a>
            </div>
        </div>

        <!-- Department Events Card -->
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
                <!-- <a href="?page=lead_events&action=add" class="rounded-full p-2 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700" title="Create Event">
                    <i class="fas fa-plus" style="color: #FBBC05;"></i>
                </a> -->
            </div>
            <div class="flex items-end justify-between">
                <h3 class="text-3xl font-normal" style="color: var(--color-text-primary);"><?php echo number_format($totalEvents); ?></h3>
                <a href="?page=lead_events" class="text-sm font-medium" style="color: #FBBC05;">View all</a>
            </div>
        </div>

        <!-- Department Tasks Card -->
        <div class="google-card p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="rounded-full p-3 mr-4" style="background-color: rgba(52, 168, 83, 0.1);">
                        <i class="fas fa-tasks text-lg" style="color: #34A853;"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium" style="color: var(--color-text-secondary);">Tasks</p>
                    </div>
                </div>
                <a href="?page=lead_tasks&action=add" class="rounded-full p-2 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700" title="Create Task">
                    <i class="fas fa-plus" style="color: #34A853;"></i>
                </a>
            </div>
            <div class="flex items-end justify-between">
                <h3 class="text-3xl font-normal" style="color: var(--color-text-primary);"><?php echo number_format($totalTasks); ?></h3>
                <a href="?page=lead_tasks" class="text-sm font-medium" style="color: #34A853;">View all</a>
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

    <!-- UPCOMING EVENTS SECTION - COPIED DIRECTLY FROM MEMBER DASHBOARD -->
    <div class="google-card p-5 mb-8">
        <h2 class="text-lg font-normal mb-4" style="color: var(--color-text-primary);">Upcoming Events</h2>
        
        <?php if (empty($upcomingEvents)): ?>
            <p class="text-center py-4" style="color: var(--color-text-secondary);">No upcoming events at this time.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($upcomingEvents as $event): ?>
                    <div class="p-4 border rounded-lg" style="border-color: var(--color-border-light);">
                        <div class="flex items-start">
                            <div class="rounded-full p-3 mr-4" style="background-color: rgba(66, 133, 244, 0.1);">
                            <i class="fas fa-calendar-alt text-lg" style="color: #4285F4;"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-md font-medium" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p class="text-sm mt-1" style="color: var(--color-text-secondary);">
                                    <i class="fas fa-clock mr-1"></i> 
                                    <?php echo date('M d, Y h:i A', strtotime($event['start_date'])); ?> - 
                                    <?php echo date('M d, Y h:i A', strtotime($event['end_date'])); ?>
                                </p>
                                <?php if (!empty($event['location'])): ?>
                                <p class="text-sm mt-1" style="color: var(--color-text-secondary);">
                                    <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($event['location']); ?>
                                </p>
                                <?php endif; ?>
                                <p class="text-sm mt-1" style="color: var(--color-text-tertiary);">
                                    Organized by: <?php echo htmlspecialchars($event['department_name'] ?? 'GDG on Campus WMSU'); ?>
                                </p>
                                <div class="mt-3">
                                    <a href="?page=lead_events&event_id=<?php echo $event['event_id']; ?>" class="text-sm font-medium" style="color: #4285F4;">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-4 text-center">
                <a href="?page=lead_events" class="text-sm font-medium py-2 px-4 rounded-full" style="background-color: var(--color-hover); color: var(--color-text-primary);">
                    View All Events
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Department Members and Tasks -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Department Members -->
        <div class="google-card">
            <div class="flex items-center justify-between p-5 border-b" style="border-color: var(--color-border-light);">
                <h2 class="text-lg font-normal" style="color: var(--color-text-primary);">Department Members</h2>
                <a href="?page=lead_members" class="text-sm font-medium" style="color: #4285F4;">View all</a>
            </div>
            
            <?php if (empty($departmentMembers)): ?>
            <div class="p-5 text-center" style="color: var(--color-text-tertiary);">
                <i class="fas fa-users text-4xl mb-2 opacity-30"></i>
                <p>No members found</p>
                <a href="?page=lead_members&action=invite" class="inline-block mt-3 text-sm font-medium px-4 py-2 rounded-full" style="background-color: var(--color-hover); color: var(--color-text-primary);">
                    Invite Members
                </a>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--color-border-light);">
                            <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Name</th>
                            <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departmentMembers as $member): ?>
                        <tr style="border-bottom: 1px solid var(--color-border-light);">
                            <td class="px-5 py-4 text-sm" style="color: var(--color-text-primary);">
                                <?php echo htmlspecialchars($member['username']); ?>
                            </td>
                            <td class="px-5 py-4 text-sm" style="color: var(--color-text-primary);">
                                <?php echo htmlspecialchars($member['email']); ?>
                            </td>
                            <td class="px-5 py-4 text-sm" style="color: var(--color-text-primary);">
                                <?php echo htmlspecialchars($member['role_name']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Tasks -->
        <div class="google-card">
            <div class="flex items-center justify-between p-5 border-b" style="border-color: var(--color-border-light);">
                <h2 class="text-lg font-normal" style="color: var(--color-text-primary);">Recent Tasks</h2>
                <a href="?page=lead_tasks" class="text-sm font-medium" style="color: #4285F4;">View all</a>
            </div>
            
            <?php if (empty($recentTasks)): ?>
            <div class="p-5 text-center" style="color: var(--color-text-tertiary);">
                <i class="fas fa-tasks text-4xl mb-2 opacity-30"></i>
                <p>No tasks found</p>
                <a href="?page=lead_tasks&action=add" class="inline-block mt-3 text-sm font-medium px-4 py-2 rounded-full" style="background-color: var(--color-hover); color: var(--color-text-primary);">
                    Create Task
                </a>
            </div>
            <?php else: ?>
            <div class="divide-y" style="border-color: var(--color-border-light);">
                <?php foreach ($recentTasks as $task): ?>
                <div class="p-5">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mr-4">
                            <div class="w-10 h-10 flex items-center justify-center rounded-full" 
                                 style="background-color: <?php 
                                    echo $task['status'] == 'completed' ? 'rgba(52, 168, 83, 0.1)' : 
                                         ($task['status'] == 'in_progress' ? 'rgba(66, 133, 244, 0.1)' : 
                                         'rgba(251, 188, 5, 0.1)'); 
                                    ?>;">
                                <i class="<?php 
                                    echo $task['status'] == 'completed' ? 'fas fa-check' : 
                                         ($task['status'] == 'in_progress' ? 'fas fa-spinner' : 
                                         'fas fa-clock'); 
                                    ?>" 
                                   style="color: <?php 
                                    echo $task['status'] == 'completed' ? '#34A853' : 
                                         ($task['status'] == 'in_progress' ? '#4285F4' : 
                                         '#FBBC05'); 
                                    ?>;"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-medium mb-1" style="color: var(--color-text-primary);">
                                <?php echo htmlspecialchars($task['title']); ?>
                            </h3>
                            <?php if (!empty($task['due_date'])): ?>
                            <p class="text-sm mb-1" style="color: var(--color-text-secondary);">
                                <i class="fas fa-calendar-alt mr-1"></i> 
                                Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                            </p>
                            <?php endif; ?>
                            <?php if (!empty($task['assigned_to'])): ?>
                            <p class="text-sm" style="color: var(--color-text-secondary);">
                                <i class="fas fa-user mr-1"></i> 
                                Assigned to: <?php echo htmlspecialchars($task['assigned_to']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="ml-4">
                            <span class="px-2 py-1 text-xs font-medium rounded-full" 
                                  style="background-color: <?php 
                                    echo $task['status'] == 'completed' ? 'rgba(52, 168, 83, 0.1)' : 
                                         ($task['status'] == 'in_progress' ? 'rgba(66, 133, 244, 0.1)' : 
                                         'rgba(251, 188, 5, 0.1)'); 
                                    ?>; 
                                    color: <?php 
                                    echo $task['status'] == 'completed' ? '#34A853' : 
                                         ($task['status'] == 'in_progress' ? '#4285F4' : 
                                         '#FBBC05'); 
                                    ?>;">
                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Task Status Distribution -->
        <div class="google-card p-5">
            <h2 class="text-lg font-normal mb-4" style="color: var(--color-text-primary);">Task Status Distribution</h2>
            <div style="height: 250px;">
                <canvas id="taskStatusChart"></canvas>
            </div>
        </div>
        
        <!-- Event Trend Chart -->
        <div class="google-card p-5">
            <h2 class="text-lg font-normal mb-4" style="color: var(--color-text-primary);">Event Trend (Last 6 Months)</h2>
            <div style="height: 250px;">
                <canvas id="eventTrendChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Charts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Task completion doughnut chart
    const completionCtx = document.getElementById('completionChart').getContext('2d');
    const completionChart = new Chart(completionCtx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [<?php echo $taskCompletionRate; ?>, <?php echo 100 - $taskCompletionRate; ?>],
                backgroundColor: [
                    '#34A853', // Google Green
                    '#ECEFF1'  // Light Gray
                ],
                borderWidth: 0,
                cutout: '80%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
    
    // Task status distribution chart
    const taskStatusCtx = document.getElementById('taskStatusChart').getContext('2d');
    const taskStatusChart = new Chart(taskStatusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($taskStatusLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($taskStatusCounts); ?>,
                backgroundColor: [
                    '#FBBC05', // Google Yellow - Pending
                    '#4285F4', // Google Blue - In Progress
                    '#34A853', // Google Green - Completed
                    '#EA4335'  // Google Red - Cancelled
                ],
                borderWidth: 0,
                hoverOffset: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
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
    
    // Event trend line chart
    const eventTrendCtx = document.getElementById('eventTrendChart').getContext('2d');
    const eventTrendChart = new Chart(eventTrendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($eventMonthLabels); ?>,
            datasets: [{
                label: 'Events',
                data: <?php echo json_encode($eventMonthlyCounts); ?>,
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
                        color: 'var(--color-text-secondary)',
                        precision: 0
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
    
    // Update charts when theme changes
    const updateChartsForTheme = () => {
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
        
        // Update event trend chart
        eventTrendChart.options.scales.y.grid.color = gridColor;
        eventTrendChart.update();
        
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

