<?php
// Test email functionality
include 'email_config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📧 Email Test</h1>";

// Test basic mail function
echo "<h2>Testing PHP mail() function...</h2>";
$to = "test@example.com"; // Change to your email for testing
$subject = "Test Email from StudyLance";
$body = "This is a test email from StudyLance application.";
$headers = "From: test@studylance.com\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

if (mail($to, $subject, $body, $headers)) {
    echo "✅ mail() function succeeded (check your spam folder)<br>";
} else {
    echo "❌ mail() function failed<br>";
    echo "Error: " . error_get_last()['message'] ?? "Unknown error<br>";
}

// Test StudyLance email function
echo "<h2>Testing StudyLance sendEmail() function...</h2>";
$testBody = "
<html>
<body>
    <h2>Test Email from StudyLance</h2>
    <p>This is a test email to verify the email functionality is working.</p>
    <p>Time: " . date('Y-m-d H:i:s') . "</p>
    <p>Best regards,<br>StudyLance Team</p>
</body>
</html>";

if (sendEmail($to, "StudyLance Test Email", $testBody)) {
    echo "✅ sendEmail() function executed<br>";
    echo "📝 Check your error logs for email details<br>";
} else {
    echo "❌ sendEmail() function failed<br>";
}

// Show current email configuration
echo "<h2>Current Email Configuration:</h2>";
echo "<strong>EMAIL_HOST:</strong> " . EMAIL_HOST . "<br>";
echo "<strong>EMAIL_PORT:</strong> " . EMAIL_PORT . "<br>";
echo "<strong>EMAIL_USERNAME:</strong> " . (EMAIL_USERNAME ? "Set" : "Not set") . "<br>";
echo "<strong>EMAIL_PASSWORD:</strong> " . (EMAIL_PASSWORD ? "Set" : "Not set") . "<br>";
echo "<strong>EMAIL_FROM:</strong> " . EMAIL_FROM . "<br>";

// Check if mail function is available
echo "<h2>Mail Function Status:</h2>";
if (function_exists('mail')) {
    echo "✅ mail() function is available<br>";
} else {
    echo "❌ mail() function is not available<br>";
}

// Show PHP mail configuration
echo "<h2>PHP Mail Configuration:</h2>";
echo "<strong>sendmail_path:</strong> " . ini_get('sendmail_path') . "<br>";
echo "<strong>SMTP:</strong> " . ini_get('SMTP') . "<br>";
echo "<strong>smtp_port:</strong> " . ini_get('smtp_port') . "<br>";
echo "<strong>mail.add_x_header:</strong> " . ini_get('mail.add_x_header') . "<br>";

echo "<br><h3>📝 Next Steps:</h3>";
echo "<ol>";
echo "<li>Check your email (including spam folder)</li>";
echo "<li>Check XAMPP error logs: /Applications/XAMPP/xamppfiles/logs/error_log</li>";
echo "<li>If no emails arrive, configure SMTP in email_config.php</li>";
echo "<li>For development, consider using MailHog to catch emails locally</li>";
echo "</ol>";

echo "<br><a href='client_dashboard_new.php'>← Back to Dashboard</a>";
?>
