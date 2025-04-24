<?php
// Ensure this file is included and not accessed directly
defined('APP_NAME') or die('Direct access is not permitted');

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Include events manager class
require_once 'views/lead/php/events.class.php';
$eventsManager = new EventsManager($conn);

// Get action
$action = isset($_GET['action']) ? $_GET['action'] : '';
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$view = isset($_GET['view']) ? $_GET['view'] : 'card'; // Default view is card

// Handle different actions
switch ($action) {
    case 'add':
        // Display add event form
        $pageTitle = 'Create New Event';
        $isEditing = false;
        $event = []; // Empty event for new form
        $departments = $eventsManager->getDepartments();
        $eventTypes = $eventsManager->getEventTypes();
        include 'views/lead/templates/event_form.php';
        break;
        
    case 'edit':
        // Display edit event form
        if ($event_id <= 0) {
            echo '<div class="alert alert-danger">Invalid event ID.</div>';
            exit;
        }
        
        $event = $eventsManager->getEventById($event_id);
        if (!$event) {
            echo '<div class="alert alert-danger">Event not found.</div>';
            exit;
        }
        
        $pageTitle = 'Edit Event: ' . $event['title'];
        $isEditing = true;
        $departments = $eventsManager->getDepartments();
        $eventTypes = $eventsManager->getEventTypes();
        $attachments = $eventsManager->getEventAttachments($event_id);
        
        include 'views/lead/templates/event_form.php';
        break;
        
    case 'delete':
        // Display delete confirmation
        if ($event_id <= 0) {
            echo '<div class="alert alert-danger">Invalid event ID.</div>';
            exit;
        }
        
        $event = $eventsManager->getEventById($event_id);
        if (!$event) {
            echo '<div class="alert alert-danger">Event not found.</div>';
            exit;
        }
        
        $pageTitle = 'Delete Event: ' . $event['title'];
        include 'views/lead/templates/event_delete_confirm.php';
        break;
        
    default:
        // If event_id is provided, show event details
        if ($event_id > 0) {
            $event = $eventsManager->getEventById($event_id);
            if (!$event) {
                echo '<div class="alert alert-danger">Event not found.</div>';
                exit;
            }
            
            $comments = $eventsManager->getEventComments($event_id);
            $attachments = $eventsManager->getEventAttachments($event_id);
            $rsvps = $eventsManager->getEventRSVPs($event_id);
            
            $pageTitle = 'Event Details: ' . $event['title'];
            include 'views/lead/templates/event_detail.php';
        } else {
            // Show events list
            $filter = isset($_GET['filter']) ? $_GET['filter'] : '';
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            
            // Get advanced filter parameters
            $advancedFilters = [
                'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
                'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : '',
                'type' => isset($_GET['type']) ? $_GET['type'] : '',
                'department' => isset($_GET['department']) ? $_GET['department'] : '',
                'status' => isset($_GET['status']) ? $_GET['status'] : '',
                'visibility' => isset($_GET['visibility']) ? $_GET['visibility'] : '',
            ];
            
            // Get events based on filter and search
            $events = $eventsManager->getAllEvents($filter, $search, $advancedFilters);
            $eventStats = $eventsManager->getEventStatistics();
            
            // Display events dashboard
            ?>
            <div class="mb-6">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-normal" style="color: var(--color-text-primary);">Events Management</h1>
                    <a href="?page=lead_events&action=add" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Create Event
                    </a>
                </div>
                <p class="text-sm" style="color: var(--color-text-secondary);">
                    Create and manage events for your community
                </p>
            </div>
            
            <!-- Event Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <?php
                // Define stat cards
                $statCards = [
                    [
                        'title' => 'Total Events',
                        'value' => $eventStats['total'],
                        'icon' => 'fa-calendar-alt',
                        'color' => '#4285F4',
                        'bg' => 'rgba(66, 133, 244, 0.1)'
                    ],
                    [
                        'title' => 'Upcoming Events',
                        'value' => $eventStats['upcoming'],
                        'icon' => 'fa-calendar-plus',
                        'color' => '#34A853',
                        'bg' => 'rgba(52, 168, 83, 0.1)'
                    ],
                    [
                        'title' => 'Ongoing Events',
                        'value' => $eventStats['ongoing'],
                        'icon' => 'fa-calendar-check',
                        'color' => '#FBBC05',
                        'bg' => 'rgba(251, 188, 5, 0.1)'
                    ],
                    [
                        'title' => 'Completed Events',
                        'value' => $eventStats['completed'],
                        'icon' => 'fa-calendar-times',
                        'color' => '#EA4335',
                        'bg' => 'rgba(234, 67, 53, 0.1)'
                    ]
                ];
                
                foreach ($statCards as $card) {
                    echo '<div class="google-card p-4">';
                    echo '<div class="flex items-center">';
                    echo '<div class="rounded-full p-3 mr-4" style="background-color: ' . $card['bg'] . ';">';
                    echo '<i class="fas ' . $card['icon'] . ' text-lg" style="color: ' . $card['color'] . ';"></i>';
                    echo '</div>';
                    echo '<div>';
                    echo '<div class="text-2xl font-medium" style="color: var(--color-text-primary);">' . $card['value'] . '</div>';
                    echo '<div class="text-sm" style="color: var(--color-text-secondary);">' . $card['title'] . '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
            
            <!-- View Tabs and Search -->
            <div class="google-card p-4 mb-6">
                <div class="flex flex-wrap items-center justify-between">
                    <div class="flex flex-wrap gap-3 mb-3 sm:mb-0">
                        <!-- Status dropdown filter with solid background -->
                        <div class="relative inline-block mr-2">
                            <button id="status-dropdown-btn" class="flex items-center px-3 py-2 rounded-md text-sm transition-colors duration-200 hover:bg-gray-100" style="background-color: var(--color-surface); color: var(--color-text-secondary); border: 1px solid var(--color-border-light);">
                                <i class="fas fa-tasks mr-1"></i> Status <i class="fas fa-chevron-down ml-2 text-xs"></i>
                            </button>
                            <div id="status-dropdown" class="absolute left-0 mt-2 hidden z-50 w-48 rounded-lg shadow-lg" style="background-color: var(--color-surface); border: 1px solid var(--color-border-light);">
                                <div class="p-2">
                                    <a href="?page=lead_events" class="block w-full text-left px-3 py-2 text-sm rounded-md filter-btn hover:bg-gray-100 <?php echo empty($filter) ? 'active' : ''; ?>" style="margin-bottom: 4px;">All Events</a>
                                    <a href="?page=lead_events&filter=upcoming" class="block w-full text-left px-3 py-2 text-sm rounded-md filter-btn hover:bg-gray-100 <?php echo $filter === 'upcoming' ? 'active' : ''; ?>" style="margin-bottom: 4px; color: #FBBC05;">Upcoming</a>
                                    <a href="?page=lead_events&filter=ongoing" class="block w-full text-left px-3 py-2 text-sm rounded-md filter-btn hover:bg-gray-100 <?php echo $filter === 'ongoing' ? 'active' : ''; ?>" style="margin-bottom: 4px; color: #4285F4;">Ongoing</a>
                                    <a href="?page=lead_events&filter=completed" class="block w-full text-left px-3 py-2 text-sm rounded-md filter-btn hover:bg-gray-100 <?php echo $filter === 'completed' ? 'active' : ''; ?>" style="margin-bottom: 4px; color: #34A853;">Completed</a>
                                    <a href="?page=lead_events&filter=cancelled" class="block w-full text-left px-3 py-2 text-sm rounded-md filter-btn hover:bg-gray-100 <?php echo $filter === 'cancelled' ? 'active' : ''; ?>" style="color: #EA4335;">Cancelled</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <!-- Search -->
                        <div class="relative mr-3">
                            <form method="get" action="" class="flex items-center">
                                <input type="hidden" name="page" value="lead_events">
                                <?php if (!empty($filter)): ?>
                                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                                <?php endif; ?>
                                <input type="text" name="search" id="search-events" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search events..." class="pl-10 pr-4 py-2 border rounded-md w-full sm:w-64" style="border-color: var(--color-border-light); background-color: var(--color-input-bg); color: var(--color-text-primary);">
                                <div class="absolute left-3 top-2.5">
                                    <i class="fas fa-search" style="color: var(--color-text-tertiary);"></i>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Advanced Filter Dropdown with solid background -->
                        <div class="relative mr-3">
                            <button id="filter-dropdown-btn" class="flex items-center px-3 py-2 rounded-md text-sm transition-colors duration-200 hover:bg-gray-100" style="background-color: var(--color-surface); color: var(--color-text-secondary); border: 1px solid var(--color-border-light);">
                                <i class="fas fa-filter mr-1"></i> Advanced Filters
                            </button>
                            <div id="filter-dropdown" class="absolute right-0 mt-2 hidden z-50 w-72 rounded-lg shadow-lg" style="background-color: var(--color-surface); border: 1px solid var(--color-border-light); max-height: 85vh; overflow-y: auto;">
                                <div class="p-4">
                                    <h3 class="text-sm font-medium mb-3" style="color: var(--color-text-primary);">Filter Events</h3>
                                    
                                    <!-- Date Range -->
                                    <div class="mb-4">
                                        <label class="block text-xs font-medium mb-1" style="color: var(--color-text-secondary);">Date Range</label>
                                        <div class="flex gap-2">
                                            <div class="w-1/2">
                                                <label class="text-xs mb-1 block opacity-60">From</label>
                                                <input type="date" id="filter-date-from" class="text-xs p-2 w-full rounded-md border focus:ring-2 focus:ring-blue-500" style="background-color: var(--color-input-bg); color: var(--color-text-primary); border-color: var(--color-border-light);">
                                            </div>
                                            <div class="w-1/2">
                                                <label class="text-xs mb-1 block opacity-60">To</label>
                                                <input type="date" id="filter-date-to" class="text-xs p-2 w-full rounded-md border focus:ring-2 focus:ring-blue-500" style="background-color: var(--color-input-bg); color: var(--color-text-primary); border-color: var(--color-border-light);">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Event Type -->
                                    <div class="mb-4">
                                        <label class="block text-xs font-medium mb-1" style="color: var(--color-text-secondary);">Event Type</label>
                                        <select id="filter-type" class="text-xs p-2 w-full rounded-md border focus:ring-2 focus:ring-blue-500" style="background-color: var(--color-input-bg); color: var(--color-text-primary); border-color: var(--color-border-light);">
                                            <option value="">All Types</option>
                                            <?php
                                            $eventTypes = $eventsManager->getEventTypes();
                                            foreach ($eventTypes as $type) {
                                                echo '<option value="' . $type['type_id'] . '">' . htmlspecialchars($type['name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Department -->
                                    <div class="mb-4">
                                        <label class="block text-xs font-medium mb-1" style="color: var(--color-text-secondary);">Department</label>
                                        <select id="filter-department" class="text-xs p-2 w-full rounded-md border focus:ring-2 focus:ring-blue-500" style="background-color: var(--color-input-bg); color: var(--color-text-primary); border-color: var(--color-border-light);">
                                            <option value="">All Departments</option>
                                            <?php
                                            $departments = $eventsManager->getDepartments();
                                            foreach ($departments as $dept) {
                                                echo '<option value="' . $dept['department_id'] . '">' . htmlspecialchars($dept['name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Status -->
                                    <div class="mb-4">
                                        <label class="block text-xs font-medium mb-1" style="color: var(--color-text-secondary);">Status</label>
                                        <select id="filter-status" class="text-xs p-2 w-full rounded-md border focus:ring-2 focus:ring-blue-500" style="background-color: var(--color-input-bg); color: var(--color-text-primary); border-color: var(--color-border-light);">
                                            <option value="">All Statuses</option>
                                            <option value="upcoming" style="color: #34A853;">Upcoming</option>
                                            <option value="ongoing" style="color: #4285F4;">Ongoing</option>
                                            <option value="completed" style="color: #EA4335;">Completed</option>
                                            <option value="cancelled" style="color: #757575;">Cancelled</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Visibility -->
                                    <div class="mb-4">
                                        <label class="block text-xs font-medium mb-1" style="color: var(--color-text-secondary);">Visibility</label>
                                        <select id="filter-visibility" class="text-xs p-2 w-full rounded-md border focus:ring-2 focus:ring-blue-500" style="background-color: var(--color-input-bg); color: var(--color-text-primary); border-color: var(--color-border-light);">
                                            <option value="">All Visibilities</option>
                                            <option value="draft" style="color: #FBBC05;">Draft</option>
                                            <option value="private" style="color: #757575;">Private</option>
                                            <option value="unlisted" style="color: #673AB7;">Unlisted</option>
                                            <option value="public" style="color: #34A853;">Public</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Apply Filter Button -->
                                    <div class="flex justify-between mt-5">
                                        <button id="reset-filters-btn" class="text-xs px-3 py-2 rounded-md hover:bg-gray-100 transition-colors duration-200 font-medium" style="color: var(--color-text-tertiary);">
                                            Reset Filters
                                        </button>
                                        <button id="apply-filters-btn" class="text-xs px-4 py-2 rounded-md text-white transition-colors duration-200 hover:bg-blue-700 font-medium" style="background-color: var(--color-primary);">
                                            Apply Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- View Selector -->
                        <div class="flex space-x-2">
                            <a href="?page=lead_events&view=card" class="px-3 py-2 rounded-md text-sm <?php echo $view === 'card' ? 'bg-blue-500 text-white' : ''; ?>" style="<?php echo $view !== 'card' ? 'color: var(--color-text-secondary);' : ''; ?>">
                                <i class="fas fa-th-large mr-1"></i> Card
                            </a>
                            <a href="?page=lead_events&view=list" class="px-3 py-2 rounded-md text-sm <?php echo $view === 'list' ? 'bg-blue-500 text-white' : ''; ?>" style="<?php echo $view !== 'list' ? 'color: var(--color-text-secondary);' : ''; ?>">
                                <i class="fas fa-list mr-1"></i> List
                            </a>
                            <a href="?page=lead_events&view=calendar" class="px-3 py-2 rounded-md text-sm <?php echo $view === 'calendar' ? 'bg-blue-500 text-white' : ''; ?>" style="<?php echo $view !== 'calendar' ? 'color: var(--color-text-secondary);' : ''; ?>">
                                <i class="fas fa-calendar-alt mr-1"></i> Calendar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Events List -->
            <?php if (empty($events)): ?>
            <div class="text-center py-8">
                <div class="rounded-full mx-auto p-4 mb-4" style="background-color: rgba(66, 133, 244, 0.1); width: fit-content;">
                    <i class="fas fa-calendar-alt text-2xl" style="color: #4285F4;"></i>
                </div>
                <h3 class="text-lg font-medium mb-2" style="color: var(--color-text-primary);">No Events Found</h3>
                <p class="text-sm mb-4" style="color: var(--color-text-secondary);">You haven't created any events yet.</p>
                <a href="?page=lead_events&action=add" class="btn-primary py-2 px-4 rounded-md text-sm font-medium">
                    Create Your First Event
                </a>
            </div>
            <?php else: ?>
                <?php if ($view === 'calendar'): ?>
                <!-- Calendar View -->
                <div class="google-card p-5">
                    <!-- Include FullCalendar CSS and JS -->
                    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
                    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
                    <script src="https://unpkg.com/@popperjs/core@2"></script>
                    <script src="https://unpkg.com/tippy.js@6"></script>
                    
                    <!-- Calendar container -->
                    <div id="events-calendar" data-events='<?php echo json_encode($events); ?>' style="height: 700px;"></div>
                </div>
                <?php elseif ($view === 'list'): ?>
                <!-- List View -->
                <div class="google-card overflow-hidden">
                    <table class="min-w-full">
                        <thead>
                            <tr style="background-color: var(--color-hover);">
                                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Event</th>
                                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Date & Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Location</th>
                                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium" style="color: var(--color-text-secondary);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): 
                                // Determine status colors
                                $statusBg = '';
                                $statusColor = '';
                                switch ($event['status']) {
                                    case 'upcoming':
                                        $statusBg = 'rgba(52, 168, 83, 0.1)';
                                        $statusColor = '#34A853';
                                        break;
                                    case 'ongoing':
                                        $statusBg = 'rgba(66, 133, 244, 0.1)';
                                        $statusColor = '#4285F4';
                                        break;
                                    case 'completed':
                                        $statusBg = 'rgba(234, 67, 53, 0.1)';
                                        $statusColor = '#EA4335';
                                        break;
                                    default:
                                        $statusBg = 'rgba(251, 188, 5, 0.1)';
                                        $statusColor = '#FBBC05';
                                }
                            ?>
                            <tr class="border-t event-row" data-status="<?php echo $event['status']; ?>" style="border-color: var(--color-border-light);">
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <?php if (!empty($event['featured_image'])): ?>
                                        <div class="w-10 h-10 rounded-md overflow-hidden mr-3">
                                            <img src="<?php echo htmlspecialchars($event['featured_image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="w-full h-full object-cover">
                                        </div>
                                        <?php else: ?>
                                        <div class="w-10 h-10 rounded-md mr-3 flex items-center justify-center" style="background-color: <?php echo htmlspecialchars($event['type_color']); ?>;">
                                            <i class="fas fa-calendar-alt text-white"></i>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div>
                                            <div class="font-medium text-sm event-title" style="color: var(--color-text-primary);">
                                                <?php echo htmlspecialchars($event['title']); ?>
                                            </div>
                                            <div class="text-xs" style="color: var(--color-text-tertiary);">
                                                <?php echo htmlspecialchars($event['type_name']); ?> • 
                                                <?php echo htmlspecialchars($event['department_name'] ?? 'No Department'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm event-description" style="color: var(--color-text-secondary);">
                                        <?php echo date('M d, Y', strtotime($event['start_date'])); ?>
                                        <?php if (date('Y-m-d', strtotime($event['start_date'])) != date('Y-m-d', strtotime($event['end_date']))): ?>
                                        - <?php echo date('M d, Y', strtotime($event['end_date'])); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs" style="color: var(--color-text-tertiary);">
                                        <?php echo date('h:i A', strtotime($event['start_date'])); ?> - 
                                        <?php echo date('h:i A', strtotime($event['end_date'])); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm" style="color: var(--color-text-secondary);">
                                        <?php echo !empty($event['location']) ? htmlspecialchars($event['location']) : 'No location set'; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full" 
                                          style="background-color: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>;">
                                        <?php echo ucfirst($event['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-3">
                                        <a href="?page=lead_events&event_id=<?php echo $event['event_id']; ?>" class="text-sm" style="color: #4285F4;" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?page=lead_events&action=edit&event_id=<?php echo $event['event_id']; ?>" class="text-sm" style="color: #FBBC05;" title="Edit Event">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0);" class="text-sm delete-event" data-id="<?php echo $event['event_id']; ?>" style="color: #EA4335;" title="Delete Event">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <!-- Card View -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($events as $event): 
                        // Determine status colors
                        $statusBg = '';
                        $statusColor = '';
                        switch ($event['status']) {
                            case 'upcoming':
                                $statusBg = 'rgba(52, 168, 83, 0.1)';
                                $statusColor = '#34A853';
                                break;
                            case 'ongoing':
                                $statusBg = 'rgba(66, 133, 244, 0.1)';
                                $statusColor = '#4285F4';
                                break;
                            case 'completed':
                                $statusBg = 'rgba(234, 67, 53, 0.1)';
                                $statusColor = '#EA4335';
                                break;
                            default:
                                $statusBg = 'rgba(251, 188, 5, 0.1)';
                                $statusColor = '#FBBC05';
                        }
                    ?>
                    <div class="google-card overflow-hidden event-card" data-status="<?php echo $event['status']; ?>">
                        <?php if (!empty($event['featured_image'])): ?>
                        <div class="h-40 overflow-hidden">
                            <img src="<?php echo htmlspecialchars($event['featured_image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="w-full h-full object-cover">
                        </div>
                        <?php endif; ?>
                        
                        <div class="p-4">
                            <div class="flex items-center mb-2">
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full mr-2" style="background-color: <?php echo htmlspecialchars($event['type_color']); ?>; color: white;">
                                    <?php echo htmlspecialchars($event['type_name']); ?>
                                </span>
                                <span class="text-xs" style="color: var(--color-text-tertiary);">
                                    <?php echo htmlspecialchars($event['department_name'] ?? 'GDG on Campus WMSU'); ?>
                                </span>
                            </div>
                            
                            <h3 class="text-md font-medium mb-2 event-title" style="color: var(--color-text-primary);"><?php echo htmlspecialchars($event['title']); ?></h3>
                            
                            <div class="mb-3 event-description">
                                <p class="text-xs mb-1" style="color: var(--color-text-secondary);">
                                    <i class="fas fa-calendar-alt mr-1"></i> 
                                    <?php echo date('F d, Y', strtotime($event['start_date'])); ?>
                                    <?php if (date('Y-m-d', strtotime($event['start_date'])) != date('Y-m-d', strtotime($event['end_date']))): ?>
                                    - <?php echo date('F d, Y', strtotime($event['end_date'])); ?>
                                    <?php endif; ?>
                                </p>
                                
                                <p class="text-xs mb-1" style="color: var(--color-text-secondary);">
                                    <i class="fas fa-clock mr-1"></i> 
                                    <?php echo date('h:i A', strtotime($event['start_date'])); ?> - 
                                    <?php echo date('h:i A', strtotime($event['end_date'])); ?>
                                </p>
                                
                                <?php if (!empty($event['location'])): ?>
                                <p class="text-xs" style="color: var(--color-text-secondary);">
                                    <i class="fas fa-map-marker-alt mr-1"></i> 
                                    <?php echo htmlspecialchars($event['location']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="px-2 py-1 text-xs font-medium rounded-full" 
                                      style="background-color: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>;">
                                    <?php echo ucfirst($event['status']); ?>
                                </span>
                                
                                <div class="flex space-x-2">
                                    <a href="?page=lead_events&event_id=<?php echo $event['event_id']; ?>" class="text-sm font-medium" style="color: #4285F4;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?page=lead_events&action=edit&event_id=<?php echo $event['event_id']; ?>" class="text-sm font-medium" style="color: #FBBC05;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0);" class="text-sm font-medium delete-event" data-id="<?php echo $event['event_id']; ?>" style="color: #EA4335;">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Make sure the events.js file is loaded -->
            <script src="views/lead/js/events.js"></script>
            </div> <!-- End of main content container -->

            <?php include __DIR__ . '/../../includes/footer.php'; ?>
            
            <style>
                /* Improved filter dropdown styles */
                #filter-dropdown {
                    position: absolute;
                    right: 0;
                    top: 100%;
                    margin-top: 5px;
                    width: 280px;
                    z-index: 100;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    background-color: var(--color-card);
                    border: 1px solid var(--color-border-light);
                }
                
                /* Fix position for mobile */
                @media (max-width: 640px) {
                    #filter-dropdown {
                        right: -100px;
                        width: 260px;
                    }
                }
                
                /* Style for filter buttons */
                .filter-dropdown-btn {
                    position: relative;
                }
                
                /* Improved button styles */
                #apply-filters-btn, #reset-filters-btn {
                    cursor: pointer;
                    transition: all 0.2s;
                }
                
                #apply-filters-btn:hover {
                    background-color: #3367d6 !important;
                }
                
                #reset-filters-btn:hover {
                    background-color: var(--color-hover);
                }
            </style>
            
            <!-- Remove the duplicate script to avoid conflicts with events.js -->
            <?php
        }
        break;
}
?>
