<?php
include "db.php";

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm = mysqli_real_escape_string($conn, $_POST['confirm']);

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "All fields are required!";
    }
    elseif ($password !== $confirm) {
        $error = "Passwords do not match!";
    }
    else {
        $check = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conn, $check);

        if (mysqli_num_rows($result) > 0) {
            $error = "Email already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashed_password')";

            if (mysqli_query($conn, $sql)) {
                $success = "Registration successful!";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body {
            font-family: Arial;
            background: linear-gradient(135deg, #6dd5ed, #2193b0);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-container {
            background: white;
            padding: 30px;
            width: 380px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            text-align: center;
            margin-bottom: 15px;
            color: #333;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            transition: 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #2193b0;
            box-shadow: 0 0 5px rgba(33,147,176,0.4);
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            background: #2193b0;
            border: none;
            color: white;
            font-size: 17px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #17627a;
        }

        .message {
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .error {
            color: #d8000c;
        }

        .success {
            color: #4BB543;
        }

        .link {
            text-align: center;
            margin-top: 12px;
        }

        .link a {
            color: #2193b0;
            text-decoration: none;
            font-size: 14px;
        }

        .link a:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>

<div class="form-container">
    <h2>Create Account</h2>

    <?php if($error){ echo "<div class='message error'>$error</div>"; } ?>
    <?php if($success){ echo "<div class='message success'>$success</div>"; } ?>

    <form method="POST" action="">
        <input type="text" name="name" placeholder="Full Name">

        <input type="email" name="email" placeholder="Email">

        <input type="password" name="password" placeholder="Password">

        <input type="password" name="confirm" placeholder="Confirm Password">

        <button type="submit">Register</button>
    </form>

    <div class="link">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>

</body>
</html>
