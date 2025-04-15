<?php
// Check if HTTPS is on
if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
    $uri = 'https://';
} else {
    $uri = 'http://';
}

// Build the base URL
$uri .= $_SERVER['HTTP_HOST'];
$uri .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

// Redirect to landing.php (login page)
header('Location: ' . $uri . 'landing.php');
exit;
