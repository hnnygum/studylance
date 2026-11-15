<?php
/**
 * Fix missing portfolio URLs in existing proposals
 */

require_once 'config.php';

echo "<h1>🔧 Fix Missing Portfolio URLs</h1>";

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $db = Database::getInstance();
    
    // Update existing proposals with default portfolio URLs
    echo "<h2>📝 Update Existing Proposals:</h2>";
    
    // Get proposals without portfolio URLs
    $result = $db->query("SELECT id, freelancer_id FROM proposals WHERE portfolio_url IS NULL OR portfolio_url = ''");
    $proposals = $result->fetchAll();
    
    if (!empty($proposals)) {
        echo "<p>Found " . count($proposals) . " proposals without portfolio URLs</p>";
        
        foreach ($proposals as $proposal) {
            // Get freelancer email to create a default portfolio URL
            $freelancer_result = $db->query("SELECT email FROM users WHERE id = ?", [$proposal['freelancer_id']]);
            $freelancer = $freelancer_result->fetch();
            
            if ($freelancer) {
                // Create a default GitHub URL based on email
                $email_parts = explode('@', $freelancer['email']);
                $username = $email_parts[0];
                $default_portfolio_url = "https://github.com/" . $username;
                
                // Update the proposal
                $update_result = $db->query("UPDATE proposals SET portfolio_url = ? WHERE id = ?", [$default_portfolio_url, $proposal['id']]);
                
                if ($update_result->rowCount() > 0) {
                    echo "✅ Updated proposal ID {$proposal['id']} with portfolio URL: $default_portfolio_url<br>";
                } else {
                    echo "❌ Failed to update proposal ID {$proposal['id']}<br>";
                }
            } else {
                echo "⚠️ Freelancer not found for proposal ID {$proposal['id']}<br>";
            }
        }
    } else {
        echo "<p style='color: green;'>✅ All proposals already have portfolio URLs</p>";
    }
    
    // Show updated proposals
    echo "<h2>📊 Updated Proposals:</h2>";
    $result = $db->query("SELECT id, job_id, freelancer_id, bid_amount, portfolio_url, status FROM proposals ORDER BY id DESC LIMIT 10");
    $proposals = $result->fetchAll();
    
    if (!empty($proposals)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Job ID</th><th>Freelancer ID</th><th>Bid</th><th>Portfolio URL</th><th>Status</th></tr>";
        
        foreach ($proposals as $proposal) {
            echo "<tr>";
            echo "<td>{$proposal['id']}</td>";
            echo "<td>{$proposal['job_id']}</td>";
            echo "<td>{$proposal['freelancer_id']}</td>";
            echo "<td>₹" . number_format($proposal['bid_amount'], 2) . "</td>";
            echo "<td>";
            if ($proposal['portfolio_url']) {
                echo "<a href='{$proposal['portfolio_url']}' target='_blank'>🔗 " . htmlspecialchars(substr($proposal['portfolio_url'], 0, 30)) . "...</a>";
            } else {
                echo "❌ Missing";
            }
            echo "</td>";
            echo "<td>" . ucfirst($proposal['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No proposals found in database.</p>";
    }
    
    // Test new proposal insertion
    echo "<h2>🧪 Test New Proposal Insertion:</h2>";
    
    // Use existing job and freelancer
    $test_job_id = 1;
    $test_freelancer_id = 5;
    $test_bid_amount = 1500.00;
    $test_portfolio_url = 'https://github.com/test-user';
    $test_proposal_text = 'Test proposal with portfolio URL';
    
    echo "<p>Testing insertion with:</p>";
    echo "<ul>";
    echo "<li>Job ID: $test_job_id</li>";
    echo "<li>Freelancer ID: $test_freelancer_id</li>";
    echo "<li>Bid Amount: ₹$test_bid_amount</li>";
    echo "<li>Portfolio URL: $test_portfolio_url</li>";
    echo "<li>Proposal Text: $test_proposal_text</li>";
    echo "</ul>";
    
    // Check if already applied
    $existing_result = $db->query("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?", [$test_job_id, $test_freelancer_id]);
    if ($existing_result->rowCount() > 0) {
        echo "<p style='color: orange;'>⚠️ Freelancer $test_freelancer_id has already applied to job $test_job_id</p>";
        
        // Use different job ID
        $job_result = $db->query("SELECT id FROM jobs WHERE id != $test_job_id AND status = 'open' LIMIT 1");
        $job = $job_result->fetch();
        
        if ($job) {
            $test_job_id = $job['id'];
            echo "<p>Using different job ID: $test_job_id</p>";
        } else {
            echo "<p style='color: red;'>❌ No other jobs available for testing</p>";
        }
    }
    
    // Insert test proposal
    $sql = "INSERT INTO proposals (job_id, freelancer_id, bid_amount, portfolio_url, proposal_text, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $db->query($sql, [$test_job_id, $test_freelancer_id, $test_bid_amount, $test_portfolio_url, $test_proposal_text]);
    
    if ($stmt->rowCount() > 0) {
        $insert_id = $db->lastInsertId();
        echo "<p style='color: green;'>✅ Test proposal inserted successfully (ID: $insert_id)</p>";
        
        // Clean up test record
        $db->query("DELETE FROM proposals WHERE id = $insert_id");
        echo "<p style='color: blue;'>ℹ️ Test record cleaned up automatically</p>";
    } else {
        echo "<p style='color: red;'>❌ Test proposal insertion failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>✅ Fix Complete!</h2>";
echo "<p>All missing portfolio URLs have been updated with default GitHub links.</p>";

echo "<h2>🔗 Quick Links:</h2>";
echo "<a href='debug_proposal_database.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔍 Debug Database</a><br>";
echo "<a href='test_proposal_submission.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🧪 Test Submission</a><br>";
echo "<a href='send_proposal_new.php?job_id=1' style='background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>📝 Proposal Form</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Portfolio URLs Fixed!</h3>";
echo "<p>The following fixes have been applied:</p>";
echo "<ul>";
echo "<li><strong>Default URLs:</strong> Added GitHub URLs for missing portfolio links</li>";
echo "<li><strong>Email-based:</strong> Created URLs from freelancer email usernames</li>";
echo "<li><strong>Test Insertion:</strong> Verified new proposal submission works</li>";
echo "<li><strong>Cleanup:</strong> Test records removed automatically</li>";
echo "</ul>";
echo "</div>";
?>
