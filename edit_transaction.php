<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$type = $_GET['type'];
$id = (int)$_GET['id'];

$table = ($type === 'income') ? 'income' : 'expenses';

$query = mysqli_query($conn,
    "SELECT * FROM $table WHERE id=$id AND user_id=$user_id");

if (mysqli_num_rows($query) !== 1) {
    die("Unauthorized access");
}

$data = mysqli_fetch_assoc($query);
if (isset($_POST['update'])) {
    $amount = $_POST['amount'];
    $date = $_POST['date'];

    mysqli_query($conn,
        "UPDATE $table
         SET amount='$amount', date='$date'
         WHERE id=$id AND user_id=$user_id");

    header("Location: view_transactions.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction</title>
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #eef2f7;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        a.back-link {
            display: inline-block;
            margin: 20px auto 10px;
            text-decoration: none;
            background: #0c4aad;
            color: white;
            padding: 8px 14px;
            border-radius: 6px;
            font-weight: 500;
            transition: 0.2s;
        }

        a.back-link:hover {
            background: #093f8d;
        }

        .box {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            max-width: 400px;
            width: 100%;
            margin: 40px 20px;
        }

        h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #0c4aad;
            font-weight: 600;
            text-align: center;
        }

        form label {
            display: block;
            margin-top: 12px;
            font-weight: 500;
            color: #444;
        }

        form input, form select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        form button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: #0c4aad;
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s;
            font-size: 16px;
        }

        form button:hover {
            background: #093f8d;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Update Transaction</h2>
        <form method="POST">
        <label>Amount</label>
        <input type="number" name="amount" value="<?php echo $data['amount']; ?>" required>

        <label>Date</label>
        <input type="date" name="date" value="<?php echo $data['date']; ?>" required>

        <button name="update">Update Transaction</button>
</form>
    </div>
</body>
</html>