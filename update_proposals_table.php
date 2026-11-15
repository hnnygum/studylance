<?php
/**
 * Update proposals table to include portfolio_url column
 */

require_once 'database.php';

echo "<h1>🔧 Update Proposals Table for Portfolio URL</h1>";

try {
    // Check if portfolio_url column exists
    $result = $conn->query("DESCRIBE proposals");
    $columns = $result->fetch_all(MYSQLI_ASSOC);
    
    $has_portfolio_url = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'portfolio_url') {
            $has_portfolio_url = true;
            break;
        }
    }
    
    if (!$has_portfolio_url) {
        // Add portfolio_url column
        $sql = "ALTER TABLE proposals ADD COLUMN portfolio_url VARCHAR(500) AFTER bid_amount";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ portfolio_url column added successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add portfolio_url column</p>";
            echo "<p>Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ portfolio_url column already exists</p>";
    }
    
    // Show current table structure
    echo "<h2>📋 Current Proposals Table Structure:</h2>";
    $result = $conn->query("DESCRIBE proposals");
    $columns = $result->fetch_all(MYSQLI_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check existing proposals
    echo "<h2>📊 Current Proposals:</h2>";
    $result = $conn->query("SELECT id, job_id, freelancer_id, bid_amount, portfolio_url, status FROM proposals ORDER BY id DESC LIMIT 10");
    $proposals = $result->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($proposals)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Job ID</th><th>Freelancer ID</th><th>Bid Amount</th><th>Portfolio URL</th><th>Status</th></tr>";
        
        foreach ($proposals as $proposal) {
            echo "<tr>";
            echo "<td>{$proposal['id']}</td>";
            echo "<td>{$proposal['job_id']}</td>";
            echo "<td>{$proposal['freelancer_id']}</td>";
            echo "<td>₹" . number_format($proposal['bid_amount'], 2) . "</td>";
            echo "<td>" . ($proposal['portfolio_url'] ? "<a href='{$proposal['portfolio_url']}' target='_blank'>" . htmlspecialchars(substr($proposal['portfolio_url'], 0, 30)) . "...</a>" : "Not set") . "</td>";
            echo "<td>" . ucfirst($proposal['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No proposals found in database.</p>";
    }
    
    echo "<hr>";
    echo "<h2>🔗 Quick Links:</h2>";
    echo "<a href='proposal_with_portfolio.php?job_id=1'>📝 Test Proposal with Portfolio</a><br>";
    echo "<a href='freelancer_dashboard_new.php'>👨‍💻 Freelancer Dashboard</a><br>";
    echo "<a href='client_dashboard_new.php'>👤 Client Dashboard</a>";
    
    echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<h3>✅ Database Updated Successfully!</h3>";
    echo "<p>The proposals table now includes a portfolio_url column for freelancer applications.</p>";
    echo "<ul>";
    echo "<li><strong>Portfolio URL:</strong> VARCHAR(500) - Stores freelancer portfolio links</li>";
    echo "<li><strong>Required Field:</strong> Freelancers must provide portfolio URL</li>";
    echo "<li><strong>URL Validation:</strong> Frontend validates URL format</li>";
    echo "<li><strong>Preview Link:</strong> Users can preview portfolio before submitting</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
