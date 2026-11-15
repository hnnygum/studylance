<?php
/**
 * Test Proposal Update - WITH RUPEES
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
            echo "<td>₹{$proposal['bid_amount']}</td>";
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
                echo "<p class='success'>✅ Simple update successful</p>";
            } else {
                echo "<p class='error'>❌ Simple update failed</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Simple update error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        // Reset status
        $pdo->prepare("UPDATE proposals SET status = 'pending' WHERE id = ?")->execute([$testProposalId]);
        
        // Test 2: Update with subquery (the problematic one)
        echo "<h4>Test 2: Update with Subquery (Original Problem)</h4>";
        try {
            $sql = "UPDATE proposals SET status = 'accepted' 
                    WHERE id = ? AND job_id IN (
                        SELECT id FROM jobs WHERE client_id = ?
                    )";
            echo "<p class='info'>SQL: " . htmlspecialchars($sql) . "</p>";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$testProposalId, $testClientId]);
            
            if ($result) {
                echo "<p class='success'>✅ Subquery update successful</p>";
            } else {
                echo "<p class='error'>❌ Subquery update failed</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Subquery update error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        // Reset status
        $pdo->prepare("UPDATE proposals SET status = 'pending' WHERE id = ?")->execute([$testProposalId]);
        
        // Test 3: Fixed version (separate queries)
        echo "<h4>Test 3: Fixed Version (Separate Queries)</h4>";
        try {
            // First get the job_id
            $stmt = $pdo->prepare("SELECT job_id FROM proposals WHERE id = ?");
            $stmt->execute([$testProposalId]);
            $proposal = $stmt->fetch();
            
            if ($proposal) {
                $jobId = $proposal['job_id'];
                echo "<p class='info'>Found job_id: $jobId</p>";
                
                // Check if job belongs to client
                $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND client_id = ?");
                $stmt->execute([$jobId, $testClientId]);
                $job = $stmt->fetch();
                
                if ($job) {
                    echo "<p class='info'>Job belongs to client - updating proposal</p>";
                    
                    // Update the proposal
                    $stmt = $pdo->prepare("UPDATE proposals SET status = 'accepted' WHERE id = ?");
                    $result = $stmt->execute([$testProposalId]);
                    
                    if ($result) {
                        echo "<p class='success'>✅ Fixed version successful</p>";
                    } else {
                        echo "<p class='error'>❌ Fixed version failed</p>";
                    }
                } else {
                    echo "<p class='error'>❌ Job does not belong to client</p>";
                }
            } else {
                echo "<p class='error'>❌ Proposal not found</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Fixed version error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        // Reset status
        $pdo->prepare("UPDATE proposals SET status = 'pending' WHERE id = ?")->execute([$testProposalId]);
        
        // Test 4: Transaction version
        echo "<h4>Test 4: Transaction Version</h4>";
        try {
            $pdo->beginTransaction();
            
            // Get proposal details
            $stmt = $pdo->prepare("SELECT job_id FROM proposals WHERE id = ?");
            $stmt->execute([$testProposalId]);
            $proposal = $stmt->fetch();
            
            if ($proposal) {
                $jobId = $proposal['job_id'];
                
                // Verify job ownership
                $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND client_id = ?");
                $stmt->execute([$jobId, $testClientId]);
                $job = $stmt->fetch();
                
                if ($job) {
                    // Update proposal
                    $stmt = $pdo->prepare("UPDATE proposals SET status = 'accepted' WHERE id = ?");
                    $stmt->execute([$testProposalId]);
                    
                    // Reject other proposals
                    $stmt = $pdo->prepare("UPDATE proposals SET status = 'rejected' WHERE job_id = ? AND id != ?");
                    $stmt->execute([$jobId, $testProposalId]);
                    
                    // Close job
                    $stmt = $pdo->prepare("UPDATE jobs SET status = 'closed' WHERE id = ?");
                    $stmt->execute([$jobId]);
                    
                    $pdo->commit();
                    echo "<p class='success'>✅ Transaction version successful</p>";
                    
                    // Reset for next test
                    $pdo->beginTransaction();
                    $pdo->prepare("UPDATE proposals SET status = 'pending' WHERE job_id = ?")->execute([$jobId]);
                    $pdo->prepare("UPDATE jobs SET status = 'open' WHERE id = ?")->execute([$jobId]);
                    $pdo->commit();
                    
                } else {
                    $pdo->rollback();
                    echo "<p class='error'>❌ Job does not belong to client</p>";
                }
            } else {
                $pdo->rollback();
                echo "<p class='error'>❌ Proposal not found</p>";
            }
        } catch (Exception $e) {
            $pdo->rollback();
            echo "<p class='error'>❌ Transaction version error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } else {
        echo "<p class='error'>❌ No proposals found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h3>🔗 Quick Links:</h3>";
echo "<a href='client_dashboard_new.php' class='btn'>Client Dashboard</a>";
echo "<a href='freelancer_dashboard_new.php' class='btn'>Freelancer Dashboard</a>";
echo "<a href='test_data.php' class='btn'>Generate Test Data</a>";
echo "<a href='debug_dashboard.php' class='btn'>Debug Dashboard</a>";
?>
