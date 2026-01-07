<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', Arial, sans-serif;
    }

    .top-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    background: #1a73e8;
    color: white;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    }

    .top-header .logo {
    font-size: 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
    }

    .top-header .logo i {
    font-size: 24px;
    color: #f2f3f5;
    }

    .top-header .user-actions {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 16px;
    color: #f2f3f5;
    }

    .top-header .user-actions i {
    font-size: 20px;
    color: #f2f3f5;
    }

    .top-header .user-actions span {
    font-weight: 500;
    }

.top-header .user-actions a {
    color: #d5d5d5;
    text-decoration: none;
    font-weight: 500;
    transition: 0.2s;
    }

.top-header .user-actions a:hover {
    text-decoration: underline;
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<header class="top-header">
    <div class="logo">
        <i class="fa-solid fa-briefcase" style="color: #f2f3f5ff;"></i> Personal Finance Tracker
    </div>

    <div class="user-actions">
        <?php if(isset($_SESSION['name'])): ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="profile.php"><i class="fa-solid fa-user"></i></a>
                <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i></a>
            <?php endif; ?></a>
    </div>
</header>
