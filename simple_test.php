<?php
// Simple test page for debugging
echo "<h1>🔍 Simple Test Page</h1>";

// Test 1: Basic PHP functionality
echo "<h2>📋 Basic PHP Test:</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";
echo "Memory Usage: " . memory_get_usage(true) . " bytes<br>";

// Test 2: File inclusion
echo "<h2>📁 File Inclusion Test:</h2>";
$files_to_test = [
    'config.php',
    'email_config_phpmailer.php',
    'src/PHPMailer.php'
];

foreach ($files_to_test as $file) {
    if (file_exists($file)) {
        echo "✅ $file - Exists and readable<br>";
        try {
            include_once $file;
            echo "✅ $file - Included successfully<br>";
        } catch (Exception $e) {
            echo "❌ $file - Include error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ $file - Does not exist<br>";
    }
}

// Test 3: Database connection
echo "<h2>🗄 Database Test:</h2>";
try {
    include 'database.php';
    if (isset($conn) && $conn) {
        echo "✅ Database connection successful<br>";
        
        // Test simple query
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "✅ Database query successful - Users count: $count<br>";
        } else {
            echo "❌ Database query failed<br>";
        }
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 4: Session functionality
echo "<h2>🔐 Session Test:</h2>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Data: " . (empty($_SESSION) ? 'Empty' : 'Has data') . "<br>";

// Test 5: PHPMailer functionality
echo "<h2>📧 PHPMailer Test:</h2>";
try {
    include_once 'email_config_phpmailer.php';
    
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "✅ PHPMailer class loaded<br>";
        
        // Test basic PHPMailer instantiation
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        echo "✅ PHPMailer instantiated successfully<br>";
        
    } else {
        echo "❌ PHPMailer class not found<br>";
    }
    
    if (function_exists('sendEmail')) {
        echo "✅ sendEmail function exists<br>";
    } else {
        echo "❌ sendEmail function not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ PHPMailer error: " . $e->getMessage() . "<br>";
}

// Test 6: Error reporting
echo "<h2>⚠️ Error Reporting Test:</h2>";
echo "Display Errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "<br>";
echo "Error Reporting: " . (error_reporting() ? 'ON' : 'OFF') . "<br>";
echo "Log Errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "<br>";

// Test 7: Form handling
echo "<h2>📝 Form Handling Test:</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "✅ POST request detected<br>";
    echo "POST Data: " . print_r($_POST, true) . "<br>";
} else {
    echo "ℹ️ GET request detected<br>";
}

echo "<hr>";
echo "<h2>🔧 Quick Actions:</h2>";
echo "<a href='test_phpmailer.php'>📧 Test PHPMailer Email</a><br>";
echo "<a href='client_dashboard_new.php'>👤 Client Dashboard</a><br>";
echo "<a href='diagnostic.php'>🔍 Run Full Diagnostic</a><br>";
echo "<a href='phpmailer_ready.php'>📦 Check PHPMailer Installation</a>";

echo "<hr>";
echo "<h3>💡 Debugging Tips:</h3>";
echo "<ul>";
echo "<li><strong>Blank pages:</strong> Check error logs and enable display_errors</li>";
echo "<li><strong>Class not found:</strong> Check file paths and include statements</li>";
echo "<li><strong>Database errors:</strong> Verify connection credentials and permissions</li>";
echo "<li><strong>Session issues:</strong> Clear cookies and restart Apache</li>";
echo "</ul>";

// Test form submission
if (isset($_GET['test_form'])) {
    echo "<h2>📝 Form Test Submission</h2>";
    echo "<form method='post'>";
    echo "<input type='text' name='test_input' placeholder='Enter test value'>";
    echo "<input type='submit' value='Submit Test'>";
    echo "</form>";
}
?>
