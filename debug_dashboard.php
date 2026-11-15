<?php
/**
 * Debug Dashboard - Check Database Connection and Data
 */

require_once 'config.php';

// Start session for user context
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Not logged in. Please login first.<br>";
    echo '<a href="login_new.php">Login</a>';
    exit;
}

echo "<h2>Debug Dashboard</h2>";
echo "<p>Logged in as: " . htmlspecialchars($_SESSION['name'] ?? 'Unknown') . " (ID: " . $_SESSION['user_id'] . ")</p>";
echo "<p>Role: " . htmlspecialchars($_SESSION['role'] ?? 'Unknown') . "</p>";

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    echo "<h3>Database Connection: ✅ OK</h3>";
    
    // Test basic query
    echo "<h3>Testing Basic Query:</h3>";
    $stmt = $db->query("SELECT COUNT(*) as user_count FROM users");
    $result = $stmt->fetch();
    echo "<p>Total users: " . $result['user_count'] . "</p>";
    
    // Test jobs query
    echo "<h3>Testing Jobs Query:</h3>";
    $sql = "SELECT * FROM jobs WHERE client_id = ? ORDER BY created_at DESC LIMIT 5";
    echo "<p>SQL: " . $sql . "</p>";
    echo "<p>Params: [" . $userId . "]</p>";
    
    $stmt = $db->query($sql, [$userId]);
    $jobs = $stmt->fetchAll();
    
    echo "<p>Job count: " . count($jobs) . "</p>";
    if ($jobs) {
        echo "<table border='1' style='width:100%; margin-top:10px;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Budget</th><th>Status</th><th>Created</th></tr>";
        foreach ($jobs as $job) {
            echo "<tr>";
            echo "<td>" . $job['id'] . "</td>";
            echo "<td>" . htmlspecialchars($job['title']) . "</td>";
            echo "<td>" . $job['budget'] . "</td>";
            echo "<td>" . $job['status'] . "</td>";
            echo "<td>" . $job['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No jobs found for this user.</p>";
    }
    
    // Test proposals query
    echo "<h3>Testing Proposals Query:</h3>";
    $sql = "
        SELECT p.*, j.title as job_title, u.name as freelancer_name, u.email as freelancer_email, u.id as freelancer_id
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        JOIN users u ON p.freelancer_id = u.id 
        WHERE j.client_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ";
    echo "<p>SQL: " . $sql . "</p>";
    echo "<p>Params: [" . $userId . "]</p>";
    
    $stmt = $db->query($sql, [$userId]);
    $proposals = $stmt->fetchAll();
    
    echo "<p>Proposal count: " . count($proposals) . "</p>";
    if ($proposals) {
        echo "<table border='1' style='width:100%; margin-top:10px;'>";
        echo "<tr><th>ID</th><th>Freelancer</th><th>Job</th><th>Bid</th><th>Status</th><th>Created</th></tr>";
        foreach ($proposals as $proposal) {
            echo "<tr>";
            echo "<td>" . $proposal['id'] . "</td>";
            echo "<td>" . htmlspecialchars($proposal['freelancer_name']) . "</td>";
            echo "<td>" . htmlspecialchars($proposal['job_title']) . "</td>";
            echo "<td>" . $proposal['bid_amount'] . "</td>";
            echo "<td>" . $proposal['status'] . "</td>";
            echo "<td>" . $proposal['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No proposals found for this user.</p>";
    }
    
    // Check if there are any jobs in database
    echo "<h3>All Jobs in Database:</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM jobs");
    $result = $stmt->fetch();
    echo "<p>Total jobs in database: " . $result['total'] . "</p>";
    
    // Check if there are any proposals in database
    echo "<h3>All Proposals in Database:</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM proposals");
    $result = $stmt->fetch();
    echo "<p>Total proposals in database: " . $result['total'] . "</p>";
    
} catch (Exception $e) {
    echo "<h3>Database Error:</h3>";
    echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<a href='client_dashboard_new.php'>Back to Dashboard</a>";
?>
