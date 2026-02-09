<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Server-side validation
    if (empty($name) || empty($email) || empty($password)) {
        echo "All fields are required!";
        exit;
    }

    // Name must NOT contain numbers
    if (preg_match('/\d/', $name)) {
        echo "Name must not contain numbers!";
        exit;
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Please enter a valid email!";
        exit;
    }

    // Password validation (8+ chars, upper, lower, number, symbol)
    if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{8,}$/', $password)) {
        echo "Password must be 8+ chars with upper, lower, number & symbol!";
        exit;
    }

    // Check if email exists
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo "Email already registered!";
        mysqli_stmt_close($stmt);
        exit;
    }
    mysqli_stmt_close($stmt);

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
    mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed_password);

    if (mysqli_stmt_execute($stmt)) {
        echo "success";
    } else {
        echo "Registration failed. Please try again.";
    }

    mysqli_stmt_close($stmt);
}
?>
