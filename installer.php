<?php
session_start();

// Check if already installed
if (file_exists('config/config.php') && filesize('config/config.php') > 0) {
    $configContent = file_get_contents('config/config.php');
    if (strpos($configContent, 'DB_HOST') !== false) {
        // Config exists and has database settings
        if (!isset($_GET['force']) || $_GET['force'] != 'true') {
            header('Location: index.php');
            exit;
        }
    }
}

// Installation steps
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1: // Server requirements check
            // Move to next step if submitted
            header('Location: installer.php?step=2');
            exit;
            break;
            
        case 2: // Database configuration
            $dbHost = trim($_POST['db_host'] ?? '');
            $dbName = trim($_POST['db_name'] ?? '');
            $dbUser = trim($_POST['db_user'] ?? '');
            $dbPass = $_POST['db_pass'] ?? '';
            
            // Validate inputs
            if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
                $error = 'All fields except password are required';
            } else {
                // Test connection
                try {
                    $conn = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Try to create database if it doesn't exist
                    $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
                    $conn->exec("USE `$dbName`");
                    
                    // Store in session for next step
                    $_SESSION['db_config'] = [
                        'host' => $dbHost,
                        'name' => $dbName,
                        'user' => $dbUser,
                        'pass' => $dbPass
                    ];
                    
                    // Move to next step
                    header('Location: installer.php?step=3');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Database connection failed: ' . $e->getMessage();
                }
            }
            break;
            
        case 3: // Admin account creation
            $adminName = trim($_POST['admin_name'] ?? '');
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminPassword = $_POST['admin_password'] ?? '';
            $adminConfirmPassword = $_POST['admin_confirm_password'] ?? '';
            
            // Validate inputs
            if (empty($adminName) || empty($adminEmail) || empty($adminPassword)) {
                $error = 'All fields are required';
            } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address';
            } elseif ($adminPassword !== $adminConfirmPassword) {
                $error = 'Passwords do not match';
            } elseif (strlen($adminPassword) < 8) {
                $error = 'Password must be at least 8 characters';
            } else {
                // Store in session for next step
                $_SESSION['admin_config'] = [
                    'name' => $adminName,
                    'email' => $adminEmail,
                    'password' => password_hash($adminPassword, PASSWORD_DEFAULT)
                ];
                
                // Move to next step
                header('Location: installer.php?step=4');
                exit;
            }
            break;
            
        case 4: // Finalize installation
            if (!isset($_SESSION['db_config']) || !isset($_SESSION['admin_config'])) {
                $error = 'Missing configuration data. Please start over.';
                header('Location: installer.php?step=1');
                exit;
            }
            
            $dbConfig = $_SESSION['db_config'];
            $adminConfig = $_SESSION['admin_config'];
            
            try {
                // Create config file
                $configContent = "<?php\n";
                $configContent .= "// Database Configuration\n";
                $configContent .= "define('DB_HOST', '{$dbConfig['host']}');\n";
                $configContent .= "define('DB_NAME', '{$dbConfig['name']}');\n";
                $configContent .= "define('DB_USER', '{$dbConfig['user']}');\n";
                $configContent .= "define('DB_PASS', '{$dbConfig['pass']}');\n\n";
                $configContent .= "// Application Configuration\n";
                $configContent .= "define('APP_NAME', 'SJA Foundation Investment Management');\n";
                $configContent .= "define('APP_URL', (isset(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['PHP_SELF']));\n";
                $configContent .= "define('ADMIN_EMAIL', '{$adminConfig['email']}');\n";
                $configContent .= "?>";
                
                // Create config directory if it doesn't exist
                if (!is_dir('config')) {
                    mkdir('config', 0755, true);
                }
                
                // Write config file
                file_put_contents('config/config.php', $configContent);
                
                // Connect to database
                $conn = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}", $dbConfig['user'], $dbConfig['pass']);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Import database schema
                $sql = file_get_contents('database/schema.sql');
                $conn->exec($sql);
                
                // Create admin user
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
                $stmt->execute([$adminConfig['name'], $adminConfig['email'], $adminConfig['password']]);
                
                // Create installation lock file
                file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
                
                // Clear session
                unset($_SESSION['db_config']);
                unset($_SESSION['admin_config']);
                
                $success = 'Installation completed successfully!';
            } catch (Exception $e) {
                $error = 'Installation failed: ' . $e->getMessage();
            }
            break;
    }
}

