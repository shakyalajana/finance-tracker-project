<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }

        body {
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, rgba(22, 34, 63, 0.85), rgba(6, 182, 212, 0.5)),
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
            background: linear-gradient(135deg, #28605a, #4da1bc);
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

        .err {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
            color: red;
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
            font-family: 'Poppins', sans-serif;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #28605a, #4da1bc);
            border: none;
            color: white;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(83, 168, 194, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6b7280;
        }

        .link a {
            color: #4da1bc;
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo"><i class="fa-solid fa-chart-line"></i></div>
            <h2>Create Account</h2>
            <p>Start managing your finances</p>
        </div>
        <div id="msgBox" style="display:none; padding:12px; border-radius:8px; margin-bottom:15px; font-size:14px;"></div>
        <form id="register" method="post">
            <div class="form-group">
                <label>Full Name</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="name" id="name" placeholder="Enter your name" required>
                </div>
                <div class="err" id="nameErr"></div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="Enter your email" required>
                    </div>
                <div class="err" id="emailErr"></div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Enter password" required>
                    </div>
                <div class="err" id="passErr"></div>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="confirm" id="confirm" placeholder="Confirm password" required>
                    </div>
                <div class="err" id="conErr"></div>
            </div>

            <button type="submit" class="btn" id="submitBtn">Create Account</button>
        </form>

        <div class="link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#register').submit(function(e) {
                e.preventDefault();
                $(".err").text("").hide();
                let valid = true;
                let name = $('#name').val().trim();
                let email = $('#email').val().trim();
                let password = $('#password').val();
                let confirm = $('#confirm').val();
                
                // Client-side validation
                if (name === "" || /\d/.test(name)) {
                    $("#nameErr").text("Name must not be empty and contain any numbers.").show();
                    valid = false;
                }
                
                if(email === "" || !email.includes("@")) {
                    $("#emailErr").text("Enter a valid email").show();
                    valid = false;
                }

                let pw = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{8,}$/;
                if (!pw.test(password)) {
                    $("#passErr").text("Password must be at least 8 characters with uppercase, lowercase, digit & special character.").show();
                    valid = false;
                }
                
                if (password !== confirm) {
                    $("#conErr").text("Passwords do not match").show();
                    valid = false;
                }
                if (valid) {
                    $("#submitBtn").prop("disabled", true).text("Creating...");

                    $.ajax({
                        url: "register_ajax.php",
                        type: "POST",
                        data: {
                            name: name,
                            email: email,
                            password: password
                        },

                        success: function(response) {
                            $("#submitBtn").prop("disabled", false).text("Create Account");

                            if (response.trim() === "success") {
                                $("#msgBox")
                                    .css({
                                        "background": "#d1fae5",
                                        "color": "#065f46",
                                        "border": "1px solid #a7f3d0"
                                    })
                                    .text("Account created successfully! Redirecting to login...")
                                    .fadeIn();

                                $("#register")[0].reset();

                                setTimeout(function(){
                                    window.location.href = "login.php";
                                }, 2000);

                            } else {
                                $("#msgBox")
                                    .css({
                                        "background": "#fee2e2",
                                        "color": "#991b1b",
                                        "border": "1px solid #fecaca"
                                    })
                                    .text(response)
                                    .fadeIn();
                            }
                        },

                        error: function() {
                            $("#submitBtn").prop("disabled", false).text("Create Account");

                            $("#msgBox")
                                .css({
                                    "background": "#fee2e2",
                                    "color": "#991b1b",
                                    "border": "1px solid #fecaca"
                                })
                                .text("Server error. Please try again.")
                                .fadeIn();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>