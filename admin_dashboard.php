<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$userCount = $conn->query("SELECT COUNT(*) AS total_users FROM users WHERE role = 'user'")->fetch_assoc()['total_users'];

// Active users (transactions this month)
$activeUsers = $conn->query("
    SELECT COUNT(DISTINCT user_id) AS total
    FROM (
        SELECT user_id FROM income
        WHERE MONTH(date)=MONTH(CURRENT_DATE()) AND YEAR(date)=YEAR(CURRENT_DATE())
        UNION
        SELECT user_id FROM expenses
        WHERE MONTH(date)=MONTH(CURRENT_DATE()) AND YEAR(date)=YEAR(CURRENT_DATE())
    ) t
")->fetch_assoc()['total'] ?? 0;

// ----- Top 5 Active Users This Month -----
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

// Active users (last 30 days login)
$active = $conn->query("
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

$inactiveAccounts = $passiveUsers;

// ----- Transactions -----
$incomeCount = $conn->query("SELECT COUNT(*) AS total FROM income")->fetch_assoc()['total'];
$expenseCount = $conn->query("SELECT COUNT(*) AS total FROM expenses")->fetch_assoc()['total'];

// Monthly transaction counts
$monthlyIncome = [];
$monthlyExpense = [];
for ($m = 1; $m <= 12; $m++) {
    $incomeResult = $conn->query("
        SELECT COUNT(*) AS total 
        FROM income 
        WHERE MONTH(date) = $m AND YEAR(date) = YEAR(CURDATE())
    ");
    $monthlyIncome[] = $incomeResult->fetch_assoc()['total'];

    $expenseResult = $conn->query("
        SELECT COUNT(*) AS total 
        FROM expenses 
        WHERE MONTH(date) = $m AND YEAR(date) = YEAR(CURDATE())
    ");
    $monthlyExpense[] = $expenseResult->fetch_assoc()['total'];
}

// ----- Alerts -----
$alerts = [];
if ($userCount > 0) {
    $passivePercentage = ($passiveUsers / $userCount) * 100;
    if ($passivePercentage > 40) {
        $alerts[] = [
            "type" => "warning",
            "message" => "<i class='fa-solid fa-face-meh'></i> High number of passive users detected (" . round($passivePercentage) . "%)."
        ];
    }
}
if ($incomeCount == 0 && $expenseCount == 0) {
    $alerts[] = [
        'type' => 'danger',
        'message' => '🚨 No transactions recorded in the system yet.'
    ];
}

// ----- Insights -----
$engagementRate = ($active / max($userCount, 1)) * 100;
$insights = [
    ['icon'=>'fa-chart-line', 'message'=>"User engagement: ".round($engagementRate)."%", 'color'=>$engagementRate>=70?'#2e7d32':($engagementRate>=40?'#f9a825':'#c62828')],
    ['icon'=>$passiveUsers>$activeUsers?'fa-user-clock':'fa-user-check', 'message'=>$passiveUsers>$activeUsers?"Inactive users > Active":"Majority active users", 'color'=>$passiveUsers>$activeUsers?'#f57f17':'#2e7d32'],
    ['icon'=>$incomeCount+$expenseCount>0?'fa-money-bill-trend-up':'fa-circle-xmark', 'message'=>$incomeCount+$expenseCount>0?"Transactions active":"Transactions underutilized", 'color'=>$incomeCount+$expenseCount>0?'#2e7d32':'#c62828']
];

// ----- Health Score -----
$activityScore = min(40, round(($engagementRate / 100) * 40));
$transactionScore = ($incomeCount + $expenseCount) > 0 ? 35 : 10;
$usageRate = $userCount > 0 ? (($userCount - $inactiveAccounts) / $userCount) * 100 : 0;
$usageScore = round(($usageRate / 100) * 25);

$healthScore = $activityScore + $transactionScore + $usageScore;

// Health label & color
if ($healthScore >= 75) {
    $healthLabel = 'Excellent';
    $healthColor = '#2e7d32';
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
    $trendColor = '#2e7d32';
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
    ['label'=>'Total Users','value'=>$userCount,'icon'=>'fa-users','color'=>'#0c4aad'],
    ['label'=>'Active Users (Month)','value'=>$activeUsers,'icon'=>'fa-user-check','color'=>'#2e7d32'],
    ['label'=>'Passive Users','value'=>$passiveUsers,'icon'=>'fa-user-slash','color'=>'#c62828'],
    ['label'=>'Income Records','value'=>$incomeCount,'icon'=>'fa-money-bill','color'=>'#2e7d32'],
    ['label'=>'Expense Records','value'=>$expenseCount,'icon'=>'fa-wallet','color'=>'#c62828'],
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', Arial, sans-serif;
            background: #eef2f7;
        }

        .navbar {
            background: #0c4aad;
            padding: 15px 30px;
            color: white;
            font-size: 20px;
            font-weight: 600;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome {
            font-size: 28px;
            margin-bottom: 25px;
            font-weight: 600;
            color: #333;
        }

        .stats {
            display: flex;
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            flex: 1;
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            transition: 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            margin: 0 0 10px;
            font-weight: 500;
            color: #444;
        }

        .card p {
            font-size: 22px;
            font-weight: 700;
            color: #0c4aad;
        }

        .buttons a {
            display: inline-block;
            margin: 10px 10px 0 0;
            padding: 12px 22px;
            background: #0c4aad;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
        }

        .buttons a:hover {
            background: #093f8d;
        }

        .logout-btn {
            background: red !important;
        }

        .logout-btn:hover {
            background: #c70000 !important;
        }

        .alert {
            padding:12px 18px;
            border-radius:8px;
            margin-bottom:10px;
            font-weight:500;
        }

        .alert.warning {
            background:#fff8e1;
            color:#f57f17;
        }

        .alert.danger {
            background:#ffebee;
            color:#c62828;
        }

        .stats-card {
            display:flex; 
            align-items:center;
            gap:12px;
            padding:20px;
            border-radius:14px;
            background:white;
            box-shadow:0 4px 12px rgba(0,0,0,0.08);
            flex:1;
            transition:0.2s;
        }
        .stats-card:hover {
            transform:translateY(-4px);
        }
        .stats-card i {
            font-size:15px;
            color:white;
            padding:12px;
            border-radius:50%;
            background:#0c4aad;
        }
        .stats-card p {
            margin:0;
            font-size:23px;
            font-weight:600;
        }
        
        .stats-card span {
            color:#555;
            font-size:14px;
        }
        .insight-card {
            display:flex; 
            align-items:center;
            gap:12px;
            padding:15px;
            border-radius:12px;
            margin-bottom:15px;
            color:white;
            font-weight:500;
        }
        .top-users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-family: 'Poppins', Arial, sans-serif;
        }

        .top-users-table th,
        .top-users-table td {
            padding: 12px 15px;
            text-align: left;
        }

        .top-users-table thead {
            background-color: #0c4aad;
            color: #fff;
            font-weight: 600;
            border-radius: 10px;
        }

        .top-users-table tbody tr {
            background: #ffffff;
            transition: 0.2s;
            cursor: default;
        }

        .top-users-table th:first-child,
        .top-users-table td:first-child {
            width: 50px;
        }
        .top-users-table tbody tr:nth-child(even){
            background:#f4f6f8;
        }
        .top-users-table td {
            color: #333;
            font-weight: 500;
        }
        .health-header {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .health-info-icon {
            color: #555;
            cursor: pointer;
            font-size: 16px;
        }

        .health-tooltip {
            position: absolute;
            top: 30px;
            right: 0;
            width: 260px;
            background: #ffffff;
            border: 1px solid #ddd;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
            display: none;
            z-index: 100;
        }

        .health-tooltip ul {
            margin: 8px 0;
            padding-left: 16px;
        }

        .health-tooltip li {
            margin-bottom: 4px;
        }

        .health-tooltip span {
            font-weight: 600;
        }
        .insight-row { 
            display:flex; 
            flex-wrap:wrap; 
            gap:15px; 
            margin-bottom:30px; 
            }
        .insight-card { 
            flex:1 1 250px; 
            display:flex; 
            align-items:center; 
            gap:10px; 
            padding:15px; 
            border-radius:12px; 
            color:white; 
            font-weight:500; 
        }
        .insight-card i { 
            font-size:20px; 
        }
        .chart-container { display:flex; flex-wrap:wrap; gap:30px; justify-content:center; }
    </style>
</head>
<body>
    <div class="navbar">🛠 Admin Dashboard</div>
    <div class="container">
        <div class="welcome">Welcome, Admin <i class="fa-solid fa-user-tie" style="color:#388dce;"></i></div>
        <div class="card" style="text-align:center;margin-bottom:30px;">
    <div class="health-header" style="justify-content:center;">
        <h2>System Health Score</h2>
        <i class="fa-solid fa-circle-info health-info-icon"></i>

        <div class="health-tooltip">
            <strong>Health Score Breakdown</strong>
            <ul>
                <li>User Engagement: <span>40%</span></li>
                <li>Transaction Activity: <span>35%</span></li>
                <li>Active vs Passive Balance: <span>25%</span></li>
            </ul>
            <small>Score is calculated dynamically based on system usage.</small>
        </div>
    </div>

    <div style="font-size:48px;font-weight:800;color:<?= $healthColor ?>;margin:12px 0;">
        <?= $healthScore ?>/100
    </div>

    <div style="font-size:17px;font-weight:600;color:<?= $trendColor ?>;
                display:flex;justify-content:center;gap:8px;margin-bottom:8px;">
        <i class="fa-solid <?= $trendIcon ?>"></i>
        <?= $trendText ?> from last month
    </div>

    <div style="display:inline-block;padding:6px 18px;border-radius:20px;
                background:<?= $healthColor ?>;color:white;font-weight:600;">
        <?= $healthLabel ?>
    </div>
</div>
        <!-- Insights -->
        <div class="insight-row">
        <?php foreach($insights as $ins): ?>
        <div class="insight-card" style="background:<?= $ins['color'] ?>"><i class="fa-solid <?= $ins['icon'] ?>"></i> <?= $ins['message'] ?></div>
        <?php endforeach; ?>
        </div>
        <!-- Alerts -->
        <?php if (!empty($alerts)): ?>
            <div style="margin-bottom:25px;">
                <?php foreach ($alerts as $alert): ?>
                    <div class="alert <?= $alert['type']; ?>"><?= $alert['message']; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!--- Stats---> 
        <div class="stats">
            <?php foreach($statsCards as $card): ?>
            <div class="stats-card">
                <i class="fa-solid <?= $card['icon'] ?>" style="background:<?= $card['color'] ?>;"></i>
                <div><p><?= $card['value'] ?></p><span><?= $card['label'] ?></span></div>
            </div>
            <?php endforeach; ?>
            </div>
            <!-- Top Users -->
            <div class="card" style="margin-bottom:20px;">
                <h3>Top 3 Active Users (This Month)</h3>
                <table class="top-users-table">
                    <thead><tr><th>S.N.</th><th>Name</th><th>Transactions</th></tr></thead>
                    <tbody>
                    <?php $i=1; while($user=$topUsers->fetch_assoc()): ?>
                    <tr>
                    <td><?= $i ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= $user['transactions'] ?></td>
                    </tr>
                    <?php $i++; endwhile; ?>
                    </tbody>
                </table>
            </div>

        <!-- User Activity Pie Chart -->
        <div class="card" style="margin-top:30px; max-width:420px; margin:auto;">
            <h3 style="margin-bottom:15px;text-align:center;">User Activity Overview</h3>
            <div style="display:flex;justify-content:center;">
                <canvas id="userActivityChart" width="280" height="280"></canvas>
            </div>
        </div>

        <!-- Monthly Trend Chart -->
        <div class="card" style="margin-top:30px;">
            <h3 style="margin-bottom:15px;">Monthly Transaction Trend (<?= date('Y') ?>)</h3>
            <canvas id="monthlyTrendChart" height="120"></canvas>
        </div>

        <!-- Buttons -->
        <div class="section buttons">
            <a href="view_users.php"><i class="fa-solid fa-users"></i> View Users</a>
            <a href="manage_category.php"><i class="fa-solid fa-list"></i> Manage Categories</a>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>
    <script>
    const infoIcon = document.querySelector('.health-info-icon');
    const tooltip = document.querySelector('.health-tooltip');

    infoIcon.addEventListener('mouseenter', () => {
        tooltip.style.display = 'block';
    });

    infoIcon.addEventListener('mouseleave', () => {
        tooltip.style.display = 'none';
    });
    </script>
    <script>
        const ctx = document.getElementById('userActivityChart').getContext('2d');
        new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Active Users','Passive Users'],
            datasets: [{
                data: [<?= $active ?>, <?= $passiveUsers ?>],
                backgroundColor:['#2e7d32','#c62828']
            }]
        },
        options: {
            responsive:false,
            onClick:(event,elements)=>{
            if(elements.length>0){
            const index=elements[0].index;
            const label=elements[0].index===0?'active':'passive';
            window.location.href='view_users.php?status='+label;
        }
        },
        plugins:{legend:{position:'bottom'}}
    }
        });
        </script>
    <script>
        const trendCtx=document.getElementById('monthlyTrendChart').getContext('2d');
            new Chart(trendCtx,{
                type:'line',
                data:{
                    labels:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                    datasets:[
                        {label:'Income',data:<?= json_encode($monthlyIncome) ?>,borderWidth:2,tension:0.4},
                        {label:'Expense',data:<?= json_encode($monthlyExpense) ?>,borderWidth:2,tension:0.4}
                    ]
                },
                options:{
                    responsive:true,
                    plugins:{legend:{position:'bottom'}},
                    scales:{y:{beginAtZero:true}}
                }
            });
    </script>
</body>
</html>
