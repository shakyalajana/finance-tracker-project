<?php
session_start();
require 'db.php';

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Total Users
$userCount = $conn->query("SELECT COUNT(*) AS total_users FROM users")->fetch_assoc()['total_users'];

// Total Income (all users)
$incomeQuery = $conn->query("SELECT SUM(amount) AS total_income FROM income");
$total_income = $incomeQuery->fetch_assoc()['total_income'] ?? 0;

// Total Expenses (all users)
$expenseQuery = $conn->query("SELECT SUM(amount) AS total_expense FROM expenses");
$total_expense = $expenseQuery->fetch_assoc()['total_expense'] ?? 0;

// Balance
$balance = $total_income - $total_expense;

// Latest 5 users
$latestUsers = $conn->query("SELECT name, email FROM users ORDER BY user_id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
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

        .section {
            background: white;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        table th {
            background: #f0f0f0;
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
    </style>
</head>

<body>

<div class="navbar">
    🛠 Admin Dashboard
</div>

<div class="container">

    <div class="welcome">Welcome, Admin 👑</div>

    <div class="stats">

        <div class="card">
            <h3>Total Users</h3>
            <p><?php echo $userCount; ?></p>
        </div>

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
            <p>Rs. <?php echo number_format($balance, 2); ?></p>
        </div>

    </div>

    <div class="section">
        <h3>Latest Registered Users</h3>

        <table>
            <tr>
                <th>Name</th>
                <th>Email</th>
            </tr>

            <?php while ($u = $latestUsers->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $u['name']; ?></td>
                    <td><?php echo $u['email']; ?></td>
                </tr>
            <?php } ?>

        </table>
    </div>

    <div class="section buttons">
        <a href="view_all_users.php">👥 View Users</a>
        <a href="view_all_transactions.php">📄 View Transactions</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

</div>

</body>
</html>
