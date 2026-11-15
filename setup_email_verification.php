<?php
// Setup email verification system
require_once 'config.php';

echo "<h1>📧 Email Verification Setup</h1>";

// Step 1: Check current email configuration
echo "<h2>📋 Current Email Configuration:</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$email_configs = [
    'EMAIL_HOST' => defined('EMAIL_HOST') ? EMAIL_HOST : 'Not defined',
    'EMAIL_PORT' => defined('EMAIL_PORT') ? EMAIL_PORT : 'Not defined',
    'EMAIL_USERNAME' => defined('EMAIL_USERNAME') ? (EMAIL_USERNAME ? 'Set' : 'Empty') : 'Not defined',
    'EMAIL_PASSWORD' => defined('EMAIL_PASSWORD') ? (EMAIL_PASSWORD ? 'Set' : 'Empty') : 'Not defined',
    'EMAIL_FROM' => defined('EMAIL_FROM') ? EMAIL_FROM : 'Not defined'
];

foreach ($email_configs as $key => $value) {
    $status = ($value !== 'Not defined' && $value !== 'Empty') ? '✅' : '❌';
    echo "<tr><td>$key</td><td>$value</td><td>$status</td></tr>";
}
echo "</table>";

// Step 2: Test email configuration
echo "<h2>🧪 Test Email Configuration:</h2>";

if (defined('EMAIL_USERNAME') && EMAIL_USERNAME && defined('EMAIL_PASSWORD') && EMAIL_PASSWORD) {
    echo "<p>✅ Email configuration appears to be set up</p>";
    
    // Create test email
    $test_email = isset($_POST['test_email']) ? $_POST['test_email'] : '';
    
    if ($test_email && filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        include 'email_config.php';
        
        $subject = "StudyLance Email Test";
        $body = "
        <html>
        <body>
            <h2>🎉 Email Test Successful!</h2>
            <p>This is a test email from StudyLance to verify that email sending is working correctly.</p>
            <p><strong>Sent at:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>From:</strong> StudyLance System</p>
            <hr>
            <p><em>If you receive this email, your email configuration is working perfectly!</em></p>
        </body>
        </html>";
        
        if (sendEmail($test_email, $subject, $body)) {
            echo "<p style='color: green;'>✅ Test email sent to $test_email</p>";
            echo "<p>Check your inbox (and spam folder) for the test email.</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to send test email</p>";
        }
    }
    
    echo "<form method='post'>";
    echo "<p><strong>Send test email to:</strong></p>";
    echo "<input type='email' name='test_email' placeholder='Enter your email address' required style='padding: 8px; width: 300px;'>";
    echo "<input type='submit' value='📧 Send Test Email' style='padding: 8px 16px; margin-left: 10px;'>";
    echo "</form>";
    
} else {
    echo "<p style='color: red;'>❌ Email configuration not complete</p>";
    echo "<p>Please configure EMAIL_USERNAME and EMAIL_PASSWORD in email_config.php</p>";
}

// Step 3: Setup email verification for users
echo "<h2>🔐 Email Verification for Users:</h2>";

// Add email verification column to users table if it doesn't exist
try {
    $db = Database::getInstance();
    
    // Check if email_verified column exists
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    $has_email_verified = false;
    $has_verification_token = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'email_verified') $has_email_verified = true;
        if ($column['Field'] === 'verification_token') $has_verification_token = true;
    }
    
    if (!$has_email_verified) {
        $db->query("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
        echo "<p>✅ Added email_verified column</p>";
    }
    
    if (!$has_verification_token) {
        $db->query("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) DEFAULT NULL");
        echo "<p>✅ Added verification_token column</p>";
    }
    
    echo "<p>✅ Database updated for email verification</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database update failed: " . $e->getMessage() . "</p>";
}

// Step 4: Instructions
echo "<h2>📋 Setup Instructions:</h2>";
echo "<ol>";
echo "<li><strong>Configure Gmail SMTP:</strong></li>";
echo "<ul>";
echo "<li>Go to your Google Account settings</li>";
echo "<li>Enable 2-Step Verification</li>";
echo "<li>Generate an App Password</li>";
echo "<li>Update email_config.php with your credentials</li>";
echo "</ul>";
echo "<li><strong>Test Configuration:</strong></li>";
echo "<ul>";
echo "<li>Use the test form above to send a test email</li>";
echo "<li>Check if you receive the email</li>";
echo "</ul>";
echo "<li><strong>Enable Email Verification:</strong></li>";
echo "<ul>";
echo "<li>Update registration form to send verification emails</li>";
echo "<li>Add verification link handling</li>";
echo "<li>Update login to check email verification</li>";
echo "</ul>";
echo "</ol>";

echo "<br>";
echo "<h3>🔗 Quick Links:</h3>";
echo "<a href='email_config.php'>✏️ Edit Email Config</a><br>";
echo "<a href='test_email.php'>🧪 Test Email Again</a><br>";
echo "<a href='login_new.php'>🔐 Go to Login</a>";

echo "<br><br>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<h3>💡 Pro Tip:</h3>";
echo "<p>For development, you can use <a href='https://github.com/mailhog/mailhog' target='_blank'>MailHog</a> to catch all emails locally without sending them to real addresses.</p>";
echo "</div>";
?>
