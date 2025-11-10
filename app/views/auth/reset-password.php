<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Maternal Care System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #E6F2F1; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { background: #FFFFFF; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #0077B6; font-size: 24px; margin-bottom: 5px; }
        .logo p { color: #6C757D; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #2D2D2D; font-weight: 500; }
        input[type="password"] { width: 100%; padding: 12px; border: 1px solid #ADB5BD; border-radius: 5px; font-size: 14px; }
        input:focus { outline: none; border-color: #0077B6; }
        .btn { width: 100%; padding: 12px; background: #0077B6; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin-bottom: 15px; }
        .btn:hover { background: #023E8A; }
        .links { text-align: center; }
        .links a { color: #0077B6; text-decoration: none; font-size: 14px; }
        .links a:hover { text-decoration: underline; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .alert-error { background: #E63946; color: white; }
        .alert-success { background: #2A9D8F; color: white; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🏥 Maternal Care</h1>
            <p>Reset Your Password</p>
        </div>

        <?php
        require_once '../../config/database.php';

        $token = $_GET['token'] ?? '';
        $valid_token = false;

        if ($token) {
            // Debug: Check if token exists at all
            $debug_stmt = $mysqli->prepare("SELECT user_id, reset_token, reset_expires, NOW() as db_time, is_active FROM users WHERE reset_token = ?");
            $debug_stmt->bind_param("s", $token);
            $debug_stmt->execute();
            $debug_result = $debug_stmt->get_result();
            
            if ($debug_result->num_rows > 0) {
                $debug_row = $debug_result->fetch_assoc();
                // Check each condition
                $token_exists = true;
                $not_expired = $debug_row['reset_expires'] > $debug_row['db_time'];
                $is_active = $debug_row['is_active'] == 1;
                
                $valid_token = $token_exists && $not_expired && $is_active;
                
                // Debug output (remove in production)
                if (!$valid_token) {
                    $debug_msg = "Debug: Token found but invalid. ";
                    if (!$not_expired) $debug_msg .= "EXPIRED (expires: {$debug_row['reset_expires']}, now: {$debug_row['db_time']}). ";
                    if (!$is_active) $debug_msg .= "USER INACTIVE. ";
                    echo "<!-- $debug_msg -->";
                }
            } else {
                $valid_token = false;
                echo "<!-- Debug: Token not found in database -->";
            }
        }

        if (!$valid_token) {
            echo '<div class="alert alert-error">Invalid or expired reset token</div>';
            echo '<div class="links"><a href="forgot-password.php">Request New Reset</a> | <a href="login.php">Back to Login</a></div>';
            echo '</div>';
            // Debug: Show token being checked
            echo "<!-- Debug: Checking token: $token -->";
            echo '</body></html>';
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if (empty($password) || empty($confirm_password)) {
                echo '<div class="alert alert-error">Please fill in all fields</div>';
            } elseif (strlen($password) < 6) {
                echo '<div class="alert alert-error">Password must be at least 6 characters</div>';
            } elseif ($password !== $confirm_password) {
                echo '<div class="alert alert-error">Passwords do not match</div>';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $mysqli->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
                $update_stmt->bind_param("ss", $hashed_password, $token);
                
                if ($update_stmt->execute()) {
                    echo '<div class="alert alert-success">Password updated successfully! You can now login with your new password.</div>';
                    echo '<div class="links"><a href="login.php">Go to Login</a></div>';
                    echo '</div></body></html>';
                    exit;
                } else {
                    echo '<div class="alert alert-error">Error updating password. Please try again</div>';
                }
            }
        }
        ?>

        <form method="POST">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit" class="btn">Update Password</button>
        </form>

        <div class="links">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>