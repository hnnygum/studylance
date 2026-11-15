<?php
/**
 * Test the exact insert with correct data
 */

echo "<h2>Testing Exact Insert</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=studylance", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p class='success'>✅ Database connected</p>";
    
    // Test with correct IDs from your data
    $jobId = 1; // WordPress Website Development
    $freelancerId = 3; // Jane Freelancer
    $bidAmount = 100.00;
    $proposalText = "Test proposal from minimal test";
    $estimatedTime = "2 weeks";
    
    echo "<p class='info'>Testing with correct data:</p>";
    echo "<pre>";
    echo "Job ID: $jobId (WordPress Website Development)\n";
    echo "Freelancer ID: $freelancerId (Jane Freelancer)\n";
    echo "Bid Amount: $bidAmount\n";
    echo "Proposal Text: $proposalText\n";
    echo "Estimated Time: $estimatedTime\n";
    echo "</pre>";
    
    // First, let's try with just the required columns
    echo "<h3>Test 1: Required Columns Only</h3>";
    try {
        $sql = "INSERT INTO proposals (job_id, freelancer_id, bid_amount, proposal_text, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())";
        echo "<p class='info'>SQL: " . htmlspecialchars($sql) . "</p>";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$jobId, $freelancerId, $bidAmount, $proposalText]);
        
        if ($result) {
            echo "<p class='success'>✅ Basic insert successful! Rows affected: " . $stmt->rowCount() . "</p>";
            
            // Clean up
            $pdo->exec("DELETE FROM proposals WHERE proposal_text = 'Test proposal from minimal test'");
            echo "<p class='info'>🧹 Test data cleaned up</p>";
        } else {
            echo "<p class='error'>❌ Basic insert failed</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Basic insert failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>";
        echo "SQL State: " . $e->errorInfo[0] . "\n";
        echo "Error Code: " . $e->errorInfo[1] . "\n";
        echo "Error Message: " . $e->errorInfo[2] . "\n";
        echo "</pre>";
    }
    
    // Now try with cover_letter column
    echo "<h3>Test 2: Using cover_letter Column</h3>";
    try {
        $sql = "INSERT INTO proposals (job_id, freelancer_id, bid_amount, cover_letter, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())";
        echo "<p class='info'>SQL: " . htmlspecialchars($sql) . "</p>";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$jobId, $freelancerId, $bidAmount, $proposalText]);
        
        if ($result) {
            echo "<p class='success'>✅ Cover letter insert successful! Rows affected: " . $stmt->rowCount() . "</p>";
            
            // Clean up
            $pdo->exec("DELETE FROM proposals WHERE cover_letter = 'Test proposal from minimal test'");
            echo "<p class='info'>🧹 Test data cleaned up</p>";
        } else {
            echo "<p class='error'>❌ Cover letter insert failed</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Cover letter insert failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>";
        echo "SQL State: " . $e->errorInfo[0] . "\n";
        echo "Error Code: " . $e->errorInfo[1] . "\n";
        echo "Error Message: " . $e->errorInfo[2] . "\n";
        echo "</pre>";
    }
    
    // Now try with all columns including estimated_time
    echo "<h3>Test 3: All Columns</h3>";
    try {
        $sql = "INSERT INTO proposals (job_id, freelancer_id, bid_amount, proposal_text, estimated_time, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        echo "<p class='info'>SQL: " . htmlspecialchars($sql) . "</p>";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$jobId, $freelancerId, $bidAmount, $proposalText, $estimatedTime]);
        
        if ($result) {
            echo "<p class='success'>✅ Full insert successful! Rows affected: " . $stmt->rowCount() . "</p>";
            
            // Clean up
            $pdo->exec("DELETE FROM proposals WHERE proposal_text = 'Test proposal from minimal test'");
            echo "<p class='info'>🧹 Test data cleaned up</p>";
            
            echo "<h3>🎉 SUCCESS! The database works perfectly.</h3>";
            echo "<p class='success'>Now we know the exact SQL that works.</p>";
        } else {
            echo "<p class='error'>❌ Full insert failed</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Full insert failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>";
        echo "SQL State: " . $e->errorInfo[0] . "\n";
        echo "Error Code: " . $e->errorInfo[1] . "\n";
        echo "Error Message: " . $e->errorInfo[2] . "\n";
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h3>🔗 Next Steps:</h3>";
echo "<p>If all tests pass, the issue is in the send_proposal_new.php file. I'll fix it with the correct SQL.</p>";
?>
