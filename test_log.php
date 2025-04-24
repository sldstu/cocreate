<?php
// Test file to check logging functionality
// This file should be accessed directly through browser at http://localhost/ems-cc/test_log.php

// Including necessary files
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Start a session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define APP_NAME to avoid direct access check failures
define('APP_NAME', 'CoCreate');

echo "<h1>Testing Log Functionality</h1>";

// Check if the activity_logs table exists
$database = new Database();
$conn = $database->getConnection();

try {
    // Check if table exists
    $checkTableQuery = "SHOW TABLES LIKE 'activity_logs'";
    $checkStmt = $conn->prepare($checkTableQuery);
    $checkStmt->execute();
    $tableExists = ($checkStmt->rowCount() > 0);
    
    if ($tableExists) {
        echo "<p style='color: green'>SUCCESS: The activity_logs table exists in the database.</p>";
    } else {
        echo "<p style='color: red'>ERROR: The activity_logs table does NOT exist in the database.</p>";
        
        // Try to create the table
        echo "<p>Attempting to create the table...</p>";
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `activity_logs` (
            `log_id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `action` text NOT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`log_id`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $conn->exec($createTableSQL);
        echo "<p style='color: green'>Table created successfully.</p>";
    }
    
    // Count existing logs
    $countQuery = "SELECT COUNT(*) as log_count FROM activity_logs";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute();
    $logCount = $countStmt->fetch(PDO::FETCH_ASSOC)['log_count'];
    
    echo "<p>Current log count in database: <strong>$logCount</strong></p>";
    
    // Set a test user ID (use lead user)
    $testUserId = 3; // Using the 'lead' user with ID 3
    
    // Create a test log entry
    $testAction = "TEST LOG: Created from test_log.php at " . date('Y-m-d H:i:s');
    $success = logAction($testUserId, $testAction);
    
    if ($success) {
        echo "<p style='color: green'>SUCCESS: Test log entry was created successfully.</p>";
        
        // Verify the new log was added
        $countStmt->execute();
        $newLogCount = $countStmt->fetch(PDO::FETCH_ASSOC)['log_count'];
        
        if ($newLogCount > $logCount) {
            echo "<p style='color: green'>VERIFIED: Log count increased from $logCount to $newLogCount.</p>";
        } else {
            echo "<p style='color: red'>ERROR: Log count did not increase. Something went wrong.</p>";
        }
        
        // Show the 5 most recent logs
        echo "<h2>5 Most Recent Logs:</h2>";
        $recentQuery = "SELECT l.*, u.username 
                        FROM activity_logs l 
                        LEFT JOIN users u ON l.user_id = u.user_id 
                        ORDER BY l.created_at DESC LIMIT 5";
        $recentStmt = $conn->prepare($recentQuery);
        $recentStmt->execute();
        $recentLogs = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recentLogs) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Log ID</th><th>User</th><th>Action</th><th>IP Address</th><th>Created At</th></tr>";
            
            foreach ($recentLogs as $log) {
                echo "<tr>";
                echo "<td>" . $log['log_id'] . "</td>";
                echo "<td>" . htmlspecialchars($log['username'] ?? 'Unknown') . "</td>";
                echo "<td>" . htmlspecialchars($log['action']) . "</td>";
                echo "<td>" . htmlspecialchars($log['ip_address']) . "</td>";
                echo "<td>" . $log['created_at'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No logs found.</p>";
        }
    } else {
        echo "<p style='color: red'>ERROR: Failed to create test log entry.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red'>DATABASE ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Add link to logs page
echo "<p><a href='?page=lead_logs' style='font-weight: bold;'>Go to Logs Page</a></p>";
echo "<p><i>Note: The link above may not work if accessed directly. Instead, navigate to the logs page from the dashboard.</i></p>";

// Display the logAction function for reference
echo "<h2>logAction() Function Definition:</h2>";
echo "<pre>";
$functionFile = file_get_contents('includes/functions.php');
if ($functionFile !== false) {
    // Extract the logAction function
    if (preg_match('/function logAction\([^\{]+\{([^\}]+)\}/s', $functionFile, $matches)) {
        echo htmlspecialchars("function logAction(...) {\n" . $matches[1] . "\n}");
    } else {
        echo "Could not find logAction() function in functions.php";
    }
}
echo "</pre>";
?>