<?php
include "db.php";
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "All fields are required!";
    } else {
        $sql = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            // verify password
            if (password_verify($password, $row['password'])) {

                // store user info in session
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['username'] = $row['name'];

                // redirect based on role
                if ($row['role'] == "admin") {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: user_dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "No account found with this email!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body {
            font-family: Arial;
            background: linear-gradient(135deg, #a8edea, #fed6e3);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-box {
            background: white;
            padding: 30px;
            width: 360px;
            border-radius: 12px;
            box-shadow: 0 8px 18px rgba(0,0,0,0.12);
            animation: pop 0.4s ease-in-out;
        }

        @keyframes pop {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
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
            border-color: #ff7eb3;
            box-shadow: 0 0 7px rgba(255,126,179,0.4);
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            background: #ff7eb3;
            border: none;
            color: white;
            font-size: 17px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #e7679b;
        }

        .message {
            text-align: center;
            color: red;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .link {
            text-align: center;
            margin-top: 12px;
        }

        .link a {
            color: #ff7eb3;
            text-decoration: none;
            font-size: 14px;
        }

        .link a:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>

<div class="form-box">
    <h2>Login</h2>

    <?php if($error){ echo "<div class='message'>$error</div>"; } ?>

    <form method="POST" action="">
        <input type="email" name="email" placeholder="Email">

        <input type="password" name="password" placeholder="Password">

        <button type="submit">Login</button>
    </form>

    <div class="link">
        Don't have an account? <a href="register.php">Register</a>
    </div>
</div>

</body>
</html>
