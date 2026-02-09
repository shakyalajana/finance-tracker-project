<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Validate type parameter
if (!isset($_GET['type']) || !in_array($_GET['type'], ['income', 'expense'])) {
    $_SESSION['error'] = "Invalid transaction type";
    header("Location: view_transactions.php");
    exit();
}

$type = $_GET['type'];
$table = ($type === 'income') ? 'income' : 'expenses';

// Validate and sanitize ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid transaction ID";
    header("Location: view_transactions.php");
    exit();
}

$id = (int)$_GET['id'];

// Verify ownership before deleting
$stmt = mysqli_prepare($conn, "SELECT id FROM $table WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) !== 1) {
    $_SESSION['error'] = "Transaction not found or unauthorized access";
    mysqli_stmt_close($stmt);
    header("Location: view_transactions.php");
    exit();
}

mysqli_stmt_close($stmt);

// Delete using prepared statement
$stmt = mysqli_prepare($conn, "DELETE FROM $table WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = ucfirst($type) . " deleted successfully";
} else {
    $_SESSION['error'] = "Failed to delete transaction";
}

mysqli_stmt_close($stmt);
header("Location: view_transactions.php");
exit();
?>