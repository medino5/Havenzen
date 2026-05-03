<?php
session_start();
require_once __DIR__ . '/../config.php';

$message = '';
$message_type = 'error';
$reset_token = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username'] ?? '');
    $phone_number = htmlspecialchars($_POST['phone_number'] ?? '');
    
    if (empty($username) || empty($phone_number)) {
        $message = "Please fill in all required fields.";
    } else {
        // Verify username and phone number match
        // Check in customers table for passengers
        $verify_stmt = $conn->prepare("
            SELECT u.user_id, u.username, c.phone_number, c.full_name, u.role
            FROM users u
            LEFT JOIN customers c ON c.user_id = u.user_id
            WHERE u.username = ?
            UNION
            SELECT u.user_id, u.username, d.phone_number, d.full_name, u.role
            FROM users u
            LEFT JOIN drivers d ON d.user_id = u.user_id
            WHERE u.username = ?
            UNION
            SELECT u.user_id, u.username, a.phone_number, a.full_name, u.role
            FROM users u
            LEFT JOIN admins a ON a.user_id = u.user_id
            WHERE u.username = ?
        ");
        
        $verify_stmt->bind_param("sss", $username, $username, $username);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Clean phone numbers for comparison (remove spaces, dashes, etc)
            $stored_phone = preg_replace('/[^0-9+]/', '', $user['phone_number'] ?? '');
            $input_phone = preg_replace('/[^0-9+]/', '', $phone_number);
            
            // Normalize both to same format for comparison
            $stored_phone_normalized = preg_replace('/^(\+63|0)/', '', $stored_phone);
            $input_phone_normalized = preg_replace('/^(\+63|0)/', '', $input_phone);
            
            if ($stored_phone_normalized === $input_phone_normalized) {
                // Generate secure reset token
                $reset_token = bin2hex(random_bytes(32));
                $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour
                
                // Store token in database
                $token_stmt = $conn->prepare("
                    INSERT INTO password_reset_tokens (user_id, token, expiry_date) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE token = ?, expiry_date = ?
                ");
                $token_stmt->bind_param("issss", $user['user_id'], $reset_token, $token_expiry, $reset_token, $token_expiry);
                
                if ($token_stmt->execute()) {
                    // Create reset link
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                    $host = $_SERVER['HTTP_HOST'];
                    $base_url = $protocol . "://" . $host . dirname($_SERVER['PHP_SELF']);
                    $reset_link = $base_url . "/reset_password.php?token=" . $reset_token;
                    
                    $message = "Password reset link generated successfully! Copy the link below and use it to reset your password. This link is valid for 1 hour.";
                    $message_type = 'success';
                    
                    // Log password reset request
                    logSystemEvent($conn, $user['user_id'], 'PASSWORD_RESET_REQUEST', 'Password reset requested for user: ' . $username);
                } else {
                    $message = "Error generating reset token. Please try again.";
                }
                
                $token_stmt->close();
            } else {
                $message = "Username and phone number do not match our records.";
            }
        } else {
            $message = "Username not found.";
        }
        
        $verify_stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Haven Zen</title>
    <link rel="stylesheet" href="loginsection.css">
    <style>
        .reset-link-box {
            background: #f0f8ff;
            border: 2px solid #e91e63;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            word-break: break-all;
        }
        
        .reset-link-box .link {
            color: #e91e63;
            font-weight: 600;
            user-select: all;
        }
        
        .copy-btn {
            background: #e91e63;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            font-weight: 600;
        }
        
        .copy-btn:hover {
            background: #c2185b;
        }
        
        .instructions {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-text">
            <h1>🚌 <span>Haven Zen Transportation</span></h1>
            <p>Password Recovery</p>
        </div>

        <div class="card-container">
            <div class="form-section">
                <h2>Forgot Your Password?</h2>
                <p style="color: #666; margin-bottom: 20px;">Enter your username and phone number to receive a password reset link.</p>
                
                <?php if ($message): ?>
                    <div class="<?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($reset_link): ?>
                    <div class="reset-link-box">
                        <p><strong>Your Password Reset Link:</strong></p>
                        <div class="link" id="resetLink"><?php echo $reset_link; ?></div>
                        <button class="copy-btn" onclick="copyResetLink()">📋 Copy Link</button>
                    </div>
                    
                    <div class="instructions">
                        <strong>⚠️ Important:</strong>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li>Copy the link above</li>
                            <li>Paste it in your browser to reset your password</li>
                            <li>This link expires in 1 hour</li>
                            <li>Do not share this link with anyone</li>
                        </ul>
                    </div>
                    
                    <div class="link-text" style="margin-top: 20px;">
                        <a href="login.php">← Back to Login</a>
                    </div>
                <?php else: ?>
                    <form action="forgot_password.php" method="POST">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" placeholder="Enter your username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone_number">Phone Number</label>
                            <input type="tel" id="phone_number" name="phone_number" 
                                   placeholder="Enter your registered phone number" 
                                   pattern="^(\+63|0)9[0-9]{9}$"
                                   title="Philippine mobile e.g. 09171234567 or +639171234567"
                                   required 
                                   value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                            <small style="color: #666;">Format: 09171234567 or +639171234567</small>
                        </div>
                        
                        <button type="submit" class="submit-btn">Generate Reset Link</button>
                    </form>

                    <div class="link-text">
                        <a href="login.php">← Back to Login</a>
                    </div>
                    
                    <div class="link-text">
                        Don't have an account? <a href="register.php">Register here</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="illustration-section">
                <div class="bus-animation">🔐</div>
                <div class="illustration-text">
                    <h3>Secure Password Recovery</h3>
                    <p>We'll verify your identity using your registered phone number to ensure your account stays secure.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyResetLink() {
            const linkText = document.getElementById('resetLink').textContent;
            navigator.clipboard.writeText(linkText).then(() => {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = '✓ Copied!';
                btn.style.background = '#4caf50';
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.background = '#e91e63';
                }, 2000);
            }).catch(err => {
                alert('Failed to copy. Please select and copy the link manually.');
            });
        }
    </script>
</body>
</html>
