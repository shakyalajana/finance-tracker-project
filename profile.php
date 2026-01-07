<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT name, role, created_at FROM users WHERE user_id='$user_id'")
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings</title>
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #eef2f7;
            margin: 0;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            color: #333;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .profile-card {
            text-align: center;
        }

        .profile-icon {
            font-size: 70px;
            color: #1a73e8;
            margin-bottom: 15px;
        }

        .profile-card h3 {
            margin: 10px 0 5px;
        }

        .profile-card p {
            color: #666;
            font-size: 14px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row span {
            color: #555;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 8px;
            background: #1a73e8;
            color: white;
            text-decoration: none;
            font-weight: 500;
            margin-top: 15px;
        }

        .btn:hover {
            background: #0f5ccc;
        }

        .btn-outline {
            background: none;
            border: 1px solid #1a73e8;
            color: #1a73e8;
        }

        .btn-outline:hover {
            background: #1a73e8;
            color: white;
        }

        .delete {
            border-left: 6px solid #c62828;
        }

        .delete-btn {
            background: #c62828;
        }

        .delete-btn:hover {
            background: #b71c1c;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include "header.php"?>
    <div class="container">
        <div class="section-title">Profile Settings</div>

        <div class="profile-grid">
            <div class="card profile-card">
                <div class="profile-icon">
                    <i class="fa-regular fa-user"></i>
                </div>
                <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                <p>Personal Finance Tracker User</p>
            </div>

            <div>
                <div class="card">
                    <h3>Account Information</h3>
                    <div class="info-row">
                        <span>Username</span>
                        <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    </div>

                    <div class="info-row">
                        <span>Account Type</span>
                        <strong>User</strong>
                    </div>

                    <a href="change_username.php" class="btn btn-outline"> Change Username</a>
                </div>

                <div class="card">
                    <h3>Security</h3>
                    <p style="color:#666; font-size:14px;">
                        Keep your account secure.
                    </p>
                    <a href="change_password.php" class="btn"> Change Password </a>
                </div>

                <div class="card delete">
                    <h3 style="color:#c62828;">Account Deletion</h3>
                    <p style="color:#666; font-size:14px;"> Deleting your account will permanently remove all your data.
                    </p>

                    <a href="delete_account.php" class="btn delete-btn">Delete Account </a>
                </div>
            </div>
        </div>
    </div>
    <?php include "footer.php"?>
</body>
</html>

