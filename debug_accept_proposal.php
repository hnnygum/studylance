<?php
// Debug accept proposal system
echo "<h1>🔍 Debug Accept Proposal System</h1>";

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Test 1: Check all accept proposal files
echo "<h2>📁 Accept Proposal Files Check:</h2>";
$accept_files = [
    'accept_proposal.php' => 'Original accept proposal',
    'accept_proposal_minimal.php' => 'Minimal version',
    'accept_proposal_simple.php' => 'Simple version',
    'accept_proposal_with_email.php' => 'With email notifications',
    'accept_proposal_phpmailer.php' => 'PHPMailer version'
];

foreach ($accept_files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $file - $description<br>";
        
        // Check file size
        $size = filesize($file);
        echo "  Size: " . number_format($size) . " bytes<br>";
        
        // Check if it's readable
        if (is_readable($file)) {
            echo "  Status: ✅ Readable<br>";
        } else {
            echo "  Status: ❌ Not readable<br>";
        }
    } else {
        echo "❌ $file - $description (Missing)<br>";
    }
    echo "<br>";
}

// Test 2: Check database connection and proposals
echo "<h2>🗄 Database & Proposals Check:</h2>";
try {
    include 'database.php';
    
    if (isset($conn) && $conn) {
        echo "✅ Database connection successful<br>";
        
        // Check proposals table
        $result = $conn->query("SELECT COUNT(*) as count FROM proposals");
        $count = $result->fetch_assoc()['count'];
        echo "✅ Proposals in database: $count<br>";
        
        // Get sample proposals for testing
        $result = $conn->query("
            SELECT p.id, p.status, p.bid_amount,
                   j.title as job_title,
                   c.name as client_name,
                   f.name as freelancer_name
            FROM proposals p
            JOIN jobs j ON p.job_id = j.id
            JOIN users c ON j.client_id = c.id
            JOIN users f ON p.freelancer_id = f.id
            ORDER BY p.id DESC
            LIMIT 5
        ");
        
        if ($result->num_rows > 0) {
            echo "<p><strong>Recent Proposals:</strong></p>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Job</th><th>Client</th><th>Freelancer</th><th>Bid</th><th>Status</th><th>Test Link</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                $test_link = "<a href='accept_proposal_minimal.php?id={$row['id']}' target='_blank'>Test</a>";
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>" . htmlspecialchars($row['job_title']) . "</td>";
                echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['freelancer_name']) . "</td>";
                echo "<td>₹" . number_format($row['bid_amount'], 2) . "</td>";
                echo "<td>" . ucfirst($row['status']) . "</td>";
                echo "<td>$test_link</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ No proposals found in database<br>";
        }
        
    } else {
        echo "❌ Database connection failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Check session functionality
echo "<h2>🔐 Session Check:</h2>";
session_start();
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? '✅ Active' : '❌ Inactive') . "<br>";
echo "Session ID: " . session_id() . "<br>";

if (isset($_SESSION['user_id'])) {
    echo "✅ Logged in User ID: " . $_SESSION['user_id'] . "<br>";
    echo "✅ User Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
    echo "✅ User Name: " . ($_SESSION['name'] ?? 'Not set') . "<br>";
    
    if ($_SESSION['role'] === 'client') {
        echo "✅ User is a client (can accept proposals)<br>";
    } else {
        echo "❌ User is not a client (cannot accept proposals)<br>";
    }
} else {
    echo "❌ User not logged in<br>";
}

// Test 4: Test minimal accept proposal directly
echo "<h2>🧪 Direct Test of Minimal Accept Proposal:</h2>";
$test_id = 1; // Change this to a valid proposal ID

echo "<p>Testing with proposal ID: $test_id</p>";

// Create a simple test version
echo "<h3>📝 Creating Test Accept Proposal Page:</h3>";

$test_content = '<?php
// Test accept proposal - Debug version
session_start();
ini_set("display_errors", 1);
error_reporting(E_ALL);

echo "<h1>🧪 Test Accept Proposal Debug</h1>";

// Check login
if (!isset($_SESSION["user_id"])) {
    echo "❌ Not logged in. Please <a href=\"login_new.php\">login</a> first.<br>";
    echo "<a href=\"login_new.php\">🔐 Go to Login</a>";
    exit();
}

echo "✅ Logged in user: " . $_SESSION["name"] . " (" . $_SESSION["role"] . ")<br>";

// Check role
if ($_SESSION["role"] !== "client") {
    echo "❌ Access denied. Only clients can accept proposals.<br>";
    echo "Your role: " . $_SESSION["role"] . "<br>";
    echo "<a href=\"freelancer_dashboard_new.php\">👨‍💻 Go to Freelancer Dashboard</a>";
    exit();
}

echo "✅ User is a client - can accept proposals<br>";

// Get proposal ID
$proposal_id = $_GET["id"] ?? 0;
echo "📋 Proposal ID from URL: $proposal_id<br>";

if (!is_numeric($proposal_id) || $proposal_id <= 0) {
    echo "❌ Invalid proposal ID<br>";
    exit();
}

// Test database connection
try {
    include "database.php";
    echo "✅ Database connected<br>";
    
    // Get proposal details
    $sql = "SELECT p.*, j.title, u.name as freelancer_name 
            FROM proposals p 
            JOIN jobs j ON p.job_id = j.id 
            JOIN users u ON p.freelancer_id = u.id 
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $proposal = $result->fetch_assoc();
        echo "✅ Proposal found:<br>";
        echo "- Job: " . htmlspecialchars($proposal["title"]) . "<br>";
        echo "- Freelancer: " . htmlspecialchars($proposal["freelancer_name"]) . "<br>";
        echo "- Bid: ₹" . number_format($proposal["bid_amount"], 2) . "<br>";
        echo "- Status: " . ucfirst($proposal["status"]) . "<br>";
        
        if ($proposal["status"] === "pending") {
            echo "✅ Proposal is pending - can be accepted<br>";
            echo "<form method=\"post\">";
            echo "<input type=\"hidden\" name=\"accept\" value=\"1\">";
            echo "<button type=\"submit\" style=\"padding: 10px 20px; background: #27ae60; color: white; border: none; border-radius: 5px;\">✅ Accept This Proposal</button>";
            echo "</form>";
        } else {
            echo "❌ Proposal already processed (status: " . $proposal["status"] . ")<br>";
        }
        
    } else {
        echo "❌ Proposal not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<br><a href=\"client_dashboard_new.php\">👤 Back to Dashboard</a>";
?>';

file_put_contents('test_accept_debug.php', $test_content);
echo "✅ Created test_accept_debug.php<br>";
echo "<a href='test_accept_debug.php?id=$test_id' target='_blank'>🧪 Run Debug Test</a><br>";

echo "<hr>";
echo "<h2>🔗 Quick Actions:</h2>";
echo "<a href='test_accept_debug.php?id=1'>🧪 Test Accept Proposal (Debug)</a><br>";
echo "<a href='accept_proposal_minimal.php?id=1'>📋 Minimal Accept Proposal</a><br>";
echo "<a href='client_dashboard_new.php'>👤 Client Dashboard</a><br>";
echo "<a href='diagnostic.php'>🔍 Full System Diagnostic</a>";

echo "<br><div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>💡 Common Accept Proposal Issues:</h3>";
echo "<ul>";
echo "<li><strong>Not logged in:</strong> User must be logged in as client</li>";
echo "<li><strong>Wrong role:</strong> Only clients can accept proposals</li>";
echo "<li><strong>Invalid proposal ID:</strong> Proposal must exist in database</li>";
echo "<li><strong>Wrong status:</strong> Only pending proposals can be accepted</li>";
echo "<li><strong>Database errors:</strong> Check database connection</li>";
echo "<li><strong>Session issues:</strong> Clear cookies and re-login</li>";
echo "</ul>";
echo "</div>";
?>
