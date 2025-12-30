<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$userCount = $conn->query("SELECT COUNT(*) AS total_users FROM users WHERE role = 'user'")->fetch_assoc()['total_users'];

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

$incomeCount = $conn->query("SELECT COUNT(*) AS total FROM income")->fetch_assoc()['total'];
$expenseCount = $conn->query("SELECT COUNT(*) AS total FROM expenses")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

    <div class="welcome">Welcome, Admin <i class="fa-solid fa-user-tie" style="color: #388dceff;"></i></div>

    <div class="stats">

    <div class="card">
        <h3>Total Users</h3>
        <p><?php echo $userCount; ?></p>
    </div>

    <div class="card">
        <h3>Active Users (This Month)</h3>
        <p><?php echo $activeUsers; ?></p>
    </div>

    <div class="card">
        <h3>Income Records</h3>
        <p><?php echo $incomeCount; ?></p>
    </div>

    <div class="card">
        <h3>Expense Records</h3>
        <p><?php echo $expenseCount; ?></p>
    </div>

</div>

    <div class="section buttons">
        <a href="view_users.php"><i class="fa-solid fa-users"></i> View Users</a>
        <a href="manage_category.php"><i class="fa-solid fa-list"></i> Manage Categories</a>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket" style="color: #ffffffff;"></i> Logout</a>
    </div>

</div>

</body>
</html>
