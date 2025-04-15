<?php
/**
 * Common header file to be included in all pages
 * This includes all necessary CSS and JS files for theming
 */
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="public/css/theme.css">
    <link rel="stylesheet" href="public/css/components.css">
    <link rel="stylesheet" href="public/css/alerts.css">
    <link rel="stylesheet" href="public/css/modals.css">
    <link rel="stylesheet" href="public/css/loaders.css">
    
    <!-- Theme Toggle JS -->
    <script src="public/js/theme.js"></script>
    
    <!-- jQuery for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <?php if (isset($additionalHeadContent)) echo $additionalHeadContent; ?>
</head>
<body class="bg-background text-text-primary">
