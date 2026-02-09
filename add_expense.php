<?php
session_start();
include "db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if (isset($_POST['add_expense'])) {
    $amount = trim($_POST['amount']);
    $description = trim($_POST['description']);
    $date = trim($_POST['date']);
    
    // Validation
    if (empty($amount) || empty($description) || empty($date)) {
        $error = "All fields are required.";
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = "Please enter a valid positive amount.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $error = "Invalid date format.";
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = mysqli_prepare($conn, "INSERT INTO expenses (user_id, amount, description, date) VALUES (?, ?, ?, ?)");
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "idss", $user_id, $amount, $description, $date);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Expense added successfully!";
                header("refresh:1;url=user_dashboard.php");
            } else {
                $error = "Failed to add expense. Please try again.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Database error. Please try again.";
        }
    }
}
?>
<?php include "header.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: linear-gradient(135deg, rgba(102,126,234,0.05), rgba(118,75,162,0.05)), #f5f7fa;
        }

        .content {
            min-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .form-container {
            background: white;
            padding: 40px;
            width: 100%;
            max-width: 480px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .form-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .form-header h2 {
            margin: 0 0 8px 0;
            color: #202124;
            font-size: 24px;
            font-weight: 600;
        }

        .form-header p {
            margin: 0;
            color: #5f6368;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #202124;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #dadce0;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #c62828;
            box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
        }

        .form-group input[readonly] {
            background: #f8f9fa;
            cursor: pointer;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .alert-error {
            background: #fce8e6;
            color: #c5221f;
            border-left: 4px solid #c5221f;
        }

        .alert-success {
            background: #e6f4ea;
            color: #137333;
            border-left: 4px solid #137333;
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: #c62828;
            border: none;
            color: white;
            font-size: 16px;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: #b71c1c;
        }

        .btn-primary:active {
            background: #8b0000;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #1a73e8;
            font-weight: 500;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #5f6368;
        }

        .input-icon input {
            padding-left: 44px;
        }

        @media (max-width: 576px) {
            .form-container {
                padding: 24px;
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>
    <script>
        $(function() {
            $("#datepicker").datepicker({
                dateFormat: "yy-mm-dd",
                maxDate: 0,
                changeMonth: true,
                changeYear: true
            });
        });
    </script>
</head>
<body>
    <div class="content">
        <div class="form-container">
            <div class="form-header">
                <h2>Add Expense</h2>
                <p>Track your spending</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="amount">Amount (Rs.)</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-rupee-sign"></i>
                        <input type="number" id="amount" name="amount" placeholder="Enter expense amount" step="0.01" min="0.01" required value="<?= isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Category</label>
                    <select name="description" id="description" required>
                        <option value="">Select a category</option>
                        <?php
                        $cat = mysqli_query($conn, "SELECT * FROM categories WHERE type='expense' ORDER BY name");
                        while ($row = mysqli_fetch_assoc($cat)) {
                            $selected = (isset($_POST['description']) && $_POST['description'] == $row['name']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['name']) . "' $selected>" . htmlspecialchars($row['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="datepicker">Date</label>
                    <div class="input-icon">
                        <i class="fa-regular fa-calendar"></i>
                        <input type="text" id="datepicker" name="date" placeholder="YYYY-MM-DD" readonly required value="<?= isset($_POST['date']) ? htmlspecialchars($_POST['date']) : date('Y-m-d') ?>">
                    </div>
                </div>

                <button type="submit" name="add_expense" class="btn-primary">
                    <i class="fa-solid fa-minus"></i> Add Expense
                </button>
            </form>

            <a href="user_dashboard.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php include "footer.php"; ?>
</body>
</html>