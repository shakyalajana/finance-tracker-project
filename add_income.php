<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if (isset($_POST['add_income'])) {
    $amount = trim($_POST['amount']);
    $category_id = trim($_POST['category_id']);
    $date = trim($_POST['date']);
    $description = trim($_POST['description']); 
    
    // Validation
    if (empty($amount) || empty($category_id) || empty($date)) {
        $error = "All fields are required.";
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = "Please enter a valid positive amount.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $error = "Invalid date format.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO income (user_id, amount, category_id, date, description) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "idiss", $user_id, $amount, $category_id, $date, $description);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Income added successfully!";
                header("refresh:1; url=user_dashboard.php");
            } else {
                $error = "Failed to add income. Please try again.";
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
    <title>Add Income</title>
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
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }

        .form-group input[readonly] {
            background: #f8f9fa;
            cursor: pointer;
        }

        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #dadce0;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: border-color 0.2s;
            box-sizing: border-box;
            resize: vertical;
            min-height: 80px;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
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
            background: #1a73e8;
            border: none;
            color: white;
            font-size: 16px;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-primary:hover {
            background: #1557b0;
        }

        .btn-primary:active {
            background: #0d47a1;
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
                <h2>Add Income</h2>
                <p>Record your earnings</p>
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
                        <input type="number" id="amount" name="amount" placeholder="Enter income amount" step="0.01" min="0.01" required value="<?= isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select name="category_id" id="category_id" required>
                        <option value="">Select a category</option>
                        <?php
                        // Changed to fetch category_id and name
                        $cat = mysqli_query($conn, "SELECT category_id, name FROM categories WHERE type='income' ORDER BY name");
                        while ($row = mysqli_fetch_assoc($cat)) {
                            $selected = (isset($_POST['category_id']) && $_POST['category_id'] == $row['category_id']) ? 'selected' : '';
                            // value is now category_id, display is name
                            echo "<option value='" . $row['category_id'] . "' $selected>" . htmlspecialchars($row['name']) . "</option>";
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

                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea name="description" id="description" rows="3" placeholder="Add additional notes (optional)" style="width: 100%; padding: 12px 16px; border: 1px solid #dadce0; border-radius: 8px; font-size: 15px; font-family: inherit; resize: vertical; box-sizing: border-box;"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                </div>
                <button type="submit" name="add_income" class="btn-primary">
                    <i class="fa-solid fa-plus"></i> Add Income
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