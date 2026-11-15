<?php
/**
 * Test freelancer messaging functionality
 */

require_once 'config.php';

echo "<h1>💬 Test Freelancer Messaging</h1>";

// Check if user is logged in as freelancer
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Please login as freelancer to test messaging</p>";
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

// Get freelancer's proposals with client information
echo "<h2>📋 My Proposals with Messaging Access:</h2>";
try {
    $db = Database::getInstance();
    
    $result = $db->query("
        SELECT p.*, j.title as job_title, j.client_id, u.name as client_name, u.email as client_email
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        JOIN users u ON j.client_id = u.id 
        WHERE p.freelancer_id = ? 
        ORDER BY p.created_at DESC
    ", [$_SESSION['user_id']]);
    
    $proposals = $result->fetchAll();
    
    if (!empty($proposals)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Job</th><th>Client</th><th>Status</th><th>Client ID</th><th>Messaging Access</th><th>Action</th></tr>";
        
        foreach ($proposals as $proposal) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($proposal['job_title']) . "</td>";
            echo "<td>" . htmlspecialchars($proposal['client_name']) . "</td>";
            echo "<td>" . ucfirst($proposal['status']) . "</td>";
            echo "<td>" . $proposal['client_id'] . "</td>";
            echo "<td>";
            if ($proposal['status'] === 'accepted' || $proposal['status'] === 'completed') {
                echo "<span style='color: green;'>✅ Available</span>";
            } else {
                echo "<span style='color: orange;'>⏳ After acceptance</span>";
            }
            echo "</td>";
            echo "<td>";
            if ($proposal['status'] === 'accepted' || $proposal['status'] === 'completed') {
                echo "<a href='messages_new.php?user=" . $proposal['client_id'] . "' style='background: #3498db; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>💬 Message</a>";
            } else {
                echo "<span style='color: #999;'>Not available</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No proposals found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Check messaging system files
echo "<h2>📁 Messaging System Files:</h2>";
$messaging_files = [
    'messages_new.php' => 'Main messaging system',
    'freelancer_messages.php' => 'Freelancer messaging (old)',
    'client_messages.php' => 'Client messaging (old)'
];

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>File</th><th>Purpose</th><th>Status</th></tr>";

foreach ($messaging_files as $file => $purpose) {
    $filepath = __DIR__ . '/' . $file;
    $status = file_exists($filepath) ? '✅ Exists' : '❌ Missing';
    $link = file_exists($filepath) ? "<a href='$file' target='_blank'>📄 View</a>" : 'N/A';
    
    echo "<tr>";
    echo "<td><code>$file</code></td>";
    echo "<td>$purpose</td>";
    echo "<td>$status $link</td>";
    echo "</tr>";
}
echo "</table>";

// Test messaging link generation
echo "<h2>🔗 Test Messaging Links:</h2>";
echo "<div style='background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px;'>";

if (!empty($proposals)) {
    foreach ($proposals as $proposal) {
        if ($proposal['status'] === 'accepted' || $proposal['status'] === 'completed') {
            $messaging_url = 'messages_new.php?user=' . $proposal['client_id'];
            echo "<p><strong>Client:</strong> " . htmlspecialchars($proposal['client_name']) . "</p>";
            echo "<p><strong>Client ID:</strong> " . $proposal['client_id'] . "</p>";
            echo "<p><strong>Messaging URL:</strong> <a href='$messaging_url' target='_blank' style='color: #3498db;'>$messaging_url</a></p>";
            echo "<p><strong>JavaScript Call:</strong> <code>messageClient(" . $proposal['client_id'] . ")</code></p>";
            echo "<hr>";
        }
    }
} else {
    echo "<p>No accepted proposals found for testing.</p>";
}

echo "</div>";

// Show JavaScript function
echo "<h2>📜 JavaScript Function:</h2>";
echo "<div style='background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px;'>";
echo "<pre style='color: #fff; font-family: monospace;'>";
echo "function messageClient(clientId) {
    if (clientId) {
        window.location.href = 'messages_new.php?user=' + clientId;
    } else {
        alert('Unable to open messaging. Please try again.');
    }
}";
echo "</pre>";
echo "</div>";

echo "<hr>";
echo "<h2>🔗 Quick Links:</h2>";
echo "<a href='freelancer_dashboard_new.php' style='background: #6a5cff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>👨‍💻 Freelancer Dashboard</a><br>";
echo "<a href='messages_new.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>💬 Messages</a><br>";
echo "<a href='client_dashboard_new.php' style='background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>👤 Client Dashboard</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Freelancer Messaging Fix Applied!</h3>";
echo "<p>The following fixes have been applied:</p>";
echo "<ul>";
echo "<li><strong>Updated JavaScript:</strong> Changed from freelancer_messages.php to messages_new.php</li>";
echo "<li><strong>Added client_id:</strong> Included client_id in proposals query</li>";
echo "<li><strong>Fixed Links:</strong> Messaging buttons now work correctly</li>";
echo "<li><strong>Access Control:</strong> Only shows for accepted/completed proposals</li>";
echo "</ul>";
echo "</div>";
?>
