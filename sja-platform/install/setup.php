<?php
/**
 * SJA Foundation Investment Management Platform
 * Installation Setup Wizard
 */

session_start();

// Check if already installed
if (file_exists('../includes/config.php') && !isset($_GET['force'])) {
    die('Platform is already installed. Add ?force=1 to force reinstallation.');
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            // Database configuration
            $dbHost = $_POST['db_host'] ?? '';
            $dbName = $_POST['db_name'] ?? '';
            $dbUser = $_POST['db_user'] ?? '';
            $dbPass = $_POST['db_pass'] ?? '';
            
            if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
                $error = 'All database fields are required';
            } else {
                // Test database connection
                try {
                    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $_SESSION['db_config'] = [
                        'host' => $dbHost,
                        'name' => $dbName,
                        'user' => $dbUser,
                        'pass' => $dbPass
                    ];
                    
                    header('Location: setup.php?step=2');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Database connection failed: ' . $e->getMessage();
                }
            }
            break;
            
        case 2:
            // Site configuration
            $siteUrl = $_POST['site_url'] ?? '';
            $siteName = $_POST['site_name'] ?? '';
            $adminEmail = $_POST['admin_email'] ?? '';
            $adminPassword = $_POST['admin_password'] ?? '';
            
            if (empty($siteUrl) || empty($siteName) || empty($adminEmail) || empty($adminPassword)) {
                $error = 'All site configuration fields are required';
            } else {
                $_SESSION['site_config'] = [
                    'url' => $siteUrl,
                    'name' => $siteName,
                    'admin_email' => $adminEmail,
                    'admin_password' => $adminPassword
                ];
                
                header('Location: setup.php?step=3');
                exit;
            }
            break;
            
        case 3:
            // Install database and create config
            try {
                $dbConfig = $_SESSION['db_config'];
                $siteConfig = $_SESSION['site_config'];
                
                // Create config file
                $configContent = "<?php
// Database Configuration
define('DB_HOST', '{$dbConfig['host']}');
define('DB_NAME', '{$dbConfig['name']}');
define('DB_USER', '{$dbConfig['user']}');
define('DB_PASS', '{$dbConfig['pass']}');

// Site Configuration
define('SITE_URL', '{$siteConfig['url']}');
define('SITE_NAME', '{$siteConfig['name']}');
define('SITE_DESCRIPTION', 'Investment Management Platform');
define('ADMIN_EMAIL', '{$siteConfig['admin_email']}');

// Security Configuration
define('ENCRYPTION_KEY', '" . bin2hex(random_bytes(32)) . "');
define('SESSION_TIMEOUT', 1800);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);

// File Upload Configuration
define('MAX_FILE_SIZE', 5242880);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx']);
define('UPLOAD_PATH', '../assets/uploads/');

// Commission Structure
define('COMMISSION_LEVELS', [
    1 => ['name' => 'Professional Ambassador', 'min_amount' => 100000, 'max_amount' => 2000000, 'rate' => 0.25],
    2 => ['name' => 'Rubies Ambassador', 'min_amount' => 3000000, 'max_amount' => 3000000, 'rate' => 0.37],
    3 => ['name' => 'Topaz Ambassador', 'min_amount' => 4000000, 'max_amount' => 4000000, 'rate' => 0.50],
    4 => ['name' => 'Silver Ambassador', 'min_amount' => 5000000, 'max_amount' => 5000000, 'rate' => 0.70],
    5 => ['name' => 'Golden Ambassador', 'min_amount' => 6000000, 'max_amount' => 6000000, 'rate' => 0.85],
    6 => ['name' => 'Platinum Ambassador', 'min_amount' => 7000000, 'max_amount' => 7000000, 'rate' => 1.00],
    7 => ['name' => 'Diamond Ambassador', 'min_amount' => 8000000, 'max_amount' => 8000000, 'rate' => 1.25],
    8 => ['name' => 'MTA', 'min_amount' => 9000000, 'max_amount' => 9000000, 'rate' => 1.50],
    9 => ['name' => 'Channel Partner', 'min_amount' => 10000000, 'max_amount' => 10000000, 'rate' => 2.00],
    10 => ['name' => 'Co-Director', 'min_amount' => 0, 'max_amount' => 0, 'rate' => 0],
    11 => ['name' => 'Director', 'min_amount' => 0, 'max_amount' => 0, 'rate' => 0],
    12 => ['name' => 'MD/CEO/CMD', 'min_amount' => 0, 'max_amount' => 0, 'rate' => 0]
]);

