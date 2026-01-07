<?php
session_start();
require 'db.php';
include "header.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$month = date('m');
$year  = date('Y');

// Monthly totals
$total_income = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(amount) AS total FROM income WHERE user_id='$user_id' AND MONTH(date)='$month' AND YEAR(date)='$year'"))['total'] ?? 0;

$total_expense = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(amount) AS total FROM expenses WHERE user_id='$user_id' AND MONTH(date)='$month' AND YEAR(date)='$year'"))['total'] ?? 0;

$savings = $total_income - $total_expense;

$all_income = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM income WHERE user_id='$user_id'"))['total'] ?? 0;
$all_expense = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM expenses WHERE user_id='$user_id'"))['total'] ?? 0;
$all_balance = $all_income - $all_expense;

$limit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT limit_amount FROM expense_limits WHERE user_id='$user_id' AND month='$month' AND year='$year'"))['limit_amount'] ?? 0;

$category_labels = $category_data = [];
$cat_q = mysqli_query($conn, "SELECT description, SUM(amount) AS total FROM expenses WHERE user_id='$user_id' AND MONTH(date)='$month' AND YEAR(date)='$year' GROUP BY description");
while ($row = mysqli_fetch_assoc($cat_q)) {
    $category_labels[] = $row['description'];
    $category_data[] = $row['total'];
}

