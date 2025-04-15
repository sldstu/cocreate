<?php
session_start();
header('Content-Type: text/plain');

echo "SESSION CONTENTS:\n";
print_r($_SESSION);

echo "\n\nUSER ROLE INFO:\n";
if (isset($_SESSION['user'])) {
    echo "Role ID: " . ($_SESSION['user']['role_id'] ?? 'Not set') . "\n";
    echo "Role Slug: " . ($_SESSION['user']['role'] ?? 'Not set') . "\n";
    
    // Try to determine the role name based on role_id
    $role_id = $_SESSION['user']['role_id'] ?? 0;
    echo "Role ID Numeric Value: $role_id\n";
    
    // Include config to get role constants
    require_once 'config/config.php';
    
    echo "ROLE_ADMIN: " . ROLE_ADMIN . "\n";
    echo "ROLE_DEPARTMENT_LEAD: " . ROLE_DEPARTMENT_LEAD . "\n";
    echo "ROLE_OFFICER: " . ROLE_OFFICER . "\n";
    echo "ROLE_MEMBER: " . ROLE_MEMBER . "\n";
    
    // Check which role matches
    if ($role_id == ROLE_ADMIN) echo "Role matches ROLE_ADMIN\n";
    if ($role_id == ROLE_DEPARTMENT_LEAD) echo "Role matches ROLE_DEPARTMENT_LEAD\n";
    if ($role_id == ROLE_OFFICER) echo "Role matches ROLE_OFFICER\n";
    if ($role_id == ROLE_MEMBER) echo "Role matches ROLE_MEMBER\n";
    
    // Check getUserRoleName function
    if (file_exists('includes/functions.php')) {
        require_once 'includes/functions.php';
        if (function_exists('getUserRoleName')) {
            echo "getUserRoleName($role_id, true): " . getUserRoleName($role_id, true) . "\n";
            echo "getUserRoleName($role_id, false): " . getUserRoleName($role_id, false) . "\n";
        } else {
            echo "getUserRoleName function not found in functions.php\n";
        }
    } else {
        echo "functions.php file not found\n";
    }
    
    // Define our own version to test
    function testGetUserRoleName($roleId, $returnSlug = false) {
        // Define constants if not already defined
        if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', 1);
        if (!defined('ROLE_DEPARTMENT_LEAD')) define('ROLE_DEPARTMENT_LEAD', 2);
        if (!defined('ROLE_OFFICER')) define('ROLE_OFFICER', 3);
        if (!defined('ROLE_MEMBER')) define('ROLE_MEMBER', 4);
        
        switch ($roleId) {
            case ROLE_ADMIN:
                return $returnSlug ? 'admin' : 'Administrator';
            case ROLE_DEPARTMENT_LEAD:
                return $returnSlug ? 'dep_lead' : 'Department Lead';
            case ROLE_OFFICER:
                return $returnSlug ? 'officer' : 'Officer';
            case ROLE_MEMBER:
                return $returnSlug ? 'member' : 'Member';
            default:
                return $returnSlug ? 'user' : 'Unknown Role';
        }
    }
    
    echo "testGetUserRoleName($role_id, true): " . testGetUserRoleName($role_id, true) . "\n";
    echo "testGetUserRoleName($role_id, false): " . testGetUserRoleName($role_id, false) . "\n";
}

echo "\n\nSERVER INFO:\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Not available') . "\n";
?>
