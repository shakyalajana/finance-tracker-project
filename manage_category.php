<?php
session_start();
include 'db.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

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
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Categories</title>
    <style>
    body {
        margin: 0;
        font-family: 'Poppins', Arial, sans-serif;
        background: #f0f4f8;
        padding: 20px;
    }

    .content {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        margin-bottom: 40px;
    }
    .form-box {
        background: #fff;
        padding: 35px;
        width: 400px;
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(0,0,0,0.1);
    }
    .form-box h2 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #1a73e8;
        font-weight: 600;
        text-align: center;
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
    button {
        width: 100%;
        padding: 13px;
        background: #1a73e8;
        border: none;
        color: #fff;
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

    .msg {
        color: green;
        text-align: center;
        margin-bottom: 10px;
    }
    .error-msg {
        color: #b71c1c;
        text-align: center;
        margin-bottom: 15px;
    }

    .category-table {
        width: 100%;
        max-width: 900px;
        margin: 0 auto 50px;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    .category-table thead {
        background: #0c4aad;
        color: #fff;
        font-weight: 600;
    }
    .category-table th, .category-table td {
        padding: 12px 15px;
        text-align: center;
    }
    .category-table tbody tr {
        background: #fff;
        transition: 0.2s;
    }
    .category-table tbody tr:nth-child(even) {
        background: #f2f4f7;
    }
    .category-table tbody tr:hover {
        background: #e8f0fe;
    }
    .action-btn {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
    }
    .edit-btn {
        background: #ffc107;
        color: #000;
    }
    .edit-btn:hover {
        background: #e0a800;
    }
    .delete-btn {
        background: #dc3545;
        color: #fff;
    }
    .delete-btn:hover {
        background: #b71c1c;
    }
    </style>
</head>
<body>

    <?php if (isset($error)) echo "<p class='error-msg'>$error</p>"; ?>

    <div class="content">
        <div class="form-box">
            <h2>Add Category</h2>
            <form method="post" action="">
                <?php if(isset($msg)) echo "<p class='msg'>$msg</p>"; ?>   
                <label>Category Name</label>
                <input type="text" name="name" required>

                <label>Category Type</label>
                <select name="type" required>
                    <option value="">--Select Type--</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>

                <button type="submit" name="add_category">Add Category</button>
            </form>
            <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Existing Categories Table -->
    <h3 style="text-align:center; margin-bottom:15px;">Existing Categories</h3>
    <table class="category-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($categories) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($categories)): ?>
            <tr>
                <td><?= $row['category_id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= ucfirst($row['type']) ?></td>
                <td>
                    <a href="edit_category.php?id=<?= $row['category_id'] ?>" class="action-btn edit-btn">Edit</a>
                    <a href="?delete_id=<?= $row['category_id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">No categories found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
