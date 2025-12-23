<?php
session_start();
require 'db.php';
include "header.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_month = date('m');
$current_year  = date('Y');

// Get expenses grouped by category
$expense_query = mysqli_query($conn, "
    SELECT description AS category, SUM(amount) AS total 
    FROM expenses 
    WHERE user_id='$user_id' 
      AND MONTH(date)='$current_month' 
      AND YEAR(date)='$current_year'
    GROUP BY description
");

// Prepare data arrays for Chart.js
$categories = [];
$amounts = [];

while($row = mysqli_fetch_assoc($expense_query)){
    $categories[] = $row['category'];
    $amounts[] = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        #expenseChart{
            width: 50%;
            height: 50%;
        }
        </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h3>Expense Breakdown by Category</h3>
    <canvas id="expenseChart" width="400" height="400"></canvas>
    <script>
        const ctx = document.getElementById('expenseChart').getContext('2d');
        const expenseChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($categories); ?>,
                datasets: [{
                    label: 'Expenses',
                    data: <?php echo json_encode($amounts); ?>,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
                    ],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'center'
                    },
                    title: {
                        display: true,
                        text: 'Expenses by Category for <?php echo date("F Y"); ?>'
                    }
                }
            }
        });
    </script>
</body>
</html>