// Check server requirements for step 1
$requirements = [];
if ($step == 1) {
    $requirements = [
        'PHP Version' => [
            'required' => '7.4.0',
            'current' => phpversion(),
            'status' => version_compare(phpversion(), '7.4.0', '>=')
        ],
        'PDO Extension' => [
            'required' => 'Enabled',
            'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('pdo')
        ],
        'MySQL PDO Extension' => [
            'required' => 'Enabled',
            'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('pdo_mysql')
        ],
        'GD Extension' => [
            'required' => 'Enabled',
            'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('gd')
        ],
        'File Uploads' => [
            'required' => 'Enabled',
            'current' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
            'status' => ini_get('file_uploads')
        ],
        'Config Writable' => [
            'required' => 'Writable',
            'current' => is_writable('config') || is_writable('.') ? 'Writable' : 'Not Writable',
            'status' => is_writable('config') || is_writable('.')
        ],
        'Uploads Writable' => [
            'required' => 'Writable',
            'current' => is_writable('uploads') || is_writable('.') ? 'Writable' : 'Not Writable',
            'status' => is_writable('uploads') || is_writable('.')
        ]
    ];
    
    $allRequirementsMet = array_reduce($requirements, function($carry, $item) {
        return $carry && $item['status'];
    }, true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SJA Foundation Investment Management - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .installer-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .installer-header {
            background: #3f51b5;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .installer-body {
            padding: 30px;
        }
        .step-indicator {
            display: flex;
            margin-bottom: 30px;
            justify-content: space-between;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            position: relative;
        }
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 50%;
            right: 0;
            width: 100%;
            height: 2px;
            background: #e9ecef;
            transform: translateY(-50%);
            z-index: -1;
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }
        .step.active .step-number {
            background: #3f51b5;
            color: white;
        }
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        .btn-primary {
            background-color: #3f51b5;
            border-color: #3f51b5;
        }
        .btn-primary:hover {
            background-color: #303f9f;
            border-color: #303f9f;
        }
        .requirement-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .requirement-status {
            font-weight: bold;
        }
        .status-ok {
            color: #28a745;
        }
        .status-error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h2><i class="bi bi-gear-fill me-2"></i> SJA Foundation Investment Management</h2>
            <p class="mb-0">Installation Wizard</p>
        </div>
        
        <div class="installer-body">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                    <div class="step-number"><?php echo $step > 1 ? '<i class="bi bi-check"></i>' : '1'; ?></div>
                    <div class="step-title">Requirements</div>
                </div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                    <div class="step-number"><?php echo $step > 2 ? '<i class="bi bi-check"></i>' : '2'; ?></div>
                    <div class="step-title">Database</div>
                </div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                    <div class="step-number"><?php echo $step > 3 ? '<i class="bi bi-check"></i>' : '3'; ?></div>
                    <div class="step-title">Admin Account</div>
                </div>
                <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                    <div class="step-number"><?php echo $step > 4 ? '<i class="bi bi-check"></i>' : '4'; ?></div>
                    <div class="step-title">Finish</div>
                </div>
            </div>
            
            <!-- Error/Success Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Step Content -->
            <?php if ($step == 1): ?>
                <div class="step-content">
                    <h4 class="mb-4">System Requirements Check</h4>
                    
                    <div class="requirements-list">
                        <?php foreach ($requirements as $name => $requirement): ?>
                            <div class="requirement-item">
                                <div>
                                    <strong><?php echo $name; ?></strong>
                                    <div class="text-muted small">Required: <?php echo $requirement['required']; ?></div>
                                </div>
                                <div class="requirement-status <?php echo $requirement['status'] ? 'status-ok' : 'status-error'; ?>">
                                    <?php echo $requirement['current']; ?>
                                    <?php if ($requirement['status']): ?>
                                        <i class="bi bi-check-circle-fill ms-2"></i>
                                    <?php else: ?>
                                        <i class="bi bi-x-circle-fill ms-2"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form method="post" class="mt-4">
                        <div class="d-flex justify-content-between">
                            <div></div>
                            <button type="submit" class="btn btn-primary" <?php echo !$allRequirementsMet ? 'disabled' : ''; ?>>
                                Continue <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            <?php elseif ($step == 2): ?>
                <div class="step-content">
                    <h4 class="mb-4">Database Configuration</h4>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="db_host" class="form-label">Database Host</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                        </div>
                        <div class="mb-3">
                            <label for="db_name" class="form-label">Database Name</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" value="sja_investment" required>
                        </div>
                        <div class="mb-3">
                            <label for="db_user" class="form-label">Database Username</label>
                            <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                        </div>
                        <div class="mb-3">
                            <label for="db_pass" class="form-label">Database Password</label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass">
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="installer.php?step=1" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Continue <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            <?php elseif ($step == 3): ?>
                <div class="step-content">
                    <h4 class="mb-4">Admin Account Setup</h4>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="admin_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="admin_name" name="admin_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="admin_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="admin_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                            <div class="form-text">Password must be at least 8 characters long</div>
                        </div>
                        <div class="mb-3">
                            <label for="admin_confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="admin_confirm_password" name="admin_confirm_password" required>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="installer.php?step=2" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Continue <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            <?php elseif ($step == 4): ?>
                <div class="step-content">
                    <h4 class="mb-4">Installation Complete</h4>
                    
                    <?php if (empty($error)): ?>
                        <div class="text-center mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                            <h5 class="mt-3">Congratulations!</h5>
                            <p>SJA Foundation Investment Management Platform has been successfully installed.</p>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-4">
                            <a href="index.php" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Go to Login
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center mb-4">
                            <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 5rem;"></i>
                            <h5 class="mt-3">Installation Failed</h5>
                            <p>Please fix the errors and try again.</p>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-4">
                            <a href="installer.php?step=1" class="btn btn-primary">
                                <i class="bi bi-arrow-repeat me-2"></i> Start Over
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 