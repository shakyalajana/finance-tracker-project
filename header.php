<style>
    * {
    margin-bottom: 0;
    font-family: Arial, sans-serif;
}

.top-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    background: #1a73e8;
    color: white;
}

.top-header .logo {
    font-size: 20px;
    font-weight: bold;
}

.top-header a {
    color: #d5d5d5ff;
    text-decoration: none;
    font-weight: 500;
}

.top-header a:hover {
    text-decoration: underline;
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<header class="top-header">
    <div class="logo">
        <i class="fa-solid fa-briefcase" style="color: #f2f3f5ff;"></i> Personal Finance Tracker
    </div>

    <div class="user-actions">
        <i class="fa-regular fa-user"></i>
        <?php if (isset($_SESSION['username'])) { ?>
            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span> |
            <a href="logout.php">Logout</a>
        <?php } ?>
    </div>
</header>
