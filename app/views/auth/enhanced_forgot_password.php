<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Maternal Care System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #E6F2F1; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { background: #FFFFFF; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #0077B6; font-size: 24px; margin-bottom: 5px; }
        .logo p { color: #6C757D; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #2D2D2D; font-weight: 500; }
        input[type="email"] { width: 100%; padding: 12px; border: 1px solid #ADB5BD; border-radius: 5px; font-size: 14px; }
        input:focus { outline: none; border-color: #0077B6; }
        .btn { width: 100%; padding: 12px; background: #0077B6; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin-bottom: 15px; }
        .btn:hover { background: #023E8A; }
        .links { text-align: center; }
        .links a { color: #0077B6; text-decoration: none; font-size: 14px; }
        .links a:hover { text-decoration: underline; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .alert-error { background: #E63946; color: white; }
        .alert-success { background: #2A9D8F; color: white; }
        .info-text { color: #6C757D; font-size: 14px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🏥 Maternal Care</h1>
            <p>Password Recovery</p>
        </div>

        <?php
        require_once '../../config/database.php';

        function sendResetEmail($email, $name, $token) {
            // Check if PHPMailer exists
            if (file_exists('../../../vendors/phpmailer/src/PHPMailer.php')) {
                require_once '../../../vendors/phpmailer/src/PHPMailer.php';
                require_once '../../../vendors/phpmailer/src/SMTP.php';
                require_once '../../../vendors/phpmailer/src/Exception.php';
                
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'your-email@gmail.com'; // Change this
                    $mail->Password = 'your-app-password';    // Change this
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    
                    $mail->setFrom('noreply@maternalcare.local', 'Maternal Care System');
                    $mail->addAddress($email, $name);
                    
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/mothercaresystem/app/views/auth/reset-password.php?token=" . $token;
                    
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset - Maternal Care System';
                    $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>Hello $name,</p>
                    <p>You requested a password reset. Click the button below to reset your password:</p>
                    <p><a href='$reset_link' style='background:#0077B6;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Reset Password</a></p>
                    <p>Or copy this link: $reset_link</p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                    ";
                    
                    $mail->send();
                    return true;
                } catch (Exception $e) {
                    return false;
                }
            } else {
                // Fallback to PHP mail()
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/mothercaresystem/app/views/auth/reset-password.php?token=" . $token;
                $subject = "Password Reset - Maternal Care System";
                $message = "Hello $name,\n\n";
                $message .= "You requested a password reset. Click the link below:\n\n";
                $message .= $reset_link . "\n\n";
                $message .= "This link expires in 1 hour.\n\n";
                $message .= "Maternal Care System";
                
                $headers = "From: noreply@maternalcare.local\r\n";
                return mail($email, $subject, $message, $headers);
            }
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
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    $update_stmt = $mysqli->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE user_id = ?");
                    $update_stmt->bind_param("ssi", $reset_token, $expires, $user['user_id']);
                    
                    if ($update_stmt->execute()) {
                        sendResetEmail($email, $user['first_name'], $reset_token);
                        echo '<div class="alert alert-success">Password reset instructions have been sent to your email</div>';
                    } else {
                        echo '<div class="alert alert-error">Error processing request. Please try again</div>';
                    }
                } else {
                    echo '<div class="alert alert-success">If an account exists with this email, reset instructions have been sent</div>';
                }
            }
        }
        ?>

        <div class="info-text">
            Enter your email address and we'll send you instructions to reset your password.
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn">Send Reset Instructions</button>
        </form>

        <div class="links">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>