<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haven Zen - Public Transportation Tracking & Booking</title>
    <link rel="stylesheet" href="landing.css">
</head>
<body>
    <!-- Header Section -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="logo">
                    <span class="company-name">Haven Zen</span>
                </div>
                <div class="mobile-auth-buttons">
                    <a href="login/login.php" class="mobile-btn mobile-login-btn">Login</a>
                    <a href="login/register.php" class="mobile-btn mobile-register-btn">Register</a>
                </div>
                <ul class="nav-menu" id="navMenu">
                    <li class="nav-item"><a href="#home" class="nav-link">Home</a></li>
                    <li class="nav-item"><a href="#features" class="nav-link">Features</a></li>
                    <li class="nav-item"><a href="#how-it-works" class="nav-link">How It Works</a></li>
                    <li class="nav-item"><a href="#contact" class="nav-link">Contact</a></li>
                    <li class="nav-item"><a href="login/login.php" class="nav-link login-btn">Login</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-title">Smart Transportation for Barugo, Leyte</h1>
                <p class="hero-subtitle">Track, Book, and Ride with Ease</p>
                <p class="hero-description">Experience seamless public transportation with our real-time tracking and booking system. Never miss your ride again!</p>
                <div class="hero-buttons">
                    <a href="#features" class="btn primary-btn">Get Started</a>
                    <a href="#how-it-works" class="btn secondary-btn">Learn More</a>
                </div>
            </div>
        </div>
        <div class="bus-animation-section">
            <div class="bus-container">
                <div class="moving-bus">
                    <div class="bus">🚌</div>
                    <div class="bus-shadow"></div>
                </div>
                <div class="road"></div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title">Why Choose Haven Zen?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📍</div>
                    <h3 class="feature-title">Real-Time Tracking</h3>
                    <p class="feature-description">Track your bus or jeepney in real-time and know exactly when it will arrive at your stop.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📅</div>
                    <h3 class="feature-title">Easy Booking</h3>
                    <p class="feature-description">Book your ride in advance with our simple and intuitive booking system.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⏱️</div>
                    <h3 class="feature-title">Save Time</h3>
                    <p class="feature-description">No more waiting unnecessarily. Know your ride's exact arrival time.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💳</div>
                    <h3 class="feature-title">Transparent Pricing</h3>
                    <p class="feature-description">Know the fare before you book. No surprises, no hidden charges.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Sign Up</h3>
                    <p class="step-description">Create your account with basic information.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Select Route</h3>
                    <p class="step-description">Choose your starting point and destination in Barugo, Leyte.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Book & Track</h3>
                    <p class="step-description">Book your ride and track your vehicle in real-time.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3 class="step-title">Enjoy Your Ride</h3>
                    <p class="step-description">Board with confidence and enjoy a stress-free journey.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <h2 class="section-title">Contact Us</h2>
            <div class="contact-content">
                <div class="contact-info">
                    <h3>Haven Zen Ventures Inc.</h3>
                    <p>Barugo, Leyte, Philippines</p>
                    <p>Email: info@havenzen.com</p>
                    <p>Phone: (053) 123-4567</p>
                </div>
                <div class="contact-form">
                    <form action="#" method="POST">
                        <input type="text" name="name" placeholder="Your Name" required>
                        <input type="email" name="email" placeholder="Your Email" required>
                        <textarea name="message" placeholder="Your Message" rows="5" required></textarea>
                        <button type="submit" class="btn primary-btn">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <span class="company-name">Haven Zen Ventures Inc.</span>
                </div>
                <div class="footer-links">
                    <a href="#home">Home</a>
                    <a href="#features">Features</a>
                    <a href="#how-it-works">How It Works</a>
                    <a href="login/login.php">Login</a>
                    <a href="#contact">Contact</a>
                </div>
                <div class="footer-copyright">
                    <p>&copy; 2025 Haven Zen Ventures Inc. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    

</body>
</html>