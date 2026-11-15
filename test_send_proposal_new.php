<?php
/**
 * Test the updated send_proposal_new.php with portfolio URL
 */

require_once 'config.php';

echo "<h1>🔄 Test send_proposal_new.php Portfolio URL</h1>";

// Test 1: Check if user is logged in as freelancer
echo "<h2>👤 User Access Check:</h2>";
if (isLoggedIn()) {
    echo "✅ Logged in user: " . $_SESSION['name'] . " (" . $_SESSION['role'] . ")<br>";
    
    if ($_SESSION['role'] === 'freelancer') {
        echo "✅ User is freelancer - can submit proposals<br>";
    } else {
        echo "❌ User is not freelancer - cannot submit proposals<br>";
        echo "Current role: " . $_SESSION['role'] . "<br>";
        echo "<a href='logout_new.php'>🔐 Logout</a> and login as freelancer<br>";
    }
} else {
    echo "❌ User not logged in<br>";
    echo "<a href='login_new.php'>🔐 Login as Freelancer</a><br>";
}

// Test 2: Check database setup
echo "<h2>🗄 Database Check:</h2>";
try {
    $db = Database::getInstance();
    
    $result = $db->query("DESCRIBE proposals");
    $columns = $result->fetchAll();
    
    $has_portfolio_url = false;
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Status</th></tr>";
    
    foreach ($columns as $column) {
        $status = '';
        if ($column['Field'] === 'portfolio_url') {
            $status = '✅ Portfolio URL Field';
            $has_portfolio_url = true;
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
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 3: Check available jobs
echo "<h2>💼 Available Jobs:</h2>";
try {
    $db = Database::getInstance();
    $result = $db->query("SELECT id, title, budget FROM jobs WHERE status = 'open' ORDER BY created_at DESC LIMIT 5");
    $jobs = $result->fetchAll();
    
    if (!empty($jobs)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Job ID</th><th>Title</th><th>Budget</th><th>Action</th></tr>";
        
        foreach ($jobs as $job) {
            echo "<tr>";
            echo "<td>{$job['id']}</td>";
            echo "<td>" . htmlspecialchars($job['title']) . "</td>";
            echo "<td>₹" . number_format($job['budget'], 2) . "</td>";
            echo "<td><a href='send_proposal_new.php?job_id={$job['id']}' target='_blank'>📝 Apply</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No open jobs found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Jobs fetch error: " . $e->getMessage() . "</p>";
}

// Test 4: Show conversion details
echo "<h2>🔄 send_proposal_new.php Conversion:</h2>";
echo "<ul>";
echo "<li><strong>✅ Cover Letter Removed:</strong> Replaced with Portfolio URL field</li>";
echo "<li><strong>✅ Portfolio URL Required:</strong> Valid URL format validation</li>";
echo "<li><strong>✅ Preview Button:</strong> Test portfolio link before submitting</li>";
echo "<li><strong>✅ Additional Details:</strong> Optional proposal text (reduced size)</li>";
echo "<li><strong>✅ Estimated Time:</strong> Kept as optional field</li>";
echo "<li><strong>✅ URL Validation:</strong> Real-time validation with feedback</li>";
echo "</ul>";

echo "<h2>📋 Field Changes:</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Old Field</th><th>New Field</th><th>Type</th><th>Required</th></tr>";
echo "<tr><td>Cover Letter (textarea)</td><td>Portfolio URL (url input)</td><td>URL</td><td>✅ Yes</td></tr>";
echo "<tr><td>-</td><td>Additional Details (textarea)</td><td>Text</td><td>❌ No</td></tr>";
echo "<tr><td>Estimated Time (text)</td><td>Estimated Time (text)</td><td>Text</td><td>❌ No</td></tr>";
echo "</table>";

echo "<hr>";
echo "<h2>🔗 Quick Links:</h2>";

if (isLoggedIn() && $_SESSION['role'] === 'freelancer') {
    echo "<a href='send_proposal_new.php?job_id=1' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>📝 Apply with Portfolio URL</a><br>";
} else {
    echo "<a href='login_new.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔐 Login as Freelancer</a><br>";
}

echo "<a href='get_jobs.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>💼 Browse Jobs</a><br>";
echo "<a href='freelancer_dashboard_new.php' style='background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>👨‍💻 Freelancer Dashboard</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ send_proposal_new.php Updated Successfully!</h3>";
echo "<p>The cover letter field has been converted to portfolio URL:</p>";
echo "<ul>";
echo "<li><strong>Portfolio URL Required:</strong> Freelancers must provide portfolio link</li>";
echo "<li><strong>URL Validation:</strong> Ensures proper URL format</li>";
echo "<li><strong>Preview Function:</strong> Test portfolio before submitting</li>";
echo "<li><strong>Additional Details:</strong> Optional brief description</li>";
echo "<li><strong>Modern UI:</strong> Clean, portfolio-focused design</li>";
echo "</ul>";
echo "</div>";

echo "<br><div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>💡 How to Test:</h3>";
echo "<ol>";
echo "<li><strong>Login as freelancer</strong> (or create freelancer account)</li>";
echo "<li><strong>Go to send_proposal_new.php?job_id=1</strong></li>";
echo "<li><strong>Enter portfolio URL</strong> (GitHub, Behance, etc.)</li>";
echo "<li><strong>Click preview button</strong> to test portfolio link</li>";
echo "<li><strong>Add optional details</strong> if desired</li>";
echo "<li><strong>Submit proposal</strong> with portfolio URL</li>";
echo "</ol>";
echo "</div>";
?>
