<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/* 1️⃣ Validate category ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid category ID");
}

$category_id = (int) $_GET['id'];

/* 2️⃣ Fetch existing category */
$result = mysqli_query($conn, "SELECT * FROM categories WHERE category_id = $category_id");

if (mysqli_num_rows($result) !== 1) {
    die("Category not found");
}

$category = mysqli_fetch_assoc($result);

/* 3️⃣ Update category */
if (isset($_POST['update_category'])) {

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);

    $update = "
        UPDATE categories 
        SET name = '$name', type = '$type'
        WHERE category_id = $category_id
    ";

    if (mysqli_query($conn, $update)) {
        header("Location: manage_category.php?success=updated");
        exit();
    } else {
        $error = "Update failed: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category</title>
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

        .error-msg {
            color: #b71c1c;
            margin-bottom: 10px;
            text-align: center;
        }
    </style>
</head>
</body>
    <?php if (isset($error)) echo "<p class='error-msg'>$error</p>"; ?>
    <div class="content">
        <div class="form-box">
            <h2>Update Category</h2>
            <form method="POST">
                <label>Category Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>

                <label>Category Type</label>
                <select name="type" required>
                    <option value="income" <?php if ($category['type'] == 'income') echo 'selected'; ?>>Income</option>
                    <option value="expense" <?php if ($category['type'] == 'expense') echo 'selected'; ?>>Expense</option>
                </select>
                <button type="submit" name="update_category">Update Category</button>
            </form>
            <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
