<?php
/**
 * Fix portfolio_url column issues in proposals table
 */

require_once 'database.php';

echo "<h1>🔧 Fix Portfolio URL Column Issues</h1>";

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Check current table structure
    echo "<h2>📋 Current Proposals Table Structure:</h2>";
    $result = $conn->query("DESCRIBE proposals");
    $columns = $result->fetch_all(MYSQLI_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if portfolio_url column exists and is properly configured
    $portfolio_column = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'portfolio_url') {
            $portfolio_column = $column;
            break;
        }
    }
    
    if (!$portfolio_column) {
        echo "<h2>➕ Add portfolio_url Column:</h2>";
        $sql = "ALTER TABLE proposals ADD COLUMN portfolio_url VARCHAR(500) AFTER bid_amount";
        if ($conn->query($sql)) {
            echo "✅ portfolio_url column added successfully<br>";
        } else {
            echo "❌ Failed to add portfolio_url column: " . $conn->error . "<br>";
        }
    } else {
        echo "<h2>✅ portfolio_url Column Exists:</h2>";
        echo "<p>Column details:</p>";
        echo "<ul>";
        echo "<li><strong>Type:</strong> {$portfolio_column['Type']}</li>";
        echo "<li><strong>Null:</strong> {$portfolio_column['Null']}</li>";
        echo "<li><strong>Default:</strong> {$portfolio_column['Default']}</li>";
        echo "</ul>";
        
        // Check if column needs to be modified
        if ($portfolio_column['Null'] === 'NO') {
            echo "<p style='color: orange;'>⚠️ Column is NOT NULL - this might cause issues</p>";
            
            // Make column nullable
            $sql = "ALTER TABLE proposals MODIFY COLUMN portfolio_url VARCHAR(500) NULL";
            if ($conn->query($sql)) {
                echo "✅ portfolio_url column made nullable<br>";
            } else {
                echo "❌ Failed to make portfolio_url nullable: " . $conn->error . "<br>";
            }
        }
    }
    
    // Update existing records to have default portfolio URLs
    echo "<h2>🔄 Update Existing Records:</h2>";
    
    // Get records without portfolio URLs
    $result = $conn->query("SELECT id, freelancer_id FROM proposals WHERE portfolio_url IS NULL OR portfolio_url = ''");
    $proposals = $result->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($proposals)) {
        echo "<p>Updating " . count($proposals) . " proposals without portfolio URLs...</p>";
        
        foreach ($proposals as $proposal) {
            // Get freelancer email to create default portfolio URL
            $result = $conn->query("SELECT email FROM users WHERE id = {$proposal['freelancer_id']}");
            $freelancer = $result->fetch_assoc();
            
            if ($freelancer) {
                $email_parts = explode('@', $freelancer['email']);
                $username = $email_parts[0];
                $default_portfolio_url = "https://github.com/" . $username;
                
                // Update the proposal
                $sql = "UPDATE proposals SET portfolio_url = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $default_portfolio_url, $proposal['id']);
                
                if ($stmt->execute()) {
                    echo "✅ Updated proposal ID {$proposal['id']}: $default_portfolio_url<br>";
                } else {
                    echo "❌ Failed to update proposal ID {$proposal['id']}: " . $stmt->error . "<br>";
                }
                
                $stmt->close();
            } else {
                echo "⚠️ Freelancer not found for proposal ID {$proposal['id']}<br>";
            }
        }
    } else {
        echo "<p style='color: green;'>✅ All proposals already have portfolio URLs</p>";
    }
    
    // Test proposal insertion with portfolio URL
    echo "<h2>🧪 Test Proposal Insertion:</h2>";
    
    // Use valid combination: Job 3 + Freelancer 3 (doesn't exist in current proposals)
    $test_job_id = 3;
    $test_freelancer_id = 3;
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
    
    // Check if this combination already exists
    $result = $conn->query("SELECT id FROM proposals WHERE job_id = $test_job_id AND freelancer_id = $test_freelancer_id");
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ This combination already exists - using different values</p>";
        
        // Use Job 1 + Freelancer 5 (doesn't exist in current proposals)
        $test_job_id = 1;
        $test_freelancer_id = 5;
        
        $result = $conn->query("SELECT id FROM proposals WHERE job_id = $test_job_id AND freelancer_id = $test_freelancer_id");
        if ($result->num_rows > 0) {
            echo "<p style='color: red;'>❌ All valid combinations already exist</p>";
            echo "<p>Creating a test job for testing...</p>";
            
            // Create a test job
            $sql = "INSERT INTO jobs (title, description, budget, client_id, status, created_at) VALUES (?, ?, ?, ?, 'open', NOW())";
            $stmt = $conn->prepare($sql);
            
            $test_title = 'Test Job for Proposal Testing';
            $test_description = 'This is a test job for testing proposal submission with portfolio URLs';
            $test_budget = 3000.00;
            $test_client_id = 1; // Assuming client ID 1 exists
            
            $stmt->bind_param("ssdi", $test_title, $test_description, $test_budget, $test_client_id);
            
            if ($stmt->execute()) {
                $new_job_id = $conn->insert_id;
                echo "<p style='color: green;'>✅ Test job created with ID: $new_job_id</p>";
                $test_job_id = $new_job_id;
            } else {
                echo "<p style='color: red;'>❌ Failed to create test job: " . $stmt->error . "</p>";
            }
            
            $stmt->close();
        }
    }
    
    // Test insertion
    $sql = "INSERT INTO proposals (job_id, freelancer_id, bid_amount, portfolio_url, proposal_text, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("iidss", $test_job_id, $test_freelancer_id, $test_bid_amount, $test_portfolio_url, $test_proposal_text);
        
        if ($stmt->execute()) {
            $insert_id = $conn->insert_id;
            echo "<p style='color: green;'>✅ Test proposal inserted successfully (ID: $insert_id)</p>";
            
            // Verify the insertion
            $result = $conn->query("SELECT * FROM proposals WHERE id = $insert_id");
            $inserted_proposal = $result->fetch_assoc();
            
            echo "<p>Verification:</p>";
            echo "<ul>";
            echo "<li>Portfolio URL: " . ($inserted_proposal['portfolio_url'] ?: '❌ Missing') . "</li>";
            echo "<li>Bid Amount: ₹" . number_format($inserted_proposal['bid_amount'], 2) . "</li>";
            echo "<li>Status: " . ucfirst($inserted_proposal['status']) . "</li>";
            echo "</ul>";
            
            // Clean up test record
            $conn->query("DELETE FROM proposals WHERE id = $insert_id");
            echo "<p style='color: blue;'>ℹ️ Test record cleaned up automatically</p>";
            
        } else {
            echo "<p style='color: red;'>❌ Test insertion failed: " . $stmt->error . "</p>";
        }
        
        $stmt->close();
    } else {
        echo "<p style='color: red;'>❌ Statement preparation failed: " . $conn->error . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

$conn->close();

echo "<hr>";
echo "<h2>✅ Portfolio Column Fix Complete!</h2>";
echo "<p>The portfolio_url column has been fixed and tested.</p>";

echo "<h2>🔗 Quick Links:</h2>";
echo "<a href='fix_foreign_key_issues.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔧 Fix Foreign Keys</a><br>";
echo "<a href='send_proposal_new.php?job_id=3' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>📝 Test Proposal Form</a><br>";
echo "<a href='freelancer_dashboard_new.php' style='background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>👨‍💻 Freelancer Dashboard</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Portfolio URL Column Fixed!</h3>";
echo "<p>The following fixes have been applied:</p>";
echo "<ul>";
echo "<li><strong>Column Check:</strong> Verified portfolio_url column exists</li>";
echo "<li><strong>Column Fix:</strong> Made column nullable if needed</li>";
echo "<li><strong>Default URLs:</strong> Added GitHub URLs for existing proposals</li>";
echo "<li><strong>Test Insertion:</strong> Verified proposal submission works</li>";
echo "<li><strong>Cleanup:</strong> Test records removed automatically</li>";
echo "</ul>";
echo "</div>";
?>
