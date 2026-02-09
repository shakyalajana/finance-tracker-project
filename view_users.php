<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$statusFilter = '';
$currentFilter = 'all';

if (isset($_GET['status'])) {
    $currentFilter = $_GET['status'];
    
    if ($_GET['status'] === 'active') {
        $statusFilter = "
            AND u.last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
    }

    if ($_GET['status'] === 'passive') {
        $statusFilter = "
            AND (u.last_login IS NULL OR u.last_login < DATE_SUB(NOW(), INTERVAL 30 DAY))
        ";
    }
}

$query = "
SELECT u.user_id, u.name, u.email, u.created_at, u.last_login, 
COUNT(DISTINCT i.id) AS income_count, COUNT(DISTINCT e.id) AS expense_count
FROM users u
LEFT JOIN income i ON u.user_id = i.user_id
LEFT JOIN expenses e ON u.user_id = e.user_id
WHERE u.role = 'user' $statusFilter
GROUP BY u.user_id
ORDER BY u.last_login DESC
";
$users = mysqli_query($conn, $query);

// Count totals
$totalUsers = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
$activeUsers = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'user' AND last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['total'];
$passiveUsers = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'user' AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 30 DAY))")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="CSS/view_users.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-users"></i> User Management</h1>
            <a href="admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: #667eea;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Users</h3>
                    <p><?= $totalUsers ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #2e7d32;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Users</h3>
                    <p><?= $activeUsers ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #f57f17;">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Passive Users</h3>
                    <p><?= $passiveUsers ?></p>
                </div>
            </div>
        </div>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="view_users.php" class="filter-btn all <?= $currentFilter === 'all' ? 'active-tab' : '' ?>">
                <i class="fas fa-list"></i> All Users
            </a>
            <a href="view_users.php?status=active" class="filter-btn active-filter <?= $currentFilter === 'active' ? 'active-tab' : '' ?>">
                <i class="fas fa-check-circle"></i> Active Only
            </a>
            <a href="view_users.php?status=passive" class="filter-btn passive-filter <?= $currentFilter === 'passive' ? 'active-tab' : '' ?>">
                <i class="fas fa-clock"></i> Passive Only
            </a>
        </div>
        
        <!-- Table -->
        <div class="table-container">
            <?php if (mysqli_num_rows($users) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Name</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-calendar-plus"></i> Joined On</th>
                        <th><i class="fas fa-clock"></i> Last Login</th>
                        <th><i class="fas fa-arrow-down"></i> Income</th>
                        <th><i class="fas fa-arrow-up"></i> Expense</th>
                        <th><i class="fas fa-signal"></i> Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($users)): 
                        $lastLogin = $row['last_login'];
                        $status = ($lastLogin && strtotime($lastLogin) >= strtotime('-30 days')) ? 'Active' : 'Passive';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= date("d M Y", strtotime($row['created_at'])) ?></td>
                        <td>
                            <?php if ($row['last_login']): ?>
                                <?= date("d M Y, h:i A", strtotime($row['last_login'])) ?>
                            <?php else: ?>
                                <span class="never-login">Never</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="count-badge"><?= $row['income_count'] ?></span></td>
                        <td><span class="count-badge"><?= $row['expense_count'] ?></span></td>
                        <td>
                            <span class="status-badge <?= strtolower($status) ?>">
                                <?= $status ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-user-slash"></i>
                <h3>No Users Found</h3>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>