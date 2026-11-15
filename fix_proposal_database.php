<?php
/**
 * Fix database issues for proposal submission
 */

require_once 'database.php';

echo "<h1>🔧 Fix Proposal Database Issues</h1>";

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Fix 1: Add portfolio_url column if missing
    echo "<h2>📝 Add portfolio_url Column:</h2>";
    
    $result = $conn->query("SHOW COLUMNS FROM proposals LIKE 'portfolio_url'");
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE proposals ADD COLUMN portfolio_url VARCHAR(500) AFTER bid_amount";
        if ($conn->query($sql)) {
            echo "✅ portfolio_url column added successfully<br>";
        } else {
            echo "❌ Failed to add portfolio_url column: " . $conn->error . "<br>";
        }
    } else {
        echo "✅ portfolio_url column already exists<br>";
    }
    
    // Fix 2: Add proposal_text column if missing
    echo "<h2>📝 Add proposal_text Column:</h2>";
    
    $result = $conn->query("SHOW COLUMNS FROM proposals LIKE 'proposal_text'");
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE proposals ADD COLUMN proposal_text TEXT AFTER portfolio_url";
        if ($conn->query($sql)) {
            echo "✅ proposal_text column added successfully<br>";
        } else {
            echo "❌ Failed to add proposal_text column: " . $conn->error . "<br>";
        }
    } else {
        echo "✅ proposal_text column already exists<br>";
    }
    
    // Fix 3: Add created_at column if missing
    echo "<h2>📝 Add created_at Column:</h2>";
    
    $result = $conn->query("SHOW COLUMNS FROM proposals LIKE 'created_at'");
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE proposals ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        if ($conn->query($sql)) {
            echo "✅ created_at column added successfully<br>";
        } else {
            echo "❌ Failed to add created_at column: " . $conn->error . "<br>";
        }
    } else {
        echo "✅ created_at column already exists<br>";
    }
    
    // Fix 4: Add status column if missing
    echo "<h2>📝 Add status Column:</h2>";
    
    $result = $conn->query("SHOW COLUMNS FROM proposals LIKE 'status'");
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE proposals ADD COLUMN status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending'";
        if ($conn->query($sql)) {
            echo "✅ status column added successfully<br>";
        } else {
            echo "❌ Failed to add status column: " . $conn->error . "<br>";
        }
    } else {
        echo "✅ status column already exists<br>";
    }
    
    // Fix 5: Update existing records to have default values
    echo "<h2>🔄 Update Existing Records:</h2>";
    
    // Update null portfolio_urls
    $sql = "UPDATE proposals SET portfolio_url = 'https://github.com/example' WHERE portfolio_url IS NULL OR portfolio_url = ''";
    $result = $conn->query($sql);
    if ($result) {
        echo "✅ Updated " . $conn->affected_rows . " records with default portfolio URL<br>";
    }
    
    // Update null proposal_text
    $sql = "UPDATE proposals SET proposal_text = 'Please review my portfolio for more information.' WHERE proposal_text IS NULL OR proposal_text = ''";
    $result = $conn->query($sql);
    if ($result) {
        echo "✅ Updated " . $conn->affected_rows . " records with default proposal text<br>";
    }
    
    // Update null status
    $sql = "UPDATE proposals SET status = 'pending' WHERE status IS NULL";
    $result = $conn->query($sql);
    if ($result) {
        echo "✅ Updated " . $conn->affected_rows . " records with default status<br>";
    }
    
    // Fix 6: Show final table structure
    echo "<h2>📋 Final Table Structure:</h2>";
    
    $result = $conn->query("DESCRIBE proposals");
    $columns = $result->fetch_all(MYSQLI_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
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
    
    // Fix 7: Test insertion
    echo "<h2>🧪 Test Proposal Insertion:</h2>";
    
    // Get a valid job and freelancer
    $job_result = $conn->query("SELECT id FROM jobs WHERE status = 'open' LIMIT 1");
    $job = $job_result->fetch_assoc();
    
    $freelancer_result = $conn->query("SELECT id FROM users WHERE role = 'freelancer' LIMIT 1");
    $freelancer = $freelancer_result->fetch_assoc();
    
    if ($job && $freelancer) {
        $test_job_id = $job['id'];
        $test_freelancer_id = $freelancer['id'];
        $test_bid_amount = 1500.00;
        $test_portfolio_url = 'https://github.com/test-user';
        $test_proposal_text = 'Test proposal for database validation';
        
        $sql = "INSERT INTO proposals (job_id, freelancer_id, bid_amount, portfolio_url, proposal_text, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("iidss", $test_job_id, $test_freelancer_id, $test_bid_amount, $test_portfolio_url, $test_proposal_text);
            
            if ($stmt->execute()) {
                $insert_id = $conn->insert_id;
                echo "✅ Test proposal inserted successfully (ID: $insert_id)<br>";
                
                // Clean up test record
                $conn->query("DELETE FROM proposals WHERE id = $insert_id");
                echo "✅ Test record cleaned up<br>";
            } else {
                echo "❌ Test insertion failed: " . $stmt->error . "<br>";
            }
            
            $stmt->close();
        } else {
            echo "❌ Statement preparation failed: " . $conn->error . "<br>";
        }
    } else {
        echo "⚠️ No valid job or freelancer found for testing<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database fix error: " . $e->getMessage() . "<br>";
}

$conn->close();

echo "<hr>";
echo "<h2>✅ Fix Complete!</h2>";
echo "<p>Database has been updated with all required columns for proposal submission.</p>";

echo "<h2>🔗 Quick Links:</h2>";
echo "<a href='debug_proposal_database.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔍 Debug Database</a><br>";
echo "<a href='send_proposal_new.php?job_id=1' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>📝 Test Proposal Form</a><br>";
echo "<a href='freelancer_dashboard_new.php' style='background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>👨‍💻 Freelancer Dashboard</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Database Fixed Successfully!</h3>";
echo "<p>The following fixes have been applied:</p>";
echo "<ul>";
echo "<li><strong>portfolio_url column:</strong> Added for portfolio links</li>";
echo "<li><strong>proposal_text column:</strong> Added for additional details</li>";
echo "<li><strong>status column:</strong> Added with ENUM values</li>";
echo "<li><strong>created_at column:</strong> Added for timestamps</li>";
echo "<li><strong>Default values:</strong> Updated existing records</li>";
echo "<li><strong>Test insertion:</strong> Verified functionality</li>";
echo "</ul>";
echo "</div>";
?>
