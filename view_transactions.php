<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$income_data = mysqli_query($conn,
    "SELECT amount, description, date
     FROM income
     WHERE user_id='$user_id'
     ORDER BY date DESC");

//EXPENSE TRANSACTIONS 
/*$expense_data = mysqli_query($conn,
    "SELECT e.amount, c.name, e.date
     FROM expenses e
     JOIN categories c ON e.category_id = c.category_id
     WHERE e.user_id='$user_id'
     ORDER BY e.date DESC");*/

$expense_data = mysqli_query($conn,
    "SELECT amount, description, date
     FROM expenses
     WHERE user_id='$user_id'
     ORDER BY date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }

        th {
            background: #343a40;
            color: white;
        }

        td, th {
            padding: 10px;
            text-align: center;
        }

        tr:nth-child(even) {
            background: #f2f2f2;
        }

        h3 {
            margin-top: 30px;
        }
        </style>

</head>
<body>
    <a href="user_dashboard.php" style="text-decoration:none; padding:8px 12px; background:#343a40; color:white; border-radius:4px;">&larr; Back to Dashboard</a>

    <h3>Income Transactions</h3>

    <table border="1" cellpadding="10" cellspacing="0">
        <tr>
            <th>Date</th>
            <th>Source</th>
            <th>Amount</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($income_data)) { ?>
        <tr>
            <td><?php echo $row['date']; ?></td>
            <td><?php echo $row['description']; ?></td>
            <td>Rs. <?php echo $row['amount']; ?></td>
        </tr>
        <?php } ?>
    </table>

    <h3>Expense Transactions</h3>

    <table border="1" cellpadding="10" cellspacing="0">
        <tr>
            <th>Date</th>
            <th>Category</th>
            <th>Amount</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($expense_data)) { ?>
        <tr>
            <td><?php echo $row['date']; ?></td>
            <td><?php echo $row['description']; ?></td>
            <td>Rs. <?php echo $row['amount']; ?></td>
        </tr>
        <?php } ?>
    </table>


</body>
</html>