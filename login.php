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
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['username'] = $row['name'];
                mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE user_id = '{$row['user_id']}'");
                
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
    <title>Login - FinTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.75), rgba(118, 75, 162, 0.75)),
                        url('https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1920&q=80') center/cover;
            display: flex;
            padding: 20px;
        }

        .container {
            background: linear-gradient(135deg, #ffffff 0%, #f1f3f6 50%, #e5e7eb 100%);
            padding: 40px;
            max-width: 420px;
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            margin: 0 auto 16px;
        }

        h2 {
            color: #1f2937;
            font-size: 24px;
            margin-bottom: 8px;
        }

        p {
            color: #6b7280;
            font-size: 14px;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        input {
            width: 100%;
            padding: 12px 12px 12px 44px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6b7280;
        }

        .link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container { padding: 30px 24px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo"><i class="fa-solid fa-chart-line"></i></div>
            <h2>Welcome Back</h2>
            <p>Sign in to your account</p>
        </div>

        <?php if($error): ?>
            <div class="alert">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>

            <button type="submit" class="btn">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div class="link">
            Don't have an account? <a href="register.php">Create Account</a>
        </div>
    </div>
    <script>
        $(document).ready(function() {

            $("#loginForm").submit(function(e){
                e.preventDefault();

                let email = $("#email").val().trim();
                let password = $("#password").val().trim();
                let valid = true;

                // Hide previous messages
                $("#msgBox").hide();

                // Validation
                if(email === "") {
                    $("#msgBox")
                        .css({ "background": "#fee2e2", "color": "#991b1b", "border": "1px solid #fecaca" })
                        .text("Email is required")
                        .fadeIn();
                    valid = false;
                }
                if(password === "") {
                    $("#msgBox")
                        .css({ "background": "#fee2e2", "color": "#991b1b", "border": "1px solid #fecaca" })
                        .text("Password is required")
                        .fadeIn();
                    valid = false;
                }

                if(!valid) return;

                // Disable button
                $("#loginBtn").prop("disabled", true).text("Signing in...");

                // AJAX request
                $.ajax({
                    url: "login_process.php",
                    type: "POST",
                    data: { email: email, password: password },
                    success: function(response){
                        $("#loginBtn").prop("disabled", false).text("Sign In");

                        if(response.trim() === "success_user"){
                            $("#msgBox")
                                .css({ "background": "#d1fae5", "color": "#065f46", "border": "1px solid #a7f3d0" })
                                .text("Login successful! Redirecting...")
                                .fadeIn();

                            setTimeout(function(){
                                window.location.href = "user_dashboard.php";
                            }, 1500);

                        } else if(response.trim() === "success_admin") {
                            $("#msgBox")
                                .css({ "background": "#d1fae5", "color": "#065f46", "border": "1px solid #a7f3d0" })
                                .text("Login successful! Redirecting...")
                                .fadeIn();

                            setTimeout(function(){
                                window.location.href = "admin_dashboard.php";
                            }, 1500);

                        } else {
                            $("#msgBox")
                                .css({ "background": "#fee2e2", "color": "#991b1b", "border": "1px solid #fecaca" })
                                .text(response)
                                .fadeIn();
                        }
                    },
                    error: function(){
                        $("#loginBtn").prop("disabled", false).text("Sign In");
                        $("#msgBox")
                            .css({ "background": "#fee2e2", "color": "#991b1b", "border": "1px solid #fecaca" })
                            .text("Server error. Please try again.")
                            .fadeIn();
                    }
                });
            });

        });
        </script>


</body>
</html>