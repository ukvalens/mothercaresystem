<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Maternal Care System</title>
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
        
        .features-section { flex: 1; padding: 1rem; }
        .features-text { text-align: center; margin-bottom: 2rem; }
        .features-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .feature-card { text-align: center; padding: 1.5rem; background: white; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: transform 0.3s; }
        .feature-card:hover { transform: translateY(-5px); }
        .feature-icon { font-size: 2.5rem; margin-bottom: 1rem; animation: bounce 2s infinite; }
        .feature-card h4 { color: #667eea; margin-bottom: 0.8rem; font-size: 1.1rem; font-weight: 600; }
        .feature-card p { color: #666; font-size: 0.9rem; line-height: 1.4; }
        
        @keyframes bounce { 0%, 20%, 50%, 80%, 100% { transform: translateY(0); } 40% { transform: translateY(-10px); } 60% { transform: translateY(-5px); } }
        
        .form-row { display: flex; gap: 15px; }
        .form-group { margin-bottom: 1.2rem; flex: 1; }
        label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600; font-size: 14px; }
        input, select { width: 100%; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
        input:focus, select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin: 1.5rem 0 1rem 0; transition: all 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .links { text-align: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #eee; }
        .links a { color: #667eea; text-decoration: none; font-weight: 500; }
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
            .features-grid { grid-template-columns: 1fr; gap: 1rem; } 
            .feature-card { padding: 1rem; }
            .form-header h1 { font-size: 1.5rem; }
            .features-text h2 { font-size: 1.8rem; }
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
                <li><a href="#features">Features</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <div class="main-content">
        <div class="form-container">
            <div class="form-header">
                <h1>Create Account</h1>
                <p>Join our healthcare platform and start managing patient care efficiently</p>
            </div>
            <?php
            require_once '../../config/database.php';

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $nid = trim($_POST['nid']);
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $phone = trim($_POST['phone']);
                $role = $_POST['role'];

                if (empty($nid) || empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
                    echo '<div class="alert alert-error">Please fill in all required fields</div>';
                } elseif ($password !== $confirm_password) {
                    echo '<div class="alert alert-error">Passwords do not match</div>';
                } elseif (strlen($password) < 6) {
                    echo '<div class="alert alert-error">Password must be at least 6 characters</div>';
                } else {
                    $check_stmt = $mysqli->prepare("SELECT user_id FROM users WHERE nid = ? OR username = ? OR email = ?");
                    $check_stmt->bind_param("sss", $nid, $username, $email);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();

                    if ($check_result->num_rows > 0) {
                        echo '<div class="alert alert-error">NID, username, or email already exists</div>';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $mysqli->prepare("INSERT INTO users (nid, username, password, email, phone, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssssss", $nid, $username, $hashed_password, $email, $phone, $role, $first_name, $last_name);
                        
                        if ($stmt->execute()) {
                            echo '<div class="alert alert-success">Account created successfully! Redirecting to login...</div>';
                            echo '<script>setTimeout(function(){ window.location.href = "login.php"; }, 2000);</script>';
                        } else {
                            echo '<div class="alert alert-error">Error creating account. Please try again.</div>';
                        }
                    }
                }
            }
            ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="nid">National ID *</label>
                    <input type="text" id="nid" name="nid" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="Patient">Patient</option>
                            <option value="Doctor">Doctor</option>
                            <option value="Nurse">Nurse</option>
                            <option value="Receptionist">Receptionist</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>

                <button type="submit" class="btn">Create Account</button>
            </form>

            <div class="links">
                <a href="login.php">Already have an account? Login</a>
            </div>
        </div>
        
        <div class="features-section">
            <div class="features-text">
                <h2 style="color: #667eea; font-size: 2.2rem; margin-bottom: 1rem;">Why Choose Our Platform?</h2>
                <p style="font-size: 1.1rem; color: #666; margin-bottom: 0.5rem;">Advanced healthcare technology for better patient outcomes</p>
                <p style="font-size: 1rem; color: #888;">Trusted by healthcare professionals across Rwanda</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h4>Patient Management</h4>
                    <p>Complete digital health records with comprehensive medical history tracking</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📅</div>
                    <h4>Smart Scheduling</h4>
                    <p>AI-powered appointment system with automated reminders and confirmations</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🤱</div>
                    <h4>Maternal Care</h4>
                    <p>Specialized pregnancy monitoring with risk assessment and ANC tracking</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔔</div>
                    <h4>Real-time Alerts</h4>
                    <p>Instant notifications, SMS alerts, and clinical reminders for better care</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💳</div>
                    <h4>Digital Payments</h4>
                    <p>Secure mobile money integration with automated billing and receipts</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h4>AI Insights</h4>
                    <p>Predictive health analytics and intelligent risk scoring algorithms</p>
                </div>
            </div>
            
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);">
                <h3 style="margin-bottom: 1rem; font-size: 1.5rem;">🎆 Join 1000+ Healthcare Professionals</h3>
                <p style="margin: 0; opacity: 0.9; font-size: 1.1rem;">Trusted by doctors, nurses, and healthcare facilities across Rwanda for comprehensive maternal care management</p>
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