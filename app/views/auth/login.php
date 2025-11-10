<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Maternal Care System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #E6F2F1; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { background: #FFFFFF; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #0077B6; font-size: 24px; margin-bottom: 5px; }
        .logo p { color: #6C757D; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #2D2D2D; font-weight: 500; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 12px; border: 1px solid #ADB5BD; border-radius: 5px; font-size: 14px; }
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
            <p>Healthcare Management System</p>
        </div>

        <?php
        session_start();
        
        // Handle logout
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

                        // Update last login
                        $update_stmt = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                        $update_stmt->bind_param("i", $user['user_id']);
                        $update_stmt->execute();

                        // Redirect to unified dashboard
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
</body>
</html>