<?php
session_start();
require 'db.php';
include "header.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$month = date('m');
$year  = date('Y');
$user_id = $_SESSION['user_id'];

$income_q = mysqli_query($conn,
    "SELECT SUM(amount) AS total_income
     FROM income
     WHERE user_id='$user_id'
     AND MONTH(date)=MONTH(CURRENT_DATE())
     AND YEAR(date)=YEAR(CURRENT_DATE())");

$income_row = mysqli_fetch_assoc($income_q);
$total_income = $income_row['total_income'] ?? 0;

$expense_q = mysqli_query($conn,
    "SELECT SUM(amount) AS total_expense
     FROM expenses
     WHERE user_id='$user_id'
     AND MONTH(date)=MONTH(CURRENT_DATE())
     AND YEAR(date)=YEAR(CURRENT_DATE())");

$expense_row = mysqli_fetch_assoc($expense_q);
$total_expense = $expense_row['total_expense'] ?? 0;

// savings
$savings = $total_income - $total_expense;

$limit_q = mysqli_query($conn,
    "SELECT limit_amount 
     FROM expense_limits
     WHERE user_id='$user_id'
     AND month='$month'
     AND year='$year'");

$limit = 0;
if (mysqli_num_rows($limit_q) > 0) {
    $limit = mysqli_fetch_assoc($limit_q)['limit_amount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', Arial, sans-serif;
            background: #eef2f7;
        }

        .navbar {
            background: #1a73e8;
            padding: 15px 30px;
            color: white;
            font-size: 20px;
            font-weight: 600;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 1000px;
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
        }

        .card {
            flex: 1;
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            margin: 0 0 10px;
            font-weight: 500;
            color: #555;
        }

        .card p {
            font-size: 22px;
            font-weight: 700;
            color: #1a73e8;
        }

        .actions {
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        }

        .actions h3 {
            margin-top: 0;
        }

        .buttons a {
            display: inline-block;
            margin: 10px 10px 0 0;
            padding: 12px 22px;
            background: #1a73e8;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
        }

        .buttons a:hover {
            background: #0f5ccc;
        }
        
        .limit {
            margin-top: 5px;
            border-radius:8px;
            background:#ffdddd;
            padding:3px;
            border-left:5px solid red;
        }
    </style>
</head>
<body>
        <div class="container">

        <div class="welcome">
            Welcome, <?php echo $_SESSION['name']; ?> <i class="fa-regular fa-face-laugh-beam" style="color: #1887dbff;"></i>
        </div>
        <?php $current_month = date('F Y'); ?>
        <h2 style="color: #1a73e8; margin-bottom: 15px; border-radius: 10px; background: white; padding: 25px;">
            Dashboard — <?php echo $current_month; ?></h2>

        <div class="stats">
            <div class="card">
                <h3>Total Income</h3>
                <p>Rs. <?php echo number_format($total_income, 2); ?></p>
            </div>

            <div class="card">
                <h3>Total Expenses</h3>
                <p>Rs. <?php echo number_format($total_expense, 2); ?></p>
            </div>

            <div class="card">
                <h3>Balance</h3>
                <p>Rs. <?php echo number_format($savings, 2); ?></p>
            </div>

        </div>

        <div class="actions">
            <h3>Quick Actions</h3>
            <div class="buttons">
                <a href="add_income.php"><i class="fa-solid fa-plus"></i> Add Income</a>
                <a href="add_expense.php"><i class="fa-solid fa-minus"></i> Add Expense</a>
                <a href="view_transactions.php"><i class="fa-regular fa-file"></i> View Transactions</a>
                <a href="expense_limit.php"><i class="fa-solid fa-wallet"></i> Set Expense Limit</a>            
            </div>
        </div>
        <?php if ($limit > 0 && $total_expense > $limit) { ?>
    <div class="limit">
        <p>⚠ Monthly expense limit exceeded!</p>
        <br>
        Limit: Rs. <?php echo $limit; ?> |
        Spent: Rs. <?php echo $total_expense; ?>
    </div>
<?php } ?>


    </div>
    <?php include "footer.php";?>
</body>
</html>