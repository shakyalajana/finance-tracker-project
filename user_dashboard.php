<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Total Income
$sql_income = "SELECT SUM(amount) AS total_income FROM income WHERE user_id='$user_id'";
$result_income = mysqli_query($conn, $sql_income);
$row_income = mysqli_fetch_assoc($result_income);
$total_income = $row_income['total_income'] ?? 0;

// Total Expenses
$sql_expense = "SELECT SUM(amount) AS total_expense FROM expenses WHERE user_id='$user_id'";
$result_expense = mysqli_query($conn, $sql_expense);
$row_expense = mysqli_fetch_assoc($result_expense);
$total_expense = $row_expense['total_expense'] ?? 0;


// savings
$savings = $total_income - $total_expense;
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
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

        .logout-btn {
            background: red !important;
        }

        .logout-btn:hover {
            background: #c70000 !important;
        }
    </style>
</head>

<body>

<div class="navbar">
    💼 Personal Finance Tracker
</div>

<div class="container">

    <div class="welcome">
        Welcome, <?php echo $_SESSION['name']; ?> 👋
    </div>

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
            <a href="add_income.php">➕ Add Income</a>
            <a href="add_expense.php">➖ Add Expense</a>
            <a href="view_transactions.php">📄 View Transactions</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

</div>

</body>
</html>
