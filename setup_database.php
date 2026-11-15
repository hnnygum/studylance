<?php
/**
 * StudyLance Database Setup
 * Creates necessary tables and inserts demo data
 */

require_once 'config.php';

echo "<h2>StudyLance Database Setup</h2>";

try {
    $db = Database::getInstance();
    
    // Create users table
    $db->query("CREATE TABLE IF NOT EXISTS users (
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
    
    echo "✅ Users table created/verified<br>";
    
    // Create jobs table
    $db->query("CREATE TABLE IF NOT EXISTS jobs (
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
    
    echo "✅ Jobs table created/verified<br>";
    
    // Create proposals table
    $db->query("CREATE TABLE IF NOT EXISTS proposals (
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
    
    echo "✅ Proposals table created/verified<br>";
    
    // Create messages table
    $db->query("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "✅ Messages table created/verified<br>";
    
    // Check if admin user exists
    $stmt = $db->query("SELECT id FROM users WHERE email = 'admin@studylance.com'");
    if ($stmt->rowCount() === 0) {
        // Insert demo admin user
        $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
        $db->query("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)", 
                   ['Admin User', 'admin@studylance.com', $hashed_password, 'admin']);
        echo "✅ Admin user created (admin@studylance.com / password123)<br>";
    } else {
        echo "ℹ️ Admin user already exists<br>";
    }
    
    // Check if demo client exists
    $stmt = $db->query("SELECT id FROM users WHERE email = 'john@example.com'");
    if ($stmt->rowCount() === 0) {
        // Insert demo client user
        $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
        $db->query("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)", 
                   ['John Doe', 'john@example.com', $hashed_password, 'client']);
        echo "✅ Demo client created (john@example.com / password123)<br>";
    } else {
        echo "ℹ️ Demo client already exists<br>";
    }
    
    // Check if demo freelancer exists
    $stmt = $db->query("SELECT id FROM users WHERE email = 'jane@example.com'");
    if ($stmt->rowCount() === 0) {
        // Insert demo freelancer user
        $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
        $db->query("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)", 
                   ['Jane Smith', 'jane@example.com', $hashed_password, 'freelancer']);
        echo "✅ Demo freelancer created (jane@example.com / password123)<br>";
    } else {
        echo "ℹ️ Demo freelancer already exists<br>";
    }
    
    echo "<br><h3>✅ Database setup completed successfully!</h3>";
    echo "<p>You can now use the StudyLance platform.</p>";
    echo "<p><a href='index.html'>Go to Homepage</a> | <a href='login_new.php'>Go to Login</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Database Setup Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection settings in config.php</p>";
}
?>
