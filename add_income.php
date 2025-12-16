<?php
include "db.php";
session_start();

$user_id = $_SESSION['user_id'];

if(isset($_POST['add_income'])){
    $amount = $_POST['amount'];
    $description = $_POST['description']; // now comes from dropdown
    $date = $_POST['date'];

    $sql = "INSERT INTO income (user_id, amount, description, date)
            VALUES ('$user_id', '$amount', '$description', '$date')";
    mysqli_query($conn, $sql);
    header("Location: user_dashboard.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add income</title>
    <style>
        body { font-family: Arial; padding: 30px; }
        input, select, button { width: 100%; padding: 10px; margin: 10px 0; }
        button { background: #dc3545; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>

<h2>Add Income</h2>

<form method="POST">
    <input type="number" name="amount" placeholder="Income Amount" required>

    <select name="description" required>
        <option value="">Select Category</option>
        <?php
        $cat = mysqli_query($conn, "SELECT * FROM categories WHERE type='income'");
        while($row = mysqli_fetch_assoc($cat)){
            echo "<option value='{$row['name']}'>{$row['name']}</option>";
        }
        ?>
    </select>

    <input type="date" name="date" required>
    <button type="submit" name="add_income">Add income</button>
</form>

</body>
</html>