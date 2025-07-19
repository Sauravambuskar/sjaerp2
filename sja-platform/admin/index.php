<?php
/**
 * SJA Foundation Investment Management Platform
 * Admin Dashboard
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = auth();

// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = db();
$currentUser = $auth->getCurrentUser();

// Get dashboard statistics
$totalUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'client'")['count'];
$totalInvestments = $db->fetch("SELECT COUNT(*) as count, SUM(amount) as total FROM investments WHERE status = 'active'");
$pendingKYC = $db->fetch("SELECT COUNT(*) as count FROM clients WHERE kyc_status = 'pending'")['count'];
$pendingWithdrawals = $db->fetch("SELECT COUNT(*) as count FROM withdrawal_requests WHERE status = 'pending'")['count'];
$totalEarnings = $db->fetch("SELECT SUM(amount) as total FROM earnings WHERE date = CURDATE()")['total'] ?? 0;
$monthlyEarnings = $db->fetch("SELECT SUM(amount) as total FROM earnings WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")['total'] ?? 0;

// Get recent activities
$recentUsers = $db->fetchAll("
    SELECT u.*, c.client_id 
    FROM users u 
    LEFT JOIN clients c ON u.id = c.user_id 
    WHERE u.role = 'client' 
    ORDER BY u.created_at DESC 
    LIMIT 5
");

$recentTransactions = $db->fetchAll("
    SELECT t.*, u.name as user_name 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 5
");

$recentWithdrawals = $db->fetchAll("
    SELECT w.*, u.name as user_name 
    FROM withdrawal_requests w 
    JOIN users u ON w.user_id = u.id 
    ORDER BY w.created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SJA Foundation</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #2B3A67;
            --secondary-color: #1E3A8A;
            --accent-color: #667eea;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --dark-color: #0A2540;
            --light-color: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-color);
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand {
            color: white;
            font-size: 1.25rem;
            font-weight: bold;
            text-decoration: none;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-link:hover,
        .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .nav-link i {
            width: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .top-bar {
            background: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .card-icon.users { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-icon.investments { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .card-icon.kyc { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .card-icon.withdrawals { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .card-icon.earnings { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .card-icon.monthly { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }

        .card-title {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .card-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .card-change {
            font-size: 0.875rem;
            color: var(--success-color);
        }

        /* Activity Cards */
        .activity-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: 100%;
        }

        .activity-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .activity-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-info h6 {
            margin: 0;
            font-size: 0.9rem;
            color: var(--primary-color);
        }

        .activity-info p {
            margin: 0;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .activity-amount {
            font-weight: 600;
            color: var(--success-color);
        }

        .activity-status {
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        /* Toggle Button */
        .sidebar-toggle {
            display: none;
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 0.5rem;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .sidebar-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                <i class="fas fa-chart-line"></i> SJA Admin
            </a>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-item">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
            </div>
            <div class="nav-item">
                <a href="investments.php" class="nav-link">
                    <i class="fas fa-chart-pie"></i> Investments
                </a>
            </div>
            <div class="nav-item">
                <a href="transactions.php" class="nav-link">
                    <i class="fas fa-exchange-alt"></i> Transactions
                </a>
            </div>
            <div class="nav-item">
                <a href="kyc.php" class="nav-link">
                    <i class="fas fa-shield-alt"></i> KYC Management
                </a>
            </div>
            <div class="nav-item">
                <a href="withdrawals.php" class="nav-link">
                    <i class="fas fa-money-bill-wave"></i> Withdrawals
                </a>
            </div>
            <div class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </div>
            <div class="nav-item">
                <a href="../logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                </div>
                <div>
                    <h6 class="mb-0"><?= htmlspecialchars($currentUser['name']) ?></h6>
                    <small class="text-muted">Administrator</small>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="row g-4 mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="card-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-title">Total Users</div>
                    <div class="card-value"><?= number_format($totalUsers) ?></div>
                    <div class="card-change">+12% this month</div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="card-icon investments">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="card-title">Active Investments</div>
                    <div class="card-value"><?= number_format($totalInvestments['count']) ?></div>
                    <div class="card-change">₹<?= number_format($totalInvestments['total'] ?? 0) ?></div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="card-icon kyc">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="card-title">Pending KYC</div>
                    <div class="card-value"><?= number_format($pendingKYC) ?></div>
                    <div class="card-change">Requires attention</div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="card-icon withdrawals">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-title">Pending Withdrawals</div>
                    <div class="card-value"><?= number_format($pendingWithdrawals) ?></div>
                    <div class="card-change">Awaiting approval</div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="card-icon earnings">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="card-title">Today's Earnings</div>
                    <div class="card-value">₹<?= number_format($totalEarnings) ?></div>
                    <div class="card-change">+5% from yesterday</div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="card-icon monthly">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="card-title">Monthly Earnings</div>
                    <div class="card-value">₹<?= number_format($monthlyEarnings) ?></div>
                    <div class="card-change">+18% this month</div>
                </div>
            </div>
        </div>

        <!-- Charts and Activities -->
        <div class="row g-4">
            <!-- Chart -->
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <h5 class="mb-3">Investment Trends</h5>
                    <canvas id="investmentChart" height="100"></canvas>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="col-lg-4">
                <div class="activity-card">
                    <div class="activity-header">
                        <h6 class="activity-title">Recent Activities</h6>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    
                    <ul class="activity-list">
                        <?php foreach ($recentUsers as $user): ?>
                        <li class="activity-item">
                            <div class="activity-info">
                                <h6>New User Registered</h6>
                                <p><?= htmlspecialchars($user['name']) ?> - <?= formatDateTime($user['created_at']) ?></p>
                            </div>
                            <span class="activity-status status-approved">New</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Transactions and Withdrawals -->
        <div class="row g-4 mt-4">
            <div class="col-lg-6">
                <div class="activity-card">
                    <div class="activity-header">
                        <h6 class="activity-title">Recent Transactions</h6>
                        <a href="transactions.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    
                    <ul class="activity-list">
                        <?php foreach ($recentTransactions as $transaction): ?>
                        <li class="activity-item">
                            <div class="activity-info">
                                <h6><?= htmlspecialchars($transaction['user_name']) ?></h6>
                                <p><?= ucfirst($transaction['type']) ?> - <?= formatDateTime($transaction['created_at']) ?></p>
                            </div>
                            <div class="text-end">
                                <div class="activity-amount">₹<?= number_format($transaction['amount']) ?></div>
                                <span class="activity-status status-<?= $transaction['status'] ?>"><?= ucfirst($transaction['status']) ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="activity-card">
                    <div class="activity-header">
                        <h6 class="activity-title">Recent Withdrawals</h6>
                        <a href="withdrawals.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    
                    <ul class="activity-list">
                        <?php foreach ($recentWithdrawals as $withdrawal): ?>
                        <li class="activity-item">
                            <div class="activity-info">
                                <h6><?= htmlspecialchars($withdrawal['user_name']) ?></h6>
                                <p><?= ucfirst($withdrawal['type']) ?> - <?= formatDateTime($withdrawal['created_at']) ?></p>
                            </div>
                            <div class="text-end">
                                <div class="activity-amount">₹<?= number_format($withdrawal['amount']) ?></div>
                                <span class="activity-status status-<?= $withdrawal['status'] ?>"><?= ucfirst($withdrawal['status']) ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }

        // Investment Chart
        const ctx = document.getElementById('investmentChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Investments (₹)',
                    data: [12000000, 15000000, 18000000, 22000000, 25000000, 30000000],
                    borderColor: '#2B3A67',
                    backgroundColor: 'rgba(43, 58, 103, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + (value / 1000000) + 'M';
                            }
                        }
                    }
                }
            }
        });

        // Auto-refresh dashboard every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>