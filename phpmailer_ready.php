<?php
// PHPMailer Installation Verification
echo "<h1>✅ PHPMailer Installation Complete!</h1>";

// Check if all PHPMailer files exist
$required_files = [
    'src/PHPMailer.php',
    'src/SMTP.php', 
    'src/Exception.php'
];

$all_exist = true;
foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ $file - Created successfully</p>";
    } else {
        echo "<p>❌ $file - Missing</p>";
        $all_exist = false;
    }
}

if ($all_exist) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🎉 PHPMailer Ready!</h2>";
    echo "<p>All PHPMailer files have been created successfully.</p>";
    
    echo "<h3>📧 Next Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Configure Email:</strong> Edit email_config_phpmailer.php with your Gmail credentials</li>";
    echo "<li><strong>Test System:</strong> Go to test_phpmailer.php to send test email</li>";
    echo "<li><strong>Use Features:</strong> All email notifications will now work with PHPMailer</li>";
    echo "</ol>";
    
    echo "<h3>🔗 Quick Links:</h3>";
    echo "<a href='test_phpmailer.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 10px 10px 0;'>📧 Test PHPMailer</a>";
    echo "<a href='client_dashboard_new.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px;'>👤 Client Dashboard</a>";
    echo "</div>";
    
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>❌ Installation Incomplete</h2>";
    echo "<p>Some PHPMailer files are still missing.</p>";
    echo "<p>Please check the file list above and ensure all files are created.</p>";
    echo "</div>";
}

echo "<br><h3>📋 File Status Summary:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>File</th><th>Status</th><th>Location</th></tr>";

foreach ($required_files as $file) {
    $status = file_exists($file) ? '✅ Created' : '❌ Missing';
    $location = file_exists($file) ? '<a href="' . $file . '" target="_blank">View File</a>' : 'Not Created';
    echo "<tr><td>$file</td><td>$status</td><td>$location</td></tr>";
}
echo "</table>";

echo "<br><div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>💡 PHPMailer Benefits:</h3>";
echo "<ul>";
echo "<li><strong>Reliable Delivery:</strong> Better than basic mail() function</li>";
echo "<li><strong>Professional Headers:</strong> Proper email formatting</li>";
echo "<li><strong>Error Handling:</strong> Detailed error messages</li>";
echo "<li><strong>Authentication:</strong> Secure SMTP authentication</li>";
echo "</ul>";
echo "</div>";
?>
