<?php
include 'db.php';
session_start();

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_POST['add_category'])) {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $sql = "INSERT INTO categories (name, type) VALUES ('$name', '$type')";
    if (mysqli_query($conn, $sql)) {
        echo "<p style='color:green;'>Category added successfully</p>";
    } else {
        echo "<p style='color:red;'>Error adding category</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category</title>
</head>
<body>
    <h2>Add Category</h2>
    <form method="post" action="">
        <label>Category Name</label><br>
        <input type="text" name="name" required><br><br>
        <label>Category Type</label><br>
        <select name="type" required>
            <option value="">--Select Type--</option>
            <option value="income">Income</option>
            <option value="expense">Expense</option>
        </select>
        <br><br>
        <button type="submit" name="add_category">Add Category</button>
    </form>
    <h3>Category List</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Type</th>
        </tr>
    <?php
    $result = mysqli_query($conn, "SELECT * FROM categories");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>".$row['category_id']."</td>";
        echo "<td>".$row['name']."</td>";
        echo "<td>".$row['type']."</td>";
        echo "</tr>";
    }
    ?>
    </table>
</body>
</html>