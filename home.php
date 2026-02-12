<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Finance Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
        }

        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(22, 34, 63, 0.95), rgba(6, 182, 212, 0.95)),
                        url('https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1920&q=80') center/cover;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 20px;
        }

        .navbar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 24px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
            font-weight: 700;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .nav {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .btn-solid {
            background: white;
            color: #0ea5e9;
        }

        .btn-solid:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .content {
            max-width: 700px;
        }

        h1 {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 24px;
            line-height: 1.2;
        }

        p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
        }

        .buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
        }

        .btn-large {
            padding: 16px 40px;
            font-size: 16px;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 20px;
            }

            h1 {
                font-size: 36px;
            }

            p {
                font-size: 16px;
            }

            .buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn-large {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="hero">
        <nav class="navbar">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <span>FinanceTracker</span>
            </div>
            <div class="nav">
                <a href="login.php" class="btn btn-outline"><i class="fa-solid fa-right-to-bracket"></i>Login</a>
                <a href="register.php" class="btn btn-solid"><i class="fa-solid fa-user-plus"></i>Sign Up</a>
            </div>
        </nav>

        <div class="content">
            <h1>Take Control of Your Finances</h1>
            <p>Track income, manage expenses, and achieve your financial goals.</p>
            
            <div class="buttons">
                <a href="register.php" class="btn btn-solid btn-large"><i class="fa-solid fa-rocket"></i>Get Started</a>
                <a href="login.php" class="btn btn-outline btn-large">Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>