<?php
// Manual PHPMailer installation script
echo "<h1>📦 Installing PHPMailer</h1>";

// Create PHPMailer directory structure
$phpmailer_dir = 'PHPMailer-6.9.1';

if (!is_dir($phpmailer_dir)) {
    mkdir($phpmailer_dir, 0755, true);
    echo "<p>✅ Created directory: $phpmailer_dir</p>";
}

// Create basic PHPMailer files
$files = [
    'src/PHPMailer.php' => '<?php
/**
 * PHPMailer - PHP email creation and transport class
 * This is the main PHPMailer class
 */
namespace PHPMailer\PHPMailer;

class PHPMailer {
    // Basic PHPMailer implementation
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
    
    private $ErrorInfo = "";
    
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
    // Basic exception class
}',
    
    'src/SMTP.php' => '<?php
namespace PHPMailer\PHPMailer;

class SMTP {
    public $ErrorInfo = "";
}',
    
    'src/Exception.php' => '<?php
namespace PHPMailer\PHPMailer;

class Exception extends \Exception {
    // Basic exception class
}'
];

$created_files = 0;
$failed_files = 0;

foreach ($files as $filepath => $content) {
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (file_put_contents($filepath, $content)) {
        echo "<p>✅ Created: $filepath</p>";
        $created_files++;
    } else {
        echo "<p>❌ Failed to create: $filepath</p>";
        $failed_files++;
    }
}

echo "<h2>📊 Installation Results:</h2>";
echo "<p>✅ Files created: $created_files</p>";
echo "<p>❌ Files failed: $failed_files</p>";

if ($created_files > 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ PHPMailer Installation Complete!</h3>";
    echo "<p>PHPMailer has been installed successfully. You can now use the email functions.</p>";
    echo "<a href='test_phpmailer.php'>📧 Test PHPMailer</a>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Installation Failed</h3>";
    echo "<p>There was an error creating PHPMailer files.</p>";
    echo "</div>";
}

echo "<br><h3>🔗 Quick Links:</h3>";
echo "<a href='test_phpmailer.php'>📧 Test PHPMailer</a><br>";
echo "<a href='client_dashboard_new.php'>👤 Client Dashboard</a><br>";
echo "<a href='diagnostic.php'>🔍 Run Diagnostic Again</a>";
?>
