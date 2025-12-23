<?php
session_start();
include "db.php";
include "header.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['month']) && $_GET['month'] != '') {
    $month = $_GET['month'];
    $year  = $_GET['year'];
    $condition = "MONTH(date)='$month' AND YEAR(date)='$year'";
} else {
    $condition = "MONTH(date)=MONTH(CURRENT_DATE()) 
                  AND YEAR(date)=YEAR(CURRENT_DATE())";
}

$income_data = mysqli_query($conn,
    "SELECT amount, description, date
     FROM income
     WHERE user_id='$user_id' AND $condition
     ORDER BY date DESC");

$expense_data = mysqli_query($conn,
    "SELECT amount, description, date
     FROM expenses
     WHERE user_id='$user_id' AND $condition
     ORDER BY date DESC");
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
            padding: 0;
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
        h3 {
            margin-top: 40px;
        }
        form {
            margin-bottom: 20px;
        }
        select, button {
            padding: 8px;
        }
        .dashboard {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <a href="user_dashboard.php" style="margin-top: 30px; text-decoration:none; padding:8px 12px; background:#343a40; color:white; border-radius:4px;">
        &larr; Back to Dashboard</a>
    </div>

    <h2>Transactions</h2>
    <form method="GET">
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
            <th>Date</th>
            <th>Source</th>
            <th>Amount</th>
        </tr>

        <?php
        if (mysqli_num_rows($income_data) > 0) {
            while ($row = mysqli_fetch_assoc($income_data)) {
                echo "<tr>
                        <td>{$row['date']}</td>
                        <td>{$row['description']}</td>
                        <td>Rs. {$row['amount']}</td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No income records</td></tr>";
        }
        ?>
    </table>

    <h3>Expense Transactions</h3>

    <table>
        <tr>
            <th>Date</th>
            <th>Category</th>
            <th>Amount</th>
        </tr>

        <?php
        if (mysqli_num_rows($expense_data) > 0) {
            while ($row = mysqli_fetch_assoc($expense_data)) {
                echo "<tr>
                        <td>{$row['date']}</td>
                        <td>{$row['description']}</td>
                        <td>Rs. {$row['amount']}</td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No expense records</td></tr>";
        }
        ?>
    </table>
    <?php include "footer.php";?>
</body>
</html>
