<?php
// Complete diagnostic tool to identify page issues
echo "<h1>🔍 StudyLance System Diagnostic</h1>";

// Check 1: PHP Version and Extensions
echo "<h2>📋 PHP Environment:</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Required Extensions Status:<br>";

$extensions = [
    'mysqli' => extension_loaded('mysqli'),
    'mbstring' => extension_loaded('mbstring'),
    'json' => extension_loaded('json'),
    'session' => extension_loaded('session'),
    'openssl' => extension_loaded('openssl'),
    'curl' => extension_loaded('curl')
];

foreach ($extensions as $ext => $loaded) {
    $status = $loaded ? '✅' : '❌';
    echo "$ext: $status<br>";
}

// Check 2: File Permissions
echo "<h2>📁 File Permissions:</h2>";
$important_files = [
    'config.php' => is_readable('config.php') && is_writable('config.php'),
    'database.php' => is_readable('database.php'),
    'email_config_phpmailer.php' => is_readable('email_config_phpmailer.php'),
    'src/PHPMailer.php' => is_readable('src/PHPMailer.php'),
    'src/SMTP.php' => is_readable('src/SMTP.php'),
    'src/Exception.php' => is_readable('src/Exception.php')
];

foreach ($important_files as $file => $accessible) {
    $status = $accessible ? '✅' : '❌';
    echo "$file: $status<br>";
}

// Check 3: Database Connection
echo "<h2>🗄 Database Connection:</h2>";
try {
    include 'database.php';
    if (isset($conn) && $conn) {
        echo "✅ Database connection successful<br>";
        
        // Test query
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $count = $result->fetch_assoc()['count'];
        echo "Users in database: $count<br>";
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Check 4: Session Status
echo "<h2>🔐 Session Status:</h2>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? '✅ Active' : '❌ Inactive') . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Data: " . (empty($_SESSION) ? 'Empty' : 'Contains data') . "<br>";

if (isset($_SESSION['user_id'])) {
    echo "Logged in User ID: " . $_SESSION['user_id'] . "<br>";
    echo "User Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
}

// Check 5: Error Reporting
echo "<h2>⚠️ Error Reporting:</h2>";
echo "Display Errors: " . (ini_get('display_errors') ? '✅ On' : '❌ Off') . "<br>";
echo "Error Reporting: " . (error_reporting() ? '✅ On' : '❌ Off') . "<br>";
echo "Log Errors: " . (ini_get('log_errors') ? '✅ On' : '❌ Off') . "<br>";

// Check 6: Apache/Server Info
echo "<h2>🌐 Server Information:</h2>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "<br>";
echo "HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "<br>";

// Check 7: Recent Errors
echo "<h2>📝 Recent Error Log Entries:</h2>";
$error_log = '/Applications/XAMPP/xamppfiles/logs/error_log';
if (file_exists($error_log)) {
    $lines = file($error_log);
    $recent_lines = array_slice($lines, -10); // Last 10 lines
    
    foreach ($recent_lines as $line) {
        if (trim($line)) {
            echo htmlspecialchars($line) . "<br>";
        }
    }
} else {
    echo "Error log file not found<br>";
}

// Check 8: URL Testing
echo "<h2>🔗 URL Accessibility Test:</h2>";
$test_urls = [
    'Homepage' => 'http://localhost/studylance/',
    'Login' => 'http://localhost/studylance/login_new.php',
    'Client Dashboard' => 'http://localhost/studylance/client_dashboard_new.php',
    'Admin Dashboard' => 'http://localhost/studylance/admin_dashboard_new.php',
    'PHPMailer Test' => 'http://localhost/studylance/test_phpmailer.php'
];

foreach ($test_urls as $name => $url) {
    echo "<strong>$name:</strong> <a href='$url' target='_blank'>$url</a><br>";
}

// Check 9: Memory and Limits
echo "<h2>💾 PHP Limits:</h2>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "s<br>";
echo "Max Upload Size: " . ini_get('upload_max_filesize') . "<br>";
echo "Max POST Size: " . ini_get('post_max_size') . "<br>";

// Check 10: XAMPP Status
echo "<h2>🔧 XAMPP Status:</h2>";
$xampp_processes = [
    'Apache' => shell_exec('ps aux | grep httpd | wc -l'),
    'MySQL' => shell_exec('ps aux | grep mysqld | wc -l')
];

foreach ($xampp_processes as $service => $count) {
    $status = trim($count) > 0 ? '✅ Running' : '❌ Stopped';
    echo "$service: $status<br>";
}

echo "<br><h2>🔧 Quick Fixes:</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<h3>Common Issues & Solutions:</h3>";
echo "<ul>";
echo "<li><strong>Blank pages:</strong> Check error logs and enable error reporting</li>";
echo "<li><strong>Database errors:</strong> Verify MySQL is running and credentials are correct</li>";
echo "<li><strong>Permission denied:</strong> Check file permissions and .htaccess rules</li>";
echo "<li><strong>Session issues:</strong> Clear browser cookies and restart Apache</li>";
echo "<li><strong>Email not working:</strong> Configure SMTP settings properly</li>";
echo "</ul>";
echo "</div>";

echo "<br><h3>🔗 Quick Actions:</h3>";
echo "<a href='test_phpmailer.php'>📧 Test PHPMailer</a> | ";
echo "<a href='check_users.php'>👥 Check Users</a> | ";
echo "<a href='fix_admin_password.php'>🔧 Fix Admin Password</a> | ";
echo "<a href='client_dashboard_new.php'>👤 Client Dashboard</a>";

echo "<br><br>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>💡 Next Steps:</h3>";
echo "<p>1. Review the diagnostic results above</p>";
echo "<p>2. Fix any issues identified</p>";
echo "<p>3. Test the specific page that's not working</p>";
echo "<p>4. Check Apache error logs for specific error messages</p>";
echo "</div>";
?>
