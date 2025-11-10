<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Maternal Care System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #E6F2F1; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .signup-container { background: #FFFFFF; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #0077B6; font-size: 24px; margin-bottom: 5px; }
        .logo p { color: #6C757D; font-size: 14px; }
        .form-row { display: flex; gap: 15px; }
        .form-group { margin-bottom: 20px; flex: 1; }
        label { display: block; margin-bottom: 5px; color: #2D2D2D; font-weight: 500; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ADB5BD; border-radius: 5px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #0077B6; }
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
    <div class="signup-container">
        <div class="logo">
            <h1>🏥 Maternal Care</h1>
            <p>Create Your Account</p>
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

            // Validation
            if (empty($nid) || empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
                echo '<div class="alert alert-error">Please fill in all required fields</div>';
            } elseif ($password !== $confirm_password) {
                echo '<div class="alert alert-error">Passwords do not match</div>';
            } elseif (strlen($password) < 6) {
                echo '<div class="alert alert-error">Password must be at least 6 characters</div>';
            } else {
                // Check if NID or username already exists
                $check_stmt = $mysqli->prepare("SELECT user_id FROM users WHERE nid = ? OR username = ? OR email = ?");
                $check_stmt->bind_param("sss", $nid, $username, $email);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    echo '<div class="alert alert-error">NID, username, or email already exists</div>';
                } else {
                    // Hash password and insert user
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
</body>
</html>