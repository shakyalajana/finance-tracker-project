<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$userCount = $conn->query("SELECT COUNT(*) AS total_users FROM users WHERE role = 'user'")->fetch_assoc()['total_users'];

// Active users (login this month)
$activeLoginUsers = $conn->query("
    SELECT COUNT(DISTINCT user_id) AS total
    FROM (
        SELECT user_id FROM income
        WHERE MONTH(date)=MONTH(CURRENT_DATE()) AND YEAR(date)=YEAR(CURRENT_DATE())
        UNION
        SELECT user_id FROM expenses
        WHERE MONTH(date)=MONTH(CURRENT_DATE()) AND YEAR(date)=YEAR(CURRENT_DATE())
    ) t
")->fetch_assoc()['total'] ?? 0;

// Active users (last 30 days login)
$activeUsers = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE role = 'user' 
    AND last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch_assoc()['total'];

// Passive users (inactive accounts)
$passiveUsers = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE role = 'user' 
    AND (
        last_login IS NULL 
        OR last_login < DATE_SUB(NOW(), INTERVAL 30 DAY)
    )
")->fetch_assoc()['total'];

// Transactions
$incomeCount = $conn->query("SELECT COUNT(*) AS total FROM income")->fetch_assoc()['total'];
$expenseCount = $conn->query("SELECT COUNT(*) AS total FROM expenses")->fetch_assoc()['total'];

// Monthly transaction counts - INCOME
$incomeData = array_fill(1, 12, 0);
$incomeQuery = mysqli_query($conn, "
    SELECT MONTH(date) as month, COUNT(*) as total
    FROM income
    WHERE YEAR(date) = YEAR(CURDATE())
    GROUP BY MONTH(date)
");

while ($row = mysqli_fetch_assoc($incomeQuery)) {
    $incomeData[(int)$row['month']] = (int)$row['total'];
}

$expenseData = array_fill(1, 12, 0);
$expenseQuery = mysqli_query($conn, "
    SELECT MONTH(date) as month, COUNT(*) as total
    FROM expenses
    WHERE YEAR(date) = YEAR(CURDATE())
    GROUP BY MONTH(date)
");

while ($row = mysqli_fetch_assoc($expenseQuery)) {
    $expenseData[(int)$row['month']] = (int)$row['total'];
}

$monthlyIncome = array_values($incomeData);
$monthlyExpense = array_values($expenseData);

// Top 3 Active Users This Month
$topUsers = $conn->query("
    SELECT u.user_id, u.name, COUNT(t.user_id) AS transactions
    FROM users u
    LEFT JOIN (
        SELECT user_id FROM income WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
        UNION ALL
        SELECT user_id FROM expenses WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
    ) t ON u.user_id = t.user_id
    WHERE u.role = 'user'
    GROUP BY u.user_id
    ORDER BY transactions DESC
    LIMIT 3
");

// Alerts
$alerts = [];
if ($userCount > 0) {
    $passivePercentage = ($passiveUsers / $userCount) * 100;
    if ($passivePercentage > 40) {
        $alerts[] = [
            "type" => "warning",
            "message" => "High number of passive users detected (" . round($passivePercentage) . "%)."
        ];
    }
}
if ($incomeCount == 0 && $expenseCount == 0) {
    $alerts[] = [
        'type' => 'danger',
        'message' => 'No transactions recorded in the system yet.'
    ];
}

// Insights (FIXED: consistent variable usage)
$engagementRate = ($activeUsers / max($userCount, 1)) * 100;
$insights = [
    ['icon'=>'fa-chart-line', 'message'=>"User engagement: ".round($engagementRate)."%", 'color'=>$engagementRate>=70?'#30793b':($engagementRate>=40?'#f9a825':'#ef4444')],
    ['icon'=>$passiveUsers>$activeUsers?'fa-user-clock':'fa-user-check', 'message'=>$passiveUsers>$activeUsers?"Inactive users > Active":"Majority active users", 'color'=>$passiveUsers>$activeUsers?'#0ea5e9':'#30793b'],
    ['icon'=>$incomeCount+$expenseCount>0?'fa-money-bill-trend-up':'fa-circle-xmark', 'message'=>$incomeCount+$expenseCount>0?"Transactions active":"Transactions underutilized", 'color'=>$incomeCount+$expenseCount>0?'#16a34a':'#c62828']
];

// Health Score
$activityScore = min(40, round(($engagementRate / 100) * 40));

$thisMonthTransactions = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM income WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())) +
        (SELECT COUNT(*) FROM expenses WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE()))
    AS total
")->fetch_assoc()['total'] ?? 0;
$transactionsPerUser = $activeLoginUsers > 0 
    ? $thisMonthTransactions / $activeLoginUsers 
    : 0;
if ($transactionsPerUser >= 20) {
    $transactionScore = 35;         
} elseif ($transactionsPerUser >= 10) {
    $transactionScore = 28;        
} elseif ($transactionsPerUser >= 5) {
    $transactionScore = 20;       
} elseif ($transactionsPerUser > 0) {
    $transactionScore = 12;        
} else {
    $transactionScore = 5;        
}

$usageRate = $userCount > 0 ? (($userCount - $passiveUsers) / $userCount) * 100 : 0;
$usageScore = round(($usageRate / 100) * 25);

$healthScore = $activityScore + $transactionScore + $usageScore;

// Health label & color
if ($healthScore >= 75) {
    $healthLabel = 'Excellent';
    $healthColor = '#30793b';
} elseif ($healthScore >= 50) {
    $healthLabel = 'Fair';
    $healthColor = '#f9a825';
} else {
    $healthLabel = 'Critical';
    $healthColor = '#c62828';
}

// Previous month trend
$prevMonth = date('m', strtotime('-1 month'));
$prevYear = date('Y', strtotime('-1 month'));
$prevActiveUsers = $conn->query("
    SELECT COUNT(DISTINCT user_id) AS total
    FROM (
        SELECT user_id FROM income
        WHERE MONTH(date)=$prevMonth AND YEAR(date)=$prevYear
        UNION
        SELECT user_id FROM expenses
        WHERE MONTH(date)=$prevMonth AND YEAR(date)=$prevYear
    ) t
")->fetch_assoc()['total'] ?? 0;
$prevEngagementRate = $userCount > 0 ? ($prevActiveUsers / $userCount) * 100 : 0;
$prevActivityScore = min(40, round(($prevEngagementRate / 100) * 40));
$prevTransactionScore = ($incomeCount + $expenseCount) > 0 ? 35 : 10;
$prevUsageScore = $usageScore;
$prevHealthScore = $prevActivityScore + $prevTransactionScore + $prevUsageScore;

// Trend icon/color
if ($healthScore > $prevHealthScore) {
    $trendIcon = 'fa-arrow-up';
    $trendText = 'Improving';
    $trendColor = '#30793b';
} elseif ($healthScore < $prevHealthScore) {
    $trendIcon = 'fa-arrow-down';
    $trendText = 'Declining';
    $trendColor = '#c62828';
} else {
    $trendIcon = 'fa-minus';
    $trendText = 'Stable';
    $trendColor = '#f9a825';
}

$statsCards = [
    ['label'=>'Total Users','value'=>$userCount,'icon'=>'fa-users','color'=>'#4f46e5'],
    ['label'=>'Active Users (Month)','value'=>$activeLoginUsers,'icon'=>'fa-user-check','color'=>'#16a34a'],
    ['label'=>'Passive Users','value'=>$passiveUsers,'icon'=>'fa-user-slash','color'=>'#f59e0b'],
    ['label'=>'Income Records','value'=>$incomeCount,'icon'=>'fa-money-bill','color'=>'#0ea5e9'],
    ['label'=>'Expense Records','value'=>$expenseCount,'icon'=>'fa-wallet','color'=>'#ef4444'],
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Finance Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="CSS/admin_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
         <div class="header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="logo-text">
                    <h1>Finance Tracker</h1>
                    <p>Admin Dashboard</p>
                </div>
            </div>
            <div class="user-info">
                <div class="user-avatar">A</div>
                <div class="user-details">
                    <h3>Admin User</h3>
                    <p>System Administrator</p>
                </div>
            </div>
        </div>
        
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-text">
                <h2>Welcome back, Admin! <i class="fa-solid fa-user-tie" style="color: #667eea;"></i></h2>
                <p>Here's what's happening with your finance tracker today.</p>
            </div>
        </div>
        
        <!-- Health Score Card -->
        <div class="health-card">
            <div class="health-header">
                <h2>System Health Score</h2>
                <i class="fa-solid fa-circle-info health-info-icon" id="healthInfoIcon"></i>
            </div>
            <div class="health-circle-wrapper">
                <canvas id="healthScoreChart" width="150" height="150"></canvas>
                <div class="health-score-text" style="color:<?= $healthColor ?>;">
                <?= $healthScore ?>/100
            </div>

            </div>
            <div class="health-trend" style="color:<?= $trendColor ?>;">
                <i class="fa-solid <?= $trendIcon ?>"></i>
                <?= $trendText ?> from last month
            </div>
            <div class="health-label" style="background:<?= $healthColor ?>;">
                <?= $healthLabel ?>
            </div>
        </div>
        
        <!-- Insights -->
        <div class="insight-row">
            <?php foreach($insights as $ins): ?>
            <div class="insight-card" style="background:<?= $ins['color'] ?>;">
                <i class="fa-solid <?= $ins['icon'] ?>"></i>
                <span><?= $ins['message'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Alerts -->
        <?php if (!empty($alerts)): ?>
            <?php foreach ($alerts as $alert): ?>
                <div class="alert <?= $alert['type']; ?>">
                    <i class="fa-solid <?= $alert['type'] === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-exclamation' ?>"></i>
                    <?= $alert['message']; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <?php foreach($statsCards as $card): ?>
            <div class="stats-card">
                <div class="stats-content">
                    <div class="stats-icon" style="background:<?= $card['color'] ?>;">
                        <i class="fa-solid <?= $card['icon'] ?>"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?= $card['label'] ?></h3>
                        <p><?= $card['value'] ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Top Users Card -->
        <div class="top-users-card">
            <h3>Top 3 Active Users This Month</h3>
            <table class="top-users-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Name</th>
                        <th>Transactions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1; 
                    while($user = $topUsers->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><div class="rank-circle"><?= $i ?></div></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= $user['transactions'] ?></td>
                    </tr>
                    <?php $i++; endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Charts -->
        <div class="chart-grid">
            <div class="chart-card">
                <h3><i class="fa-solid fa-chart-simple"></i> User Activity Overview</h3>
                <canvas id="userActivityChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fa-solid fa-money-bill-trend-up"></i> Monthly Transaction Trend (<?= date('Y') ?>)</h3>
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="view_users.php" class="btn view">
                <i class="fa-solid fa-users"></i> View All Users
            </a>
            <a href="manage_category.php" class="btn manage">
                <i class="fa-solid fa-list"></i> Manage Categories
            </a>
            <a href="logout.php" class="btn out">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>
    
    <script>
        // Circular Health Score Chart
        const healthCtx = document.getElementById('healthScoreChart').getContext('2d');

        new Chart(healthCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [<?= $healthScore ?>, <?= 100 - $healthScore ?>],
                    backgroundColor: ['<?= $healthColor ?>', '#e0e0e0'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '75%',
                responsive: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });

        // User Activity Pie Chart
        const ctx = document.getElementById('userActivityChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active Users', 'Passive Users'],
                datasets: [{
                    data: [<?= $activeLoginUsers ?>, <?= $passiveUsers ?>],
                    backgroundColor: [
                        '#16a34a',
                        '#0ea5e9'
                    ],
                    borderColor: [
                        '#16a34a',
                        '#0ea5e9'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                family: 'Poppins'
                            }
                        }
                    }
                },
                onClick: (event, elements) => {
                    if(elements.length > 0) {
                        const label = elements[0].index === 0 ? 'active' : 'passive';
                        window.location.href = 'view_users.php?status=' + label;
                    }
                }
            }
        });
        
        // Monthly Trend Line Chart
        const trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Income',
                        data: <?= json_encode($monthlyIncome) ?>,
                        borderColor: 'rgba(46, 125, 50, 1)',
                        backgroundColor: 'rgba(46, 125, 50, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Expense',
                        data: <?= json_encode($monthlyExpense) ?>,
                        borderColor: 'rgba(102, 126, 234, 1)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                family: 'Poppins'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>