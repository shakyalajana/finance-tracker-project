<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid category ID");
}

$category_id = (int) $_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM categories WHERE category_id = $category_id");
if (mysqli_num_rows($result) !== 1) {
    die("Category not found");
}
$category = mysqli_fetch_assoc($result);

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
    <title>Edit Category - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #eef2f7 0%, #e6ecf3 50%, #dde5ee 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .form-container {
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            border: 1px solid #e6ebf2;
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.4s ease;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-header .icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .form-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }
        
        .form-header p {
            font-size: 14px;
            color: #718096;
        }
        
        .error-msg {
            background: #fee;
            color: #c53030;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            animation: shake 0.3s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 8px;
        }
        
        .form-group label i {
            margin-right: 5px;
            color: #667eea;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f8fafc;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .type-badge.income {
            background: #d4edda;
            color: #155724;
        }
        
        .type-badge.expense {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }
        
        .btn {
            flex: 1;
            padding: 14px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid transparent;
        }

        /* PRIMARY BUTTON (Update) */
        .btn-primary {
            background: #5f7fa6;
            color: white;
            border-color: #5f7fa6;
            box-shadow: 0 4px 12px rgba(95, 127, 166, 0.25);
        }

        .btn-primary:hover {
            background: #4f6d8f;
            border-color: #4f6d8f;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(95, 127, 166, 0.35);
        }

        /* SECONDARY BUTTON (Cancel) */
        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border-color: #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        
        .category-info p {
            font-size: 13px;
            color: #4a5568;
            margin: 5px 0;
        }
        
        .category-info strong {
            color: #2d3748;
        }
        
        @media (max-width: 480px) {
            .form-container {
                padding: 30px 20px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .form-header h2 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h2>Edit Category</h2>
            <p>Update category information</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>
                    <i class="fas fa-tag"></i> Category Name
                </label>
                <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" placeholder="Enter category name" required
                >
            </div>
            
            <div class="form-group">
                <label>
                    <i class="fas fa-list"></i> Category Type
                </label>
                <select name="type" required>
                    <option value="income" <?= $category['type'] == 'income' ? 'selected' : '' ?>>Income</option>
                    <option value="expense" <?= $category['type'] == 'expense' ? 'selected' : '' ?>>Expense</option>
                </select>
            </div>
            
            <div class="btn-group">
                <button type="submit" name="update_category" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="manage_category.php" class="btn btn-secondary" style="text-decoration: none;">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>