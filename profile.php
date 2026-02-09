<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Fetch current user data
$stmt = mysqli_prepare($conn, "SELECT name, email FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Fetch current password hash
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Verify current password
    if (!password_verify($current_password, $row['password'])) {
        $error_msg = "Current password is incorrect.";
    } 
    // Check if new passwords match
    elseif ($new_password !== $confirm_password) {
        $error_msg = "New passwords do not match.";
    }
    // Check password length
    elseif (strlen($new_password) < 6) {
        $error_msg = "New password must be at least 6 characters long.";
    }
    else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Password changed successfully!";
        } else {
            $error_msg = "Error updating password. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Email Change
if (isset($_POST['change_email'])) {
    $new_email = trim($_POST['new_email']);
    $password = $_POST['password_for_email'];
    
    // Fetch current password hash
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Verify password
    if (!password_verify($password, $row['password'])) {
        $error_msg = "Password is incorrect.";
    }
    // Validate email format
    elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    }
    // Check if email already exists
    else {
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        mysqli_stmt_bind_param($stmt, "si", $new_email, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error_msg = "This email is already in use.";
        } else {
            // Update email
            $stmt = mysqli_prepare($conn, "UPDATE users SET email = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $new_email, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Email changed successfully!";
                $user['email'] = $new_email; // Update display
            } else {
                $error_msg = "Error updating email. Please try again.";
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Account Deletion
if (isset($_POST['delete_account'])) {
    $password = $_POST['password_for_delete'];
    $confirmation = $_POST['delete_confirmation'];
    
    // Fetch current password hash
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Verify password
    if (!password_verify($password, $row['password'])) {
        $error_msg = "Password is incorrect.";
    }
    // Check confirmation text
    elseif (strtoupper(trim($confirmation)) !== "DELETE") {
        $error_msg = "Please type DELETE to confirm account deletion.";
    }
    else {
        // Delete user's transactions first
        $stmt = mysqli_prepare($conn, "DELETE FROM income WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        $stmt = mysqli_prepare($conn, "DELETE FROM expenses WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Delete user account
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            // Destroy session
            session_destroy();
            header("Location: login.php?deleted=1");
            exit();
        } else {
            $error_msg = "Error deleting account. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<?php include "header.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - FinTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, rgba(102,126,234,0.05), rgba(118,75,162,0.05)), #f5f7fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 24px;
        }

        .page-header {
            background: white;
            padding: 24px 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header h2 {
            margin: 0;
            color: #1f2937;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h2 i {
            color: #667eea;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .profile-info {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
        }

        .profile-info h3 {
            margin: 0 0 16px 0;
            color: #1f2937;
            font-size: 18px;
            font-weight: 600;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item i {
            color: #667eea;
            width: 20px;
        }

        .info-item strong {
            color: #374151;
            min-width: 80px;
        }

        .info-item span {
            color: #6b7280;
        }

        .settings-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
        }

        .settings-section h3 {
            margin: 0 0 20px 0;
            color: #1f2937;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-section h3 i {
            color: #667eea;
        }

        .danger-zone h3 i {
            color: #dc2626;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .submit-btn {
            padding: 10px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Poppins', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .submit-btn:hover {
            background: #5568d3;
        }

        .danger-btn {
            background: #dc2626;
        }

        .danger-btn:hover {
            background: #b91c1c;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .danger-zone {
            border: 2px solid #fee2e2;
        }

        .warning-text {
            background: #fef3c7;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
            margin-bottom: 20px;
            font-size: 14px;
            color: #92400e;
        }

        .warning-text i {
            color: #f59e0b;
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 16px;
            }

            .page-header {
                padding: 20px;
            }

            .settings-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2><i class="fa-solid fa-user-gear"></i> Profile Settings</h2>
            <a href="user_dashboard.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>
                Dashboard
            </a>
        </div>

        <?php if ($success_msg): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- Profile Info -->
        <div class="profile-info">
            <h3>Account Information</h3>
            <div class="info-item">
                <i class="fa-solid fa-user"></i>
                <strong>Name:</strong>
                <span><?= htmlspecialchars($user['name']) ?></span>
            </div>
            <div class="info-item">
                <i class="fa-solid fa-envelope"></i>
                <strong>Email:</strong>
                <span><?= htmlspecialchars($user['email']) ?></span>
            </div>
        </div>

        <!-- Change Password -->
        <div class="settings-section">
            <h3><i class="fa-solid fa-key"></i> Change Password</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password (min. 6 characters)</label>
                    <input type="password" id="new_password" name="new_password" minlength="6" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                </div>
                <button type="submit" name="change_password" class="submit-btn">
                    <i class="fa-solid fa-check"></i> Update Password
                </button>
            </form>
        </div>

        <!-- Change Email -->
        <div class="settings-section">
            <h3><i class="fa-solid fa-at"></i> Change Email</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="new_email">New Email Address</label>
                    <input type="email" id="new_email" name="new_email" required>
                </div>
                <div class="form-group">
                    <label for="password_for_email">Confirm with Password</label>
                    <input type="password" id="password_for_email" name="password_for_email" required>
                </div>
                <button type="submit" name="change_email" class="submit-btn">
                    <i class="fa-solid fa-check"></i> Update Email
                </button>
            </form>
        </div>

        <!-- Delete Account -->
        <div class="settings-section danger-zone">
            <h3><i class="fa-solid fa-triangle-exclamation"></i> Danger Zone</h3>
            <div class="warning-text">
                <i class="fa-solid fa-exclamation-triangle"></i>
                <strong>Warning:</strong> Account deletion is permanent and cannot be undone. All your transactions and data will be permanently deleted.
            </div>
            <form method="POST" onsubmit="return confirm('Are you absolutely sure? This action cannot be undone!');">
                <div class="form-group">
                    <label for="delete_confirmation">Type DELETE to confirm</label>
                    <input type="text" id="delete_confirmation" name="delete_confirmation" placeholder="DELETE" required>
                </div>
                <div class="form-group">
                    <label for="password_for_delete">Enter Password</label>
                    <input type="password" id="password_for_delete" name="password_for_delete" required>
                </div>
                <button type="submit" name="delete_account" class="submit-btn danger-btn">
                    <i class="fa-solid fa-trash"></i> Delete Account Permanently
                </button>
            </form>
        </div>
    </div>
    <?php include "footer.php"; ?>
</body>
</html>