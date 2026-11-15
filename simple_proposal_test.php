<?php
/**
 * Simple Proposal Test
 * Tests database insertion with minimal code
 */

echo "<h2>Simple Proposal Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
</style>";

// Simple database connection test
try {
    $pdo = new PDO("mysql:host=localhost;dbname=studylance", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p class='success'>✅ Database connected</p>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test if proposals table exists
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM proposals LIMIT 1");
    echo "<p class='success'>✅ Proposals table exists and is accessible</p>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ Proposals table issue: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Try to create the table
    echo "<p>Creating proposals table...</p>";
    try {
        $pdo->exec("CREATE TABLE proposals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            freelancer_id INT NOT NULL,
            bid_amount DECIMAL(10,2) NOT NULL,
            cover_letter TEXT NOT NULL,
            status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<p class='success'>✅ Proposals table created</p>";
    } catch (PDOException $e2) {
        echo "<p class='error'>❌ Failed to create table: " . htmlspecialchars($e2->getMessage()) . "</p>";
        exit;
    }
}

// Check if we have test data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'open'");
    $jobCount = $stmt->fetch()['count'];
    echo "<p class='info'>📊 Found $jobCount open jobs</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'freelancer'");
    $freelancerCount = $stmt->fetch()['count'];
    echo "<p class='info'>👤 Found $freelancerCount freelancers</p>";
    
    if ($jobCount == 0 || $freelancerCount == 0) {
        echo "<p class='error'>❌ Missing test data. Creating sample data...</p>";
        
        // Create sample user if needed
        if ($freelancerCount == 0) {
            $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Test Freelancer', 'test@freelancer.com', 'password123', 'freelancer')");
            echo "<p class='success'>✅ Created test freelancer</p>";
        }
        
        // Create sample job if needed
        if ($jobCount == 0) {
            $pdo->exec("INSERT INTO jobs (client_id, title, description, budget, status) VALUES (1, 'Test Job', 'This is a test job description', 500.00, 'open')");
            echo "<p class='success'>✅ Created test job</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Data check failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test proposal insertion
echo "<h3>🧪 Testing Proposal Insertion</h3>";
try {
    // Get a job and freelancer
    $stmt = $pdo->query("SELECT id FROM jobs WHERE status = 'open' LIMIT 1");
    $job = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'freelancer' LIMIT 1");
    $freelancer = $stmt->fetch();
    
    if ($job && $freelancer) {
        echo "<p class='info'>Testing with Job ID: {$job['id']}, Freelancer ID: {$freelancer['id']}</p>";
        
        // Delete any existing test proposal
        $pdo->exec("DELETE FROM proposals WHERE job_id = {$job['id']} AND freelancer_id = {$freelancer['id']} AND cover_letter LIKE 'Test proposal%'");
        
        // Insert test proposal
        $stmt = $pdo->prepare("INSERT INTO proposals (job_id, freelancer_id, bid_amount, cover_letter, status) VALUES (?, ?, ?, ?, 'pending')");
        $result = $stmt->execute([$job['id'], $freelancer['id'], 250.00, 'Test proposal for debugging']);
        
        if ($result) {
            echo "<p class='success'>✅ Test proposal inserted successfully!</p>";
            
            // Verify it was inserted
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM proposals WHERE job_id = {$job['id']} AND freelancer_id = {$freelancer['id']}");
            $count = $stmt->fetch()['count'];
            echo "<p class='success'>✅ Verification: Found $count proposal(s)</p>";
            
            // Clean up test data
            $pdo->exec("DELETE FROM proposals WHERE job_id = {$job['id']} AND freelancer_id = {$freelancer['id']} AND cover_letter LIKE 'Test proposal%'");
            echo "<p class='info'>🧹 Test data cleaned up</p>";
            
        } else {
            echo "<p class='error'>❌ Insert failed but no error thrown</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Could not find test job or freelancer</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Proposal test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Show SQL error details
    echo "<h4>Error Details:</h4>";
    echo "<pre>";
    echo "SQL State: " . $e->errorInfo[0] . "\n";
    echo "Error Code: " . $e->errorInfo[1] . "\n";
    echo "Error Message: " . $e->errorInfo[2] . "\n";
    echo "</pre>";
}

echo "<h3>🔗 Next Steps</h3>";
echo "<a href='send_proposal_new.php' class='btn'>📝 Try Submitting Proposal</a>";
echo "<a href='db_check.php' class='btn'>🔍 Full Database Check</a>";
echo "<a href='login_new.php' class='btn'>🔐 Login to Test</a>";

echo "<h3>💡 If This Test Passes</h3>";
echo "<p>The database is working fine. The issue might be in:</p>";
echo "<ul>";
echo "<li>Session management (user not logged in)</li>";
echo "<li>Form validation logic</li>";
echo "<li>Database configuration in config.php</li>";
echo "<li>Foreign key constraints</li>";
echo "</ul>";
?>
