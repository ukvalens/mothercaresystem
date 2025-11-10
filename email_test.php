<!DOCTYPE html>
<html>
<head>
    <title>PHPMailer Test - Maternal Care System</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .success { color: green; padding: 10px; background: #f0f8f0; border-radius: 4px; }
        .error { color: red; padding: 10px; background: #f8f0f0; border-radius: 4px; }
    </style>
</head>
<body>
    <h2>PHPMailer Email Test</h2>
    
    <?php
    if ($_POST) {
        require_once 'app/config/email_config.php';
        
        $to_email = $_POST['to_email'];
        $to_name = $_POST['to_name'];
        $subject = $_POST['subject'];
        $message = $_POST['message'];
        
        $result = sendEmail($to_email, $to_name, $subject, $message);
        
        if ($result) {
            echo '<div class="success">✓ Email sent successfully to ' . htmlspecialchars($to_email) . '</div>';
        } else {
            echo '<div class="error">✗ Failed to send email. Check email_log.txt for details.</div>';
        }
    }
    ?>
    
    <form method="POST">
        <div class="form-group">
            <label>To Email:</label>
            <input type="email" name="to_email" value="ukwitegetsev9@gmail.com" required>
        </div>
        
        <div class="form-group">
            <label>To Name:</label>
            <input type="text" name="to_name" value="Test User" required>
        </div>
        
        <div class="form-group">
            <label>Subject:</label>
            <input type="text" name="subject" value="Test Email from Maternal Care System" required>
        </div>
        
        <div class="form-group">
            <label>Message:</label>
            <textarea name="message" rows="5" required>This is a test email sent using PHPMailer from the Maternal Care System.

The email system is now properly configured and working.

Best regards,
Maternal Care System</textarea>
        </div>
        
        <button type="submit">Send Test Email</button>
    </form>
</body>
</html>