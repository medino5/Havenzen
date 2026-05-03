<?php
session_start();
require_once __DIR__ . '/../config.php';

$reg_message = '';
$message_type = 'error'; // 'error' or 'success'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve user input
    $username = htmlspecialchars($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $full_name = htmlspecialchars($first_name . ' ' . $last_name);
    $email = htmlspecialchars($_POST['email'] ?? '');
    $phone_number = htmlspecialchars($_POST['phone_number'] ?? '');
    $role = 'passenger'; // Hardcoded as requested
    
    // Clean phone number (keep only digits and leading +)
    $phone_digits = preg_replace('/[^0-9+]/', '', $phone_number);
    
    // Basic Validation
    if (empty($username) || empty($password) || empty($first_name) || empty($last_name) || empty($email) || empty($phone_number)) {
        $reg_message = "Please fill in all required fields.";
    } elseif (strlen($password) < 6) {
        $reg_message = "Password must be at least 6 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $reg_message = "Please provide a valid email address.";
    } elseif (!preg_match('/^(\+63|0)9[0-9]{9}$/', $phone_digits)) {
        $reg_message = "Please provide a valid Philippine phone number (e.g. 09171234567 or +639171234567).";
    } else {
        // Check if username already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $reg_message = "Username already exists. Please choose a different username.";
        } else {
            // Hash the password before storing
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user (auth-only)
            $insert_stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $username, $hashed_password, $role);
            
            if ($insert_stmt->execute()) {
                $newId = $conn->insert_id;

                // Insert passenger profile into customers table with all fields
                $profile_stmt = $conn->prepare("INSERT INTO customers (user_id, full_name, email, phone_number) VALUES (?, ?, ?, ?)");
                if ($profile_stmt) {
                    $profile_stmt->bind_param("isss", $newId, $full_name, $email, $phone_digits);
                    $profile_stmt->execute();
                    $profile_stmt->close();
                }

                $reg_message = "Registration successful! You can now login with your credentials.";
                $message_type = 'success';
                
                // Log self-registration
                logCRUD($conn, $newId, 'CREATE', 'users', $newId, 'Self-registered user: ' . $username);

                // Clear form fields on success
                $_POST = array();
            } else {
                $reg_message = "Registration failed. Please try again.";
            }
            
            $insert_stmt->close();
        }
        
        $check_stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Register - Haven Zen</title>
    <link rel="stylesheet" href="loginsection.css">
</head>
<body>
    <div class="container">
        <div class="header-text">
            <h1>🚌 <span>Haven Zen Transportation</span></h1>
            <p>Track, Book, and Manage Your Journey in Barugo, Leyte</p>
        </div>

        <div class="card-container">
            <div class="form-section">
                <h2>Create Your Account</h2>
                
                <?php if ($reg_message): ?>
                    <div class="<?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
                        <?php echo $reg_message; ?>
                    </div>
                <?php endif; ?>
                
                <form action="register.php" method="POST">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" placeholder="Choose a unique username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" placeholder="Create a strong password (min. 6 characters)" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" placeholder="e.g. Juan" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" placeholder="e.g. Dela Cruz" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" placeholder="name@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number *</label>
                        <input type="tel" id="phone_number" name="phone_number" 
                               inputmode="numeric"
                               pattern="^(\+63|0)9[0-9]{9}$"
                               placeholder="e.g. 09171234567 or +639171234567"
                               title="Philippine mobile e.g. 09171234567 or +639171234567"
                               required 
                               value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                        <small style="color: #666; font-size: 0.85em;">Format: 09171234567 or +639171234567</small>
                    </div>
                    
                    <button type="submit" class="submit-btn">Register</button>
                </form>

                <div class="link-text">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
                
                <div class="link-text">
                    <a href="../index.php">← Back to Home</a>
                </div>
            </div>

            <div class="illustration-section">
                <div class="bus-animation">🚌</div>
                <div class="illustration-text">
                    <h3>Join Haven Zen Today!</h3>
                    <p>Create your account to start tracking buses, booking rides, and enjoying seamless transportation in Barugo.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>