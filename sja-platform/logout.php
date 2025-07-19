<?php
/**
 * SJA Foundation Investment Management Platform
 * Logout Script
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = auth();

// Log the logout activity if user was logged in
if ($auth->isLoggedIn()) {
    $currentUser = $auth->getCurrentUser();
    logActivity($currentUser['id'], 'logout', 'User logged out successfully');
}

// Logout the user
$auth->logout();

// Redirect to login page with success message
redirectWithMessage('login.php', 'You have been logged out successfully', 'success');
?>