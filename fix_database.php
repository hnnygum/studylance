<?php
/**
 * Complete Database Fix for StudyLance
 * Creates all necessary tables with correct structure
 */

require_once 'config.php';

echo "<h2>StudyLance Complete Database Fix</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px; }
</style>";

try {
    $db = Database::getInstance();
    echo "<p class='success'>✅ Database connection successful</p>";
    
    // Drop existing tables to start fresh
    echo "<h3>🔄 Resetting Database Tables</h3>";
    
    try {
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->query("DROP TABLE IF EXISTS proposals");
        $db->query("DROP TABLE IF EXISTS jobs");
        $db->query("DROP TABLE IF EXISTS users");
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
        echo "<p class='info'>📝 Dropped existing tables</p>";
    } catch (Exception $e) {
        echo "<p class='info'>ℹ️ Some tables might not exist yet</p>";
    }
    
    // Create users table
    echo "<h3>👥 Creating Users Table</h3>";
    $db->query("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'client', 'freelancer') NOT NULL,
        remember_token VARCHAR(255) NULL,
        remember_expiry DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p class='success'>✅ Users table created</p>";
    
    // Create jobs table
    echo "<h3>💼 Creating Jobs Table</h3>";
    $db->query("CREATE TABLE jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        budget DECIMAL(10,2) NOT NULL,
        status ENUM('open', 'in_progress', 'completed') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p class='success'>✅ Jobs table created</p>";
    
    // Create proposals table
    echo "<h3>📄 Creating Proposals Table</h3>";
    $db->query("CREATE TABLE proposals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        freelancer_id INT NOT NULL,
        bid_amount DECIMAL(10,2) NOT NULL,
        cover_letter TEXT NOT NULL,
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
        FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_proposal (job_id, freelancer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p class='success'>✅ Proposals table created</p>";
    
    // Create messages table
    echo "<h3>💬 Creating Messages Table</h3>";
    $db->query("CREATE TABLE messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p class='success'>✅ Messages table created</p>";
    
    // Insert demo users
    echo "<h3>👤 Creating Demo Users</h3>";
    
    $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
    
    $db->query("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)", 
               ['Admin User', 'admin@studylance.com', $hashed_password, 'admin']);
    
    $db->query("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)", 
               ['John Doe', 'john@example.com', $hashed_password, 'client']);
    
    $db->query("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)", 
               ['Jane Smith', 'jane@example.com', $hashed_password, 'freelancer']);
    
    echo "<p class='success'>✅ Demo users created</p>";
    
    // Insert demo job
    echo "<h3>💼 Creating Demo Job</h3>";
    $stmt = $db->query("SELECT id FROM users WHERE role = 'client' LIMIT 1");
    $client = $stmt->fetch();
    
    if ($client) {
        $db->query("INSERT INTO jobs (client_id, title, description, budget) VALUES (?, ?, ?, ?)", 
                   [$client['id'], 'Website Development Project', 'Looking for an experienced web developer to create a modern e-commerce website with shopping cart functionality.', 1500.00]);
        echo "<p class='success'>✅ Demo job created</p>";
        
        // Get the job ID for testing
        $stmt = $db->query("SELECT id FROM jobs ORDER BY id DESC LIMIT 1");
        $job = $stmt->fetch();
        
        // Test proposal insertion
        echo "<h3>🧪 Testing Proposal Insertion</h3>";
        $stmt = $db->query("SELECT id FROM users WHERE role = 'freelancer' LIMIT 1");
        $freelancer = $stmt->fetch();
        
        if ($freelancer && $job) {
            $db->query("INSERT INTO proposals (job_id, freelancer_id, bid_amount, cover_letter) VALUES (?, ?, ?, ?)", 
                       [$job['id'], $freelancer['id'], 1200.00, 'I am an experienced web developer with 5+ years of experience in e-commerce websites. I can deliver this project within 2 weeks with high quality code.']);
            echo "<p class='success'>✅ Test proposal inserted successfully</p>";
        }
    }
    
    echo "<h2>🎉 Database Fix Complete!</h2>";
    echo "<p class='success'>All tables have been created with correct structure.</p>";
    echo "<p class='info'>Demo accounts created:</p>";
    echo "<pre>
Admin: admin@studylance.com / password123
Client: john@example.com / password123  
Freelancer: jane@example.com / password123</pre>";
    
    echo "<div style='margin-top: 30px;'>";
    echo "<a href='login_new.php' class='btn'>🔐 Go to Login</a>";
    echo "<a href='test_proposals.php' class='btn'>🧪 Test Database</a>";
    echo "<a href='send_proposal_new.php' class='btn'>📝 Submit Proposal</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3 class='error'>❌ Database Fix Error</h3>";
    echo "<p class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>MySQL server is running (XAMPP)</li>";
    echo "<li>Database 'studylance' exists</li>";
    echo "<li>Database credentials in config.php are correct</li>";
    echo "</ul>";
    
    echo "<h3>🔧 Quick Fix Steps:</h3>";
    echo "<ol>";
    echo "<li>Start XAMPP MySQL service</li>";
    echo "<li>Create database: <code>CREATE DATABASE studylance;</code></li>";
    echo "<li>Run this script again</li>";
    echo "</ol>";
}
?>
