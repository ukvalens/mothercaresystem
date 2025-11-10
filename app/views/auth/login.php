<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Maternal Care System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 0; position: fixed; width: 100%; top: 0; z-index: 1000; }
        .nav { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 2rem; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links { display: flex; list-style: none; gap: 2rem; }
        .nav-links a { color: white; text-decoration: none; transition: opacity 0.3s; }
        .nav-links a:hover { opacity: 0.8; }
        
        .main-content { margin-top: 80px; padding: 1rem; display: flex; gap: 2rem; max-width: 1200px; margin-left: auto; margin-right: auto; height: calc(100vh - 160px); }
        .form-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); width: 350px; height: fit-content; }
        
        .info-section { flex: 1; padding: 1rem; }
        .welcome-text { text-align: center; margin-bottom: 1rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem; text-align: center; }
        .stat-card { padding: 0.8rem; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: bold; color: #667eea; animation: pulse 2s infinite; }
        .stat-label { font-size: 0.9rem; color: #666; }
        
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #2D2D2D; font-weight: 500; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        input:focus { outline: none; border-color: #667eea; }
        .btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin: 10px 0; }
        .btn:hover { opacity: 0.9; }
        .links { text-align: center; margin-top: 1rem; }
        .links a { color: #667eea; text-decoration: none; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .alert-error { background: #E63946; color: white; }
        .alert-success { background: #2A9D8F; color: white; }
        
        .footer { background: #333; color: white; padding: 2rem; text-align: center; margin-top: 3rem; }
        
        @media (max-width: 768px) { .nav-links { display: none; } .main-content { flex-direction: column; height: auto; } .form-container { width: 100%; } .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🏥 Maternal Care System</div>
            <ul class="nav-links">
                <li><a href="../../../public/index.html">Home</a></li>
                <li><a href="signup.php">Sign Up</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <div class="main-content">
        <div class="form-container">
            <?php
            session_start();
            
            if (isset($_GET['logout'])) {
                session_destroy();
                header('Location: login.php');
                exit();
            }
            
            require_once '../../config/database.php';

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $username = trim($_POST['username']);
                $password = $_POST['password'];

                if (empty($username) || empty($password)) {
                    echo '<div class="alert alert-error">Please fill in all fields</div>';
                } else {
                    $stmt = $mysqli->prepare("SELECT user_id, username, password, role, first_name, last_name, is_active FROM users WHERE username = ? OR email = ?");
                    $stmt->bind_param("ss", $username, $username);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($user = $result->fetch_assoc()) {
                        if (!$user['is_active']) {
                            echo '<div class="alert alert-error">Account is deactivated. Contact administrator.</div>';
                        } elseif (password_verify($password, $user['password'])) {
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];

                            $update_stmt = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                            $update_stmt->bind_param("i", $user['user_id']);
                            $update_stmt->execute();

                            header('Location: ../dashboard/index.php');
                            exit();
                        } else {
                            echo '<div class="alert alert-error">Invalid username or password</div>';
                        }
                    } else {
                        echo '<div class="alert alert-error">Invalid username or password</div>';
                    }
                }
            }
            ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>

            <div class="links">
                <a href="signup.php">Create Account</a> | 
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
        </div>
        
        <div class="info-section">
            <div class="welcome-text">
                <h2 style="color: #667eea; margin-bottom: 1rem;">Welcome to Healthcare Excellence</h2>
                <p>Advanced maternal care management platform trusted by healthcare professionals</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">5</div>
                    <div class="stat-label">User Roles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">15+</div>
                    <div class="stat-label">Core Features</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">System Uptime</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Data Security</div>
                </div>
            </div>
            
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 10px; margin-top: 1rem; text-align: center;">
                <h3 style="margin-bottom: 0.5rem;">🔒 Secure Access</h3>
                <p style="margin: 0; opacity: 0.9;">Role-based authentication ensures data privacy and system security for all users</p>
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