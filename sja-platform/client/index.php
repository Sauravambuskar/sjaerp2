<?php
/**
 * SJA Foundation Investment Management Platform
 * Client Dashboard
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = auth();

// Check if user is logged in and is client
if (!$auth->isLoggedIn() || $auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = db();
$currentUser = $auth->getCurrentUser();
$userId = $currentUser['id'];

// Get user data
$client = $db->fetch("SELECT * FROM clients WHERE user_id = ?", [$userId]);
$wallet = $db->fetch("SELECT * FROM wallets WHERE user_id = ?", [$userId]);

// Get user statistics
$investmentSummary = getUserInvestmentSummary($userId);
$earningsSummary = getUserEarningsSummary($userId);
$referralCount = getUserReferralCount($userId);

// Get recent transactions
$recentTransactions = $db->fetchAll("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$userId]);

// Get active investments
$activeInvestments = $db->fetchAll("
    SELECT * FROM investments 
    WHERE user_id = ? AND status = 'active' 
    ORDER BY created_at DESC 
    LIMIT 5
", [$userId]);

// Get recent earnings
$recentEarnings = $db->fetchAll("
    SELECT * FROM earnings 
    WHERE user_id = ? 
    ORDER BY date DESC 
    LIMIT 5
", [$userId]);

// Get notifications
$notifications = getUserNotifications($userId, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SJA Foundation</title>
    
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

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
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
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .nav-menu {
            display: flex;
            gap: 1rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Main Content */
        .main-content {
            padding: 2rem 0;
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

        .card-icon.wallet { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-icon.investments { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .card-icon.earnings { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .card-icon.referrals { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

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
            justify-content: space-between;
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
        .status-completed { background: #d1ecf1; color: #0c5460; }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-btn {
            background: white;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 1rem;
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .action-btn i {
            font-size: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h5 class="mb-0"><?= htmlspecialchars($currentUser['name']) ?></h5>
                        <small>Client ID: <?= htmlspecialchars($client['client_id']) ?></small>
                    </div>
                </div>
                
                <nav>
                    <ul class="nav-menu">
                        <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a href="investments.php"><i class="fas fa-chart-pie"></i> Investments</a></li>
                        <li><a href="wallet.php"><i class="fas fa-wallet"></i> Wallet</a></li>
                        <li><a href="referrals.php"><i class="fas fa-users"></i> Referrals</a></li>
                        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="wallet.php" class="action-btn">
                    <i class="fas fa-plus"></i>
                    <span>Add Money</span>
                </a>
                <a href="investments.php" class="action-btn">
                    <i class="fas fa-chart-line"></i>
                    <span>New Investment</span>
                </a>
                <a href="wallet.php" class="action-btn">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Withdraw</span>
                </a>
                <a href="referrals.php" class="action-btn">
                    <i class="fas fa-share"></i>
                    <span>Share Referral</span>
                </a>
            </div>

            <!-- Dashboard Stats -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="dashboard-card">
                        <div class="card-icon wallet">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="card-title">Wallet Balance</div>
                        <div class="card-value">₹<?= number_format($wallet['balance'] ?? 0) ?></div>
                        <div class="card-change">Available for investment</div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="dashboard-card">
                        <div class="card-icon investments">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="card-title">Active Investments</div>
                        <div class="card-value"><?= number_format($investmentSummary['active']['count'] ?? 0) ?></div>
                        <div class="card-change">₹<?= number_format($investmentSummary['active']['total_amount'] ?? 0) ?></div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="dashboard-card">
                        <div class="card-icon earnings">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="card-title">Today's Earnings</div>
                        <div class="card-value">₹<?= number_format($earningsSummary['daily']) ?></div>
                        <div class="card-change">+₹<?= number_format($earningsSummary['monthly']) ?> this month</div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="dashboard-card">
                        <div class="card-icon referrals">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-title">Total Referrals</div>
                        <div class="card-value"><?= number_format($referralCount) ?></div>
                        <div class="card-change">Earn from referrals</div>
                    </div>
                </div>
            </div>

            <!-- Charts and Activities -->
            <div class="row g-4">
                <!-- Chart -->
                <div class="col-lg-8">
                    <div class="dashboard-card">
                        <h5 class="mb-3">Investment Performance</h5>
                        <canvas id="investmentChart" height="100"></canvas>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="col-lg-4">
                    <div class="activity-card">
                        <div class="activity-header">
                            <h6 class="activity-title">Notifications</h6>
                            <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        
                        <ul class="activity-list">
                            <?php if (empty($notifications)): ?>
                            <li class="activity-item">
                                <div class="activity-info">
                                    <p>No new notifications</p>
                                </div>
                            </li>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                <li class="activity-item">
                                    <div class="activity-info">
                                        <h6><?= htmlspecialchars($notification['title']) ?></h6>
                                        <p><?= htmlspecialchars($notification['message']) ?></p>
                                    </div>
                                    <span class="activity-status status-<?= $notification['type'] ?>"><?= ucfirst($notification['type']) ?></span>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row g-4 mt-4">
                <div class="col-lg-6">
                    <div class="activity-card">
                        <div class="activity-header">
                            <h6 class="activity-title">Recent Transactions</h6>
                            <a href="wallet.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        
                        <ul class="activity-list">
                            <?php if (empty($recentTransactions)): ?>
                            <li class="activity-item">
                                <div class="activity-info">
                                    <p>No transactions yet</p>
                                </div>
                            </li>
                            <?php else: ?>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                <li class="activity-item">
                                    <div class="activity-info">
                                        <h6><?= ucfirst($transaction['type']) ?></h6>
                                        <p><?= formatDateTime($transaction['created_at']) ?></p>
                                    </div>
                                    <div class="text-end">
                                        <div class="activity-amount">₹<?= number_format($transaction['amount']) ?></div>
                                        <span class="activity-status status-<?= $transaction['status'] ?>"><?= ucfirst($transaction['status']) ?></span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="activity-card">
                        <div class="activity-header">
                            <h6 class="activity-title">Active Investments</h6>
                            <a href="investments.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        
                        <ul class="activity-list">
                            <?php if (empty($activeInvestments)): ?>
                            <li class="activity-item">
                                <div class="activity-info">
                                    <p>No active investments</p>
                                </div>
                            </li>
                            <?php else: ?>
                                <?php foreach ($activeInvestments as $investment): ?>
                                <li class="activity-item">
                                    <div class="activity-info">
                                        <h6><?= htmlspecialchars($investment['plan_name']) ?></h6>
                                        <p>Matures: <?= formatDate($investment['maturity_date']) ?></p>
                                    </div>
                                    <div class="text-end">
                                        <div class="activity-amount">₹<?= number_format($investment['amount']) ?></div>
                                        <span class="activity-status status-completed"><?= $investment['interest_rate'] ?>%</span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Investment Chart
        const ctx = document.getElementById('investmentChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Investment Value (₹)',
                    data: [50000, 75000, 100000, 125000, 150000, 175000],
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
                                return '₹' + value.toLocaleString();
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