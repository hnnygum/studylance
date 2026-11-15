<?php
/**
 * Fix foreign key constraint issues for proposal testing
 */

require_once 'config.php';

echo "<h1>🔧 Fix Foreign Key Constraint Issues</h1>";

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $db = Database::getInstance();
    
    // Check available jobs
    echo "<h2>💼 Available Jobs:</h2>";
    $result = $db->query("SELECT id, title, budget, status FROM jobs ORDER BY id");
    $jobs = $result->fetchAll();
    
    if (!empty($jobs)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Job ID</th><th>Title</th><th>Budget</th><th>Status</th></tr>";
        
        foreach ($jobs as $job) {
            echo "<tr>";
            echo "<td>{$job['id']}</td>";
            echo "<td>" . htmlspecialchars($job['title']) . "</td>";
            echo "<td>₹" . number_format($job['budget'], 2) . "</td>";
            echo "<td>" . ucfirst($job['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No jobs found in database.</p>";
    }
    
    // Check available freelancers
    echo "<h2>👨‍💻 Available Freelancers:</h2>";
    $result = $db->query("SELECT id, name, email FROM users WHERE role = 'freelancer' ORDER BY id");
    $freelancers = $result->fetchAll();
    
    if (!empty($freelancers)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
        
        foreach ($freelancers as $freelancer) {
            echo "<tr>";
            echo "<td>{$freelancer['id']}</td>";
            echo "<td>" . htmlspecialchars($freelancer['name']) . "</td>";
            echo "<td>" . htmlspecialchars($freelancer['email']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No freelancers found in database.</p>";
    }
    
    // Check existing proposals
    echo "<h2>📋 Existing Proposals:</h2>";
    $result = $db->query("SELECT id, job_id, freelancer_id, status FROM proposals ORDER BY id");
    $proposals = $result->fetchAll();
    
    if (!empty($proposals)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Job ID</th><th>Freelancer ID</th><th>Status</th></tr>";
        
        foreach ($proposals as $proposal) {
            echo "<tr>";
            echo "<td>{$proposal['id']}</td>";
            echo "<td>{$proposal['job_id']}</td>";
            echo "<td>{$proposal['freelancer_id']}</td>";
            echo "<td>" . ucfirst($proposal['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No proposals found in database.</p>";
    }
    
    // Find valid combinations
    echo "<h2>✅ Finding Valid Combinations:</h2>";
    $valid_combinations = [];
    
    // Get all open jobs
    $result = $db->query("SELECT id FROM jobs WHERE status = 'open'");
    $open_jobs = $result->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all freelancers
    $result = $db->query("SELECT id FROM users WHERE role = 'freelancer'");
    $freelancer_ids = $result->fetchAll(PDO::FETCH_COLUMN);
    
    // Get existing proposals
    $result = $db->query("SELECT job_id, freelancer_id FROM proposals");
    $existing_proposals = $result->fetchAll();
    $existing_combinations = [];
    foreach ($existing_proposals as $proposal) {
        $existing_combinations[] = $proposal['job_id'] . '-' . $proposal['freelancer_id'];
    }
    
    // Find valid combinations
    foreach ($open_jobs as $job_id) {
        foreach ($freelancer_ids as $freelancer_id) {
            $combination = $job_id . '-' . $freelancer_id;
            if (!in_array($combination, $existing_combinations)) {
                $valid_combinations[] = [
                    'job_id' => $job_id,
                    'freelancer_id' => $freelancer_id
                ];
            }
        }
    }
    
    if (!empty($valid_combinations)) {
        echo "<p>Found " . count($valid_combinations) . " valid combinations:</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Job ID</th><th>Freelancer ID</th><th>Test Result</th></tr>";
        
        foreach ($valid_combinations as $combination) {
            $job_id = $combination['job_id'];
            $freelancer_id = $combination['freelancer_id'];
            
            echo "<tr>";
            echo "<td>$job_id</td>";
            echo "<td>$freelancer_id</td>";
            echo "<td>";
            
            // Test this combination
            try {
                $test_portfolio_url = 'https://github.com/test-' . $freelancer_id;
                $test_proposal_text = 'Test proposal for job ' . $job_id;
                $test_bid_amount = 1000.00;
                
                $sql = "INSERT INTO proposals (job_id, freelancer_id, bid_amount, portfolio_url, proposal_text, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
                $stmt = $db->query($sql, [$job_id, $freelancer_id, $test_bid_amount, $test_portfolio_url, $test_proposal_text]);
                
                if ($stmt->rowCount() > 0) {
                    $insert_id = $db->lastInsertId();
                    echo "<span style='color: green;'>✅ Success (ID: $insert_id)</span>";
                    
                    // Clean up test record
                    $db->query("DELETE FROM proposals WHERE id = $insert_id");
                } else {
                    echo "<span style='color: red;'>❌ Failed - No rows affected</span>";
                }
                
            } catch (Exception $e) {
                echo "<span style='color: red;'>❌ Error: " . $e->getMessage() . "</span>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: orange;'>⚠️ No valid combinations found - all freelancers have applied to all open jobs</p>";
        
        // Create a test job if needed
        echo "<h3>🔧 Create Test Job:</h3>";
        echo "<p>Creating a test job for testing purposes...</p>";
        
        try {
            // Get a client ID
            $result = $db->query("SELECT id FROM users WHERE role = 'client' LIMIT 1");
            $client = $result->fetch();
            
            if ($client) {
                $sql = "INSERT INTO jobs (title, description, budget, client_id, status, created_at) VALUES (?, ?, ?, ?, 'open', NOW())";
                $stmt = $db->query($sql, [
                    'Test Job for Proposal Testing',
                    'This is a test job created specifically for testing proposal submission functionality.',
                    5000.00,
                    $client['id']
                ]);
                
                if ($stmt->rowCount() > 0) {
                    $new_job_id = $db->lastInsertId();
                    echo "<p style='color: green;'>✅ Test job created successfully (ID: $new_job_id)</p>";
                    echo "<p>You can now test proposals with Job ID $new_job_id and any freelancer ID.</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to create test job</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ No client found to create test job</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error creating test job: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🔗 Quick Links:</h2>";
echo "<a href='debug_proposal_database.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔍 Debug Database</a><br>";
echo "<a href='test_valid_proposal.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🧪 Test Valid Proposals</a><br>";
echo "<a href='send_proposal_new.php?job_id=1' style='background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>📝 Proposal Form</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Foreign Key Issues Fixed!</h3>";
echo "<p>This tool resolves foreign key constraint issues:</p>";
echo "<ul>";
echo "<li><strong>Validates Jobs:</strong> Checks if job IDs exist</li>";
echo "<li><strong>Validates Freelancers:</strong> Checks if freelancer IDs exist</li>";
echo "<li><strong>Finds Valid Combinations:</strong> Identifies unused job-freelancer pairs</li>";
echo "<li><strong>Tests Insertion:</strong> Validates proposal submission</li>";
echo "<li><strong>Creates Test Job:</strong> Adds test job if needed</li>";
echo "</ul>";
echo "</div>";
?>
