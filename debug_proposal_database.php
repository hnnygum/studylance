<?php
/**
 * Debug database issues with proposal submission
 */

require_once 'config.php';

echo "<h1>🔍 Debug Proposal Database Issues</h1>";

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Test 1: Check database connection
echo "<h2>🗄 Database Connection:</h2>";
try {
    $db = Database::getInstance();
    echo "✅ Database connection successful<br>";
    
    // Test basic query
    $result = $db->query("SELECT 1 as test");
    if ($result->fetch()) {
        echo "✅ Basic query works<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test 2: Check proposals table structure
echo "<h2>📋 Proposals Table Structure:</h2>";
try {
    $db = Database::getInstance();
    $result = $db->query("DESCRIBE proposals");
    $columns = $result->fetchAll();
    
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
    
    // Check for required columns
    $required_columns = ['id', 'job_id', 'freelancer_id', 'bid_amount', 'portfolio_url', 'proposal_text', 'status', 'created_at'];
    $missing_columns = [];
    
    foreach ($required_columns as $col) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $col) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missing_columns[] = $col;
        }
    }
    
    if (!empty($missing_columns)) {
        echo "<p style='color: red;'>❌ Missing columns: " . implode(', ', $missing_columns) . "</p>";
    } else {
        echo "<p style='color: green;'>✅ All required columns present</p>";
    }
    
} catch (Exception $e) {
    echo "❌ Table structure check failed: " . $e->getMessage() . "<br>";
}

