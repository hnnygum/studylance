<?php
// Simple working test for accept proposal
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🧪 Accept Proposal Working Test</h1>";

// Check login
if (!isset($_SESSION['user_id'])) {
    echo "❌ Not logged in. Please <a href='login_new.php'>login</a> first.<br>";
    echo "<a href='login_new.php'>🔐 Go to Login</a>";
    exit();
}

echo "✅ Logged in user: " . $_SESSION['name'] . " (" . $_SESSION['role'] . ")<br>";

// Check role
if ($_SESSION['role'] !== 'client') {
    echo "❌ Access denied. Only clients can accept proposals.<br>";
    echo "Your role: " . $_SESSION['role'] . "<br>";
    echo "<a href='freelancer_dashboard_new.php'>👨‍💻 Go to Freelancer Dashboard</a>";
    exit();
}

echo "✅ User is a client - can accept proposals<br>";

// Get proposal ID
$proposal_id = $_GET['id'] ?? 0;
echo "📋 Proposal ID from URL: $proposal_id<br>";

if (!is_numeric($proposal_id) || $proposal_id <= 0) {
    echo "❌ Invalid proposal ID<br>";
    exit();
}

// Test database connection
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
            echo "<form method='post'>";
            echo "<input type='hidden' name='accept' value='1'>";
            echo "<button type='submit' style='padding: 10px 20px; background: #27ae60; color: white; border: none; border-radius: 5px;'>✅ Accept This Proposal</button>";
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

echo "<br><a href='client_dashboard_new.php'>👤 Back to Dashboard</a>";
?>
