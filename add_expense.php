<?php
include "db.php";
session_start();

$user_id = $_SESSION['user_id'];

if(isset($_POST['add_expense'])){
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $date = $_POST['date'];

    $sql = "INSERT INTO expenses (user_id, amount, description, date)
            VALUES ('$user_id', '$amount', '$description', '$date')";
    mysqli_query($conn, $sql);
    header("Location: user_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense</title>
    <style>
            body {
            margin: 0;
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #e3f2fd, #ffffff);
        }

        .content {
            height: 75vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .form-box {
            background: white;
            padding: 35px;
            width: 380px;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
            animation: fadeUp 0.4s ease;
        }

        h2 {
            text-align: center;
            margin-top: 0;
            margin-bottom: 20px;
            color: #1a73e8;
            font-weight: 600;
        }

        label {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }

        input, select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 15px;
        }

        input {
            outline: none;
            width: 93%;
        }

        button {
            width: 100%;
            padding: 13px;
            background: #1a73e8;
            border: none;
            color: white;
            font-size: 16px;
            font-weight: 500;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #0f5ccc;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            color: #1a73e8;
            font-weight: 500;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="/resources/demos/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>
    <script>
    $( function() {
        $("#datepicker").datepicker({
            dateFormat: "yy-mm-dd"
        });
    } );
    </script>
</head>
<body>
    <?php include "header.php";?>
    <div class="content">
    <div class="form-box">
        <h2>Add Expense</h2>
            <form method="POST">
                <label>Amount</label>
                <input type="number" name="amount" placeholder="Enter expense amount" required>

                <label>Category</label>
                <select name="description" required>
                    <option value="">Select category</option>
                    <?php
                    $cat = mysqli_query($conn, "SELECT * FROM categories WHERE type='expense'");
                    while($row = mysqli_fetch_assoc($cat)){
                        echo "<option value='{$row['name']}'>{$row['name']}</option>";
                    }
                    ?>
                </select>

                <label>Date</label>
                <input type="text" id="datepicker" name="date" required>

                <button type="submit" name="add_expense">Add Expense</button>
            </form>
            <a href="user_dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
    <?php include "footer.php";?>
</body>
</html>