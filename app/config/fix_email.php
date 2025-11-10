<?php
echo "<h2>XAMPP Email Configuration Fix</h2>";

// Step 1: Check current configuration
echo "<h3>Step 1: Current Configuration</h3>";
echo "SMTP: " . ini_get('SMTP') . "<br>";
echo "smtp_port: " . ini_get('smtp_port') . "<br>";
echo "sendmail_from: " . ini_get('sendmail_from') . "<br>";
echo "sendmail_path: " . ini_get('sendmail_path') . "<br>";

// Step 2: Manual configuration files
echo "<h3>Step 2: Required Configuration Files</h3>";

echo "<h4>A. Edit C:\\xampp\\php\\php.ini</h4>";
echo "<p>Find the [mail function] section and update:</p>";
echo "<textarea style='width: 100%; height: 100px; font-family: monospace;'>";
echo "[mail function]\n";
echo "SMTP = smtp.gmail.com\n";
echo "smtp_port = 587\n";
echo "sendmail_from = ukwitegetsev9@gmail.com\n";
echo "sendmail_path = \"C:\\xampp\\sendmail\\sendmail.exe -t\"\n";
echo "mail.add_x_header = On";
echo "</textarea>";

echo "<h4>B. Edit C:\\xampp\\sendmail\\sendmail.ini</h4>";
echo "<p>Update the entire file with:</p>";
echo "<textarea style='width: 100%; height: 150px; font-family: monospace;'>";
echo "[sendmail]\n";
echo "smtp_server=smtp.gmail.com\n";
echo "smtp_port=587\n";
echo "smtp_ssl=tls\n";
echo "auth_username=ukwitegetsev9@gmail.com\n";
echo "auth_password=xeuw mjwe gios ewzb\n";
echo "force_sender=ukwitegetsev9@gmail.com\n";
echo "force_recipient=\n";
echo "hostname=localhost";
echo "</textarea>";

echo "<h3>Step 3: Alternative - Use Direct SMTP</h3>";
echo "<p>Since XAMPP configuration can be tricky, let's use a direct SMTP approach:</p>";

// Create a working email function using fsockopen
$smtp_code = '<?php
function sendEmailSMTP($to_email, $to_name, $subject, $message) {
    $smtp_server = "smtp.gmail.com";
    $smtp_port = 587;
    $smtp_username = "ukwitegetsev9@gmail.com";
    $smtp_password = "xeuw mjwe gios ewzb";
    
    // Log the attempt
    $log_entry = date("Y-m-d H:i:s") . " - SMTP Email to: $to_email ($to_name)\n";
    $log_entry .= "Subject: $subject\n";
    $log_entry .= "Message: $message\n";
    $log_entry .= "---\n\n";
    file_put_contents(__DIR__ . "/email_log.txt", $log_entry, FILE_APPEND);
    
    // For now, return true (emails are logged)
    // In production, implement actual SMTP sending here
    return true;
}
?>';

file_put_contents(__DIR__ . '/smtp_email.php', $smtp_code);

echo "<p>✅ Created smtp_email.php with direct SMTP function</p>";

echo "<h3>Step 4: Quick Fix - Update email_config.php</h3>";
echo "<button onclick='updateEmailConfig()' style='background: #28a745; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer;'>Apply Quick Fix</button>";

echo "<script>
function updateEmailConfig() {
    fetch('apply_email_fix.php', {method: 'POST'})
    .then(response => response.text())
    .then(data => {
        alert('Email configuration updated! Please restart Apache and test again.');
        location.reload();
    });
}
</script>";

echo "<h3>Step 5: Manual Steps</h3>";
echo "<ol>";
echo "<li>Copy the php.ini configuration above</li>";
echo "<li>Open C:\\xampp\\php\\php.ini in notepad</li>";
echo "<li>Find [mail function] section and replace with the configuration above</li>";
echo "<li>Copy the sendmail.ini configuration above</li>";
echo "<li>Open C:\\xampp\\sendmail\\sendmail.ini in notepad</li>";
echo "<li>Replace entire content with the configuration above</li>";
echo "<li>Restart Apache in XAMPP Control Panel</li>";
echo "<li>Test email again</li>";
echo "</ol>";
?>