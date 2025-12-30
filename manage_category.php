<?php
session_start();
include 'db.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Add category
if (isset($_POST['add_category'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $type = $_POST['type'];

    $sql = "INSERT INTO categories (name, type) VALUES ('$name', '$type')";
    if (mysqli_query($conn, $sql)) {
        $msg = "Category added successfully!";
    } else {
        $msg = "Error: " . mysqli_error($conn);
    }
}

// Delete category
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM categories WHERE category_id = $delete_id");
    header("Location: manage_category.php");
    exit();
}

// Fetch all categories
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Categories</title>
    <style>
        body {
            font-family: Arial;
            padding: 30px;
            background: #f5f5f5;
        }

        h2
        {
            margin-bottom: 20px;
        }

        form
        {
            background: #fff;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }

        input, select, button
        {
            padding: 8px;
            margin-top: 5px;
            width: 100%;
        }

        table
        {
            width:100%;
            border-collapse:collapse;
            background:#fff;
            border-radius:8px;
            overflow:hidden;
        }

        th
        {
            background: #343a40; 
            color: white;
        }

        td, th
        {
            padding: 10px; 
            text-align: center; 
            border: 1px solid #ccc;
        }

        tr:nth-child(even)
        {
            background: #f2f2f2;
        }

        .msg
        {
            color: green; 
            margin-bottom: 15px;
        }

        .action-btn
        {
            padding: 5px 10px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer;
        }

        .edit-btn
        {
            background: #ffc107; 
            color: #000;
        }

        .delete-btn
        { background: #dc3545; 
            color: #fff;
        }

</style>
</head>
<body>

<a href="admin_dashboard.php" style="text-decoration:none; padding:8px 12px; background:#343a40; color:white; border-radius:4px;">&larr; Back to Dashboard</a>
<h2>Manage Categories</h2>

<?php if(isset($msg)) echo "<p class='msg'>$msg</p>"; ?>

<!-- Add Category Form -->
<form method="post" action="">
    <label>Category Name</label><br>
    <input type="text" name="name" required><br><br>

    <label>Category Type</label><br>
    <select name="type" required>
        <option value="">--Select Type--</option>
        <option value="income">Income</option>
        <option value="expense">Expense</option>
    </select><br><br>

    <button type="submit" name="add_category">Add Category</button>
</form>

<!-- Category List Table -->
<h3>Existing Categories</h3>
<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Type</th>
        <th>Actions</th>
    </tr>
    <?php
        if (mysqli_num_rows($categories) > 0) {
            while ($row = mysqli_fetch_assoc($categories)) {
                echo "<tr>";
                echo "<td>{$row['category_id']}</td>";
                echo "<td>{$row['name']}</td>";
                echo "<td>{$row['type']}</td>";
                echo "<td>
                        <a href='edit_category.php?id={$row['category_id']}' class='action-btn edit-btn'>Edit</a>
                        <a href='?delete_id={$row['category_id']}' class='action-btn delete-btn' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                    </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No categories found</td></tr>";
        }
    ?>

</table>

</body>
</html>
