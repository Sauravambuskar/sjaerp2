<?php
/**
 * Utility Functions
 * Common helper functions used throughout the application
 */

require_once 'config.php';

/**
 * Format currency
 */
function formatCurrency($amount, $currency = '₹') {
    return $currency . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'd M Y H:i') {
    return date($format, strtotime($datetime));
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

/**
 * Generate transaction ID
 */
function generateTransactionId() {
    return 'TXN' . date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedTypes, $maxSize = MAX_FILE_SIZE) {
    $errors = [];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload failed";
        return $errors;
    }

    if ($file['size'] > $maxSize) {
        $errors[] = "File size exceeds maximum limit";
    }

    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedTypes)) {
        $errors[] = "File type not allowed";
    }

    return $errors;
}

/**
 * Upload file
 */
function uploadFile($file, $directory = UPLOAD_PATH) {
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    $fileName = uniqid() . '_' . time() . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filePath = $directory . $fileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return $fileName;
    }

    return false;
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number
 */
function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

/**
 * Send notification
 */
function sendNotification($userId, $title, $message, $type = 'info') {
    $db = db();
    return $db->insert('notifications', [
        'user_id' => $userId,
        'title' => $title,
        'message' => $message,
        'type' => $type
    ]);
}

/**
 * Get user notifications
 */
function getUserNotifications($userId, $limit = 10) {
    $db = db();
    return $db->fetchAll("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ", [$userId, $limit]);
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notificationId) {
    $db = db();
    return $db->update('notifications', 
        ['read_status' => 1], 
        'id = ?', 
        [$notificationId]
    );
}

/**
 * Calculate commission
 */
function calculateCommission($amount, $level) {
    if (isset(COMMISSION_LEVELS[$level])) {
        return ($amount * COMMISSION_LEVELS[$level]['rate']) / 100;
    }
    return 0;
}

/**
 * Get user level name
 */
function getUserLevelName($level) {
    if (isset(COMMISSION_LEVELS[$level])) {
        return COMMISSION_LEVELS[$level]['name'];
    }
    return 'Unknown Level';
}

/**
 * Get user wallet balance
 */
function getUserWalletBalance($userId) {
    $db = db();
    $wallet = $db->fetch("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
    return $wallet ? $wallet['balance'] : 0;
}

/**
 * Update user wallet
 */
function updateUserWallet($userId, $amount, $type = 'credit') {
    $db = db();
    $wallet = $db->fetch("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
    
    if (!$wallet) {
        return false;
    }

    $newBalance = $type === 'credit' ? $wallet['balance'] + $amount : $wallet['balance'] - $amount;
    
    return $db->update('wallets', 
        ['balance' => $newBalance], 
        'user_id = ?', 
        [$userId]
    );
}

/**
 * Create transaction record
 */
function createTransaction($userId, $type, $amount, $description = '') {
    $db = db();
    $transactionId = generateTransactionId();
    
    return $db->insert('transactions', [
        'user_id' => $userId,
        'transaction_id' => $transactionId,
        'type' => $type,
        'amount' => $amount,
        'description' => $description,
        'status' => 'completed'
    ]);
}

/**
 * Get user investment summary
 */
function getUserInvestmentSummary($userId) {
    $db = db();
    
    $activeInvestments = $db->fetch("
        SELECT COUNT(*) as count, SUM(amount) as total_amount 
        FROM investments 
        WHERE user_id = ? AND status = 'active'
    ", [$userId]);
    
    $maturedInvestments = $db->fetch("
        SELECT COUNT(*) as count, SUM(amount) as total_amount 
        FROM investments 
        WHERE user_id = ? AND status = 'matured'
    ", [$userId]);
    
    return [
        'active' => $activeInvestments,
        'matured' => $maturedInvestments
    ];
}

/**
 * Get user earnings summary
 */
function getUserEarningsSummary($userId) {
    $db = db();
    
    $today = date('Y-m-d');
    $thisMonth = date('Y-m-01');
    
    $dailyEarnings = $db->fetch("
        SELECT SUM(amount) as total 
        FROM earnings 
        WHERE user_id = ? AND date = ?
    ", [$userId, $today]);
    
    $monthlyEarnings = $db->fetch("
        SELECT SUM(amount) as total 
        FROM earnings 
        WHERE user_id = ? AND date >= ?
    ", [$userId, $thisMonth]);
    
    return [
        'daily' => $dailyEarnings['total'] ?? 0,
        'monthly' => $monthlyEarnings['total'] ?? 0
    ];
}

/**
 * Check if user has completed KYC
 */
function isKYCCompleted($userId) {
    $db = db();
    $kyc = $db->fetch("SELECT kyc_status FROM clients WHERE user_id = ?", [$userId]);
    return $kyc && $kyc['kyc_status'] === 'verified';
}

/**
 * Get user referral count
 */
function getUserReferralCount($userId) {
    $db = db();
    $result = $db->fetch("
        SELECT COUNT(*) as count 
        FROM referrals 
        WHERE referrer_id = ?
    ", [$userId]);
    return $result['count'] ?? 0;
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit;
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message'], $_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = '') {
    $db = db();
    return $db->insert('activity_logs', [
        'user_id' => $userId,
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

/**
 * Generate pagination
 */
function generatePagination($totalRecords, $recordsPerPage, $currentPage, $baseUrl) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    
    if ($totalPages <= 1) {
        return '';
    }
    
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">Previous</a></li>';
    }
    
    // Page numbers
    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        $active = $i == $currentPage ? ' active' : '';
        $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Next</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    
    return $pagination;
}

/**
 * Get current page number
 */
function getCurrentPage() {
    return isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
}

/**
 * Get records per page
 */
function getRecordsPerPage() {
    return isset($_GET['limit']) ? max(10, intval($_GET['limit'])) : 20;
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Clean old sessions
 */
function cleanOldSessions() {
    $db = db();
    $timeout = time() - SESSION_TIMEOUT;
    $db->query("DELETE FROM sessions WHERE last_activity < ?", [$timeout]);
}
?>