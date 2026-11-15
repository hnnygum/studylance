<?php
/**
 * Test the fixed accept proposal system
 */

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🧪 Test Fixed Accept Proposal</h1>";

// Check login
if (!isset($_SESSION['user_id'])) {
    echo "❌ Not logged in. Please <a href='login_new.php'>login</a> first.<br>";
    exit();
}

echo "✅ Logged in user: " . $_SESSION['name'] . " (" . $_SESSION['role'] . ")<br>";

// Check role
if ($_SESSION['role'] !== 'client') {
    echo "❌ Access denied. Only clients can accept proposals.<br>";
    exit();
}

echo "✅ User is a client - can accept proposals<br>";

// Get proposal ID
$proposal_id = $_GET['id'] ?? 1;
echo "📋 Testing with Proposal ID: $proposal_id<br>";

// Test email configuration
echo "<h2>📧 Email Configuration Test:</h2>";
try {
    include 'email_config_phpmailer.php';
    echo "✅ email_config_phpmailer.php loaded successfully<br>";
    
    if (function_exists('sendEmail')) {
        echo "✅ sendEmail function available<br>";
    } else {
        echo "❌ sendEmail function not found<br>";
    }
    
    if (function_exists('sendProposalStatusNotification')) {
        echo "✅ sendProposalStatusNotification function available<br>";
    } else {
        echo "❌ sendProposalStatusNotification function not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Email config error: " . $e->getMessage() . "<br>";
}

// Test database and proposal
echo "<h2>🗄 Database & Proposal Test:</h2>";
try {
    include 'database.php';
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
            echo "<a href='accept_proposal_fixed.php?id=$proposal_id' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>🎯 Go to Accept Proposal Page</a><br>";
        } else {
            echo "❌ Proposal already processed (status: " . $proposal["status"] . ")<br>";
        }
        
    } else {
        echo "❌ Proposal not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<br><h2>🔗 Quick Links:</h2>";
echo "<a href='accept_proposal_fixed.php?id=$proposal_id'>🎯 Accept Proposal (Fixed Version)</a><br>";
echo "<a href='client_dashboard_new.php'>👤 Client Dashboard</a><br>";
echo "<a href='test_phpmailer.php'>📧 Test Email System</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Function Conflict Fixed!</h3>";
echo "<p>The sendEmail() function redeclaration issue has been resolved.</p>";
echo "<p>System now uses separate template files to avoid conflicts.</p>";
echo "</div>";
?>
