<?php
// admin/config/Database.php
class Database {
    private $conn;
    private static $instance = null;
    
    private function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        
        $this->conn->set_charset("utf8mb4");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function executeQuery($sql, $params = [], $types = '') {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->logError("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            $this->logError("Execute failed: " . $stmt->error);
            return false;
        }
        
        return $stmt->get_result();
    }
    
    public function executeNonQuery($sql, $params = [], $types = '') {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        return $stmt->execute();
    }
    
    private function logError($message) {
        error_log("[Admin Dashboard] " . $message);
    }
    
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
<?php
// admin/config/AdminSecurity.php
class AdminSecurity {
    public static function checkAdminAccess() {
        session_start();
        
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            self::redirectWithError('login.php', 'Access denied. Admin privileges required.');
        }
        
        // Check session timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            self::redirectWithError('login.php', 'Session expired. Please login again.');
        }
        
        $_SESSION['last_activity'] = time();
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return true;
    }
    
    public static function validateCSRF($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }
    
    public static function generateCSRFField() {
        return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
    }
    
    public static function logAdminAction($action, $details = '') {
        $db = Database::getInstance();
        $admin_id = $_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $sql = "INSERT INTO admin_logs (admin_id, action, ip_address, details) VALUES (?, ?, ?, ?)";
        $db->executeNonQuery($sql, [$admin_id, $action, $ip, $details], 'isss');
    }
    
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(stripslashes(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateInteger($value, $min = null, $max = null) {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            return false;
        }
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        return true;
    }
    
    private static function redirectWithError($url, $message) {
        $_SESSION['error'] = $message;
        header("Location: $url");
        exit();
    }
}
?>
<?php
// admin/models/AdminModel.php

class AdminModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // === USER MANAGEMENT ===
    public function getTotalUsers() {
        $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
        $result = $this->db->executeQuery($sql);
        return $result ? $result->fetch_assoc()['total'] : 0;
    }
    
    public function getActiveUsers($month = null, $year = null) {
        $month = $month ?? date('m');
        $year = $year ?? date('Y');
        
        $sql = "
            SELECT COUNT(DISTINCT user_id) as total
            FROM (
                SELECT user_id FROM income 
                WHERE MONTH(date) = ? AND YEAR(date) = ?
                UNION
                SELECT user_id FROM expenses 
                WHERE MONTH(date) = ? AND YEAR(date) = ?
            ) t
        ";
        
        $result = $this->db->executeQuery($sql, [$month, $year, $month, $year], 'iiii');
        return $result ? $result->fetch_assoc()['total'] : 0;
    }
    
    public function getUserActivityStats() {
        $sql = "
            SELECT 
                COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_last_30,
                COUNT(CASE WHEN last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as inactive_last_30,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_last_7
            FROM users 
            WHERE role = 'user'
        ";
        
        $result = $this->db->executeQuery($sql);
        return $result ? $result->fetch_assoc() : ['active_last_30' => 0, 'inactive_last_30' => 0, 'new_last_7' => 0];
    }
    
    public function getTopActiveUsers($limit = 5) {
        $sql = "
            SELECT 
                u.user_id, 
                u.name, 
                u.email,
                COUNT(DISTINCT i.income_id) as income_count,
                COUNT(DISTINCT e.expense_id) as expense_count,
                (COUNT(DISTINCT i.income_id) + COUNT(DISTINCT e.expense_id)) as total_transactions,
                u.last_login
            FROM users u
            LEFT JOIN income i ON u.user_id = i.user_id 
                AND MONTH(i.date) = MONTH(CURDATE()) 
                AND YEAR(i.date) = YEAR(CURDATE())
            LEFT JOIN expenses e ON u.user_id = e.user_id 
                AND MONTH(e.date) = MONTH(CURDATE()) 
                AND YEAR(e.date) = YEAR(CURDATE())
            WHERE u.role = 'user'
            GROUP BY u.user_id
            ORDER BY total_transactions DESC, u.last_login DESC
            LIMIT ?
        ";
        
        $result = $this->db->executeQuery($sql, [$limit], 'i');
        
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        return $users;
    }
    
    // === TRANSACTION STATISTICS ===
    public function getTransactionStats() {
        $sql = "
            SELECT 
                (SELECT COUNT(*) FROM income) as total_income,
                (SELECT COUNT(*) FROM expenses) as total_expense,
                (SELECT SUM(amount) FROM income) as total_income_amount,
                (SELECT SUM(amount) FROM expenses) as total_expense_amount,
                (SELECT COUNT(DISTINCT user_id) FROM income) as users_with_income,
                (SELECT COUNT(DISTINCT user_id) FROM expenses) as users_with_expense
        ";
        
        $result = $this->db->executeQuery($sql);
        return $result ? $result->fetch_assoc() : [];
    }
    
    public function getMonthlyTransactionTrends($year = null) {
        $year = $year ?? date('Y');
        
        $sql = "
            SELECT 
                m.month,
                COALESCE(i.income_count, 0) as income_count,
                COALESCE(i.income_amount, 0) as income_amount,
                COALESCE(e.expense_count, 0) as expense_count,
                COALESCE(e.expense_amount, 0) as expense_amount
            FROM (
                SELECT 1 as month UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 
                UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
            ) m
            LEFT JOIN (
                SELECT 
                    MONTH(date) as month,
                    COUNT(*) as income_count,
                    SUM(amount) as income_amount
                FROM income 
                WHERE YEAR(date) = ?
                GROUP BY MONTH(date)
            ) i ON m.month = i.month
            LEFT JOIN (
                SELECT 
                    MONTH(date) as month,
                    COUNT(*) as expense_count,
                    SUM(amount) as expense_amount
                FROM expenses 
                WHERE YEAR(date) = ?
                GROUP BY MONTH(date)
            ) e ON m.month = e.month
            ORDER BY m.month
        ";
        
        $result = $this->db->executeQuery($sql, [$year, $year], 'ii');
        
        $trends = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $trends[] = $row;
            }
        }
        return $trends;
    }
    
    public function getCategoryBreakdown($limit = 10) {
        $sql = "
            SELECT 
                category,
                COUNT(*) as transaction_count,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount
            FROM expenses
            GROUP BY category
            ORDER BY total_amount DESC
            LIMIT ?
        ";
        
        $result = $this->db->executeQuery($sql, [$limit], 'i');
        
        $categories = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }
    
    // === SYSTEM HEALTH ===
    public function calculateSystemHealth() {
        $totalUsers = $this->getTotalUsers();
        $activeUsers = $this->getActiveUsers();
        $transactionStats = $this->getTransactionStats();
        
        // Engagement Score (40%)
        $engagementRate = $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0;
        $engagementScore = min(40, ($engagementRate / 100) * 40);
        
        // Activity Score (35%)
        $totalTransactions = ($transactionStats['total_income'] ?? 0) + ($transactionStats['total_expense'] ?? 0);
        $activityScore = min(35, ($totalTransactions / 100) * 35); // Scale based on transactions
        
        // Growth Score (25%)
        $newUsers = $this->getNewUsersLastMonth();
        $growthRate = $totalUsers > 0 ? ($newUsers / $totalUsers) * 100 : 0;
        $growthScore = min(25, ($growthRate / 100) * 25);
        
        $totalScore = round($engagementScore + $activityScore + $growthScore);
        
        return [
            'score' => $totalScore,
            'breakdown' => [
                'engagement' => round($engagementScore),
                'activity' => round($activityScore),
                'growth' => round($growthScore)
            ],
            'details' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'engagement_rate' => round($engagementRate, 1),
                'total_transactions' => $totalTransactions,
                'new_users' => $newUsers,
                'growth_rate' => round($growthRate, 1)
            ]
        ];
    }
    
    private function getNewUsersLastMonth() {
        $sql = "
            SELECT COUNT(*) as count 
            FROM users 
            WHERE role = 'user' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
        
        $result = $this->db->executeQuery($sql);
        return $result ? $result->fetch_assoc()['count'] : 0;
    }
    
    // === ALERTS & NOTIFICATIONS ===
    public function getSystemAlerts() {
        $alerts = [];
        
        $totalUsers = $this->getTotalUsers();
        $activityStats = $this->getUserActivityStats();
        
        // High inactive users alert
        $inactiveRate = $totalUsers > 0 ? ($activityStats['inactive_last_30'] / $totalUsers) * 100 : 0;
        if ($inactiveRate > 40) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Inactivity Rate',
                'message' => round($inactiveRate) . '% of users are inactive',
                'icon' => 'fa-user-slash'
            ];
        }
        
        // No transactions alert
        $transactionStats = $this->getTransactionStats();
        if (($transactionStats['total_income'] + $transactionStats['total_expense']) == 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'No Transactions',
                'message' => 'No transactions recorded yet',
                'icon' => 'fa-exclamation-triangle'
            ];
        }
        
        // Low new users alert
        if ($activityStats['new_last_7'] == 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'No New Users',
                'message' => 'No new users in the last 7 days',
                'icon' => 'fa-user-plus'
            ];
        }
        
        return $alerts;
    }
    
    // === USER MANAGEMENT METHODS ===
    public function getAllUsers($page = 1, $limit = 20, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM users WHERE role = 'user' ";
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $sql .= "AND (name LIKE ? OR email LIKE ?) ";
            $searchTerm = "%$search%";
            $params = [$searchTerm, $searchTerm];
            $types = 'ss';
        }
        
        $sql .= "ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params = array_merge($params, [$limit, $offset]);
        $types .= 'ii';
        
        $result = $this->db->executeQuery($sql, $params, $types);
        
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM users WHERE role = 'user' ";
        if (!empty($search)) {
            $countSql .= "AND (name LIKE ? OR email LIKE ?)";
            $countResult = $this->db->executeQuery($countSql, ["%$search%", "%$search%"], 'ss');
        } else {
            $countResult = $this->db->executeQuery($countSql);
        }
        
        $total = $countResult ? $countResult->fetch_assoc()['total'] : 0;
        $totalPages = ceil($total / $limit);
        
        return [
            'users' => $users,
            'total' => $total,
            'total_pages' => $totalPages,
            'current_page' => $page
        ];
    }
    
    public function updateUserStatus($user_id, $status) {
        $sql = "UPDATE users SET status = ? WHERE user_id = ? AND role = 'user'";
        return $this->db->executeNonQuery($sql, [$status, $user_id], 'si');
    }
    
    public function deleteUser($user_id) {
        // Begin transaction
        $this->db->beginTransaction();
        
        try {
            // Delete user transactions first
            $this->db->executeNonQuery("DELETE FROM income WHERE user_id = ?", [$user_id], 'i');
            $this->db->executeNonQuery("DELETE FROM expenses WHERE user_id = ?", [$user_id], 'i');
            $this->db->executeNonQuery("DELETE FROM expense_limits WHERE user_id = ?", [$user_id], 'i');
            
            // Then delete the user
            $result = $this->db->executeNonQuery("DELETE FROM users WHERE user_id = ? AND role = 'user'", [$user_id], 'i');
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Delete user failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
<?php
// Check admin access
AdminSecurity::checkAdminAccess();

// Log dashboard access
AdminSecurity::logAdminAction('access_dashboard', 'Accessed admin dashboard');

// Initialize models
$adminModel = new AdminModel();

// Get all dashboard data
try {
    $totalUsers = $adminModel->getTotalUsers();
    $userActivity = $adminModel->getUserActivityStats();
    $topUsers = $adminModel->getTopActiveUsers(5);
    $transactionStats = $adminModel->getTransactionStats();
    $monthlyTrends = $adminModel->getMonthlyTransactionTrends(date('Y'));
    $categoryBreakdown = $adminModel->getCategoryBreakdown(8);
    $systemHealth = $adminModel->calculateSystemHealth();
    $systemAlerts = $adminModel->getSystemAlerts();
    
    // Calculate percentages
    $activePercentage = $totalUsers > 0 ? round(($userActivity['active_last_30'] / $totalUsers) * 100, 1) : 0;
    $inactivePercentage = $totalUsers > 0 ? round(($userActivity['inactive_last_30'] / $totalUsers) * 100, 1) : 0;
    $newUserPercentage = $totalUsers > 0 ? round(($userActivity['new_last_7'] / $totalUsers) * 100, 1) : 0;
    
    // Prepare data for charts
    $monthlyIncomeCounts = array_column($monthlyTrends, 'income_count');
    $monthlyExpenseCounts = array_column($monthlyTrends, 'expense_count');
    $monthlyIncomeAmounts = array_column($monthlyTrends, 'income_amount');
    $monthlyExpenseAmounts = array_column($monthlyTrends, 'expense_amount');
    
    $categoryLabels = array_column($categoryBreakdown, 'category');
    $categoryAmounts = array_column($categoryBreakdown, 'total_amount');
    
} catch (Exception $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
    error_log($error);
    
    // Set default values on error
    $totalUsers = $userActivity = $topUsers = $transactionStats = [];
    $monthlyTrends = $categoryBreakdown = $systemAlerts = [];
    $systemHealth = ['score' => 0, 'breakdown' => [], 'details' => []];
    $activePercentage = $inactivePercentage = $newUserPercentage = 0;
    $monthlyIncomeCounts = $monthlyExpenseCounts = $monthlyIncomeAmounts = $monthlyExpenseAmounts = [];
    $categoryLabels = $categoryAmounts = [];
}

// Include view

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Personal Finance Tracker</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin/assets/css/admin.css">
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin/assets/js/admin.js" defer></script>
    
    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <style>
        /* admin/assets/css/admin.css */
:root {
    --primary-color: #1a237e;
    --secondary-color: #3949ab;
    --success-color: #2e7d32;
    --danger-color: #c62828;
    --warning-color: #f9a825;
    --info-color: #0277bd;
    --light-color: #f5f7fa;
    --dark-color: #263238;
    --sidebar-width: 250px;
    --navbar-height: 60px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.admin-body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
    color: #333;
    min-height: 100vh;
}

/* Navbar */
.admin-navbar {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    height: var(--navbar-height);
    padding: 0 1.5rem;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.admin-navbar .navbar-brand {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    text-decoration: none;
}

.admin-navbar .navbar-brand i {
    margin-right: 10px;
}

.navbar-right {
    display: flex;
    align-items: center;
}

.btn-user {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-user:hover {
    background: rgba(255,255,255,0.2);
}

/* Container */
.admin-container {
    display: flex;
    min-height: calc(100vh - var(--navbar-height));
    margin-top: var(--navbar-height);
}

/* Sidebar */
.admin-sidebar {
    width: var(--sidebar-width);
    background: white;
    border-right: 1px solid #e0e0e0;
    padding: 1rem 0;
    display: flex;
    flex-direction: column;
    position: fixed;
    height: calc(100vh - var(--navbar-height));
    overflow-y: auto;
}

.sidebar-header {
    padding: 0 1.5rem 1.5rem;
    border-bottom: 1px solid #e0e0e0;
}

.sidebar-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary-color);
    margin: 0;
}

.sidebar-header h3 i {
    margin-right: 10px;
}

.sidebar-menu {
    flex: 1;
    padding: 1.5rem 0;
}

.menu-section {
    padding: 0 1.5rem;
    margin-bottom: 1.5rem;
}

.menu-section h6 {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #6c757d;
    font-weight: 600;
    margin-bottom: 0.75rem;
    letter-spacing: 0.5px;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: #495057;
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 0.25rem;
    transition: all 0.3s;
    font-weight: 500;
}

.menu-item i {
    width: 20px;
    margin-right: 10px;
    font-size: 0.875rem;
}

.menu-item:hover {
    background-color: #e9ecef;
    color: var(--primary-color);
    transform: translateX(5px);
}

.menu-item.active {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    box-shadow: 0 4px 12px rgba(26, 35, 126, 0.2);
}

.sidebar-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e0e0e0;
}

/* Main Content */
.admin-main {
    flex: 1;
    margin-left: var(--sidebar-width);
    padding: 2rem;
    background-color: #f8f9fa;
    min-height: calc(100vh - var(--navbar-height));
}

/* Welcome Card */
.welcome-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}

