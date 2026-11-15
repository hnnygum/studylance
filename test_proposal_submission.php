<?php
/**
 * Test proposal submission with portfolio URL
 */

require_once 'config.php';

echo "<h1>🧪 Test Proposal Submission</h1>";

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in as freelancer
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Please login as freelancer to test proposal submission</p>";
    echo "<a href='login_new.php'>🔐 Login</a>";
    exit();
}

if ($_SESSION['role'] !== 'freelancer') {
    echo "<p style='color: red;'>❌ You must be logged in as freelancer</p>";
    echo "<p>Current role: " . $_SESSION['role'] . "</p>";
    echo "<a href='logout_new.php'>🔐 Logout</a>";
    exit();
}

echo "<p style='color: green;'>✅ Logged in as freelancer: " . $_SESSION['name'] . "</p>";

// Get available jobs
echo "<h2>💼 Available Jobs for Testing:</h2>";
try {
    $db = Database::getInstance();
    $result = $db->query("SELECT id, title, budget FROM jobs WHERE status = 'open' ORDER BY created_at DESC LIMIT 5");
    $jobs = $result->fetchAll();
    
    if (!empty($jobs)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Job ID</th><th>Title</th><th>Budget</th><th>Action</th></tr>";
        
        foreach ($jobs as $job) {
            echo "<tr>";
            echo "<td>{$job['id']}</td>";
            echo "<td>" . htmlspecialchars($job['title']) . "</td>";
            echo "<td>₹" . number_format($job['budget'], 2) . "</td>";
            echo "<td><a href='send_proposal_new.php?job_id={$job['id']}' target='_blank'>📝 Apply</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No open jobs found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Jobs fetch error: " . $e->getMessage() . "</p>";
}

// Test direct proposal submission
echo "<h2>🧪 Direct Proposal Test:</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_submit'])) {
    $job_id = (int)$_POST['job_id'];
    $portfolio_url = trim($_POST['portfolio_url']);
    $proposal_text = trim($_POST['proposal_text']);
    $bid_amount = (float)$_POST['bid_amount'];
    
    echo "<h3>📋 Submission Details:</h3>";
    echo "<ul>";
    echo "<li><strong>Job ID:</strong> $job_id</li>";
    echo "<li><strong>Portfolio URL:</strong> $portfolio_url</li>";
    echo "<li><strong>Proposal Text:</strong> $proposal_text</li>";
    echo "<li><strong>Bid Amount:</strong> ₹$bid_amount</li>";
    echo "<li><strong>Freelancer ID:</strong> " . $_SESSION['user_id'] . "</li>";
    echo "</ul>";
    
    // Validation
    $errors = [];
    
    if (empty($job_id) || $job_id <= 0) {
        $errors[] = "Invalid job ID";
    }
    
    if (empty($portfolio_url)) {
        $errors[] = "Portfolio URL is required";
    } elseif (!filter_var($portfolio_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Invalid portfolio URL format";
    }
    
    if (empty($bid_amount) || $bid_amount <= 0) {
        $errors[] = "Invalid bid amount";
    }
    
    if (!empty($errors)) {
        echo "<h3 style='color: red;'>❌ Validation Errors:</h3>";
        foreach ($errors as $error) {
            echo "<p style='color: red;'>❌ $error</p>";
        }
    } else {
        // Try to insert proposal
        try {
            $db = Database::getInstance();
            
            // Check if already applied
            $stmt = $db->query("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?", [$job_id, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: orange;'>⚠️ You have already applied to this job</p>";
            } else {
                // Insert proposal
                $sql = "INSERT INTO proposals (job_id, freelancer_id, bid_amount, portfolio_url, proposal_text, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
                $stmt = $db->query($sql, [$job_id, $_SESSION['user_id'], $bid_amount, $portfolio_url, $proposal_text]);
                
                if ($stmt->rowCount() > 0) {
                    echo "<h3 style='color: green;'>✅ Proposal Submitted Successfully!</h3>";
                    echo "<p>Proposal ID: " . $db->lastInsertId() . "</p>";
                    echo "<p>Status: Pending</p>";
                    
                    // Clean up test record
                    $db->query("DELETE FROM proposals WHERE id = " . $db->lastInsertId());
                    echo "<p style='color: blue;'>ℹ️ Test record cleaned up automatically</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to submit proposal</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
        }
    }
} else {
    // Show test form
    echo "<form method='post'>";
    echo "<div style='background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label style='display: block; margin-bottom: 5px; color: white;'>Job ID:</label>";
    echo "<input type='number' name='job_id' value='1' min='1' style='width: 100%; padding: 8px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); color: white;'>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label style='display: block; margin-bottom: 5px; color: white;'>Portfolio URL:</label>";
    echo "<input type='url' name='portfolio_url' value='https://github.com/test' style='width: 100%; padding: 8px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); color: white;'>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label style='display: block; margin-bottom: 5px; color: white;'>Proposal Text:</label>";
    echo "<textarea name='proposal_text' rows='3' style='width: 100%; padding: 8px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); color: white;'>Test proposal for database validation</textarea>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label style='display: block; margin-bottom: 5px; color: white;'>Bid Amount (₹):</label>";
    echo "<input type='number' name='bid_amount' value='1000' min='1' step='0.01' style='width: 100%; padding: 8px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); color: white;'>";
    echo "</div>";
    
    echo "<button type='submit' name='test_submit' style='background: #6a5cff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🧪 Test Submit</button>";
    echo "</div>";
    echo "</form>";
}

echo "<hr>";
echo "<h2>🔗 Quick Links:</h2>";
echo "<a href='debug_proposal_database.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔍 Debug Database</a><br>";
echo "<a href='fix_proposal_database.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔧 Fix Database</a><br>";
echo "<a href='send_proposal_new.php?job_id=1' style='background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>📝 Proposal Form</a><br>";
echo "<a href='freelancer_dashboard_new.php' style='background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>👨‍💻 Dashboard</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Proposal Submission Test</h3>";
echo "<p>This page tests the complete proposal submission process:</p>";
echo "<ul>";
echo "<li><strong>Database Connection:</strong> Verifies database connectivity</li>";
echo "<li><strong>Table Structure:</strong> Checks required columns exist</li>";
echo "<li><strong>Validation:</strong> Tests form validation logic</li>";
echo "<li><strong>Insertion:</strong> Tests database insertion process</li>";
echo "<li><strong>Cleanup:</strong> Removes test records automatically</li>";
echo "</ul>";
echo "</div>";
?>
