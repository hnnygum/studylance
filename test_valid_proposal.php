<?php
/**
 * Test proposal submission with valid job-freelancer combinations
 */

require_once 'config.php';

echo "<h1>🧪 Test Valid Proposal Submission</h1>";

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $db = Database::getInstance();
    
    // Get all available jobs
    echo "<h2>💼 Available Jobs:</h2>";
    $result = $db->query("SELECT id, title, budget FROM jobs WHERE status = 'open' ORDER BY id");
    $jobs = $result->fetchAll();
    
    if (!empty($jobs)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Job ID</th><th>Title</th><th>Budget</th></tr>";
        
        foreach ($jobs as $job) {
            echo "<tr>";
            echo "<td>{$job['id']}</td>";
            echo "<td>" . htmlspecialchars($job['title']) . "</td>";
            echo "<td>₹" . number_format($job['budget'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No open jobs found.</p>";
    }
    
    // Get all freelancers
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
        echo "<p>No freelancers found.</p>";
    }
    
    // Get existing proposals to avoid duplicates
    echo "<h2>📋 Existing Proposals:</h2>";
    $result = $db->query("SELECT job_id, freelancer_id FROM proposals ORDER BY job_id, freelancer_id");
    $existing_proposals = $result->fetchAll();
    
    if (!empty($existing_proposals)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Job ID</th><th>Freelancer ID</th></tr>";
        
        foreach ($existing_proposals as $proposal) {
            echo "<tr>";
            echo "<td>{$proposal['job_id']}</td>";
            echo "<td>{$proposal['freelancer_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Create existing combinations array
        $existing_combinations = [];
        foreach ($existing_proposals as $proposal) {
            $existing_combinations[] = $proposal['job_id'] . '-' . $proposal['freelancer_id'];
        }
    } else {
        echo "<p>No existing proposals found.</p>";
        $existing_combinations = [];
    }
    
    // Find valid combinations
    echo "<h2>✅ Valid Job-Freelancer Combinations:</h2>";
    $valid_combinations = [];
    
    foreach ($jobs as $job) {
        foreach ($freelancers as $freelancer) {
            $combination = $job['id'] . '-' . $freelancer['id'];
            if (!in_array($combination, $existing_combinations)) {
                $valid_combinations[] = [
                    'job_id' => $job['id'],
                    'job_title' => $job['title'],
                    'freelancer_id' => $freelancer['id'],
                    'freelancer_name' => $freelancer['name'],
                    'freelancer_email' => $freelancer['email']
                ];
            }
        }
    }
    
    if (!empty($valid_combinations)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Job ID</th><th>Job Title</th><th>Freelancer ID</th><th>Freelancer Name</th><th>Action</th></tr>";
        
        foreach ($valid_combinations as $combination) {
            echo "<tr>";
            echo "<td>{$combination['job_id']}</td>";
            echo "<td>" . htmlspecialchars($combination['job_title']) . "</td>";
            echo "<td>{$combination['freelancer_id']}</td>";
            echo "<td>" . htmlspecialchars($combination['freelancer_name']) . "</td>";
            echo "<td><button onclick='testProposal({$combination['job_id']}, {$combination['freelancer_id']})' style='background: #6a5cff; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;'>🧪 Test</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No valid combinations found - all freelancers have applied to all jobs</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test proposal submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_proposal'])) {
    $job_id = (int)$_POST['job_id'];
    $freelancer_id = (int)$_POST['freelancer_id'];
    $portfolio_url = 'https://github.com/test-user';
    $proposal_text = 'Test proposal for validation';
    $bid_amount = 1000.00;
    
    try {
        $db = Database::getInstance();
        
        // Insert proposal
        $sql = "INSERT INTO proposals (job_id, freelancer_id, bid_amount, portfolio_url, proposal_text, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        $stmt = $db->query($sql, [$job_id, $freelancer_id, $bid_amount, $portfolio_url, $proposal_text]);
        
        if ($stmt->rowCount() > 0) {
            $insert_id = $db->lastInsertId();
            
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => "Test proposal inserted successfully (ID: $insert_id)",
                'proposal_id' => $insert_id
            ]);
            
            // Clean up test record
            $db->query("DELETE FROM proposals WHERE id = $insert_id");
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to insert proposal'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    
    exit;
}
?>

<script>
function testProposal(jobId, freelancerId) {
    if (confirm(`Test proposal for Job ${jobId} by Freelancer ${freelancerId}?`)) {
        fetch('test_valid_proposal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `test_proposal=1&job_id=${jobId}&freelancer_id=${freelancerId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error: ' + error.message);
        });
    }
}
</script>

<style>
table {
    margin: 20px 0;
    border-collapse: collapse;
    width: 100%;
}

th, td {
    padding: 10px;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background-color: #f2f2f2;
    font-weight: bold;
}

button {
    transition: all 0.3s ease;
}

button:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
</style>

<hr>
<h2>🔗 Quick Links:</h2>
<a href="debug_proposal_database.php" style="background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;">🔍 Debug Database</a><br>
<a href="fix_portfolio_urls.php" style="background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;">🔧 Fix Portfolio URLs</a><br>
<a href="send_proposal_new.php?job_id=1" style="background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;">📝 Proposal Form</a>

<br><div style="background: #d4edda; padding: 15px; border-radius: 5px;">
<h3>✅ Valid Proposal Testing</h3>
<p>This tool helps test proposal submission with valid combinations:</p>
<ul>
<li><strong>Available Jobs:</strong> Shows all open jobs</li>
<li><strong>Available Freelancers:</strong> Shows all freelancer accounts</li>
<li><strong>Existing Proposals:</strong> Shows current proposals to avoid duplicates</li>
<li><strong>Valid Combinations:</strong> Shows job-freelancer pairs that can be tested</li>
<li><strong>Test Button:</strong> Tests proposal insertion and cleanup</li>
</ul>
</div>