.welcome-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.welcome-subtitle {
    opacity: 0.9;
    font-size: 1rem;
}

.current-time {
    background: rgba(255,255,255,0.1);
    padding: 0.75rem 1rem;
    border-radius: 8px;
    display: inline-block;
}

.current-time i {
    margin-right: 8px;
}

/* Health Score Card */
.health-card {
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-radius: 12px;
    overflow: hidden;
}

.health-card .card-header {
    background: white;
    border-bottom: 1px solid #e0e0e0;
    padding: 1.25rem 1.5rem;
}

.health-score-circle {
    width: 120px;
    height: 120px;
    margin: 0 auto;
    position: relative;
    border-radius: 50%;
    background: conic-gradient(var(--circle-color, #2e7d32) 0% calc(var(--health-score, 0) * 1%), #e0e0e0 calc(var(--health-score, 0) * 1%) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

.health-score-circle::before {
    content: '';
    position: absolute;
    width: 90px;
    height: 90px;
    background: white;
    border-radius: 50%;
}

.score-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--dark-color);
    position: relative;
    z-index: 1;
}

.score-label {
    font-size: 0.875rem;
    color: #6c757d;
    position: relative;
    z-index: 1;
}

.health-metric {
    padding: 0.5rem 0;
}

.metric-label {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #495057;
}

/* Stat Cards */
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-users .stat-icon { background: linear-gradient(135deg, #1a237e, #3949ab); }
.stat-active .stat-icon { background: linear-gradient(135deg, #2e7d32, #4caf50); }
.stat-income .stat-icon { background: linear-gradient(135deg, #0277bd, #03a9f4); }
.stat-expense .stat-icon { background: linear-gradient(135deg, #c62828, #f44336); }

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.stat-trend {
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Chart Cards */
.chart-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    height: 100%;
}

.chart-card .card-header {
    background: white;
    border-bottom: 1px solid #e0e0e0;
    padding: 1.25rem 1.5rem;
}

.chart-card .card-body {
    padding: 1.5rem;
}

/* Tables */
.table {
    margin-bottom: 0;
}

.table thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
    padding: 1rem;
}

.table tbody tr {
    transition: background-color 0.3s;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
}

.avatar-placeholder {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.category-badge {
    background: #e3f2fd;
    color: #1565c0;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 500;
}

/* Quick Actions */
.quick-action-card {
    display: block;
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    height: 100%;
}

.quick-action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    color: var(--primary-color);
}

.action-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}

.action-text {
    font-weight: 600;
    font-size: 0.875rem;
}

/* Alert Card */
.alert-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.alert-card .alert {
    border: none;
    border-radius: 8px;
    margin-bottom: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s;
        z-index: 1040;
    }
    
    .admin-sidebar.show {
        transform: translateX(0);
    }
    
    .admin-main {
        margin-left: 0;
        padding: 1rem;
    }
    
    .welcome-card {
        padding: 1.5rem;
    }
    
    .welcome-title {
        font-size: 1.5rem;
    }
}

/* Animation for health score */
@keyframes draw-circle {
    from {
        stroke-dashoffset: var(--circumference);
    }
    to {
        stroke-dashoffset: var(--offset);
    }
}

.health-score-circle svg circle {
    animation: draw-circle 1.5s ease-out forwards;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
        </style>
</head>
<body class="admin-body">
    <!-- Navigation -->
    <nav class="admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-chart-line"></i> Finance Tracker Admin
            </a>
            
            <div class="navbar-right">
                <div class="dropdown">
                    <button class="btn btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                        <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Settings</a></li>
                        <li><a class="dropdown-item" href="admin_profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-main">
            <div class="container-fluid py-4">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li class="breadcrumb-item active">Overview</li>
                    </ol>
                </nav>
                
                <!-- Error Alert (if any) -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <!-- Success Message (if any) -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <aside class="admin-sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-tachometer-alt"></i> Admin Panel</h3>
    </div>
    
    <div class="sidebar-menu">
        <div class="menu-section">
            <h6>DASHBOARD</h6>
            <a href="admin_dashboard.php" class="menu-item active">
                <i class="fas fa-home"></i> Overview
            </a>
        </div>
        
        <div class="menu-section">
            <h6>USER MANAGEMENT</h6>
            <a href="admin_users.php" class="menu-item">
                <i class="fas fa-users"></i> All Users
            </a>
            <a href="admin_users.php?filter=active" class="menu-item">
                <i class="fas fa-user-check"></i> Active Users
            </a>
            <a href="admin_users.php?filter=inactive" class="menu-item">
                <i class="fas fa-user-slash"></i> Inactive Users
            </a>
            <a href="admin_add_user.php" class="menu-item">
                <i class="fas fa-user-plus"></i> Add User
            </a>
        </div>
        
        <div class="menu-section">
            <h6>TRANSACTIONS</h6>
            <a href="admin_transactions.php" class="menu-item">
                <i class="fas fa-exchange-alt"></i> All Transactions
            </a>
            <a href="admin_transactions.php?type=income" class="menu-item">
                <i class="fas fa-arrow-down"></i> Income
            </a>
            <a href="admin_transactions.php?type=expense" class="menu-item">
                <i class="fas fa-arrow-up"></i> Expenses
            </a>
        </div>
        
        <div class="menu-section">
            <h6>ANALYTICS & REPORTS</h6>
            <a href="admin_reports.php" class="menu-item">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="admin_analytics.php" class="menu-item">
                <i class="fas fa-chart-line"></i> Analytics
            </a>
            <a href="admin_export.php" class="menu-item">
                <i class="fas fa-file-export"></i> Export Data
            </a>
        </div>
        
        <div class="menu-section">
            <h6>SYSTEM</h6>
            <a href="admin_settings.php" class="menu-item">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="admin_logs.php" class="menu-item">
                <i class="fas fa-clipboard-list"></i> Activity Logs
            </a>
            <a href="admin_backup.php" class="menu-item">
                <i class="fas fa-database"></i> Backup
            </a>
        </div>
        
        <div class="menu-section mt-auto">
            <a href="../index.php" class="menu-item">
                <i class="fas fa-globe"></i> View Site
            </a>
            <a href="admin_logout.php" class="menu-item text-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <div class="sidebar-footer">
        <small class="text-muted">v1.0.0</small>
    </div>
</aside>
<div class="row mb-4">
    <div class="col-12">
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="welcome-title">Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>!</h1>
                    <p class="welcome-subtitle">Here's what's happening with your finance tracker today.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="current-time">
                        <i class="fas fa-clock"></i>
                        <span id="live-clock"><?= date('h:i A, F j, Y') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Health Score -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card health-card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-heartbeat"></i> System Health Score</h4>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="health-score-circle" data-score="<?= $systemHealth['score'] ?>">
                            <div class="score-value"><?= $systemHealth['score'] ?></div>
                            <div class="score-label">/100</div>
                        </div>
                        <div class="health-status mt-2">
                            <?php if ($systemHealth['score'] >= 80): ?>
                                <span class="badge bg-success">Excellent</span>
                            <?php elseif ($systemHealth['score'] >= 60): ?>
                                <span class="badge bg-warning">Good</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Needs Attention</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="health-metric">
                                    <div class="metric-label">User Engagement</div>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?= $systemHealth['breakdown']['engagement'] ?? 0 ?>%">
                                            <?= $systemHealth['breakdown']['engagement'] ?? 0 ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?= $systemHealth['details']['active_users'] ?? 0 ?> active of <?= $systemHealth['details']['total_users'] ?? 0 ?> total users
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="health-metric">
                                    <div class="metric-label">Transaction Activity</div>
                                    <div class="progress">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?= $systemHealth['breakdown']['activity'] ?? 0 ?>%">
                                            <?= $systemHealth['breakdown']['activity'] ?? 0 ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?= $systemHealth['details']['total_transactions'] ?? 0 ?> total transactions
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="health-metric">
                                    <div class="metric-label">User Growth</div>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?= $systemHealth['breakdown']['growth'] ?? 0 ?>%">
                                            <?= $systemHealth['breakdown']['growth'] ?? 0 ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?= $systemHealth['details']['new_users'] ?? 0 ?> new users last month
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Alerts -->
<?php if (!empty($systemAlerts)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card alert-card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-bell"></i> System Alerts</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($systemAlerts as $alert): ?>
                    <div class="col-md-4 mb-3">
                        <div class="alert alert-<?= $alert['type'] ?> d-flex align-items-center">
                            <i class="fas <?= $alert['icon'] ?> me-3"></i>
                            <div>
                                <strong><?= $alert['title'] ?></strong>
                                <div class="small"><?= $alert['message'] ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Key Statistics -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card stat-users">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $totalUsers ?></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up text-success"></i>
                    <span class="text-success">+<?= $userActivity['new_last_7'] ?> this week</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card stat-active">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $userActivity['active_last_30'] ?></div>
                <div class="stat-label">Active Users (30 days)</div>
                <div class="stat-trend">
                    <span class="<?= $activePercentage >= 50 ? 'text-success' : 'text-warning' ?>">
                        <?= $activePercentage ?>% active rate
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card stat-income">
            <div class="stat-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $transactionStats['total_income'] ?? 0 ?></div>
                <div class="stat-label">Income Records</div>
                <div class="stat-trend">
                    <span>Rs. <?= number_format($transactionStats['total_income_amount'] ?? 0, 2) ?> total</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card stat-expense">
            <div class="stat-icon">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $transactionStats['total_expense'] ?? 0 ?></div>
                <div class="stat-label">Expense Records</div>
                <div class="stat-trend">
                    <span>Rs. <?= number_format($transactionStats['total_expense_amount'] ?? 0, 2) ?> total</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-lg-8 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-chart-line"></i> Monthly Transaction Trends</h4>
            </div>
            <div class="card-body">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-chart-pie"></i> User Activity Distribution</h4>
            </div>
            <div class="card-body">
                <canvas id="userActivityChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Users & Categories -->
<div class="row mb-4">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-trophy"></i> Top Active Users (This Month)</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Transactions</th>
                                <th>Last Active</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($topUsers)): ?>
                                <?php foreach ($topUsers as $index => $user): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-placeholder me-2">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $user['total_transactions'] ?></span>
                                        <small class="text-muted d-block">
                                            <?= $user['income_count'] ?> income, <?= $user['expense_count'] ?> expenses
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($user['last_login']): ?>
                                            <?= date('M j, Y', strtotime($user['last_login'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="admin_user_details.php?id=<?= $user['user_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-users-slash fa-2x mb-2"></i><br>
                                        No active users found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-tags"></i> Top Expense Categories</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Transactions</th>
                                <th>Total Amount</th>
                                <th>Avg. Transaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categoryBreakdown)): ?>
                                <?php foreach ($categoryBreakdown as $category): ?>
                                <tr>
                                    <td>
                                        <span class="category-badge"><?= htmlspecialchars($category['category']) ?></span>
                                    </td>
                                    <td><?= $category['transaction_count'] ?></td>
                                    <td class="fw-bold text-danger">Rs. <?= number_format($category['total_amount'], 2) ?></td>
                                    <td class="text-muted">Rs. <?= number_format($category['average_amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-chart-pie fa-2x mb-2"></i><br>
                                        No expense data available
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h4>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <a href="view_users.php" class="quick-action-card">
                            <div class="action-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="action-text">Manage Users</div>
                        </a>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="manage_category.php" class="quick-action-card">
                            <div class="action-icon">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <div class="action-text">View Transactions</div>
                        </a>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="admin_reports.php" class="quick-action-card">
                            <div class="action-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="action-text">Generate Report</div>
                        </a>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="admin_settings.php" class="quick-action-card">
                            <div class="action-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="action-text">System Settings</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Trend Chart
    const trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Income Count',
                data: <?= json_encode($monthlyIncomeCounts) ?>,
                borderColor: '#2e7d32',
                backgroundColor: 'rgba(46, 125, 50, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Expense Count',
                data: <?= json_encode($monthlyExpenseCounts) ?>,
                borderColor: '#c62828',
                backgroundColor: 'rgba(198, 40, 40, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Transactions'
                    }
                }
            }
        }
    });
    
    // User Activity Chart
    const activityCtx = document.getElementById('userActivityChart').getContext('2d');
    new Chart(activityCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active (30 days)', 'Inactive (30+ days)', 'New (7 days)'],
            datasets: [{
                data: [
                    <?= $userActivity['active_last_30'] ?>,
                    <?= $userActivity['inactive_last_30'] ?>,
                    <?= $userActivity['new_last_7'] ?>
                ],
                backgroundColor: ['#2e7d32', '#c62828', '#1a73e8'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw;
                            let total = <?= $totalUsers ?>;
                            let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Health Score Circle Animation
    const healthScore = <?= $systemHealth['score'] ?>;
    const circle = document.querySelector('.health-score-circle');
    if (circle) {
        const circumference = 2 * Math.PI * 45;
        const offset = circumference - (healthScore / 100) * circumference;
        
        circle.style.setProperty('--circumference', circumference);
        circle.style.setProperty('--offset', offset);
        
        // Set color based on score
        if (healthScore >= 80) {
            circle.style.setProperty('--circle-color', '#2e7d32');
        } else if (healthScore >= 60) {
            circle.style.setProperty('--circle-color', '#f9a825');
        } else {
            circle.style.setProperty('--circle-color', '#c62828');
        }
    }
    
    // Live Clock
    function updateClock() {
        const now = new Date();
        const options = { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            hour12: true 
        };
        const dateStr = now.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        const timeStr = now.toLocaleTimeString('en-US', options);
        document.getElementById('live-clock').textContent = `${timeStr}, ${dateStr}`;
    }
    
    updateClock();
    setInterval(updateClock, 1000);
});
// admin/assets/js/admin.js
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Health score circle animation
    function animateHealthScore() {
        const circles = document.querySelectorAll('.health-score-circle');
        circles.forEach(circle => {
            const score = parseInt(circle.dataset.score);
            const circumference = 2 * Math.PI * 45;
            const offset = circumference - (score / 100) * circumference;
            
            // Create SVG circle if not exists
            if (!circle.querySelector('svg')) {
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('width', '120');
                svg.setAttribute('height', '120');
                svg.setAttribute('viewBox', '0 0 120 120');
                
                const backgroundCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                backgroundCircle.setAttribute('cx', '60');
                backgroundCircle.setAttribute('cy', '60');
                backgroundCircle.setAttribute('r', '45');
                backgroundCircle.setAttribute('fill', 'none');
                backgroundCircle.setAttribute('stroke', '#e0e0e0');
                backgroundCircle.setAttribute('stroke-width', '10');
                
                const scoreCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                scoreCircle.setAttribute('cx', '60');
                scoreCircle.setAttribute('cy', '60');
                scoreCircle.setAttribute('r', '45');
                scoreCircle.setAttribute('fill', 'none');
                scoreCircle.setAttribute('stroke', getScoreColor(score));
                scoreCircle.setAttribute('stroke-width', '10');
                scoreCircle.setAttribute('stroke-linecap', 'round');
                scoreCircle.setAttribute('stroke-dasharray', circumference);
                scoreCircle.setAttribute('stroke-dashoffset', circumference);
                
                svg.appendChild(backgroundCircle);
                svg.appendChild(scoreCircle);
                circle.appendChild(svg);
                
                // Animate the circle
                setTimeout(() => {
                    scoreCircle.style.transition = 'stroke-dashoffset 1.5s ease-out';
                    scoreCircle.style.strokeDashoffset = offset;
                }, 100);
            }
        });
    }
    
    function getScoreColor(score) {
        if (score >= 80) return '#2e7d32';
        if (score >= 60) return '#f9a825';
        return '#c62828';
    }
    
    // Auto-refresh data every 5 minutes
    function startAutoRefresh() {
        setInterval(() => {
            fetchDashboardData();
        }, 5 * 60 * 1000); // 5 minutes
    }
    
    // Fetch updated dashboard data via AJAX
    async function fetchDashboardData() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('admin_ajax.php?action=dashboard_stats', {
                headers: {
                    'X-CSRF-Token': csrfToken
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                updateDashboardStats(data);
            }
        } catch (error) {
            console.error('Failed to fetch dashboard data:', error);
        }
    }
    
    function updateDashboardStats(data) {
        // Update statistics cards
        document.querySelectorAll('.stat-value').forEach((el, index) => {
            // This is a simplified example - you'd map each element to specific data
            if (data && data.stats) {
                // Update based on your actual data structure
            }
        });
        
        // Show notification for new data
        showNotification('Dashboard data updated', 'info');
    }
    
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
        
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    // Export data function
    window.exportData = function(type, format) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch(`admin_export.php?type=${type}&format=${format}`, {
            headers: {
                'X-CSRF-Token': csrfToken
            }
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${type}_export.${format}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showNotification(`${type} exported successfully as ${format.toUpperCase()}`, 'success');
        })
        .catch(error => {
            console.error('Export failed:', error);
            showNotification('Export failed', 'danger');
        });
    };
    
    // Initialize
    animateHealthScore();
    startAutoRefresh();
    
    // Handle sidebar toggle on mobile
    const sidebarToggle = document.createElement('button');
    sidebarToggle.className = 'btn btn-primary d-md-none position-fixed';
    sidebarToggle.style.cssText = 'bottom: 20px; right: 20px; z-index: 1030; border-radius: 50%; width: 50px; height: 50px;';
    sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
    
    sidebarToggle.addEventListener('click', function() {
        document.querySelector('.admin-sidebar').classList.toggle('show');
    });
    
    document.body.appendChild(sidebarToggle);
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.admin-sidebar');
        const toggleBtn = sidebarToggle;
        
        if (window.innerWidth < 768 && 
            !sidebar.contains(event.target) && 
            !toggleBtn.contains(event.target) &&
            sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    });
});
<?php
// admin_ajax.php
AdminSecurity::checkAdminAccess();

// Set JSON header
header('Content-Type: application/json');

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!AdminSecurity::validateCSRF($token)) {
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

// Get action
$action = $_GET['action'] ?? '';

try {
    $adminModel = new AdminModel();
    
    switch ($action) {
        case 'dashboard_stats':
            $stats = [
                'total_users' => $adminModel->getTotalUsers(),
                'user_activity' => $adminModel->getUserActivityStats(),
                'transaction_stats' => $adminModel->getTransactionStats(),
                'health_score' => $adminModel->calculateSystemHealth(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'user_stats':
            $user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
            if (!$user_id) {
                throw new Exception('Invalid user ID');
            }
            
            // Get user statistics
            // ... implementation
            echo json_encode(['success' => true, 'data' => []]);
            break;
            
        case 'delete_user':
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            if (!$user_id) {
                throw new Exception('Invalid user ID');
            }
            
            $result = $adminModel->deleteUser($user_id);
            if ($result) {
                AdminSecurity::logAdminAction('delete_user', "Deleted user ID: $user_id");
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete user']);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("AJAX Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
</script>
        </body>
        </html>
        