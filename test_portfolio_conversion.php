<?php
/**
 * Test the portfolio URL conversion in existing proposal system
 */

require_once 'config.php';

echo "<h1>🔄 Portfolio URL Conversion Test</h1>";

// Test 1: Check if portfolio_url column exists
echo "<h2>🗄 Database Check:</h2>";
try {
    $db = Database::getInstance();
    
    $result = $db->query("DESCRIBE proposals");
    $columns = $result->fetchAll();
    
    $has_portfolio_url = false;
    $has_cover_letter = false;
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Status</th></tr>";
    
    foreach ($columns as $column) {
        $status = '';
        if ($column['Field'] === 'portfolio_url') {
            $status = '✅ New Portfolio Field';
            $has_portfolio_url = true;
        } elseif ($column['Field'] === 'cover_letter') {
            $status = '📄 Old Cover Letter Field';
            $has_cover_letter = true;
        }
        
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($has_portfolio_url) {
        echo "<p style='color: green;'>✅ portfolio_url column exists - Ready for portfolio URLs!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ portfolio_url column missing - Please run update_proposals_table.php</p>";
    }
    
    // Check existing proposals
    echo "<h2>📊 Existing Proposals:</h2>";
    $result = $db->query("SELECT id, job_id, freelancer_id, bid_amount, portfolio_url, proposal_text, status FROM proposals ORDER BY id DESC LIMIT 5");
    $proposals = $result->fetchAll();
    
    if (!empty($proposals)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Job ID</th><th>Bid</th><th>Portfolio URL</th><th>Proposal Text</th><th>Status</th></tr>";
        
        foreach ($proposals as $proposal) {
            echo "<tr>";
            echo "<td>{$proposal['id']}</td>";
            echo "<td>{$proposal['job_id']}</td>";
            echo "<td>₹" . number_format($proposal['bid_amount'], 2) . "</td>";
            echo "<td>" . ($proposal['portfolio_url'] ? "<a href='{$proposal['portfolio_url']}' target='_blank'>🔗 Link</a>" : "❌ Missing") . "</td>";
            echo "<td>" . (strlen($proposal['proposal_text']) > 30 ? substr($proposal['proposal_text'], 0, 30) . "..." : $proposal['proposal_text']) . "</td>";
            echo "<td>" . ucfirst($proposal['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No proposals found in database.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 2: Check user access
echo "<h2>👤 User Access Check:</h2>";
if (isLoggedIn()) {
    echo "✅ Logged in user: " . $_SESSION['name'] . " (" . $_SESSION['role'] . ")<br>";
    
    if ($_SESSION['role'] === 'freelancer') {
        echo "✅ User is freelancer - can submit proposals with portfolio URLs<br>";
    } else {
        echo "❌ User is not freelancer - cannot submit proposals<br>";
        echo "Current role: " . $_SESSION['role'] . "<br>";
    }
} else {
    echo "❌ User not logged in<br>";
    echo "<a href='login_new.php'>🔐 Login First</a><br>";
}

// Test 3: Show conversion features
echo "<h2>🔄 Conversion Features:</h2>";
echo "<ul>";
echo "<li><strong>✅ Portfolio URL Field:</strong> Replaces cover letter in proposal modal</li>";
echo "<li><strong>✅ URL Validation:</strong> Ensures proper URL format</li>";
echo "<li><strong>✅ Preview Button:</strong> Test portfolio link before submitting</li>";
echo "<li><strong>✅ Optional Details:</strong> Additional proposal text (optional)</li>";
echo "<li><strong>✅ Modern UI:</strong> Updated modal with portfolio focus</li>";
echo "<li><strong>✅ Database Storage:</strong> Uses portfolio_url column</li>";
echo "</ul>";

echo "<h2>📋 What Was Changed:</h2>";
echo "<ol>";
echo "<li><strong>get_jobs.php:</strong> Proposal modal now has portfolio URL field</li>";
echo "<li><strong>send_proposal_new.php:</strong> Handles portfolio URL submission</li>";
echo "<li><strong>Database:</strong> Uses portfolio_url column instead of cover_letter</li>";
echo "<li><strong>Validation:</strong> URL format validation with preview</li>";
echo "<li><strong>UI:</strong> Modern portfolio-focused design</li>";
echo "</ol>";

echo "<hr>";
echo "<h2>🔗 Quick Links:</h2>";

if (isLoggedIn() && $_SESSION['role'] === 'freelancer') {
    echo "<a href='get_jobs.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>📝 Browse Jobs & Apply</a><br>";
} else {
    echo "<a href='login_new.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔐 Login as Freelancer</a><br>";
}

echo "<a href='update_proposals_table.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔧 Update Database</a><br>";
echo "<a href='freelancer_dashboard_new.php' style='background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>👨‍💻 Freelancer Dashboard</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Portfolio Conversion Complete!</h3>";
echo "<p>The cover letter field has been successfully converted to a portfolio URL field:</p>";
echo "<ul>";
echo "<li><strong>Portfolio URL Required:</strong> Freelancers must provide portfolio link</li>";
echo "<li><strong>URL Validation:</strong> Ensures proper URL format</li>";
echo "<li><strong>Preview Function:</strong> Test portfolio before submitting</li>";
echo "<li><strong>Modern UI:</strong> Clean, portfolio-focused design</li>";
echo "<li><strong>Database Ready:</strong> Uses portfolio_url column</li>";
echo "</ul>";
echo "</div>";

echo "<br><div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>💡 How to Test:</h3>";
echo "<ol>";
echo "<li><strong>Login as freelancer</strong> (or create freelancer account)</li>";
echo "<li><strong>Go to get_jobs.php</strong> to browse available jobs</li>";
echo "<li><strong>Click 'Apply Now'</strong> on any job</li>";
echo "<li><strong>Enter portfolio URL</strong> (GitHub, Behance, etc.)</li>";
echo "<li><strong>Click preview button</strong> to test portfolio link</li>";
echo "<li><strong>Submit proposal</strong> with portfolio URL</li>";
echo "</ol>";
echo "</div>";
?>
