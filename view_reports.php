<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_year = date('Y');
$current_month = date('m');

// Income by category - Current Month
$stmt = mysqli_prepare($conn,
    "SELECT description, SUM(amount) AS total
     FROM income
     WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
     GROUP BY description
     ORDER BY total DESC");
mysqli_stmt_bind_param($stmt, "iss", $user_id, $current_month, $current_year);
mysqli_stmt_execute($stmt);
$income_result = mysqli_stmt_get_result($stmt);

$income_labels = [];
$income_data = [];
while ($row = mysqli_fetch_assoc($income_result)) {
    $income_labels[] = $row['description'];
    $income_data[] = $row['total'];
}
mysqli_stmt_close($stmt);

// Expense by category - Current Month
$stmt = mysqli_prepare($conn,
    "SELECT description, SUM(amount) AS total
     FROM expenses
     WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
     GROUP BY description
     ORDER BY total DESC");
mysqli_stmt_bind_param($stmt, "iss", $user_id, $current_month, $current_year);
mysqli_stmt_execute($stmt);
$expense_result = mysqli_stmt_get_result($stmt);

$expense_labels = [];
$expense_data = [];
while ($row = mysqli_fetch_assoc($expense_result)) {
    $expense_labels[] = $row['description'];
    $expense_data[] = $row['total'];
}
mysqli_stmt_close($stmt);

// Yearly Income Data
$stmt = mysqli_prepare($conn,
    "SELECT MONTH(date) AS month, SUM(amount) AS total
     FROM income
     WHERE user_id = ? AND YEAR(date) = ?
     GROUP BY MONTH(date)");
mysqli_stmt_bind_param($stmt, "is", $user_id, $current_year);
mysqli_stmt_execute($stmt);
$yearly_income_result = mysqli_stmt_get_result($stmt);

$monthly_income = array_fill(1, 12, 0);
while ($row = mysqli_fetch_assoc($yearly_income_result)) {
    $monthly_income[(int)$row['month']] = $row['total'];
}
mysqli_stmt_close($stmt);

// Yearly Expense Data
$stmt = mysqli_prepare($conn,
    "SELECT MONTH(date) AS month, SUM(amount) AS total
     FROM expenses
     WHERE user_id = ? AND YEAR(date) = ?
     GROUP BY MONTH(date)");
mysqli_stmt_bind_param($stmt, "is", $user_id, $current_year);
mysqli_stmt_execute($stmt);
$yearly_expense_result = mysqli_stmt_get_result($stmt);

$monthly_expense = array_fill(1, 12, 0);
while ($row = mysqli_fetch_assoc($yearly_expense_result)) {
    $monthly_expense[(int)$row['month']] = $row['total'];
}
mysqli_stmt_close($stmt);

$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// Calculate totals and insights
$total_income_month = array_sum($income_data);
$total_expense_month = array_sum($expense_data);
$top_income_category = count($income_labels) > 0 ? $income_labels[0] : 'N/A';
$top_expense_category = count($expense_labels) > 0 ? $expense_labels[0] : 'N/A';
?>
<?php include "header.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - FinTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, rgba(102,126,234,0.05), rgba(118,75,162,0.05)), #f5f7fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 24px;
        }

        .page-header {
            background: white;
            padding: 24px 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header h2 {
            margin: 0;
            color: #1f2937;
            font-size: 24px;
            font-weight: 700;
        }

        .page-header p {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 14px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .insight-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: transform 0.2s;
        }

        .insight-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .insight-card h4 {
            margin: 0 0 8px 0;
            font-size: 13px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .insight-card p {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
        }

        .chart-container {
            background: white;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
        }

        .chart-container h3 {
            margin: 0 0 24px 0;
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-container h3 i {
            color: #667eea;
        }

        /* Pie charts - smaller */
        .chart-container.pie canvas {
            display: block;
            margin: 0 auto;
            max-width: 450px;
            max-height: 450px;
        }

        /* Bar chart - MUCH BIGGER */
        .chart-container.bar {
            padding: 32px;
        }

        .chart-container.bar canvas {
            max-width: 100%;
            height: 500px !important; /* Fixed height for larger chart */
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 16px;
        }

        .empty-state p {
            margin: 0;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 16px;
            }

            .page-header {
                padding: 20px;
            }

            .chart-container {
                padding: 20px;
            }

            .chart-container.bar {
                padding: 24px 16px;
            }

            .chart-container.bar canvas {
                height: 350px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div>
                <h2>Financial Reports</h2>
                <p><?= date('F Y') ?></p>
            </div>
            <a href="user_dashboard.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>
                Dashboard
            </a>
        </div>

        <div class="insights-grid">
            <div class="insight-card">
                <h4>Total Income This Month</h4>
                <p style="color: #10b981;">Rs. <?= number_format($total_income_month, 2) ?></p>
            </div>

            <div class="insight-card">
                <h4>Total Expense This Month</h4>
                <p style="color: #ef4444;">Rs. <?= number_format($total_expense_month, 2) ?></p>
            </div>

            <div class="insight-card">
                <h4>Top Income Source</h4>
                <p><?= htmlspecialchars($top_income_category) ?></p>
            </div>

            <div class="insight-card">
                <h4>Top Expense Category</h4>
                <p><?= htmlspecialchars($top_expense_category) ?></p>
            </div>
        </div>

        <div class="chart-container pie">
            <h3>
                <i class="fa-solid fa-chart-pie"></i>
                Income by Category - <?= date('F Y') ?>
            </h3>
            <?php if (count($income_labels) > 0): ?>
                <canvas id="incomePie"></canvas>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>No income data for this month</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="chart-container pie">
            <h3>
                <i class="fa-solid fa-chart-pie"></i>
                Expenses by Category - <?= date('F Y') ?>
            </h3>
            <?php if (count($expense_labels) > 0): ?>
                <canvas id="expensePie"></canvas>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>No expense data for this month</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="chart-container bar">
            <h3>
                <i class="fa-solid fa-chart-line"></i>
                Yearly Overview - <?= $current_year ?>
            </h3>
            <canvas id="yearlyBar"></canvas>
        </div>
    </div>

    <script>
        <?php if (count($income_labels) > 0): ?>
        new Chart(document.getElementById('incomePie'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($income_labels) ?>,
                datasets: [{
                    data: <?= json_encode($income_data) ?>,
                    backgroundColor: [
                        '#10b981', '#34d399', '#6ee7b7', '#a7f3d0', 
                        '#059669', '#047857', '#065f46'
                    ],
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
                            font: { size: 13, family: "'Poppins', sans-serif" }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) label += ': ';
                                label += 'Rs. ' + context.parsed.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                return label;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if (count($expense_labels) > 0): ?>
        new Chart(document.getElementById('expensePie'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($expense_labels) ?>,
                datasets: [{
                    data: <?= json_encode($expense_data) ?>,
                    backgroundColor: [
                        '#ef4444', '#f87171', '#fca5a5', '#fecaca',
                        '#dc2626', '#b91c1c', '#991b1b'
                    ],
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
                            font: { size: 13, family: "'Poppins', sans-serif" }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) label += ': ';
                                label += 'Rs. ' + context.parsed.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                return label;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        new Chart(document.getElementById('yearlyBar'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [
                    {
                        label: 'Income',
                        data: <?= json_encode(array_values($monthly_income)) ?>,
                        backgroundColor: '#10b981',
                        borderSkipped: false,
                    },
                    {
                        label: 'Expenses',
                        data: <?= json_encode(array_values($monthly_expense)) ?>,
                        backgroundColor: '#ef4444',
                        borderSkipped: false,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Important for fixed height
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 20,
                            font: { size: 14, family: "'Poppins', sans-serif", weight: '600' },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14, family: "'Poppins', sans-serif" },
                        bodyFont: { size: 13, family: "'Poppins', sans-serif" },
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                label += 'Rs. ' + context.parsed.y.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 12, family: "'Poppins', sans-serif" }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: { size: 12, family: "'Poppins', sans-serif" },
                            callback: function(value) {
                                return 'Rs. ' + value.toLocaleString('en-IN');
                            }
                        }
                    }
                }
            }
        });
    </script>

    <?php include "footer.php"; ?>
</body>
</html>