// Yearly income/expense data
$yearly_income = $yearly_expense = [];
for ($m = 1; $m <= 12; $m++) {
    $yearly_income[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM income WHERE user_id='$user_id' AND MONTH(date)='$m' AND YEAR(date)='$year'"))['total'] ?? 0;
    $yearly_expense[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM expenses WHERE user_id='$user_id' AND MONTH(date)='$m' AND YEAR(date)='$year'"))['total'] ?? 0;
}

// Recent transactions
$recent_q = mysqli_query($conn, "
    SELECT 'Income' AS type, amount, description, date FROM income WHERE user_id = '$user_id'
    UNION ALL
    SELECT 'Expense' AS type, amount, description, date FROM expenses WHERE user_id = '$user_id'
    ORDER BY date DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            font-family:'Poppins', 
            Arial; background:#eef2f7; 
            margin:0; 
            padding:0; 
        }
        .container { 
            max-width: 1100px; 
            margin: 40px auto; 
            padding: 0 20px; 
        }
        .welcome { 
            font-size: 28px; 
            margin-bottom: 20px; 
            font-weight: 600; 
            color: #333; 
        }
        .stats { 
            display: flex; 
            gap: 25px; 
            margin-bottom: 40px; 
            flex-wrap: wrap; 
        }
        .card { 
            flex:1; 
            background:white; 
            padding:25px; 
            border-radius:14px; 
            box-shadow:0 6px 18px rgba(0,0,0,0.08); 
            transition:transform 0.2s; 
        }
        .card:hover { 
            transform:translateY(-5px); 
        }
        .card h3 { 
            margin:0 0 10px; 
            font-weight:500; 
            color:#555; 
        }
        .card p { 
            font-size:22px; 
            font-weight:700; 
            color:#1a73e8; 
        }
        .actions { 
            background:white; 
            padding:25px; 
            border-radius:14px; 
            box-shadow:0 6px 18px rgba(0,0,0,0.08); 
        }
        .buttons a { 
            display:inline-block; 
            margin:10px 10px 0 0; 
            padding:12px 22px; 
            background:#1a73e8; 
            color:white; 
            border-radius:8px; 
            text-decoration:none; 
            font-weight:500; 
            transition:0.2s; 
        }
        .buttons a:hover { 
            background:#0f5ccc; 
        }
        .limit { 
            margin:20px 0; 
            padding:12px; 
            background:#ffeded; 
            border-left:6px solid #d32f2f; 
            border-radius:8px; 
            color:#b71c1c; 
            font-weight:500; 
        }
        .section-title { 
            margin: 30px 0 15px; 
            padding: 15px 20px; 
            background:white; 
            border-radius:10px; 
            color:#444; 
            box-shadow:0 4px 12px rgba(0,0,0,0.05); 
        }
        .charts { 
            display:flex; 
            flex-wrap:wrap; 
            gap:20px; 
            margin:30px 0; 
        }
        .chart { 
            flex:1; 
            min-width:300px; 
            background:white; 
            padding:20px; 
            border-radius:12px; 
            box-shadow:0 6px 18px rgba(0,0,0,0.08); 
        }

        .chart-controls {
            margin: 20px 0;
            text-align: left;
            background:white; 
            padding:25px; 
            border-radius:14px; 
            box-shadow:0 6px 18px rgba(0,0,0,0.08); 
        }

        .chart-controls button {
            padding: 10px 18px;
            margin: 0 8px;
            border: none;
            border-radius: 8px;
            background: #1a73e8;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .chart-controls button:hover {
            background: #0f5ccc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome">Welcome, <?php echo $_SESSION['name']; ?> 
        <i class="fa-regular fa-face-laugh-beam" style="color: #1887dbff;"></i></div>

        <h2 class="section-title">This Month — <?php echo date('F Y'); ?></h2>
        <div class="stats">
            <div class="card"><h3>Income</h3><p style="color:#2e7d32;">Rs. <?= number_format($total_income,2); ?></p></div>
            <div class="card"><h3>Expenses</h3><p style="color:#c62828;">Rs. <?= number_format($total_expense,2); ?></p></div>
            <div class="card"><h3>Balance</h3><p style="color:#1565c0;">Rs. <?= number_format($savings,2); ?></p></div>
        </div>

    <?php if($limit>0 && $total_expense>$limit): ?>
        <div class="limit"><i class="fa-solid fa-triangle-exclamation"></i> Monthly expense limit exceeded! 
        <br>Limit: Rs. <?= $limit; ?> | Spent: Rs. <?= $total_expense; ?></div>
    <?php endif; ?>

    <!-- Pie Chart -->
    <div class="chart" style="margin-bottom:40px;">
        <h3 style="text-align:center; margin-bottom:15px;">This Month — Income vs Expense</h3>
        <canvas id="monthPieChart" style="max-width:350px; margin:0 auto; display:block;"></canvas>
    </div>

    <!-- Overall Summary -->
    <h2 class="section-title">Overall Summary</h2>
    <div class="stats">
        <div class="card"><h3>Total Income</h3><p>Rs. <?= number_format($all_income,2); ?></p></div>
        <div class="card"><h3>Total Expenses</h3><p>Rs. <?= number_format($all_expense,2); ?></p></div>
        <div class="card"><h3>Net Savings</h3><p>Rs. <?= number_format($all_balance,2); ?></p></div>
    </div>

        <h2 class="section-title">Recent Transactions</h2>
        <div class="actions">
            <table style="width:100%; border-collapse:collapse;">
                <tr style="background:#f1f4fb;">
                    <th style="padding:10px; text-align:left;">Type</th>
                    <th style="padding:10px; text-align:left;">Category</th>
                    <th style="padding:10px;">Amount</th>
                    <th style="padding:10px;">Date</th>
                </tr>

                <?php
                if (mysqli_num_rows($recent_q) > 0) {
                    while ($row = mysqli_fetch_assoc($recent_q)) {
                        $color = ($row['type'] == 'Income') ? '#2e7d32' : '#c62828';
                        $sign  = ($row['type'] == 'Income') ? '+' : '-';
                ?>
                <tr>
                    <td style="padding:10px; font-weight:600; color:<?php echo $color; ?>">
                        <?php echo $row['type']; ?>
                    </td>
                    <td style="padding:10px;">
                        <?php echo htmlspecialchars($row['description']); ?>
                    </td>
                    <td style="padding:10px; color:<?php echo $color; ?>; font-weight:600;">
                        <?php echo $sign; ?> Rs. <?php echo number_format($row['amount'], 2); ?>
                    </td>
                    <td style="padding:10px;">
                        <?php echo date("d M Y", strtotime($row['date'])); ?>
                    </td>
                </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='4' style='padding:15px; text-align:center;'>No recent transactions</td></tr>";
                }
                ?>
            </table>

            <div style="margin-top:15px; text-align:right;">
                <a href="view_transactions.php" style="text-decoration:none; color:#1a73e8; font-weight:500;">  
                    View all transactions
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 class="section-title">Quick Actions</h2>
        <div class="actions">
            <div class="buttons">
                <a href="add_income.php"><i class="fa-solid fa-plus"></i> Add Income</a>
                <a href="add_expense.php"><i class="fa-solid fa-minus"></i> Add Expense</a>
                <a href="view_transactions.php"><i class="fa-regular fa-file"></i> View Transactions</a>
                <a href="expense_limit.php"><i class="fa-solid fa-wallet"></i> Expense Limit</a>
                <a href="view_reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a>
            </div>
        </div>
    </div>

    <script>
    new Chart(document.getElementById('monthPieChart'), {
        type: 'pie',
        data: {
            labels: ['Income','Expense'],
            datasets: [{
                data: [<?= $total_income ?>, <?= $total_expense ?>],
                backgroundColor: ['#2e7d32','#c62828']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    </script>

    <?php include "footer.php"; ?>
</body>
</html>
