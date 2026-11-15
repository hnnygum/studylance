<?php
// Test freelancer messaging system
require_once 'config.php';

echo "<h1>📧 Freelancer Messages System Test</h1>";

// Test 1: Check file exists
echo "<h2>📁 File Check:</h2>";
if (file_exists('freelancer_messages.php')) {
    echo "✅ freelancer_messages.php exists<br>";
} else {
    echo "❌ freelancer_messages.php missing<br>";
}

// Test 2: Database messages table
echo "<h2>🗄 Database Messages Table:</h2>";
try {
    $db = Database::getInstance();
    
    // Check if messages table exists
    $stmt = $db->query("SHOW TABLES LIKE 'messages'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Messages table exists<br>";
        
        // Check table structure
        $stmt = $db->query("DESCRIBE messages");
        $columns = $stmt->fetchAll();
        echo "<p><strong>Table structure:</strong></p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $column) {
            echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Key']}</td></tr>";
        }
        echo "</table>";
        
        // Count messages
        $stmt = $db->query("SELECT COUNT(*) as count FROM messages");
        $count = $stmt->fetch()['count'];
        echo "<p><strong>Total messages:</strong> $count</p>";
        
    } else {
        echo "❌ Messages table does not exist<br>";
        
        // Create messages table
        $create_sql = "CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_read TINYINT(1) DEFAULT 0,
            FOREIGN KEY (sender_id) REFERENCES users(id),
            FOREIGN KEY (receiver_id) REFERENCES users(id)
        )";
        
        if ($db->query($create_sql)) {
            echo "✅ Messages table created successfully<br>";
        } else {
            echo "❌ Failed to create messages table<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Check users
echo "<h2>👥 Users Check:</h2>";
try {
    $db = Database::getInstance();
    
    // Get freelancers
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'freelancer'");
    $freelancers = $stmt->fetchAll();
    echo "<p><strong>Freelancers (" . count($freelancers) . "):</strong></p>";
    foreach ($freelancers as $freelancer) {
        echo "- {$freelancer['name']} (ID: {$freelancer['id']})<br>";
    }
    
    // Get clients
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'client'");
    $clients = $stmt->fetchAll();
    echo "<p><strong>Clients (" . count($clients) . "):</strong></p>";
    foreach ($clients as $client) {
        echo "- {$client['name']} (ID: {$client['id']})<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Users check error: " . $e->getMessage() . "<br>";
}

// Test 4: Check proposals for messaging
echo "<h2>💼 Proposals Check:</h2>";
try {
    $db = Database::getInstance();
    
    // Get accepted proposals
    $stmt = $db->query("
        SELECT p.id, p.job_id, p.freelancer_id, p.status,
               j.title as job_title,
               c.name as client_name, c.id as client_id,
               f.name as freelancer_name
        FROM proposals p
        JOIN jobs j ON p.job_id = j.id
        JOIN users c ON j.client_id = c.id
        JOIN users f ON p.freelancer_id = f.id
        WHERE p.status IN ('accepted', 'completed')
        ORDER BY p.created_at DESC
    ");
    $accepted_proposals = $stmt->fetchAll();
    
    echo "<p><strong>Accepted/Completed Proposals (" . count($accepted_proposals) . "):</strong></p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Job</th><th>Freelancer</th><th>Client</th><th>Status</th><th>Can Message</th></tr>";
    
    foreach ($accepted_proposals as $proposal) {
        $can_message = ($proposal['status'] === 'accepted' || $proposal['status'] === 'completed') ? '✅ Yes' : '❌ No';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($proposal['job_title']) . "</td>";
        echo "<td>" . htmlspecialchars($proposal['freelancer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($proposal['client_name']) . "</td>";
        echo "<td>" . ucfirst($proposal['status']) . "</td>";
        echo "<td>$can_message</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Proposals check error: " . $e->getMessage() . "<br>";
}

// Test 5: Email configuration
echo "<h2>📧 Email Configuration:</h2>";
if (file_exists('email_config_phpmailer.php')) {
    echo "✅ email_config_phpmailer.php exists<br>";
    
    include 'email_config_phpmailer.php';
    
    if (function_exists('sendEmail')) {
        echo "✅ sendEmail function available<br>";
    } else {
        echo "❌ sendEmail function not found<br>";
    }
} else {
    echo "❌ email_config_phpmailer.php missing<br>";
}

echo "<hr>";
echo "<h2>🔗 Quick Links:</h2>";
echo "<a href='freelancer_messages.php'>💬 Freelancer Messages</a><br>";
echo "<a href='freelancer_dashboard_new.php'>👨‍💻 Freelancer Dashboard</a><br>";
echo "<a href='messages_new.php'>💬 General Messages</a><br>";
echo "<a href='test_phpmailer.php'>📧 Test PHPMailer</a>";

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Freelancer Messaging System Features:</h3>";
echo "<ul>";
echo "<li><strong>Client List:</strong> Shows all available clients to message</li>";
echo "<li><strong>Message History:</strong> View conversation with specific client</li>";
echo "<li><strong>Send Messages:</strong> Compose and send messages to clients</li>";
echo "<li><strong>Email Notifications:</strong> Clients get email when freelancer messages</li>";
echo "<li><strong>Access Control:</strong> Only freelancers can access this page</li>";
echo "<li><strong>Delete Messages:</strong> Remove sent messages</li>";
echo "</ul>";
echo "</div>";

echo "<br><div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>💡 How to Use:</h3>";
echo "<ol>";
echo "<li><strong>Access:</strong> Click '💬 Message' button on accepted proposals</li>";
echo "<li><strong>Select Client:</strong> Choose client from the left sidebar</li>";
echo "<li><strong>Compose:</strong> Type your message and send</li>";
echo "<li><strong>Notifications:</strong> Client receives email notification</li>";
echo "<li><strong>History:</strong> View all past messages</li>";
echo "</ol>";
echo "</div>";
?>
