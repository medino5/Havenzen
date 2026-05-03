<?php
session_start();
require_once __DIR__ . '/../config.php';

$message = '';
$message_type = 'error';
$token_valid = false;
$user_id = null;
$username = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = htmlspecialchars($_GET['token']);
    
    // Verify token and check expiry
    $token_stmt = $conn->prepare("
        SELECT prt.user_id, u.username, prt.expiry_date
        FROM password_reset_tokens prt
        JOIN users u ON u.user_id = prt.user_id
        WHERE prt.token = ? AND prt.expiry_date > NOW()
    ");
    $token_stmt->bind_param("s", $token);
    $token_stmt->execute();
    $result = $token_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $token_data = $result->fetch_assoc();
        $token_valid = true;
        $user_id = $token_data['user_id'];
        $username = $token_data['username'];
    } else {
        $message = "Invalid or expired reset link. Please request a new password reset link.";
    }
    
    $token_stmt->close();
} else {
    $message = "No reset token provided.";
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $token = htmlspecialchars($_POST['token'] ?? '');
    
    if (empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
    } elseif (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            // Delete used token
            $delete_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
            $delete_stmt->bind_param("s", $token);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            // Log successful password reset
            logSystemEvent($conn, $user_id, 'PASSWORD_RESET_SUCCESS', 'Password successfully reset for user: ' . $username);
            logCRUD($conn, $user_id, 'UPDATE', 'users', $user_id, 'Password reset completed');
            
            $message = "Password reset successful! You can now login with your new password.";
            $message_type = 'success';
            $token_valid = false; // Prevent form from showing again
        } else {
            $message = "Error resetting password. Please try again.";
        }
        
        $update_stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Haven Zen</title>
    <link rel="stylesheet" href="loginsection.css">
    <style>
        .password-requirements {
            background: #f0f8ff;
            border-left: 4px solid #2196F3;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        .password-requirements ul {
            margin: 8px 0 0 20px;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-text">
            <h1>🚌 <span>Haven Zen Transportation</span></h1>
            <p>Reset Your Password</p>
        </div>

        <div class="card-container">
            <div class="form-section">
                <h2>Create New Password</h2>
                
                <?php if ($message): ?>
                    <div class="<?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($token_valid): ?>
                    <p style="color: #666; margin-bottom: 20px;">
                        Resetting password for: <strong><?php echo htmlspecialchars($username); ?></strong>
                    </p>
                    
                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li>At least 6 characters long</li>
                            <li>Should be unique and not easily guessable</li>
                            <li>Consider using a mix of letters, numbers, and symbols</li>
                        </ul>
                    </div>
                    
                    <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password (min. 6 characters)" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required minlength="6">
                        </div>
                        
                        <button type="submit" class="submit-btn">Reset Password</button>
                    </form>
                <?php else: ?>
                    <div class="link-text" style="margin-top: 20px;">
                        <?php if ($message_type === 'success'): ?>
                            <a href="login.php">← Go to Login</a>
                        <?php else: ?>
                            <a href="forgot_password.php">← Request New Reset Link</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="illustration-section">
                <div class="bus-animation">🔒</div>
                <div class="illustration-text">
                    <h3>Secure Your Account</h3>
                    <p>Choose a strong password to keep your account safe and secure.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
