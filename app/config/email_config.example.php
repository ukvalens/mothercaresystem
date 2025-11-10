<?php
// Email configuration template
// Copy this file to email_config.php and update with your actual email credentials

require_once __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// SMTP Configuration
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'your_email@gmail.com';
$smtp_password = 'your_app_password'; // Use Gmail App Password
$smtp_encryption = 'tls';

function sendEmail($to_email, $to_name, $subject, $html_content, $is_html = true) {
    global $smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_encryption;
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $smtp_encryption;
        $mail->Port = $smtp_port;
        
        // Recipients
        $mail->setFrom($smtp_username, 'Maternal Care System');
        $mail->addAddress($to_email, $to_name);
        
        // Content
        $mail->isHTML($is_html);
        $mail->Subject = $subject;
        $mail->Body = $html_content;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Add other email functions here...
?>