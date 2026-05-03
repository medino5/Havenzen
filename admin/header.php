<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Haven Zen</title>
    <link rel="stylesheet" href="admin.css?v=<?php echo filemtime(__DIR__ . '/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>Haven Zen</h1>
                <p>Admin Panel</p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a></li>
                    <li><a href="vehicles.php" class="<?php echo $current_page == 'vehicles.php' ? 'active' : ''; ?>">
                        <i class="fas fa-bus"></i>
                        <span>Vehicles</span>
                    </a></li>
                    <li><a href="drivers.php" class="<?php echo $current_page == 'drivers.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i>
                        <span>Drivers</span>
                    </a></li>
                    <li><a href="routes.php" class="<?php echo $current_page == 'routes.php' ? 'active' : ''; ?>">
                        <i class="fas fa-route"></i>
                        <span>Routes & Fares</span>
                    </a></li>
                    <li><a href="schedules.php" class="<?php echo $current_page == 'schedules.php' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i>
                        <span>Schedules</span>
                    </a></li>
                    <li><a href="bookings.php" class="<?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span>Bookings</span>
                    </a></li>
                    <li><a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Users Management</span>
                    </a></li>
                    <li><a href="system-logs.php" class="<?php echo $current_page == 'system-logs.php' ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>System Logs</span>
                    </a></li>
                    <li><a href="#" onclick="return confirmLogout();" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a></li>
                </ul>
            </nav>
        </aside>
        
        <script>
        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (menuToggle && sidebar && overlay) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                });
                
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });
                
                // Close sidebar when a menu item is clicked on mobile
                const menuItems = sidebar.querySelectorAll('.sidebar-nav a');
                menuItems.forEach(item => {
                    item.addEventListener('click', function() {
                        if (window.innerWidth <= 768) {
                            sidebar.classList.remove('active');
                            overlay.classList.remove('active');
                        }
                    });
                });
            }
        });
        </script>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1><?php echo $page_title ?? 'Admin Panel'; ?></h1>
                <div class="user-info">
                    <div class="welcome-text">
                        Welcome, <strong><?php echo $_SESSION['full_name'] ?? 'Admin'; ?></strong>
                    </div>
                    <a href="#" onclick="return confirmLogout();" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
