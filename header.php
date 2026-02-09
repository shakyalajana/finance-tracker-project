<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .site-header {
        background: linear-gradient(145deg, #ffffff, #f7f8fa); /* matches card vibe */
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .header-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 70px;
    }

    .site-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        font-size: 22px;
        font-weight: 700;
        color: #1a73e8;
    }

    .logo-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea, #4364f7);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
    }

    .main-nav {
        display: flex;
        gap: 4px;
    }

    .main-nav a {
        padding: 10px 20px;
        text-decoration: none;
        color: #5f6368;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .main-nav a:hover {
        background: #f1f3f4;
        color: #1a73e8;
    }

    .main-nav a.active {
        background: #d2e3fc;
        color: #174ea6;
        font-weight: 600;
    }

    .user-menu {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .user-badge {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 16px;
        border-radius: 24px;
        background: #eef3fd;
        border: 1px solid #d2e3fc;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 15px;
    }

    .user-name {
        font-weight: 500;
        color: #202124;
        font-size: 14px;
    }

    .logout-btn {
        padding: 10px 18px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .logout-btn:hover {
        background: #5568d3;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .mobile-menu-btn {
        display: none;
        background: none;
        border: none;
        font-size: 24px;
        color: #5f6368;
        cursor: pointer;
        padding: 8px;
    }

    @media (max-width: 768px) {
        .header-container {
            padding: 0 16px;
        }

        .main-nav {
            position: fixed;
            top: 70px;
            left: -100%;
            width: 280px;
            height: calc(100vh - 70px);
            background: white;
            flex-direction: column;
            gap: 8px;
            padding: 20px;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
            transition: left 0.3s;
        }

        .main-nav.mobile-active {
            left: 0;
        }

        .main-nav a {
            padding: 14px 16px;
            width: 100%;
        }

        .mobile-menu-btn {
            display: block;
        }

        .user-name {
            display: none;
        }

        .user-badge {
            padding: 8px;
        }

        .logout-btn span {
            display: none;
        }

        .logout-btn {
            padding: 10px;
            width: 40px;
            height: 40px;
            justify-content: center;
        }
    }

    .overlay {
        display: none;
        position: fixed;
        top: 70px;
        left: 0;
        width: 100%;
        height: calc(100vh - 70px);
        background: rgba(0, 0, 0, 0.5);
        z-index: 99;
    }

    .overlay.show {
        display: block;
    }
</style>

<header class="site-header">
    <div class="header-container">
        <a href="user_dashboard.php" class="site-logo">
            <div class="logo-icon">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div style="display:flex; flex-direction:column; line-height:1.1;">
                <span>FinanceTracker</span>
            </div>
        </a>

        <button class="mobile-menu-btn" onclick="toggleMenu()">
            <i class="fa-solid fa-bars"></i>
        </button>

        <nav class="main-nav" id="nav">
            <a href="user_dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i>Dashboard</a>
            <a href="view_transactions.php" class="nav-link"><i class="fa-solid fa-list"></i>Transactions</a>
            <a href="view_reports.php" class="nav-link"><i class="fa-solid fa-chart-bar"></i>Reports</a>
            <a href="expense_limit.php" class="nav-link"><i class="fa-solid fa-gauge-high"></i>Limits</a>
        </nav>

        <div class="user-menu">
            <div class="user-badge">
                <div class="user-avatar">
                    <a href="profile.php" style="text-decoration: none;">
                    <?php $name = $_SESSION['name'] ?? 'User'; echo strtoupper(substr($name, 0, 1));?>
                </div>
                    <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
                </a>
            </div>

            <a href="logout.php" class="logout-btn">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</header>

<div class="overlay" id="overlay" onclick="toggleMenu()"></div>

<script>
    function toggleMenu() {
        const nav = document.getElementById('nav');
        const overlay = document.getElementById('overlay');
        nav.classList.toggle('mobile-active');
        overlay.classList.toggle('show');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage) {
                link.classList.add('active');
            }
        });
    });

    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                toggleMenu();
            }
        });
    });
</script>