<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Dashboard - Haven Zen</title>
    <link rel="stylesheet" href="users.css?v=<?php echo filemtime(__DIR__ . '/users.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation Header -->
    <nav class="user-nav">
        <div class="nav-container">
            <div class="nav-brand">
                <div class="logo" aria-hidden="true"><i class="fas fa-bus"></i></div>
                <span class="brand-name">Haven Zen</span>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>

            <div class="nav-content" id="navContent">
                <div class="nav-links">
                    <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Live Tracking</span>
                    </a>
                    <a href="booking.php" class="nav-link <?php echo $current_page == 'booking.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Book Trips</span>
                    </a>
                    <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
                    </a>
                    <!-- Mobile Logout Link -->
                    <a href="#" onclick="return confirmLogout();" class="nav-link mobile-only-link" style="color: #f44336;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>

                <div class="user-menu">
                    <div class="user-info">
                        <span class="welcome">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                        <span class="user-role">Passenger</span>
                    </div>
                    <a href="#" onclick="return confirmLogout();" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
        <div class="nav-overlay" id="navOverlay"></div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobileMenuToggle');
            const navContent = document.getElementById('navContent');
            const navOverlay = document.getElementById('navOverlay');
            const mobileNav = document.querySelector('.mobile-nav');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    navContent.classList.toggle('active');
                    navOverlay.classList.toggle('active');
                    document.body.style.overflow = navContent.classList.contains('active') ? 'hidden' : '';
                    
                    // Toggle mobile nav visibility
                    if (mobileNav) {
                        mobileNav.style.display = navContent.classList.contains('active') ? 'none' : 'flex';
                    }
                });
                
                navOverlay.addEventListener('click', function() {
                    navContent.classList.remove('active');
                    navOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                    
                    // Show mobile nav again
                    if (mobileNav) {
                        mobileNav.style.display = 'flex';
                    }
                });
            }
        });
    </script>

    <!-- Mobile Menu -->
    <div class="mobile-nav">
        <a href="index.php" class="mobile-nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-map-marker-alt"></i>
            <span>Map</span>
        </a>
        <a href="booking.php" class="mobile-nav-item <?php echo $current_page == 'booking.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-plus"></i>
            <span>Trips</span>
        </a>
        <a href="profile.php" class="mobile-nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>

    <!-- Main Content -->
    <main class="user-main">
        <div class="container">