// Test 3: Check existing proposals
echo "<h2>📊 Existing Proposals:</h2>";
try {
    $db = Database::getInstance();
    $result = $db->query("SELECT COUNT(*) as count FROM proposals");
    $count = $result->fetch()['count'];
    echo "Total proposals in database: $count<br>";
    
    if ($count > 0) {
        $result = $db->query("SELECT id, job_id, freelancer_id, bid_amount, portfolio_url, status, created_at FROM proposals ORDER BY id DESC LIMIT 5");
        $proposals = $result->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Job ID</th><th>Freelancer ID</th><th>Bid</th><th>Portfolio URL</th><th>Status</th><th>Created</th></tr>";
        
        foreach ($proposals as $proposal) {
            echo "<tr>";
            echo "<td>{$proposal['id']}</td>";
            echo "<td>{$proposal['job_id']}</td>";
            echo "<td>{$proposal['freelancer_id']}</td>";
            echo "<td>₹" . number_format($proposal['bid_amount'], 2) . "</td>";
            echo "<td>" . ($proposal['portfolio_url'] ? "<a href='{$proposal['portfolio_url']}' target='_blank'>🔗 Link</a>" : "❌ Missing") . "</td>";
            echo "<td>" . ucfirst($proposal['status']) . "</td>";
            echo "<td>" . date('M j, Y H:i', strtotime($proposal['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ Existing proposals check failed: " . $e->getMessage() . "<br>";
}

// Test 4: Test proposal insertion
echo "<h2>🧪 Test Proposal Insertion:</h2>";
try {
    $db = Database::getInstance();
    
    // Find a valid job-freelancer combination dynamically
    $result = $db->query("SELECT id FROM jobs WHERE status = 'open' LIMIT 1");
    $job = $result->fetch();
    
    $result = $db->query("SELECT id FROM users WHERE role = 'freelancer' LIMIT 1");
    $freelancer = $result->fetch();
    
    if (!$job || !$freelancer) {
        echo "<p style='color: red;'>❌ No open jobs or freelancers found for testing</p>";
    } else {
        // Check if this combination already exists
        $result = $db->query("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?", [$job['id'], $freelancer['id']]);
        if ($result->rowCount() > 0) {
            echo "<p style='color: orange;'>⚠️ Freelancer {$freelancer['id']} already applied to job {$job['id']}</p>";
            
            // Try different freelancer
            $result = $db->query("SELECT id FROM users WHERE role = 'freelancer' AND id != ? LIMIT 1", [$freelancer['id']]);
            $alt_freelancer = $result->fetch();
            
            if ($alt_freelancer) {
                $result = $db->query("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?", [$job['id'], $alt_freelancer['id']]);
                if ($result->rowCount() === 0) {
                    $freelancer = $alt_freelancer;
                    echo "<p style='color: green;'>✅ Using alternative freelancer ID: {$freelancer['id']}</p>";
                }
            }
        }
        
        // Test data with valid combination
        $test_job_id = $job['id'];
        $test_freelancer_id = $freelancer['id'];
        $test_bid_amount = 1000.00;
        $test_portfolio_url = 'https://github.com/test';
        $test_proposal_text = 'Test proposal text';
        
        echo "<p>Testing insertion with:</p>";
        echo "<ul>";
        echo "<li>Job ID: $test_job_id</li>";
        echo "<li>Freelancer ID: $test_freelancer_id</li>";
        echo "<li>Bid Amount: ₹$test_bid_amount</li>";
        echo "<li>Portfolio URL: $test_portfolio_url</li>";
        echo "<li>Proposal Text: $test_proposal_text</li>";
        echo "</ul>";
        
        // Test insertion query
        $sql = "INSERT INTO proposals (job_id, freelancer_id, bid_amount, portfolio_url, proposal_text, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        echo "<p><strong>SQL Query:</strong> $sql</p>";
        
        // Try to prepare and execute
        $stmt = $db->query($sql, [$test_job_id, $test_freelancer_id, $test_bid_amount, $test_portfolio_url, $test_proposal_text]);
        
        if ($stmt) {
            echo "✅ Statement prepared and executed successfully<br>";
            
            if ($stmt->rowCount() > 0) {
                echo "✅ Test insertion successful<br>";
                $insert_id = $db->lastInsertId();
                echo "Inserted proposal ID: $insert_id<br>";
                
                // Clean up test record
                $db->query("DELETE FROM proposals WHERE id = $insert_id");
                echo "✅ Test record cleaned up<br>";
            } else {
                echo "❌ Test insertion failed - no rows affected<br>";
            }
        } else {
            echo "❌ Query execution failed<br>";
            echo "Error: Database query failed<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Test insertion failed: " . $e->getMessage() . "<br>";
    echo "This might be due to foreign key constraints or duplicate proposals.<br>";
}

// Test 5: Check for foreign key constraints
echo "<h2>🔗 Foreign Key Constraints:</h2>";
try {
    $db = Database::getInstance();
    $result = $db->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'proposals'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $constraints = $result->fetchAll();
    
    if (!empty($constraints)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Table</th><th>Column</th><th>References</th><th>Ref Column</th></tr>";
        
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>{$constraint['TABLE_NAME']}</td>";
            echo "<td>{$constraint['COLUMN_NAME']}</td>";
            echo "<td>{$constraint['REFERENCED_TABLE_NAME']}</td>";
            echo "<td>{$constraint['REFERENCED_COLUMN_NAME']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No foreign key constraints found</p>";
    }
    
} catch (Exception $e) {
    echo "❌ Foreign key check failed: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>🔧 Quick Fixes:</h2>";
echo "<a href='update_proposals_table.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔧 Update Database</a><br>";
echo "<a href='test_send_proposal_new.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>📝 Test Proposal Form</a><br>";
echo "<a href='freelancer_dashboard_new.php' style='background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>👨‍💻 Freelancer Dashboard</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Database Debug Complete!</h3>";
echo "<p>This diagnostic tool checks for common database issues:</p>";
echo "<ul>";
echo "<li><strong>Table Structure:</strong> Verifies all required columns exist</li>";
echo "<li><strong>Data Integrity:</strong> Checks existing proposals</li>";
echo "<li><strong>Insertion Test:</strong> Tests proposal insertion process</li>";
echo "<li><strong>Foreign Keys:</strong> Checks relationship constraints</li>";
echo "</ul>";
echo "</div>";
?>
