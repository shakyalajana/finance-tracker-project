<?php
include "db.php";
session_start();

$user_id = $_SESSION['user_id'];
$month = date('m');
$year  = date('Y');

if (isset($_POST['save_limit'])) {
    $limit = $_POST['limit_amount'];
    $check = mysqli_query($conn,
        "SELECT id FROM expense_limits 
         WHERE user_id='$user_id' AND month='$month' AND year='$year'");

    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn,
            "UPDATE expense_limits 
             SET limit_amount='$limit' 
             WHERE user_id='$user_id' AND month='$month' AND year='$year'");
    } else {
        mysqli_query($conn,
            "INSERT INTO expense_limits (user_id, month, year, limit_amount)
             VALUES ('$user_id','$month','$year','$limit')");
    }

    header("Location: user_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Limit</title>
    <style>
        body {
        margin: 0;
        font-family: 'Poppins', Arial, sans-serif;
        background: linear-gradient(135deg, #e0f2ff, #fefefe);
    }

    .container {
        max-width: 500px;
        margin: 80px auto;
        padding: 0 20px;
    }

    .card {
        background: white;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        animation: fadeIn 0.4s ease;
    }

    h2 {
        margin-top: 0;
        color: #1a73e8;
        text-align: center;
    }

    p {
        text-align: center;
        color: #555;
        margin-bottom: 25px;
    }

    input {
        width: 100%;
        padding: 12px;
        font-size: 16px;
        border-radius: 8px;
        border: 1px solid #ccc;
        margin-bottom: 15px;
    }

    button {
        width: 100%;
        padding: 12px;
        font-size: 16px;
        background: #1a73e8;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.3s;
    }

    button:hover {
        background: #0f5ccc;
    }

    .back {
        display: block;
        text-align: center;
        margin-top: 15px;
        text-decoration: none;
        color: #1a73e8;
        font-weight: 500;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
</head>
<body>
    <?php include "header.php"; ?>
    <div class="container">
        <div class="card">
            <h2>Monthly Expense Limit</h2>
            <p>Set a budget to manage your spending for this month.</p>

            <form method="POST">
                <input type="number" name="limit_amount" placeholder="Enter amount (Rs.)" required>
                <button name="save_limit">Save Limit</button>
            </form>

            <a href="user_dashboard.php" class="back">&larr; Back to Dashboard</a>
        </div>
    </div>
    <?php include "footer.php"; ?>
</body>
</html>