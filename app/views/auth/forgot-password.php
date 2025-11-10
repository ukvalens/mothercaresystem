<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Maternal Care System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 0; position: fixed; width: 100%; top: 0; z-index: 1000; }
        .nav { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 2rem; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links { display: flex; list-style: none; gap: 2rem; }
        .nav-links a { color: white; text-decoration: none; transition: opacity 0.3s; }
        .nav-links a:hover { opacity: 0.8; }
        
        .main-content { margin-top: 80px; padding: 2rem; display: flex; gap: 3rem; max-width: 1400px; margin-left: auto; margin-right: auto; min-height: calc(100vh - 200px); align-items: flex-start; }
        .form-container { background: white; padding: 2.5rem; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 450px; }
        .form-header { text-align: center; margin-bottom: 2rem; }
        .form-header h1 { color: #667eea; font-size: 2rem; margin-bottom: 0.5rem; }
        .form-header p { color: #666; font-size: 1rem; }
        
        .help-section { flex: 1; padding: 1rem; }
        .help-text { text-align: center; margin-bottom: 2rem; }
        .help-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .help-card { text-align: center; padding: 2rem; background: white; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: transform 0.3s; }
        .help-card:hover { transform: translateY(-5px); }
        .help-icon { font-size: 3rem; margin-bottom: 1rem; animation: rotate 3s linear infinite; }
        .help-card h4 { color: #28a745; margin-bottom: 1rem; font-size: 1.2rem; font-weight: 600; }
        .help-card p { color: #666; font-size: 1rem; line-height: 1.5; }
        .help-card h4 { color: #28a745; margin-bottom: 0.5rem; }
        
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600; font-size: 14px; }
        input { width: 100%; padding: 15px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
        input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin: 1.5rem 0 1rem 0; transition: all 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .links { text-align: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #eee; }
        .links a { color: #667eea; text-decoration: none; font-weight: 500; margin: 0 0.5rem; }
        .links a:hover { text-decoration: underline; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .alert-error { background: #E63946; color: white; }
        .alert-success { background: #2A9D8F; color: white; }
        
        .footer { background: #2c3e50; color: white; padding: 3rem 2rem 2rem 2rem; margin-top: 4rem; }
        .footer h4 { color: #667eea; margin-bottom: 1rem; font-size: 1.1rem; }
        .footer p { margin-bottom: 0.5rem; line-height: 1.6; }
        .footer a { color: #bbb; transition: color 0.3s; }
        .footer a:hover { color: #667eea; }
        
        @media (max-width: 768px) { 
            .nav-links { display: none; } 
            .main-content { flex-direction: column; height: auto; padding: 1rem; gap: 2rem; } 
            .form-container { width: 100%; padding: 2rem; } 
            .help-grid { grid-template-columns: 1fr; gap: 1rem; } 
            .help-card { padding: 1.5rem; }
            .form-header h1 { font-size: 1.5rem; }
            .help-text h2 { font-size: 1.8rem; }
            .help-icon { font-size: 2.5rem; }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🏥 Maternal Care System</div>
            <ul class="nav-links">
                <li><a href="../../../public/index.html">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php">Sign Up</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <div class="main-content">
        <div class="form-container">
            <div class="form-header">
                <h1>Password Recovery</h1>
                <p>Enter your email address to receive secure reset instructions</p>
            </div>
            <?php
            require_once '../../config/database.php';
            require_once '../../config/email_config.php';

            function sendResetEmail($email, $name, $token) {
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/mothercaresystem/app/views/auth/reset-password.php?token=" . $token;
                
                $content = '
                    <h2>🔐 Password Reset Request</h2>
                    <p>Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
                    <p>You have requested to reset your password for your Maternal Care System account.</p>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
                        <p style="margin-bottom: 15px;">Click the button below to reset your password:</p>
                        <a href="' . $reset_link . '" style="background: #0077B6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;">🔑 Reset My Password</a>
                    </div>
                    
                    <p><strong>Important Security Information:</strong></p>
                    <ul>
                        <li>This link will expire in <strong>2 hours</strong></li>
                        <li>If you did not request this reset, please ignore this email</li>
                        <li>For security, this link can only be used once</li>
                    </ul>
                ';
                
                $html = getEmailTemplate('Password Reset Request', $content);
                return sendEmail($email, $name, 'Password Reset - Maternal Care System', $html, true);
            }

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $email = trim($_POST['email']);

                if (empty($email)) {
                    echo '<div class="alert alert-error">Please enter your email address</div>';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo '<div class="alert alert-error">Please enter a valid email address</div>';
                } else {
                    $stmt = $mysqli->prepare("SELECT user_id, first_name FROM users WHERE email = ? AND is_active = 1");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();
                        $reset_token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+2 hours'));

                        $update_stmt = $mysqli->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE user_id = ?");
                        $update_stmt->bind_param("ssi", $reset_token, $expires, $user['user_id']);
                        
                        if ($update_stmt->execute()) {
                            if (sendResetEmail($email, $user['first_name'], $reset_token)) {
                                echo '<div class="alert alert-success">✅ Password reset instructions have been sent to your email address.</div>';
                            } else {
                                echo '<div class="alert alert-error">❌ Error sending email. Please try again.</div>';
                            }
                        } else {
                            echo '<div class="alert alert-error">❌ Error processing request. Please try again.</div>';
                        }
                    } else {
                        echo '<div class="alert alert-success">✅ If an account exists with this email address, reset instructions have been sent.</div>';
                    }
                }
            }
            ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">📧 Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your registered email address" required>
                </div>
                <button type="submit" class="btn">🚀 Send Reset Instructions</button>
            </form>

            <div class="links">
                <a href="login.php">← Back to Login</a> | 
                <a href="signup.php">Create New Account</a>
            </div>
        </div>
        
        <div class="help-section">
            <div class="help-text">
                <h2 style="color: #667eea; font-size: 2.2rem; margin-bottom: 1rem;">Secure Recovery Process</h2>
                <p style="font-size: 1.1rem; color: #666; margin-bottom: 0.5rem;">Get back to your healthcare dashboard quickly and securely</p>
                <p style="font-size: 1rem; color: #888;">Your account security is our top priority</p>
            </div>

            <div class="help-grid">
                <div class="help-card">
                    <div class="help-icon">📧</div>
                    <h4>Email Verification</h4>
                    <p>We'll send secure reset instructions to your registered email address. Check your inbox and spam folder for the recovery link.</p>
                </div>
                <div class="help-card">
                    <div class="help-icon">⏱️</div>
                    <h4>Fast & Secure Recovery</h4>
                    <p>Reset links are valid for 2 hours and can only be used once. This ensures maximum security for your healthcare data.</p>
                </div>
                <div class="help-card">
                    <div class="help-icon">🔒</div>
                    <h4>Bank-Level Security</h4>
                    <p>All recovery attempts are encrypted, logged, and monitored. Your patient data and medical records remain completely secure.</p>
                </div>
            </div>
            
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);">
                <h3 style="margin-bottom: 1rem; font-size: 1.5rem;">🚪 Need Additional Help?</h3>
                <p style="margin: 0; opacity: 0.9; font-size: 1.1rem;">Contact our 24/7 support team if you don't receive the reset email within 10 minutes or need assistance with your account</p>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; text-align: left;">
            <div>
                <h4 style="color: #667eea; margin-bottom: 1rem;">🏥 Maternal Care System</h4>
                <p style="margin-bottom: 0.5rem;">Comprehensive healthcare management for maternal and child health services.</p>
                <p style="font-size: 14px; opacity: 0.8;">&copy; 2024 All rights reserved.</p>
            </div>
            <div>
                <h4 style="color: #667eea; margin-bottom: 1rem;">Quick Links</h4>
                <p><a href="../../../public/index.html" style="color: #ccc; text-decoration: none;">Home</a></p>
                <p><a href="login.php" style="color: #ccc; text-decoration: none;">Login</a></p>
                <p><a href="signup.php" style="color: #ccc; text-decoration: none;">Sign Up</a></p>
            </div>
            <div>
                <h4 style="color: #667eea; margin-bottom: 1rem;">Contact Info</h4>
                <p style="margin-bottom: 0.5rem;">📧 ukwitegetsev9@gmail.com</p>
                <p style="margin-bottom: 0.5rem;">🌐 GitHub: ukvalens/mothercaresystem</p>
                <p>📱 +250 123 456 789</p>
            </div>
        </div>
    </footer>
</body>
</html>