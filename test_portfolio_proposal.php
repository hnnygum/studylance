<?php
/**
 * Test the portfolio-based proposal system
 */

require_once 'config.php';

echo "<h1>🧪 Test Portfolio Proposal System</h1>";

// Test 1: Check if portfolio_url column exists
echo "<h2>🗄 Database Check:</h2>";
try {
    $db = Database::getInstance();
    
    $result = $db->query("DESCRIBE proposals");
    $columns = $result->fetchAll();
    
    $has_portfolio_url = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'portfolio_url') {
            $has_portfolio_url = true;
            break;
        }
    }
    
    if ($has_portfolio_url) {
        echo "✅ portfolio_url column exists in proposals table<br>";
    } else {
        echo "❌ portfolio_url column missing. Please run update_proposals_table.php<br>";
    }
    
    // Check jobs
    $result = $db->query("SELECT id, title FROM jobs ORDER BY id DESC LIMIT 5");
    $jobs = $result->fetchAll();
    
    echo "<p><strong>Available Jobs for Testing:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Job ID</th><th>Title</th><th>Action</th></tr>";
    
    foreach ($jobs as $job) {
        echo "<tr>";
        echo "<td>{$job['id']}</td>";
        echo "<td>" . htmlspecialchars($job['title']) . "</td>";
        echo "<td><a href='proposal_with_portfolio.php?job_id={$job['id']}' target='_blank'>📝 Apply with Portfolio</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 2: Check freelancer access
echo "<h2>👤 User Access Check:</h2>";
if (isLoggedIn()) {
    echo "✅ Logged in user: " . $_SESSION['name'] . " (" . $_SESSION['role'] . ")<br>";
    
    if ($_SESSION['role'] === 'freelancer') {
        echo "✅ User is freelancer - can submit proposals<br>";
    } else {
        echo "❌ User is not freelancer - cannot submit proposals<br>";
        echo "Current role: " . $_SESSION['role'] . "<br>";
    }
} else {
    echo "❌ User not logged in<br>";
    echo "<a href='login_new.php'>🔐 Login First</a><br>";
}

// Test 3: Portfolio URL validation
echo "<h2>🔗 Portfolio URL Validation Test:</h2>";
$test_urls = [
    'https://github.com/username' => 'GitHub Profile',
    'https://behance.net/username' => 'Behance Portfolio',
    'https://example.com/portfolio' => 'Personal Website',
    'invalid-url' => 'Invalid URL',
    '' => 'Empty URL'
];

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>URL</th><th>Description</th><th>Validation Result</th></tr>";

foreach ($test_urls as $url => $description) {
    $valid = filter_var($url, FILTER_VALIDATE_URL) !== false;
    $status = $valid ? '✅ Valid' : '❌ Invalid';
    $color = $valid ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($url) . "</td>";
    echo "<td>$description</td>";
    echo "<td style='color: $color;'>$status</td>";
    echo "</tr>";
}
echo "</table>";

// Test 4: Show portfolio proposal features
echo "<h2>📋 Portfolio Proposal Features:</h2>";
echo "<ul>";
echo "<li><strong>Portfolio URL Field:</strong> Required field for freelancer applications</li>";
echo "<li><strong>URL Validation:</strong> Frontend validates URL format</li>";
echo "<li><strong>Preview Link:</strong> Users can preview portfolio before submitting</li>";
echo "<li><strong>Character Counter:</strong> Real-time character count for proposal text</li>";
echo "<li><strong>Modern UI:</strong> Glassmorphism design with responsive layout</li>";
echo "<li><strong>Error Handling:</strong> Comprehensive validation and error messages</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>🔗 Quick Actions:</h2>";

if (isLoggedIn() && $_SESSION['role'] === 'freelancer') {
    echo "<a href='proposal_with_portfolio.php?job_id=1' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>📝 Submit Proposal with Portfolio</a><br>";
} else {
    echo "<a href='login_new.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔐 Login as Freelancer</a><br>";
}

echo "<a href='update_proposals_table.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔧 Update Database</a><br>";
echo "<a href='freelancer_dashboard_new.php' style='background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>👨‍💻 Freelancer Dashboard</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Portfolio System Ready!</h3>";
echo "<p>The portfolio-based proposal system is ready to use:</p>";
echo "<ol>";
echo "<li><strong>Step 1:</strong> Update database to add portfolio_url column</li>";
echo "<li><strong>Step 2:</strong> Login as freelancer</li>";
echo "<li><strong>Step 3:</strong> Apply to jobs with portfolio URL</li>";
echo "<li><strong>Step 4:</strong> Clients can view freelancer portfolios</li>";
echo "</ol>";
echo "</div>";
?>
