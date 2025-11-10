<?php
function sendEmailSMTP($to_email, $to_name, $subject, $message) {
    $smtp_server = "smtp.gmail.com";
    $smtp_port = 587;
    $smtp_username = "ukwitegetsev9@gmail.com";
    $smtp_password = "acji cjin txmo oiub";
    
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
?>