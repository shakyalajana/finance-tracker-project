<?php
$host = "localhost";
$user = "root"; // default
$pass = ""; // default
$db = "finance_tracker";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
