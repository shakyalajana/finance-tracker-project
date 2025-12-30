<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$type = $_GET['type'];
$id = (int)$_GET['id'];

$table = ($type === 'income') ? 'income' : 'expenses';

mysqli_query($conn,
    "DELETE FROM $table WHERE id=$id AND user_id=$user_id");

header("Location: view_transactions.php");
exit();

?>