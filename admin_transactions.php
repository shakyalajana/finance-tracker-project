<?php
session_start();
include "db.php";

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['month']) && $_GET['month'] != '') {
    $month = $_GET['month'];
    $year  = $_GET['year'];
    $condition = "MONTH(date)='$month' AND YEAR(date)='$year'";
} else {
    $condition = "MONTH(date)=MONTH(CURRENT_DATE())
                  AND YEAR(date)=YEAR(CURRENT_DATE())";
}

/* ===== TOTAL INCOME (ALL USERS) ===== */
$income_sum_q = mysqli_query($conn,
    "SELECT SUM(amount) AS total_income
     FROM income
     WHERE $condition");

$income_sum = mysqli_fetch_assoc($income_sum_q);
$total_income = $income_sum['total_income'] ?? 0;

/* ===== TOTAL EXPENSE (ALL USERS) ===== */
$expense_sum_q = mysqli_query($conn,
    "SELECT SUM(amount) AS total_expense
     FROM expenses
     WHERE $condition");

$expense_sum = mysqli_fetch_assoc($expense_sum_q);
$total_expense = $expense_sum['total_expense'] ?? 0;

/* ===== BALANCE ===== */
$balance = $total_income - $total_expense;

/* ===== FETCH INCOME TRANSACTIONS ===== */
$income_data = mysqli_query($conn,
    "SELECT u.name, i.amount, i.description, i.date
     FROM income i
     JOIN users u ON i.user_id = u.user_id
     WHERE $condition
     ORDER BY i.date DESC");

/* ===== FETCH EXPENSE TRANSACTIONS ===== */
$expense_data = mysqli_query($conn,
    "SELECT u.name, e.amount, e.description, e.date
     FROM expenses e
     JOIN users u ON e.user_id = u.user_id
     WHERE $condition
     ORDER BY e.date DESC");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transactions</title>
    <style>
        body {
            font-family: Arial;
            padding: 30px;
            background: #f5f5f5;
            margin: 0;
            padding: 13px;
        }

        h2, h3 {
            margin-top: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
        }

        th {
            background: #343a40;
            color: white;
        }

        td, th {
            padding: 10px;
            text-align: center;
            border: 1px solid #ccc;
        }

        tr:nth-child(even) {
            background: #f2f2f2;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            background: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <div class="dashboard">
        <a href="admin_dashboard.php" style="margin-top: 30px; text-decoration:none; padding:8px 12px; background:#343a40; color:white; border-radius:4px;">
        &larr; Back to Dashboard</a>
    </div>
    <form method="GET" style="margin-top:30px;">
        <select name="month">
            <option value="">Current Month</option>
            <?php
            for ($m = 1; $m <= 12; $m++) {
                echo "<option value='$m'>$m</option>";
            }
            ?>
        </select>

        <select name="year">
            <?php
            for ($y = date('Y'); $y >= 2022; $y--) {
                echo "<option value='$y'>$y</option>";
            }
            ?>
        </select>

        <button type="submit">View</button>
    </form>
    <h3>Income Transactions</h3>
    <table>
        <tr>
            <th>User</th>
            <th>Date</th>
            <th>Source</th>
            <th>Amount</th>
        </tr>

    <?php
    if (mysqli_num_rows($income_data) > 0) {
        while ($row = mysqli_fetch_assoc($income_data)) {
            echo "<tr>
                    <td>{$row['name']}</td>
                    <td>{$row['date']}</td>
                    <td>{$row['description']}</td>
                    <td>Rs. {$row['amount']}</td>
                </tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No income records</td></tr>";
    }
    ?>
    </table>
    <h3>Expense Transactions</h3>
    <table>
        <tr>
            <th>User</th>
            <th>Date</th>
            <th>Category</th>
            <th>Amount</th>
        </tr>

    <?php
    if (mysqli_num_rows($expense_data) > 0) {
        while ($row = mysqli_fetch_assoc($expense_data)) {
            echo "<tr>
                    <td>{$row['name']}</td>
                    <td>{$row['date']}</td>
                    <td>{$row['description']}</td>
                    <td>Rs. {$row['amount']}</td>
                </tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No expense records</td></tr>";
    }
    ?>
    </table>

</body>
</html>
