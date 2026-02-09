<?php
include "db.php";
session_start();

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if(empty($email) || empty($password)){
        echo "All fields are required!";
        exit;
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) == 1){
        $row = mysqli_fetch_assoc($result);
        if(password_verify($password, $row['password'])){
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['name'] = $row['name'];

            mysqli_query($conn, "UPDATE users SET last_login=NOW() WHERE user_id='{$row['user_id']}'");

            if($row['role'] == "admin"){
                echo "success_admin";
            } else {
                echo "success_user";
            }
        } else {
            echo "Invalid email or password!";
        }
    } else {
        echo "No account found with this email!";
    }
} else {
    echo "Invalid request!";
}
?>
