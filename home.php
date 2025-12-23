<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Finance Tracker</title>
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family: 'Poppins', Arial, sans-serif;
        }

        html, body {
            height: 100%;
            overflow: hidden;
        }

        body {
            background: url('Images/main.jpg') no-repeat center center/cover;
            position: relative;
            display: flex;
            /*justify-content: center;
            align-items: center;*/
        }

        /* Dark overlay for readability */
        body::before {
            content: "";
            position: absolute;
            top:0; left:0;
            width:100%; height:100%;
            background: rgba(0,0,0,0.5);
            z-index:1;
        }

        /* Animated shapes 
        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.6;
            animation: float 10s infinite linear;
        }

        .shape1 {
            width: 150px;
            height: 150px;
            background: #ff6b6b;
            top: 10%;
            left: 15%;
            animation-duration: 12s;
        }

        .shape2 {
            width: 100px;
            height: 100px;
            background: #1dd1a1;
            top: 70%;
            left: 10%;
            animation-duration: 15s;
        }

        .shape3 {
            width: 120px;
            height: 120px;
            background: #feca57;
            top: 20%;
            left: 75%;
            animation-duration: 18s;
        }

        .shape4 {
            width: 80px;
            height: 80px;
            background: #5f27cd;
            top: 65%;
            left: 80%;
            animation-duration: 20s;
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
            100% { transform: translateY(0px) rotate(360deg); }
        }*/

        /* Main card */
        .home-container {
            position: relative;
            z-index:2;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 200px 40px;
            border-radius: 20px;
            text-align: center;
            color: #fff;
            max-width: 450px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.5);
            animation: slideIn 1s ease-in-out;
        }

        @keyframes slideIn {
            0% {
                transform: translateX(-80px);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }


        h1 {
            font-size: 32px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        p {
            font-size: 16px;
            margin-bottom: 40px;
            color: #f0f0f0;
        }

        .btn {
            display: inline-block;
            margin: 10px;
            padding: 14px 28px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            text-decoration: none;
            transition: 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-login {
            background: #00c6ff;
            color: #fff;
        }

        .btn-login:hover {
            background: #0072b1;
        }

        .btn-register {
            background: #f0a500;
            color: #fff;
        }

        .btn-register:hover {
            background: #c17a00;
        }

        .btn:hover {
            transform: translateY(-3px);
        }

    </style>
</head>
<body>

    <!-- Animated shapes 
    <div class="shape shape1"></div>
    <div class="shape shape2"></div>
    <div class="shape shape3"></div>
    <div class="shape shape4"></div>--->

    <div class="home-container">
        <h1>Personal Finance Tracker</h1>
        <p>Manage your income and expenses easily. Track your money, plan your budget, and achieve your financial goals!</p>

        <a href="login.php" class="btn btn-login">Login</a>
        <a href="register.php" class="btn btn-register">Register</a>
    </div>

</body>
</html>
