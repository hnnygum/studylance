<?php
/**
 * Test portfolio preview functionality for clients
 */

require_once 'config.php';

echo "<h1>🔗 Test Portfolio Preview for Clients</h1>";

// Check if user is logged in as client
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Please login as client to test portfolio preview</p>";
    echo "<a href='login_new.php'>🔐 Login</a>";
    exit();
}

if ($_SESSION['role'] !== 'client') {
    echo "<p style='color: red;'>❌ You must be logged in as client</p>";
    echo "<p>Current role: " . $_SESSION['role'] . "</p>";
    echo "<a href='logout_new.php'>🔐 Logout</a>";
    exit();
}

echo "<p style='color: green;'>✅ Logged in as client: " . $_SESSION['name'] . "</p>";

// Get proposals with portfolio URLs
echo "<h2>📋 Proposals with Portfolio URLs:</h2>";
try {
    $db = Database::getInstance();
    
    $result = $db->query("
        SELECT p.*, j.title as job_title, u.name as freelancer_name 
        FROM proposals p
        JOIN jobs j ON p.job_id = j.id
        JOIN users u ON p.freelancer_id = u.id
        WHERE j.client_id = ? AND p.portfolio_url IS NOT NULL AND p.portfolio_url != ''
        ORDER BY p.created_at DESC
    ", [$_SESSION['user_id']]);
    
    $proposals = $result->fetchAll();
    
    if (!empty($proposals)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Job</th><th>Freelancer</th><th>Bid</th><th>Portfolio URL</th><th>Status</th><th>Action</th></tr>";
        
        foreach ($proposals as $proposal) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($proposal['job_title']) . "</td>";
            echo "<td>" . htmlspecialchars($proposal['freelancer_name']) . "</td>";
            echo "<td>₹" . number_format($proposal['bid_amount'], 2) . "</td>";
            echo "<td>";
            echo "<a href='" . htmlspecialchars($proposal['portfolio_url']) . "' target='_blank' style='color: #6a5cff; text-decoration: none;'>🔗 " . htmlspecialchars(substr($proposal['portfolio_url'], 0, 30)) . "...</a>";
            echo "</td>";
            echo "<td>" . ucfirst($proposal['status']) . "</td>";
            echo "<td>";
            if ($proposal['status'] === 'pending') {
                echo "<a href='accept_proposal_clean.php?id=" . $proposal['id'] . "' style='background: #27ae60; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>👁️ Accept & Preview</a>";
            } else {
                echo "<span style='color: #888;'>Already processed</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No proposals with portfolio URLs found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Show portfolio preview features
echo "<h2>🔗 Portfolio Preview Features:</h2>";
echo "<ul>";
echo "<li><strong>🔗 Direct Link:</strong> Click portfolio URL to open in new tab</li>";
echo "<li><strong>👁️ Preview Button:</strong> Preview portfolio in modal window</li>";
echo "<li><strong>📱 Responsive:</strong> Works on all screen sizes</li>";
echo "<li><strong>⚡ Fast Loading:</strong> Quick preview without leaving page</li>";
echo "<li><strong>🔒 Secure:</strong> Opens in iframe sandbox</li>";
echo "<li><strong>⌨️ Keyboard:</strong> ESC key to close preview</li>";
echo "</ul>";

// Show example portfolio URLs
echo "<h2>📝 Example Portfolio URLs:</h2>";
echo "<div style='background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px;'>";
echo "<p><strong>GitHub:</strong> https://github.com/username</p>";
echo "<p><strong>Behance:</strong> https://www.behance.net/username</p>";
echo "<p><strong>Personal:</strong> https://yourwebsite.com/portfolio</p>";
echo "<p><strong>LinkedIn:</strong> https://linkedin.com/in/username</p>";
echo "</div>";

echo "<hr>";
echo "<h2>🔗 Quick Links:</h2>";
echo "<a href='client_dashboard_new.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>👤 Client Dashboard</a><br>";
echo "<a href='accept_proposal_clean.php?id=1' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>👁️ Test Accept Proposal</a><br>";
echo "<a href='freelancer_dashboard_new.php' style='background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>👨‍💻 Freelancer Dashboard</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Portfolio Preview System Ready!</h3>";
echo "<p>Clients can now preview freelancer portfolios before accepting proposals:</p>";
echo "<ul>";
echo "<li><strong>View Portfolio:</strong> Direct link to freelancer's work</li>";
echo "<li><strong>Preview Modal:</strong> In-page preview without leaving</li>";
echo "<li><strong>Easy Navigation:</strong> Multiple ways to access portfolio</li>";
echo "<li><strong>Informed Decisions:</strong> Review work before accepting</li>";
echo "<li><strong>Professional Experience:</strong> Modern portfolio preview system</li>";
echo "</ul>";
echo "</div>";
?>
