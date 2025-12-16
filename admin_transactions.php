<?php
include "db.php";
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

/* FETCH ALL INCOME TRANSACTIONS */
$income_data = mysqli_query($conn,
    "SELECT u.name, i.amount, i.description, i.date
     FROM income i
     JOIN users u ON i.user_id = u.user_id
     ORDER BY i.date DESC"
);

/* FETCH ALL EXPENSE TRANSACTIONS */
$expense_data = mysqli_query($conn,
    "SELECT u.name, e.amount, e.description, e.date
     FROM expenses e
     JOIN users u ON e.user_id = u.user_id
     ORDER BY e.date DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Transactions</title>
    <style>
        body {
            font-family: Arial;
            padding: 30px;
            background: #f5f5f5;
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

    <a href="admin_dashboard.php" style="text-decoration:none; padding:8px 12px; background:#343a40; color:white; border-radius:4px;">&larr; Back to Dashboard</a>

    <h2>All Transactions</h2>

    <h3>Income Transactions</h3>

    <table>
        <tr>
            <th>User</th>
            <th>Date</th>
            <th>Description</th>
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
            echo "<tr><td colspan='4'>No income records found</td></tr>";
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
            echo "<tr><td colspan='4'>No expense records found</td></tr>";
        }
        ?>
    </table>

</body>
</html>
