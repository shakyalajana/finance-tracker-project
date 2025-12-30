<?php
session_start();
include "db.php";
include "header.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_year = date('Y');
$current_month = date('m');

// ==================== CURRENT MONTH DATA ====================
// Income by description
$income_q = mysqli_query($conn,
    "SELECT description, SUM(amount) AS total
     FROM income
     WHERE user_id='$user_id' AND MONTH(date)='$current_month' AND YEAR(date)='$current_year'
     GROUP BY description");

// Expense by description
$expense_q = mysqli_query($conn,
    "SELECT description, SUM(amount) AS total
     FROM expenses
     WHERE user_id='$user_id' AND MONTH(date)='$current_month' AND YEAR(date)='$current_year'
     GROUP BY description");

// ==================== YEARLY DATA ====================
$yearly_income_q = mysqli_query($conn,
    "SELECT MONTH(date) AS month, SUM(amount) AS total
     FROM income
     WHERE user_id='$user_id' AND YEAR(date)='$current_year'
     GROUP BY MONTH(date)");

$yearly_expense_q = mysqli_query($conn,
    "SELECT MONTH(date) AS month, SUM(amount) AS total
     FROM expenses
     WHERE user_id='$user_id' AND YEAR(date)='$current_year'
     GROUP BY MONTH(date)");

// Prepare arrays for charts
$income_labels = $income_data = [];
while($row = mysqli_fetch_assoc($income_q)){
    $income_labels[] = $row['description'];
    $income_data[] = $row['total'];
}

$expense_labels = $expense_data = [];
while($row = mysqli_fetch_assoc($expense_q)){
    $expense_labels[] = $row['description'];
    $expense_data[] = $row['total'];
}

$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$monthly_income = array_fill(1,12,0);
while($row = mysqli_fetch_assoc($yearly_income_q)){
    $monthly_income[(int)$row['month']] = $row['total'];
}
$monthly_expense = array_fill(1,12,0);
while($row = mysqli_fetch_assoc($yearly_expense_q)){
    $monthly_expense[(int)$row['month']] = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Finance Tracker</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    body {
        font-family: 'Poppins', Arial, sans-serif;
        background: #eef2f7;
        margin: 0; padding: 0;
    }
    .container {
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 20px;
    }
    h2 {
        text-align: center;
        color: #1a73e8;
        margin-bottom: 20px;
    }
    .chart-container {
        background: white;
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        margin: 20px auto;
        max-width: 500px;
        height: 300px;
    }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reports — <?php echo date('F Y'); ?></h2>

        <div class="chart-container">
            <canvas id="incomePie"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="expensePie"></canvas>
        </div>
        <div class="chart-container" style="max-width: 700px;">
                <canvas id="yearlyBar"></canvas>
        </div>
    </div>

    <script>
        const incomePie = new Chart(document.getElementById('incomePie'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($income_labels); ?>,
                datasets: [{
                    label: 'Income',
                    data: <?php echo json_encode($income_data); ?>,
                    backgroundColor: ['#4caf50','#81c784','#a5d6a7','#c8e6c9','#2e7d32','#66bb6a']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Income by Category (Current Month)'
                    }
                }
            }
        });

        const expensePie = new Chart(document.getElementById('expensePie'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($expense_labels); ?>,
                datasets: [{
                    label: 'Expenses',
                    data: <?php echo json_encode($expense_data); ?>,
                    backgroundColor: ['#e53935','#ef9a9a','#ffcdd2','#f44336','#d32f2f','#ff6659']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Expenses by Category (Current Month)'
                    }
                }
            }
        });

        const yearlyBar = new Chart(document.getElementById('yearlyBar'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [
                    {
                        label: 'Income',
                        data: <?php echo json_encode(array_values($monthly_income)); ?>,
                        backgroundColor: '#4caf50'
                    },
                    {
                        label: 'Expenses',
                        data: <?php echo json_encode(array_values($monthly_expense)); ?>,
                        backgroundColor: '#e53935'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Yearly Overview'
                    }
                }
            }
        });
    </script>
<?php include "footer.php"; ?>
</body>
</html>
