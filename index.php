<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Check if the application is installed
if (!file_exists('config/config.php') || !defined('DB_HOST')) {
    header('Location: installer.php');
    exit;
}

// Route handling
$route = isset($_GET['route']) ? $_GET['route'] : 'home';

// Check authentication
if (!isLoggedIn() && !in_array($route, ['login', 'register', 'forgot-password', 'reset-password'])) {
    header('Location: index.php?route=login');
    exit;
}

// Handle routing based on user role
if (isLoggedIn()) {
    $userRole = $_SESSION['user_role'];
    
    // Redirect to appropriate dashboard if trying to access login page
    if ($route == 'login' || $route == 'register') {
        header('Location: index.php?route=' . ($userRole == 'admin' ? 'admin/dashboard' : 'client/dashboard'));
        exit;
    }
    
    // Route handling based on user role
    if ($userRole == 'admin') {
        // Admin routes
        if (strpos($route, 'admin/') !== 0 && $route != 'home') {
            header('Location: index.php?route=admin/dashboard');
            exit;
        }
    } else {
        // Client routes
        if (strpos($route, 'admin/') === 0) {
            header('Location: index.php?route=client/dashboard');
            exit;
        }
    }
}

// Include header
include 'views/includes/header.php';

// Include sidebar if logged in
if (isLoggedIn()) {
    include 'views/includes/sidebar.php';
}

// Route to the appropriate controller
switch ($route) {
    case 'home':
        include 'controllers/home_controller.php';
        break;
    case 'login':
        include 'controllers/auth_controller.php';
        include 'views/auth/login.php';
        break;
    case 'register':
        include 'controllers/auth_controller.php';
        include 'views/auth/register.php';
        break;
    case 'logout':
        include 'controllers/auth_controller.php';
        logout();
        break;
    case 'forgot-password':
        include 'controllers/auth_controller.php';
        include 'views/auth/forgot_password.php';
        break;
    case 'reset-password':
        include 'controllers/auth_controller.php';
        include 'views/auth/reset_password.php';
        break;
    // Admin routes
    case 'admin/dashboard':
        include 'controllers/admin_controller.php';
        include 'views/admin/dashboard.php';
        break;
    case 'admin/clients':
        include 'controllers/admin_controller.php';
        include 'views/admin/clients.php';
        break;
    case 'admin/investments':
        include 'controllers/admin_controller.php';
        include 'views/admin/investments.php';
        break;
    case 'admin/withdrawals':
        include 'controllers/admin_controller.php';
        include 'views/admin/withdrawals.php';
        break;
    case 'admin/kyc':
        include 'controllers/admin_controller.php';
        include 'views/admin/kyc.php';
        break;
    case 'admin/reports':
        include 'controllers/admin_controller.php';
        include 'views/admin/reports.php';
        break;
    case 'admin/notifications':
        include 'controllers/admin_controller.php';
        include 'views/admin/notifications.php';
        break;
    // Client routes
    case 'client/dashboard':
        include 'controllers/client_controller.php';
        include 'views/client/dashboard.php';
        break;
    case 'client/profile':
        include 'controllers/client_controller.php';
        include 'views/client/profile.php';
        break;
    case 'client/kyc':
        include 'controllers/client_controller.php';
        include 'views/client/kyc.php';
        break;
    case 'client/investments':
        include 'controllers/client_controller.php';
        include 'views/client/investments.php';
        break;
    case 'client/wallet':
        include 'controllers/client_controller.php';
        include 'views/client/wallet.php';
        break;
    case 'client/withdrawals':
        include 'controllers/client_controller.php';
        include 'views/client/withdrawals.php';
        break;
    case 'client/referrals':
        include 'controllers/client_controller.php';
        include 'views/client/referrals.php';
        break;
    default:
        include 'views/404.php';
        break;
}

// Include footer
include 'views/includes/footer.php';
?> 