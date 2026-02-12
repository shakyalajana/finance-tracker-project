<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT created_at FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_row = mysqli_fetch_assoc($result);
$user_created_at = date('Y-m-d', strtotime($user_row['created_at']));
mysqli_stmt_close($stmt);

$today = date('Y-m-d');

// First day of current month
$current_month_start = date('Y-m-01');

// Default: show current month only
$from_date = max($current_month_start, $user_created_at);
$to_date = $today;

// Handle date filtering with validation
if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    $from_input = $_GET['from_date'];
    $to_input = $_GET['to_date'];
    
    // Validate date format
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_input) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_input)) {
        $from_date = max($from_input, $user_created_at);
        $to_date = min($to_input, $today);
    }
}

// Fetch income data with JOIN to get category name
$stmt = mysqli_prepare($conn, 
    "SELECT i.id, i.amount, c.name as category_name, i.date, i.description
     FROM income i
     LEFT JOIN categories c ON i.category_id = c.category_id
     WHERE i.user_id = ? AND i.date BETWEEN ? AND ? 
     ORDER BY i.date DESC");
mysqli_stmt_bind_param($stmt, "iss", $user_id, $from_date, $to_date);
mysqli_stmt_execute($stmt);
$income_data = mysqli_stmt_get_result($stmt);

// Fetch expense data with JOIN to get category name
$stmt2 = mysqli_prepare($conn, 
    "SELECT e.id, e.amount, c.name as category_name, e.date, e.description
     FROM expenses e
     LEFT JOIN categories c ON e.category_id = c.category_id
     WHERE e.user_id = ? AND e.date BETWEEN ? AND ? 
     ORDER BY e.date DESC");
mysqli_stmt_bind_param($stmt2, "iss", $user_id, $from_date, $to_date);
mysqli_stmt_execute($stmt2);
$expense_data = mysqli_stmt_get_result($stmt2);

// Calculate totals
$total_income = 0;
$total_expense = 0;
$income_array = [];
$expense_array = [];

while ($row = mysqli_fetch_assoc($income_data)) {
    $income_array[] = $row;
    $total_income += $row['amount'];
}

while ($row = mysqli_fetch_assoc($expense_data)) {
    $expense_array[] = $row;
    $total_expense += $row['amount'];
}
?>
<?php include "header.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - FinTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, rgba(102,126,234,0.05), rgba(118,75,162,0.05)), #f5f7fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 24px;
        }

        .page-header {
            background: white;
            padding: 24px 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header h2 {
            margin: 0;
            color: #1f2937;
            font-size: 24px;
            font-weight: 700;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .filter-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
        }

        .filter-form {
            display: flex;
            gap: 16px;
            align-items: end;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-btn {
            padding: 10px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn:hover {
            background: #5568d3;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s;
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .summary-icon.income {
            background: #d1fae5;
            color: #065f46;
        }

        .summary-icon.expense {
            background: #fee2e2;
            color: #991b1b;
        }

        .summary-icon.balance {
            background: #dbeafe;
            color: #1e40af;
        }

        .summary-content h3 {
            margin: 0 0 4px 0;
            font-size: 13px;
            font-weight: 500;
            color: #6b7280;
        }

        .summary-content p {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
        }

        .section-header {
            margin: 32px 0 16px 0;
        }

        .section-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f9fafb;
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
            font-size: 14px;
        }

        tr:hover {
            background: #f9fafb;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .empty-state {
            padding: 48px 24px;
            text-align: center;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #d1d5db;
        }

        .action-links {
            display: flex;
            gap: 12px;
        }

        .action-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            transition: color 0.2s;
        }

        .action-links a:hover {
            color: #5568d3;
            text-decoration: underline;
        }

        .action-links a.delete {
            color: #dc2626;
        }

        .action-links a.delete:hover {
            color: #b91c1c;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 16px;
            }

            .page-header {
                padding: 20px;
            }

            .filter-form {
                flex-direction: column;
            }

            .form-group {
                width: 100%;
            }

            table {
                font-size: 13px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2>All Transactions</h2>
            <a href="user_dashboard.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>
                Dashboard
            </a>
        </div>

        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="from_date">From Date</label>
                    <input type="date" id="from_date" name="from_date" min="<?= htmlspecialchars($user_created_at) ?>" max="<?= htmlspecialchars($today) ?>" value="<?= htmlspecialchars($from_date) ?>" required>
                </div>
                <div class="form-group">
                    <label for="to_date">To Date</label>
                    <input type="date" id="to_date" name="to_date" min="<?= htmlspecialchars($user_created_at) ?>" max="<?= htmlspecialchars($today) ?>" value="<?= htmlspecialchars($to_date) ?>" required>
                </div>
                <button type="submit" class="filter-btn">
                    <i class="fa-solid fa-filter"></i> Apply Filter
                </button>
            </form>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-icon income">
                    <i class="fa-solid fa-arrow-up"></i>
                </div>
                <div class="summary-content">
                    <h3>Total Income</h3>
                    <p style="color: green;">Rs. <?= number_format($total_income, 2) ?></p>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-icon expense">
                    <i class="fa-solid fa-arrow-down"></i>
                </div>
                <div class="summary-content">
                    <h3>Total Expense</h3>
                    <p style="color: red;">Rs. <?= number_format($total_expense, 2) ?></p>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-icon balance">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <div class="summary-content">
                    <h3>Net Balance</h3>
                    <p style="color: <?= ($total_income - $total_expense) >= 0 ? 'green' : 'red' ?>;">Rs. <?= number_format($total_income - $total_expense, 2) ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h3>Income Transactions (<?= count($income_array) ?>)</h3>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description</th> 
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($income_array) > 0): ?>
                        <?php foreach ($income_array as $row): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($row['date'])) ?></td>
                            <td><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></td>
                            <td style="font-weight: 600; color: green;">
                                + Rs. <?= number_format($row['amount'], 2) ?>
                            </td>
                            <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
                            <td>
                                <div class="action-links">
                                    <a href="edit_transaction.php?type=income&id=<?= $row['id'] ?>">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </a>
                                    <a href="delete_transaction.php?type=income&id=<?= $row['id'] ?>" 
                                    class="delete"
                                    onclick="return confirm('Are you sure you want to delete this income?')">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state"> 
                                <i class="fa-regular fa-folder-open"></i>
                                <p>No income records found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section-header">
            <h3>Expense Transactions (<?= count($expense_array) ?>)</h3>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($expense_array) > 0): ?>
                        <?php foreach ($expense_array as $row): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($row['date'])) ?></td>
                            <td><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></td>
                            <td style="font-weight: 600; color: green;">
                                + Rs. <?= number_format($row['amount'], 2) ?>
                            </td>
                            <td><?= htmlspecialchars($row['description'] ?? '-') ?></td> 
                            <td>
                                <div class="action-links">
                                    <a href="edit_transaction.php?type=expense&id=<?= $row['id'] ?>">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </a>
                                    <a href="delete_transaction.php?type=expense&id=<?= $row['id'] ?>" 
                                    class="delete"
                                    onclick="return confirm('Are you sure you want to delete this expense?')">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <i class="fa-regular fa-folder-open"></i>
                                <p>No expense records found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include "footer.php"; ?>
</body>
</html>