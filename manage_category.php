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

// Count categories
$incomeCount = mysqli_query($conn, "SELECT COUNT(*) as total FROM categories WHERE type='income'")->fetch_assoc()['total'];
$expenseCount = mysqli_query($conn, "SELECT COUNT(*) as total FROM categories WHERE type='expense'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
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
            background: linear-gradient(135deg, #dbe3ec 0%, #d2dbe6 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header h1 i {
            color: #667eea;
        }
        
        .back-btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
        }
        
        .stat-info h3 {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .stat-info p {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
        }
        
        /* Main Content */
        .content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
        }
        
        /* Form Box */
        .form-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            height: fit-content;
        }
        
        .form-box h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            color: #374151;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .submit-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        /* Messages */
        .message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            font-size: 14px;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Table Box */
        .table-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }
        
        .table-box h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        thead th {
            background: #f9fafb;
            color: #374151;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        thead th:first-child {
            border-radius: 8px 0 0 0;
        }
        
        thead th:last-child {
            border-radius: 0 8px 0 0;
        }
        
        tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s;
        }
        
        tbody tr:hover {
            background: #f9fafb;
        }
        
        tbody td {
            padding: 15px;
            color: #374151;
            font-size: 14px;
        }
        
        tbody td:first-child {
            font-weight: 600;
            color: #667eea;
        }
        
        .type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .type-badge.income {
            background: #d1fae5;
            color: #065f46;
        }
        
        .type-badge.expense {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .edit-btn {
            background: #fef3c7;
            color: #92400e;
        }
        
        .edit-btn:hover {
            background: #fde68a;
            transform: translateY(-2px);
        }
        
        .delete-btn {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .delete-btn:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.3;
            color: #9ca3af;
        }
        
        .empty-state p {
            color: #374151;
        }
        
        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 13px;
            }
            
            thead th, tbody td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-list"></i> Manage Categories</h1>
            <a href="admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: #2e7d32;">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-info">
                    <h3>Income Categories</h3>
                    <p><?= $incomeCount ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #f57f17;">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-info">
                    <h3>Expense Categories</h3>
                    <p><?= $expenseCount ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #667eea;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Categories</h3>
                    <p><?= $incomeCount + $expenseCount ?></p>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content">
            <!-- Add Category Form -->
            <div class="form-box">
                <h2>Add New Category</h2>
                
                <?php if(isset($msg)): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <?= $msg ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($error)): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" name="name" placeholder="e.g., Salary, Food, Transport" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Category Type</label>
                        <select name="type" required>
                            <option value="">Select Type</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="add_category" class="submit-btn">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </form>
            </div>
            
            <!-- Categories Table -->
            <div class="table-box">
                <h2>All Categories</h2>
                
                <?php if (mysqli_num_rows($categories) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($categories)): ?>
                        <tr>
                            <td><?= $row['category_id'] ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td>
                                <span class="type-badge <?= $row['type'] ?>">
                                    <?= ucfirst($row['type']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="edit_category.php?id=<?= $row['category_id'] ?>" class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete_id=<?= $row['category_id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this category?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>No categories found. Add your first category!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>