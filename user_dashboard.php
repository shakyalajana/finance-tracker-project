<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$month = date('m');
$year = date('Y');

// Monthly totals using prepared statements
$stmt = mysqli_prepare($conn, "SELECT SUM(amount) AS total FROM income WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
mysqli_stmt_bind_param($stmt, "iss", $user_id, $month, $year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_income = mysqli_fetch_assoc($result)['total'] ?? 0;
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT SUM(amount) AS total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
mysqli_stmt_bind_param($stmt, "iss", $user_id, $month, $year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_expense = mysqli_fetch_assoc($result)['total'] ?? 0;
mysqli_stmt_close($stmt);

$savings = $total_income - $total_expense;

// All-time totals using prepared statements
$stmt = mysqli_prepare($conn, "SELECT SUM(amount) AS total FROM income WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$all_income = mysqli_fetch_assoc($result)['total'] ?? 0;
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT SUM(amount) AS total FROM expenses WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$all_expense = mysqli_fetch_assoc($result)['total'] ?? 0;
mysqli_stmt_close($stmt);

$all_balance = $all_income - $all_expense;

// Insight message
$insightMessage = '';
$insightIcon = '';
$insightColor = '';

if ($savings > 0) {
    $insightMessage = "Great job! You saved more than you spent this month.";
    $insightIcon = "fa-arrow-trend-up";
    $insightColor = "#e8f5e9";
} elseif ($savings == 0) {
    $insightMessage = "Your income and expenses are balanced this month.";
    $insightIcon = "fa-scale-balanced";
    $insightColor = "#e3f2fd";
} else {
    $insightMessage = "You spent more than you earned. Consider reviewing expenses.";
    $insightIcon = "fa-arrow-trend-down";
    $insightColor = "#fff3e0";
}

// Get expense limit using prepared statement
$stmt = mysqli_prepare($conn, "SELECT limit_amount FROM expense_limits WHERE user_id = ? AND month = ? AND year = ?");
mysqli_stmt_bind_param($stmt, "iss", $user_id, $month, $year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$limit_row = mysqli_fetch_assoc($result);
$limit = $limit_row['limit_amount'] ?? 0;
mysqli_stmt_close($stmt);

// Get expense breakdown by category for chart
$stmt = mysqli_prepare($conn, 
    "SELECT c.name as description, SUM(e.amount) AS total
     FROM expenses e
     LEFT JOIN categories c ON e.category_id = c.category_id
     WHERE e.user_id = ? AND MONTH(e.date) = ? AND YEAR(e.date) = ?
     GROUP BY c.name
     ORDER BY total DESC");
mysqli_stmt_bind_param($stmt, "iss", $user_id, $month, $year);
mysqli_stmt_execute($stmt);
$expense_breakdown = mysqli_stmt_get_result($stmt);

$expense_categories = [];
$expense_amounts = [];
$expense_colors = ['#e53935', '#d32f2f', '#c62828', '#b71c1c', '#f44336', '#ef5350'];

while ($row = mysqli_fetch_assoc($expense_breakdown)) {
    $expense_categories[] = $row['description'];
    $expense_amounts[] = $row['total'];
}
mysqli_stmt_close($stmt);

// Recent transactions using prepared statement
$stmt = mysqli_prepare($conn, "
    SELECT 'Income' AS type, i.amount, c.name AS category_name, i.date 
    FROM income i
    LEFT JOIN categories c ON i.category_id = c.category_id
    WHERE i.user_id = ?
    UNION ALL
    SELECT 'Expense' AS type, e.amount, c.name AS category_name, e.date 
    FROM expenses e
    LEFT JOIN categories c ON e.category_id = c.category_id
    WHERE e.user_id = ?
    ORDER BY date DESC
    LIMIT 5
");
mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
mysqli_stmt_execute($stmt);
$recent_q = mysqli_stmt_get_result($stmt);
?>
<?php include "header.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="CSS/user_dashboard.css">
</head>
<body>
    <div class="container">
        <div class="welcome-section">
            <h1>Welcome back, <?= htmlspecialchars($_SESSION['name']) ?></h1>
            <p>Here's your financial overview for <?= date('F Y'); ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon income">
                    <i class="fa-solid fa-arrow-up"></i>
                </div>
                <div class="stat-content">
                    <h3>Income</h3>
                    <p style="color: green;">Rs. <?= number_format($total_income, 2); ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon expense">
                    <i class="fa-solid fa-arrow-down"></i>
                </div>
                <div class="stat-content">
                    <h3>Expenses</h3>
                    <p style="color: red;">Rs. <?= number_format($total_expense, 2); ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon balance">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <div class="stat-content">
                    <h3>Balance</h3>
                    <p style="color: <?= $savings >= 0 ? 'green' : 'red' ?>;">
                        Rs. <?= number_format($savings, 2); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="insight-banner" style="background: <?= $insightColor ?>;">
            <i class="fa-solid <?= $insightIcon ?>"></i>
            <span><?= $insightMessage ?></span>
        </div>

        <?php if ($limit > 0 && $total_expense > $limit): ?>
            <div class="alert-box">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Monthly expense limit exceeded! Limit: Rs. <?= number_format($limit, 2); ?> | Spent: Rs. <?= number_format($total_expense, 2); ?>
            </div>
        <?php endif; ?>

        <div class="chart-section">
            <h3>
                <i class="fa-solid fa-chart-pie"></i>
                Expense Breakdown by Category - This Month
            </h3>
            <?php if (count($expense_categories) > 0): ?>
                <canvas id="expenseChart"></canvas>
            <?php else: ?>
                <div class="empty-chart">
                    <i class="fa-regular fa-chart-bar"></i>
                    <p>No expense data available for this month</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="section-header">
            <h2>Overall Summary</h2>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon income">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Income</h3>
                    <p style="color: green;">Rs. <?= number_format($all_income, 2); ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon expense">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Expenses</h3>
                    <p style="color: red;">Rs. <?= number_format($all_expense, 2); ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon balance">
                    <i class="fa-solid fa-piggy-bank"></i>
                </div>
                <div class="stat-content">
                    <h3>Net Savings</h3>
                    <p style="color: <?= $all_balance >= 0 ? 'green' : 'red' ?>;">
                        Rs. <?= number_format($all_balance, 2); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2>Recent Transactions</h2>
        </div>

        <div class="content-box">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($recent_q) > 0): 
                        while ($row = mysqli_fetch_assoc($recent_q)):
                            $color = ($row['type'] == 'Income') ? 'green' : 'red';
                            $sign = ($row['type'] == 'Income') ? '+' : '-';
                    ?>
                    <tr>
                        <td>
                            <span style="font-weight: 600; color: <?= $color ?>;">
                                <?= htmlspecialchars($row['type']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></td>
                        <td>
                            <span style="font-weight: 600; color: <?= $color ?>;">
                                <?= $sign ?> Rs. <?= number_format($row['amount'], 2) ?>
                            </span>
                        </td>
                        <td><?= date("d M Y", strtotime($row['date'])) ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #5f6368; padding: 32px;">
                            No recent transactions
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="view_transactions.php" class="view-all-link">
                View all transactions →
            </a>
        </div>

        <div class="section-header">
            <h2>Quick Actions</h2>
        </div>

        <div class="action-grid">
            <a href="add_income.php" class="action-card">
                <div class="action-icon">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <div class="action-content">
                    <h4>Add Income</h4>
                    <p>Record earnings</p>
                </div>
            </a>

            <a href="add_expense.php" class="action-card">
                <div class="action-icon">
                    <i class="fa-solid fa-minus"></i>
                </div>
                <div class="action-content">
                    <h4>Add Expense</h4>
                    <p>Track spending</p>
                </div>
            </a>

            <a href="view_transactions.php" class="action-card">
                <div class="action-icon">
                    <i class="fa-solid fa-list"></i>
                </div>
                <div class="action-content">
                    <h4>Transactions</h4>
                    <p>View all activity</p>
                </div>
            </a>

            <a href="expense_limit.php" class="action-card">
                <div class="action-icon">
                    <i class="fa-solid fa-gauge-high"></i>
                </div>
                <div class="action-content">
                    <h4>Set Limits</h4>
                    <p>Manage budgets</p>
                </div>
            </a>

            <a href="view_reports.php" class="action-card">
                <div class="action-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="action-content">
                    <h4>Reports</h4>
                    <p>Analyze data</p>
                </div>
            </a>
        </div>
    </div>

    <script>
        <?php if (count($expense_categories) > 0): ?>
        const ctx = document.getElementById('expenseChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($expense_categories) ?>,
                datasets: [{
                    data: <?= json_encode($expense_amounts) ?>,
                    backgroundColor: <?= json_encode(array_slice($expense_colors, 0, count($expense_categories))) ?>,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            font: {
                                size: 13,
                                family: "'Segoe UI', sans-serif"
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'Rs. ' + context.parsed.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                return label;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>

    <?php include "footer.php"; ?>
</body>
</html>