// Investment Configuration
define('MIN_INVESTMENT', 0);
define('MAX_INVESTMENT', 0);
define('LOCK_IN_PERIOD', 11);
define('PARTIAL_WITHDRAWAL_PENALTY', 3);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/error.log');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>";
                
                file_put_contents('../includes/config.php', $configContent);
                
                // Import database schema
                $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}", $dbConfig['user'], $dbConfig['pass']);
                $sql = file_get_contents('database.sql');
                $pdo->exec($sql);
                
                // Update admin password
                $hashedPassword = password_hash($siteConfig['admin_password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, email = ? WHERE role = 'admin'");
                $stmt->execute([$hashedPassword, $siteConfig['admin_email']]);
                
                // Create required directories
                $directories = [
                    '../assets/uploads',
                    '../assets/images',
                    '../logs'
                ];
                
                foreach ($directories as $dir) {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                }
                
                $success = 'Installation completed successfully!';
                $step = 4;
                
            } catch (Exception $e) {
                $error = 'Installation failed: ' . $e->getMessage();
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SJA Foundation - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .install-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        .install-header {
            background: linear-gradient(135deg, #2B3A67 0%, #1E3A8A 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .install-body {
            padding: 2rem;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.5rem;
            font-weight: bold;
        }
        .step.active {
            background: #2B3A67;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .form-control:focus {
            border-color: #2B3A67;
            box-shadow: 0 0 0 0.2rem rgba(43, 58, 103, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #2B3A67 0%, #1E3A8A 100%);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #1E3A8A 0%, #2B3A67 100%);
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1><i class="fas fa-chart-line"></i> SJA Foundation</h1>
            <p class="mb-0">Investment Management Platform Installation</p>
        </div>
        
        <div class="install-body">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?= $step >= 1 ? 'active' : '' ?>">1</div>
                <div class="step <?= $step >= 2 ? 'active' : '' ?>">2</div>
                <div class="step <?= $step >= 3 ? 'active' : '' ?>">3</div>
                <div class="step <?= $step >= 4 ? 'active' : '' ?>">4</div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
                <!-- Step 1: Database Configuration -->
                <h3 class="mb-4">Database Configuration</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Database Host</label>
                        <input type="text" class="form-control" name="db_host" value="localhost" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Name</label>
                        <input type="text" class="form-control" name="db_name" value="sja_platform" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Username</label>
                        <input type="text" class="form-control" name="db_user" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Password</label>
                        <input type="password" class="form-control" name="db_pass">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-arrow-right"></i> Continue
                    </button>
                </form>
                
            <?php elseif ($step === 2): ?>
                <!-- Step 2: Site Configuration -->
                <h3 class="mb-4">Site Configuration</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Site URL</label>
                        <input type="url" class="form-control" name="site_url" value="<?= $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI'])) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Site Name</label>
                        <input type="text" class="form-control" name="site_name" value="SJA Foundation" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Email</label>
                        <input type="email" class="form-control" name="admin_email" value="admin@sja-foundation.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Password</label>
                        <input type="password" class="form-control" name="admin_password" required>
                        <small class="form-text text-muted">Minimum 8 characters</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-arrow-right"></i> Continue
                    </button>
                </form>
                
            <?php elseif ($step === 3): ?>
                <!-- Step 3: Installation -->
                <h3 class="mb-4">Installation Progress</h3>
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Installing...</span>
                    </div>
                    <p>Installing SJA Foundation Investment Management Platform...</p>
                    <form method="POST">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download"></i> Install Now
                        </button>
                    </form>
                </div>
                
            <?php elseif ($step === 4): ?>
                <!-- Step 4: Installation Complete -->
                <div class="text-center">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    <h3 class="mt-3">Installation Complete!</h3>
                    <p class="text-muted">SJA Foundation Investment Management Platform has been successfully installed.</p>
                    
                    <div class="alert alert-info">
                        <h5>Default Login Credentials:</h5>
                        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($_SESSION['site_config']['admin_email']) ?></p>
                        <p class="mb-0"><strong>Password:</strong> (The password you set during installation)</p>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="../admin/" class="btn btn-primary">
                            <i class="fas fa-cog"></i> Go to Admin Panel
                        </a>
                        <a href="../" class="btn btn-outline-primary">
                            <i class="fas fa-home"></i> Go to Homepage
                        </a>
                    </div>
                    
                    <div class="mt-4">
                        <small class="text-muted">
                            <strong>Important:</strong> Delete the install directory for security reasons.
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>