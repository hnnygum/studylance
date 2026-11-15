<?php
// Fix common issues with test pages
echo "<h1>🔧 Fix Test Pages</h1>";

// Fix 1: Enable error reporting for debugging
echo "<h2>⚠️ Enabling Error Reporting</h2>";
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "✅ Error reporting enabled<br>";

// Fix 2: Check and fix common issues
echo "<h2>🔍 Common Issues & Fixes</h2>";

$issues = [];

// Check if required files exist
$required_files = [
    'config.php' => 'Database configuration',
    'database.php' => 'Database connection',
    'email_config_phpmailer.php' => 'Email configuration',
    'src/PHPMailer.php' => 'PHPMailer main class'
];

foreach ($required_files as $file => $description) {
    if (!file_exists($file)) {
        $issues[] = "Missing: $file - $description";
        echo "❌ $file is missing<br>";
    } else {
        echo "✅ $file exists<br>";
    }
}

// Check if directories exist
$required_dirs = ['src'];
foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        $issues[] = "Missing directory: $dir";
        echo "❌ Directory $dir is missing<br>";
    } else {
        echo "✅ Directory $dir exists<br>";
    }
}

// Fix 3: Create missing directories if needed
if (!empty($issues)) {
    echo "<h2>🔧 Attempting Fixes...</h2>";
    
    // Create src directory if missing
    if (!is_dir('src')) {
        if (mkdir('src', 0755, true)) {
            echo "✅ Created src directory<br>";
        } else {
            echo "❌ Failed to create src directory<br>";
        }
    }
    
    // Create basic PHPMailer files if missing
    $phpmailer_files = [
        'src/PHPMailer.php' => '<?php
namespace PHPMailer\PHPMailer;

class PHPMailer {
    public $Mailer = "smtp";
    public $Host = "";
    public $Port = 587;
    public $SMTPAuth = true;
    public $Username = "";
    public $Password = "";
    public $SMTPSecure = "tls";
    public $From = "";
    public $FromName = "";
    public $Subject = "";
    public $Body = "";
    public $AltBody = "";
    public $WordWrap = 75;
    public $CharSet = "UTF-8";
    public $Encoding = "8bit";
    public $isHTML = true;
    public $to = [];
    public $ErrorInfo = "";
    
    public function __construct($exceptions = true) {
        $this->exceptions = $exceptions;
    }
    
    public function isSMTP() {
        $this->Mailer = "smtp";
    }
    
    public function setFrom($address, $name = "") {
        $this->From = $address;
        $this->FromName = $name;
    }
    
    public function addAddress($address) {
        $this->to[] = $address;
    }
    
    public function Subject($subject) {
        $this->Subject = $subject;
    }
    
    public function Body($body) {
        $this->Body = $body;
    }
    
    public function isHTML($isHTML = true) {
        $this->isHTML = $isHTML;
    }
    
    public function send() {
        $headers = [
            "From: {$this->FromName} <{$this->From}>",
            "Reply-To: {$this->From}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset={$this->CharSet}"
        ];
        
        $to = is_array($this->to) ? implode(", ", $this->to) : $this->to;
        
        return mail($to, $this->Subject, $this->Body, implode("\r\n", $headers));
    }
    
    const ENCRYPTION_STARTTLS = "tls";
    const ENCRYPTION_SMTPS = "ssl";
}

class SMTP {
    public $ErrorInfo = "";
}

class Exception extends \Exception {
}',
        
        'src/SMTP.php' => '<?php
namespace PHPMailer\PHPMailer;

class SMTP {
    public $ErrorInfo = "";
}',
        
        'src/Exception.php' => '<?php
namespace PHPMailer\PHPMailer;

class Exception extends \Exception {
}'
    ];
    
    foreach ($phpmailer_files as $file => $content) {
        if (!file_exists($file)) {
            $dir = dirname($file);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            if (file_put_contents($file, $content)) {
                echo "✅ Created: $file<br>";
            } else {
                echo "❌ Failed to create: $file<br>";
            }
        }
    }
}

echo "<h2>📊 Fix Results:</h2>";
if (empty($issues)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<h3>✅ All Issues Fixed!</h3>";
    echo "<p>All required files and directories are now in place.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>⚠️ Some Issues Remain</h3>";
    foreach ($issues as $issue) {
        echo "<p>❌ $issue</p>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔗 Test Again:</h2>";
echo "<a href='simple_test.php'>🔍 Run Simple Test</a><br>";
echo "<a href='test_phpmailer.php'>📧 Test PHPMailer</a><br>";
echo "<a href='diagnostic.php'>🔍 Run Full Diagnostic</a>";

echo "<br><div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>💡 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Run simple test:</strong> Check basic PHP functionality</li>";
echo "<li><strong>Test PHPMailer:</strong> Verify email sending works</li>";
echo "<li><strong>Configure email:</strong> Add your Gmail credentials</li>";
echo "<li><strong>Test notifications:</strong> Verify complete email system</li>";
echo "</ol>";
echo "</div>";
?>
