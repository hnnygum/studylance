<?php
/**
 * Add Missing Columns to Database Tables
 * Fixes the "Unknown column" errors
 */

echo "<h2>Adding Missing Database Columns</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
</style>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=studylance", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p class='success'>✅ Database connected</p>";
    
    // Check current proposals table structure
    echo "<h3>📋 Current Proposals Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE proposals");
    $columns = $stmt->fetchAll();
    echo "<pre>";
    foreach ($columns as $column) {
        echo sprintf("%-20s %-20s %-10s %-10s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key']
        );
    }
    echo "</pre>";
    
    // Check if cover_letter column exists
    $coverLetterExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'cover_letter') {
            $coverLetterExists = true;
            break;
        }
    }
    
    if (!$coverLetterExists) {
        echo "<p class='warning'>⚠️ cover_letter column missing - adding it...</p>";
        try {
            $pdo->exec("ALTER TABLE proposals ADD COLUMN cover_letter TEXT NOT NULL AFTER bid_amount");
            echo "<p class='success'>✅ cover_letter column added</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Failed to add cover_letter column: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='success'>✅ cover_letter column already exists</p>";
    }
    
    // Check if status column exists
    $statusExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            $statusExists = true;
            break;
        }
    }
    
    if (!$statusExists) {
        echo "<p class='warning'>⚠️ status column missing - adding it...</p>";
        try {
            $pdo->exec("ALTER TABLE proposals ADD COLUMN status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending' AFTER cover_letter");
            echo "<p class='success'>✅ status column added</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Failed to add status column: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='success'>✅ status column already exists</p>";
    }
    
    // Check if created_at column exists
    $createdAtExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'created_at') {
            $createdAtExists = true;
            break;
        }
    }
    
    if (!$createdAtExists) {
        echo "<p class='warning'>⚠️ created_at column missing - adding it...</p>";
        try {
            $pdo->exec("ALTER TABLE proposals ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status");
            echo "<p class='success'>✅ created_at column added</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Failed to add created_at column: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='success'>✅ created_at column already exists</p>";
    }
    
    // Show updated structure
    echo "<h3>📋 Updated Proposals Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE proposals");
    $columns = $stmt->fetchAll();
    echo "<pre>";
    foreach ($columns as $column) {
        echo sprintf("%-20s %-20s %-10s %-10s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key']
        );
    }
    echo "</pre>";
    
    // Test proposal insertion again
    echo "<h3>🧪 Testing Proposal Insertion Again</h3>";
    try {
        // Get a job and freelancer
        $stmt = $pdo->query("SELECT id FROM jobs WHERE status = 'open' LIMIT 1");
        $job = $stmt->fetch();
        
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'freelancer' LIMIT 1");
        $freelancer = $stmt->fetch();
        
        if ($job && $freelancer) {
            echo "<p class='info'>Testing with Job ID: {$job['id']}, Freelancer ID: {$freelancer['id']}</p>";
            
            // Clean up any existing test proposals
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
                
                echo "<h3>🎉 SUCCESS! Database is now fixed!</h3>";
                echo "<p class='success'>You can now submit proposals without errors.</p>";
                
            } else {
                echo "<p class='error'>❌ Insert failed but no error thrown</p>";
            }
            
        } else {
            echo "<p class='error'>❌ Could not find test job or freelancer</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Proposal test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h3>🔗 Next Steps</h3>";
echo "<a href='send_proposal_new.php' class='btn'>📝 Submit Proposal Now</a>";
echo "<a href='simple_proposal_test.php' class='btn'>🧪 Test Again</a>";
echo "<a href='login_new.php' class='btn'>🔐 Login to Test</a>";
?>
