<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Unauthorized access';
    exit;
}

// Include database connection
require_once 'db_connect.php';
// Include Events class
require_once 'events.class.php';

// Get event ID from query parameter
$eventId = isset($_GET['eventId']) ? intval($_GET['eventId']) : 0;

if ($eventId <= 0) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid event ID';
    exit;
}

// Create Events instance
$events = new Events($db);

// Get event details
$event = $events->getEvent($eventId);

if (!$event) {
    header('HTTP/1.1 404 Not Found');
    echo 'Event not found';
    exit;
}

// Get attendees
$attendees = $events->getEventAttendees($eventId);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="event_attendees_' . $eventId . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Create Excel file content
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Event Attendees</title>
</head>
<body>
    <table border='1'>
        <thead>
            <tr>
                <th colspan='6'>Event: " . htmlspecialchars($event['name']) . "</th>
            </tr>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Registration Date</th>
                <th>Status</th>
                <th>Comments</th>
            </tr>
        </thead>
        <tbody>";

foreach ($attendees as $attendee) {
    echo "<tr>
            <td>" . htmlspecialchars($attendee['name']) . "</td>
            <td>" . htmlspecialchars($attendee['email']) . "</td>
            <td>" . htmlspecialchars($attendee['phone'] ?? 'N/A') . "</td>
            <td>" . date('Y-m-d H:i:s', strtotime($attendee['registration_date'])) . "</td>
            <td>" . ucfirst(htmlspecialchars($attendee['status'])) . "</td>
            <td>" . htmlspecialchars($attendee['comments'] ?? '') . "</td>
        </tr>";
}

echo "</tbody>
    </table>
</body>
</html>";
exit;
?>
