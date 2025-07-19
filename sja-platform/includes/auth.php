<?php
/**
 * Authentication System
 * Handles user authentication, registration, and session management
 */

require_once 'database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = db();
    }

    /**
     * User Registration
     */
    public function register($data) {
        try {
            // Validate required fields
            $required = ['name', 'email', 'phone', 'password', 'referral_code'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("$field is required");
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Validate phone number
            if (!preg_match('/^[0-9]{10}$/', $data['phone'])) {
                throw new Exception("Invalid phone number format");
            }

            // Check if email already exists
            $existingUser = $this->db->fetch("SELECT id FROM users WHERE email = ?", [$data['email']]);
            if ($existingUser) {
                throw new Exception("Email already registered");
            }

            // Check if phone already exists
            $existingPhone = $this->db->fetch("SELECT id FROM users WHERE phone = ?", [$data['phone']]);
            if ($existingPhone) {
                throw new Exception("Phone number already registered");
            }

            // Validate referral code
            $referrer = $this->db->fetch("SELECT id, level FROM users WHERE id = ?", [$data['referral_code']]);
            if (!$referrer) {
                throw new Exception("Invalid referral code");
            }

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // Generate unique client ID
            $clientId = 'SJA' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

            // Begin transaction
            $this->db->beginTransaction();

            // Insert user
            $userId = $this->db->insert('users', [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $hashedPassword,
                'parent_id' => $referrer['id'],
                'level' => $referrer['level'] + 1,
                'role' => 'client'
            ]);

            // Insert client details
            $this->db->insert('clients', [
                'user_id' => $userId,
                'client_id' => $clientId
            ]);

            // Create wallet
            $this->db->insert('wallets', [
                'user_id' => $userId,
                'balance' => 0.00,
                'locked_amount' => 0.00
            ]);

            // Create referral record
            $this->db->insert('referrals', [
                'referrer_id' => $referrer['id'],
                'referred_id' => $userId,
                'level' => 1,
                'commission_rate' => COMMISSION_LEVELS[1]['rate']
            ]);

            // Commit transaction
            $this->db->commit();

            return [
                'status' => 'success',
                'message' => 'Registration successful',
                'user_id' => $userId,
                'client_id' => $clientId
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * User Login
     */
    public function login($email, $password) {
        try {
            // Get user by email
            $user = $this->db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
            if (!$user) {
                throw new Exception("Invalid email or password");
            }

            // Check if account is active
            if ($user['status'] !== 'active') {
                throw new Exception("Account is " . $user['status']);
            }

            // Verify password
            if (!password_verify($password, $user['password'])) {
                throw new Exception("Invalid email or password");
            }

            // Create session
            $this->createSession($user);

            // Update last login
            $this->db->update('users', 
                ['updated_at' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$user['id']]
            );

            return [
                'status' => 'success',
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create User Session
     */
    private function createSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['session_id'] = session_id();
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Check session timeout
        if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    /**
     * Change Password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                throw new Exception("User not found");
            }

            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->db->update('users', 
                ['password' => $hashedPassword], 
                'id = ?', 
                [$userId]
            );

            return [
                'status' => 'success',
                'message' => 'Password changed successfully'
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate Referral Code
     */
    public function generateReferralCode($userId) {
        return $userId; // Using user ID as referral code for simplicity
    }

    /**
     * Get User Referrals
     */
    public function getUserReferrals($userId) {
        return $this->db->fetchAll("
            SELECT u.*, c.client_id, r.level, r.commission_rate
            FROM users u
            LEFT JOIN clients c ON u.id = c.user_id
            LEFT JOIN referrals r ON u.id = r.referred_id
            WHERE r.referrer_id = ?
            ORDER BY r.created_at DESC
        ", [$userId]);
    }

    /**
     * Get Referral Tree
     */
    public function getReferralTree($userId, $maxLevel = 5) {
        $tree = [];
        $this->buildReferralTree($userId, $tree, 0, $maxLevel);
        return $tree;
    }

    private function buildReferralTree($userId, &$tree, $level, $maxLevel) {
        if ($level >= $maxLevel) return;

        $referrals = $this->db->fetchAll("
            SELECT u.id, u.name, u.email, u.level, c.client_id
            FROM users u
            LEFT JOIN clients c ON u.id = c.user_id
            LEFT JOIN referrals r ON u.id = r.referred_id
            WHERE r.referrer_id = ?
        ", [$userId]);

        foreach ($referrals as $referral) {
            $referral['children'] = [];
            $this->buildReferralTree($referral['id'], $referral['children'], $level + 1, $maxLevel);
            $tree[] = $referral;
        }
    }
}

// Global auth instance
function auth() {
    return new Auth();
}
?>