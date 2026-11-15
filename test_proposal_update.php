<?php
/**
 * Test Proposal Update
 * Debug the proposal acceptance/rejection issue
 */

echo "<h2>Test Proposal Update</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
</style>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=studylance", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p class='success'>✅ Database connected</p>";
    
    // Check current proposals
    echo "<h3>📋 Current Proposals:</h3>";
    $stmt = $pdo->query("
        SELECT p.*, j.title as job_title, j.client_id, u.name as freelancer_name 
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        JOIN users u ON p.freelancer_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $proposals = $stmt->fetchAll();
    
    if ($proposals) {
        echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr><th>ID</th><th>Job</th><th>Freelancer</th><th>Bid</th><th>Status</th><th>Client ID</th></tr>";
        foreach ($proposals as $proposal) {
            echo "<tr>";
            echo "<td>{$proposal['id']}</td>";
            echo "<td>" . htmlspecialchars($proposal['job_title']) . "</td>";
            echo "<td>" . htmlspecialchars($proposal['freelancer_name']) . "</td>";
            echo "<td>\${$proposal['bid_amount']}</td>";
            echo "<td>{$proposal['status']}</td>";
            echo "<td>{$proposal['client_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test updating a proposal
        $testProposalId = $proposals[0]['id'];
        $testClientId = $proposals[0]['client_id'];
        
        echo "<h3>🧪 Testing Proposal Update:</h3>";
        echo "<p class='info'>Testing with Proposal ID: $testProposalId, Client ID: $testClientId</p>";
        
        // Test 1: Simple status update
        echo "<h4>Test 1: Simple Status Update</h4>";
        try {
            $sql = "UPDATE proposals SET status = 'accepted' WHERE id = ?";
            echo "<p class='info'>SQL: " . htmlspecialchars($sql) . "</p>";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$testProposalId]);
            
            if ($result) {
                echo "<p class='success'>✅ Simple update successful! Rows affected: " . $stmt->rowCount() . "</p>";
                
                // Reset to pending
                $pdo->exec("UPDATE proposals SET status = 'pending' WHERE id = $testProposalId");
                echo "<p class='info'>🔄 Reset to pending</p>";
            } else {
                echo "<p class='error'>❌ Simple update failed</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Simple update failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        // Test 2: Update with client check (the problematic query)
        echo "<h4>Test 2: Update with Client Check</h4>";
        try {
            $sql = "UPDATE proposals SET status = 'accepted' WHERE id = ? AND job_id IN (SELECT id FROM jobs WHERE client_id = ?)";
            echo "<p class='info'>SQL: " . htmlspecialchars($sql) . "</p>";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$testProposalId, $testClientId]);
            
            if ($result) {
                echo "<p class='success'>✅ Client check update successful! Rows affected: " . $stmt->rowCount() . "</p>";
                
                // Reset to pending
                $pdo->exec("UPDATE proposals SET status = 'pending' WHERE id = $testProposalId");
                echo "<p class='info'>🔄 Reset to pending</p>";
            } else {
                echo "<p class='error'>❌ Client check update failed</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Client check update failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>";
            echo "SQL State: " . $e->errorInfo[0] . "\n";
            echo "Error Code: " . $e->errorInfo[1] . "\n";
            echo "Error Message: " . $e->errorInfo[2] . "\n";
            echo "</pre>";
        }
        
        // Test 3: Simplified query (alternative approach)
        echo "<h4>Test 3: Simplified Query</h4>";
        try {
            $sql = "UPDATE proposals p JOIN jobs j ON p.job_id = j.id SET p.status = 'accepted' WHERE p.id = ? AND j.client_id = ?";
            echo "<p class='info'>SQL: " . htmlspecialchars($sql) . "</p>";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$testProposalId, $testClientId]);
            
            if ($result) {
                echo "<p class='success'>✅ Simplified query successful! Rows affected: " . $stmt->rowCount() . "</p>";
                
                // Reset to pending
                $pdo->exec("UPDATE proposals SET status = 'pending' WHERE id = $testProposalId");
                echo "<p class='info'>🔄 Reset to pending</p>";
                
                echo "<h3>🎉 SOLUTION FOUND!</h3>";
                echo "<p class='success'>The simplified query works. I'll update the client dashboard with this fix.</p>";
            } else {
                echo "<p class='error'>❌ Simplified query failed</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Simplified query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>";
            echo "SQL State: " . $e->errorInfo[0] . "\n";
            echo "Error Code: " . $e->errorInfo[1] . "\n";
            echo "Error Message: " . $e->errorInfo[2] . "\n";
            echo "</pre>";
        }
        
    } else {
        echo "<p class='error'>❌ No proposals found in database</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h3>🔗 Next Steps:</h3>";
echo "<a href='client_dashboard_new.php' class='btn'>👤 Client Dashboard</a>";
echo "<a href='send_proposal_new.php' class='btn'>📝 Submit Proposal</a>";
echo "<a href='login_new.php' class='btn'>🔐 Login to Test</a>";
?>
