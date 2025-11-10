<?php
echo "<h2>XAMPP SMTP Setup Instructions</h2>";

echo "<h3>Current PHP Configuration</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Mail function available: " . (function_exists('mail') ? 'Yes' : 'No') . "<br>";

echo "<h3>XAMPP SMTP Configuration Steps</h3>";
echo "<ol>";
echo "<li><strong>Edit php.ini file:</strong><br>";
echo "Location: C:\\xampp\\php\\php.ini<br>";
echo "Find and update these lines:<br>";
echo "<pre style='background: #f8f9fa; padding: 1rem;'>";
echo "[mail function]\n";
echo "SMTP = smtp.gmail.com\n";
echo "smtp_port = 587\n";
echo "sendmail_from = " . SMTP_USERNAME . "\n";
echo "sendmail_path = \"C:\\xampp\\sendmail\\sendmail.exe -t\"";
echo "</pre></li>";

echo "<li><strong>Edit sendmail.ini file:</strong><br>";
echo "Location: C:\\xampp\\sendmail\\sendmail.ini<br>";
echo "Update these lines:<br>";
echo "<pre style='background: #f8f9fa; padding: 1rem;'>";
echo "smtp_server=smtp.gmail.com\n";
echo "smtp_port=587\n";
echo "smtp_ssl=tls\n";
echo "auth_username=" . SMTP_USERNAME . "\n";
echo "auth_password=" . SMTP_PASSWORD . "\n";
echo "force_sender=" . SMTP_USERNAME;
echo "</pre></li>";

echo "<li><strong>Restart Apache</strong> in XAMPP Control Panel</li>";
echo "</ol>";

echo "<h3>Alternative: Use PHPMailer (Recommended)</h3>";
echo "<p>For better email delivery, consider using PHPMailer:</p>";
echo "<ol>";
echo "<li>Download PHPMailer from GitHub</li>";
echo "<li>Extract to: C:\\xampp\\htdocs\\mothercaresystem\\vendor\\phpmailer\\</li>";
echo "<li>Update email_config.php to use PHPMailer</li>";
echo "</ol>";

echo "<h3>Test Email System</h3>";
echo "<a href='test_email.php' style='background: #0077B6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Run Email Test</a>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 2rem; }
pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
ol li { margin-bottom: 1rem; }
</style>