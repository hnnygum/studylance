<?php
// Test PHPMailer configuration and functionality
require_once 'config.php';
include 'email_config_phpmailer.php';

echo "<h1>📧 PHPMailer Setup & Test</h1>";

// Step 1: Check PHPMailer installation
echo "<h2>📋 PHPMailer Installation Check:</h2>";

if (file_exists('src/PHPMailer.php') && file_exists('src/SMTP.php') && file_exists('src/Exception.php')) {
    echo "<p style='color: green;'>✅ PHPMailer library is installed</p>";
} else {
    echo "<p style='color: red;'>❌ PHPMailer library not found</p>";
    echo "<p>Please download PHPMailer or check installation</p>";
}

// Step 2: Check email configuration
echo "<h2>⚙️ Email Configuration:</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$email_configs = [
    'EMAIL_HOST' => defined('EMAIL_HOST') ? EMAIL_HOST : 'Not defined',
    'EMAIL_PORT' => defined('EMAIL_PORT') ? EMAIL_PORT : 'Not defined',
    'EMAIL_USERNAME' => defined('EMAIL_USERNAME') ? (EMAIL_USERNAME ? 'Set' : 'Empty') : 'Not defined'),
    'EMAIL_PASSWORD' => defined('EMAIL_PASSWORD') ? (EMAIL_PASSWORD ? 'Set' : 'Empty') : 'Not defined'),
    'EMAIL_FROM' => defined('EMAIL_FROM') ? EMAIL_FROM : 'Not defined'
];

foreach ($email_configs as $key => $value) {
    $status = ($value !== 'Not defined' && $value !== 'Empty') ? '✅' : '❌';
    echo "<tr><td>$key</td><td>$value</td><td>$status</td></tr>";
}
echo "</table>";

// Step 3: Test email sending
echo "<h2>🧪 Test PHPMailer Email:</h2>";

if (isset($_POST['test_email']) && filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL)) {
    $test_email = $_POST['test_email'];
    
    echo "<p>Testing PHPMailer with email: $test_email</p>";
    
    $result = testPHPMailerConfig($test_email);
    
    if ($result['success']) {
        echo "<div style='color: green; font-size: 18px;'>";
        echo "✅ <strong>PHPMailer Test Successful!</strong><br>";
        echo "</div>";
        echo "<p>" . $result['message'] . "</p>";
        echo "<p>Check your inbox for the test email.</p>";
    } else {
        echo "<div style='color: red; font-size: 18px;'>";
        echo "❌ <strong>PHPMailer Test Failed!</strong><br>";
        echo "</div>";
        echo "<p><strong>Error:</strong> " . $result['message'] . "</p>";
        if (isset($result['error_info'])) {
            echo "<p><strong>SMTP Error:</strong> " . $result['error_info'] . "</p>";
        }
    }
    
    echo "<br><h3>📊 Test Results Summary:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Component</th><th>Status</th></tr>";
    echo "<tr><td>PHPMailer Library</td><td>✅ Installed</td></tr>";
    echo "<tr><td>Email Configuration</td><td>" . ($result['success'] ? '✅ Working' : '❌ Failed') . "</td></tr>";
    echo "<tr><td>SMTP Connection</td><td>" . ($result['success'] ? '✅ Connected' : '❌ Failed') . "</td></tr>";
    echo "</table>";
}

echo "<form method='post'>";
echo "<p><strong>Send test email via PHPMailer to:</strong></p>";
echo "<input type='email' name='test_email' placeholder='Enter your email address' required style='padding: 8px; width: 300px;'>";
echo "<input type='submit' value='📧 Send PHPMailer Test' style='padding: 8px 16px; margin-left: 10px;'>";
echo "</form>";

// Step 4: Setup instructions
echo "<h2>📋 PHPMailer Setup Instructions:</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<h3>🔧 Configure Gmail SMTP:</h3>";
echo "<ol>";
echo "<li><strong>Enable 2-Step Verification:</strong> Go to Google Account settings</li>";
echo "<li><strong>Generate App Password:</strong> Create app-specific password</li>";
echo "<li><strong>Update Configuration:</strong> Edit email_config_phpmailer.php</li>";
echo "</ol>";

echo "<h3>📧 Update Configuration:</h3>";
echo "<code>";
echo "define('EMAIL_USERNAME', 'your-gmail@gmail.com');<br>";
echo "define('EMAIL_PASSWORD', 'your-16-digit-app-password');<br>";
echo "define('EMAIL_FROM', 'your-gmail@gmail.com');<br>";
echo "</code>";
echo "</div>";

// Step 5: Benefits of PHPMailer
echo "<h2>🎯 PHPMailer Benefits:</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<ul>";
echo "<li>✅ <strong>Reliable SMTP:</strong> Better than basic mail() function</li>";
echo "<li>✅ <strong>Professional Headers:</strong> Proper email formatting</li>";
echo "<li>✅ <strong>Error Handling:</strong> Detailed error messages</li>";
echo "<li>✅ <strong>Authentication:</strong> Secure SMTP authentication</li>";
echo "<li>✅ <strong>Attachments:</strong> Support for file attachments</li>";
echo "<li>✅ <strong>HTML Emails:</strong> Rich formatting support</li>";
echo "</ul>";
echo "</div>";

echo "<br>";
echo "<h3>🔗 Quick Links:</h3>";
echo "<a href='accept_proposal_phpmailer.php'>📧 Test Accept with PHPMailer</a><br>";
echo "<a href='client_dashboard_new.php'>👤 Client Dashboard</a><br>";
echo "<a href='freelancer_dashboard_new.php'>👨‍💻 Freelancer Dashboard</a>";

echo "<br><br>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>💡 Pro Tip:</h3>";
echo "<p>PHPMailer is the industry standard for sending emails in PHP. It's much more reliable than the basic mail() function and provides better error handling and debugging capabilities.</p>";
echo "</div>";
?>
