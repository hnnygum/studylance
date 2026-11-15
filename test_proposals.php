<?php
/**
 * Test Proposals Table and Database Connection
 */

require_once 'config.php';

echo "<h2>Proposals Database Test</h2>";

try {
    $db = Database::getInstance();
    echo "✅ Database connection successful<br>";
    
    // Check if proposals table exists
    $stmt = $db->query("SHOW TABLES LIKE 'proposals'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Proposals table exists<br>";
        
        // Show table structure
        $stmt = $db->query("DESCRIBE proposals");
        $columns = $stmt->fetchAll();
        
        echo "<h3>📋 Proposals Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if there are any existing proposals
        $stmt = $db->query("SELECT COUNT(*) as count FROM proposals");
        $count = $stmt->fetch()['count'];
        echo "<p>📊 Total proposals in database: {$count}</p>";
        
        // Test inserting a sample proposal
        echo "<h3>🧪 Testing Proposal Insert:</h3>";
        
        // Get a sample job and freelancer
        $stmt = $db->query("SELECT id FROM jobs WHERE status = 'open' LIMIT 1");
        $job = $stmt->fetch();
        
        $stmt = $db->query("SELECT id FROM users WHERE role = 'freelancer' LIMIT 1");
        $freelancer = $stmt->fetch();
        
        if ($job && $freelancer) {
            echo "<p>Testing with Job ID: {$job['id']} and Freelancer ID: {$freelancer['id']}</p>";
            
            // Try to insert a test proposal
            try {
                $stmt = $db->query(
                    "INSERT INTO proposals (job_id, freelancer_id, bid_amount, cover_letter, status, created_at) VALUES (?, ?, ?, ?, 'pending', ?)",
                    [$job['id'], $freelancer['id'], 100.00, 'Test proposal for debugging', date('Y-m-d H:i:s')]
                );
                
                if ($stmt->rowCount() > 0) {
                    echo "✅ Test proposal inserted successfully<br>";
                    
                    // Delete the test proposal
                    $stmt = $db->query("DELETE FROM proposals WHERE job_id = ? AND freelancer_id = ? AND cover_letter = 'Test proposal for debugging'", 
                                    [$job['id'], $freelancer['id']]);
                    echo "✅ Test proposal cleaned up<br>";
                } else {
                    echo "❌ Test proposal insertion failed<br>";
                }
            } catch (Exception $e) {
                echo "❌ Test proposal error: " . htmlspecialchars($e->getMessage()) . "<br>";
            }
        } else {
            echo "⚠️ No jobs or freelancers found for testing<br>";
        }
        
    } else {
        echo "❌ Proposals table does not exist<br>";
        echo "<p>Creating proposals table...</p>";
        
        // Create the proposals table
        $db->query("CREATE TABLE IF NOT EXISTS proposals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            freelancer_id INT NOT NULL,
            bid_amount DECIMAL(10,2) NOT NULL,
            cover_letter TEXT NOT NULL,
            status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
            FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_proposal (job_id, freelancer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        echo "✅ Proposals table created successfully<br>";
    }
    
    // Check if jobs table exists and has records
    $stmt = $db->query("SHOW TABLES LIKE 'jobs'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'open'");
        $openJobs = $stmt->fetch()['count'];
        echo "<p>📋 Open jobs available: {$openJobs}</p>";
    }
    
    // Check if users table exists and has freelancers
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'freelancer'");
        $freelancers = $stmt->fetch()['count'];
        echo "<p>👥 Freelancers available: {$freelancers}</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Database Connection Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection settings in config.php</p>";
}

echo "<br><p><a href='send_proposal_new.php'>Try Submitting Proposal Again</a></p>";
?>
