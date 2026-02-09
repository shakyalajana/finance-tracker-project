<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$month = date('m');
$year = date('Y');
$error = '';
$success = '';

// Fetch current limit using prepared statement
$stmt = mysqli_prepare($conn, 
    "SELECT limit_amount FROM expense_limits 
     WHERE user_id = ? AND month = ? AND year = ?");
mysqli_stmt_bind_param($stmt, "iss", $user_id, $month, $year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$current_limit = 0;

if ($row = mysqli_fetch_assoc($result)) {
    $current_limit = $row['limit_amount'];
}
mysqli_stmt_close($stmt);

// Fetch current month's expenses
$stmt = mysqli_prepare($conn,
    "SELECT SUM(amount) AS total FROM expenses 
     WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
mysqli_stmt_bind_param($stmt, "iss", $user_id, $month, $year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$expense_row = mysqli_fetch_assoc($result);
$current_expense = $expense_row['total'] ?? 0;
mysqli_stmt_close($stmt);

if (isset($_POST['save_limit'])) {
    $limit = trim($_POST['limit_amount']);
    
    // Validation
    if (empty($limit)) {
        $error = "Please enter a limit amount.";
    } elseif (!is_numeric($limit) || $limit <= 0) {
        $error = "Please enter a valid positive amount.";
    } else {
        // Check if limit exists
        $stmt = mysqli_prepare($conn,
            "SELECT id FROM expense_limits 
             WHERE user_id = ? AND month = ? AND year = ?");
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $month, $year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            // Update existing limit
            mysqli_stmt_close($stmt);
            $stmt = mysqli_prepare($conn,
                "UPDATE expense_limits 
                 SET limit_amount = ? 
                 WHERE user_id = ? AND month = ? AND year = ?");
            mysqli_stmt_bind_param($stmt, "diss", $limit, $user_id, $month, $year);
        } else {
            // Insert new limit
            mysqli_stmt_close($stmt);
            $stmt = mysqli_prepare($conn,
                "INSERT INTO expense_limits (user_id, month, year, limit_amount)
                 VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "issd", $user_id, $month, $year, $limit);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Expense limit saved successfully!";
            $current_limit = $limit;
            //header("location: user_dashboard.php");
        } else {
            $error = "Failed to save limit. Please try again.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Calculate percentage used
$percentage_used = 0;
if ($current_limit > 0) {
    $percentage_used = min(($current_expense / $current_limit) * 100, 100);
}
?>
<?php include "header.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Limit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="CSS/expense_limit.css">
</head>
<body>
    <div class="content">
        <div class="form-container">
            <div class="form-header">
                <span class="month-label">
                    <i class="fa-regular fa-calendar"></i>
                    <?= date('F Y') ?>
                </span>
                <h2>Set Expense Limit</h2>
                <p>Control your monthly spending with a budget</p>
            </div>

            <?php if ($current_limit > 0): ?>
                <div class="current-status">
                    <div class="status-row">
                        <span class="status-label">Current Limit</span>
                        <span class="status-value" style="color: #1a73e8;">
                            Rs. <?= number_format($current_limit, 2) ?>
                        </span>
                    </div>
                    <div class="status-row">
                        <span class="status-label">Spent So Far</span>
                        <span class="status-value" style="color: <?= $current_expense > $current_limit ? '#ea4335' : '#34a853' ?>;">
                            Rs. <?= number_format($current_expense, 2) ?>
                        </span>
                    </div>
                    <div class="status-row">
                        <span class="status-label">Remaining</span>
                        <span class="status-value" style="color: <?= ($current_limit - $current_expense) < 0 ? '#ea4335' : '#34a853' ?>;">
                            Rs. <?= number_format(max(0, $current_limit - $current_expense), 2) ?>
                        </span>
                    </div>

                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Usage</span>
                            <span><strong><?= number_format($percentage_used, 1) ?>%</strong></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?= $percentage_used >= 100 ? 'progress-danger' : ($percentage_used >= 80 ? 'progress-warning' : 'progress-safe') ?>" 
                                 style="width: <?= $percentage_used ?>%;">
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($current_expense > $current_limit): ?>
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>You've exceeded your limit by Rs. <?= number_format($current_expense - $current_limit, 2) ?></span>
                    </div>
                <?php elseif ($percentage_used >= 80): ?>
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>You've used <?= number_format($percentage_used, 1) ?>% of your budget</span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

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
                    <label for="limit_amount">
                        <?= $current_limit > 0 ? 'Update Limit (Rs.)' : 'Set Limit (Rs.)' ?>
                    </label>
                    <div class="input-icon">
                        <i class="fa-solid fa-rupee-sign"></i>
                        <input 
                            type="number" 
                            id="limit_amount"
                            name="limit_amount" 
                            placeholder="Enter monthly expense limit"
                            value="<?= $current_limit > 0 ? $current_limit : '' ?>"
                            step="0.01"
                            min="0.01"
                            required
                        >
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="save_limit" class="btn btn-primary">
                        <i class="fa-solid fa-check"></i> 
                        <?= $current_limit > 0 ? 'Update Limit' : 'Set Limit' ?>
                    </button>
                    <a href="user_dashboard.php" class="btn btn-secondary">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php include "footer.php"; ?>
</body>
</html>