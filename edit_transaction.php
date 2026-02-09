<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Validate type parameter
if (!isset($_GET['type']) || !in_array($_GET['type'], ['income', 'expense'])) {
    die("Invalid transaction type");
}

$type = $_GET['type'];
$table = ($type === 'income') ? 'income' : 'expenses';

// Validate and sanitize ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid transaction ID");
}

$id = (int)$_GET['id'];

// Fetch transaction data using prepared statement
$stmt = mysqli_prepare($conn, "SELECT * FROM $table WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) !== 1) {
    die("Transaction not found or unauthorized access");
}

$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle form submission
if (isset($_POST['update'])) {
    $amount = trim($_POST['amount']);
    $date = trim($_POST['date']);
    
    // Validation
    if (empty($amount) || empty($date)) {
        $error = "All fields are required.";
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = "Please enter a valid positive amount.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $error = "Invalid date format.";
    } else {
        // Update using prepared statement
        $stmt = mysqli_prepare($conn, "UPDATE $table SET amount = ?, date = ? WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "dsii", $amount, $date, $id, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Transaction updated successfully!";
            header("refresh:1;url=view_transactions.php");
        } else {
            $error = "Failed to update transaction. Please try again.";
        }
        
        mysqli_stmt_close($stmt);
    }
}
?>
<?php include "header.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, rgba(102,126,234,0.05), rgba(118,75,162,0.05)), #f5f7fa;
            margin: 0;
            padding: 0;
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

        .transaction-info {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            border-left: 4px solid #1a73e8;
        }

        .transaction-info p {
            margin: 0 0 8px 0;
            color: #5f6368;
            font-size: 14px;
        }

        .transaction-info p:last-child {
            margin: 0;
        }

        .transaction-info strong {
            color: #202124;
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

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #dadce0;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .form-group input:focus {
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

        .btn-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            font-size: 16px;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-primary {
            background: #1a73e8;
            color: white;
        }

        .btn-primary:hover {
            background: #1557b0;
        }

        .btn-secondary {
            background: #f1f3f4;
            color: #5f6368;
        }

        .btn-secondary:hover {
            background: #e8eaed;
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

            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="form-container">
            <div class="form-header">
                <h2>Edit Transaction</h2>
                <p>Update <?= htmlspecialchars($type) ?> details</p>
            </div>

            <div class="transaction-info">
                <p><strong>Category:</strong> <?= htmlspecialchars($data['description']) ?></p>
                <p><strong>Current Amount:</strong> Rs. <?= number_format($data['amount'], 2) ?></p>
                <p><strong>Current Date:</strong> <?= date('d M Y', strtotime($data['date'])) ?></p>
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
                        <input type="number" id="amount" name="amount" value="<?= htmlspecialchars($data['amount']) ?>" step="0.01"min="0.01" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="date">Date</label>
                    <div class="input-icon">
                        <i class="fa-regular fa-calendar"></i>
                        <input type="date" id="date" name="date" value="<?= htmlspecialchars($data['date']) ?>"max="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="update" class="btn btn-primary">
                        <i class="fa-solid fa-check"></i> Update
                    </button>
                    <a href="view_transactions.php" class="btn btn-secondary">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php include "footer.php"; ?>
</body>
</html>