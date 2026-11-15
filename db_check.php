<?php
/**
 * Simple Database Check
 * Diagnoses database connection and table issues
 */

echo "<h2>Database Connection Check</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; }
</style>";

// Step 1: Check if MySQL extension is available
if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
    echo "<p class='error'>❌ PDO or PDO_MYSQL extension not loaded</p>";
    echo "<p class='info'>Please enable PDO and PDO_MYSQL extensions in php.ini</p>";
    exit;
} else {
    echo "<p class='success'>✅ PDO and PDO_MYSQL extensions are loaded</p>";
}

// Step 2: Check database connection
try {
    // Try to connect without database first
    $pdo = new PDO("mysql:host=localhost", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p class='success'>✅ MySQL connection successful</p>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ MySQL connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p class='info'>Please check:</p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL service is running</li>";
    echo "<li>MySQL host is correct (usually localhost)</li>";
    echo "<li>MySQL username/password are correct (usually root/empty)</li>";
    echo "</ul>";
    exit;
}

// Step 3: Check if studylance database exists
try {
    $stmt = $pdo->query("SHOW DATABASES LIKE 'studylance'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ 'studylance' database exists</p>";
        
        // Connect to the studylance database
        $pdo = new PDO("mysql:host=localhost;dbname=studylance", "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "<p class='success'>✅ Connected to studylance database</p>";
        
        // Step 4: Check if tables exist
        $tables = ['users', 'jobs', 'proposals', 'messages'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✅ '$table' table exists</p>";
            } else {
                echo "<p class='warning'>⚠️ '$table' table missing</p>";
            }
        }
        
        // Step 5: Show table structures
        echo "<h3>Table Structures</h3>";
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<h4>$table table:</h4>";
                $stmt = $pdo->query("DESCRIBE $table");
                $columns = $stmt->fetchAll();
                echo "<pre>";
                foreach ($columns as $column) {
                    echo sprintf("%-20s %-20s %-10s %-10s\n", 
                        $column['Field'], 
                        $column['Type'], 
                        $column['Null'], 
                        $column['Key']
                    );
                }
                echo "</pre>";
            }
        }
        
        // Step 6: Test simple query
        echo "<h3>Query Test</h3>";
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            echo "<p class='success'>✅ Simple query works: {$result['count']} users in database</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } else {
        echo "<p class='error'>❌ 'studylance' database does not exist</p>";
        echo "<p class='info'>Creating studylance database...</p>";
        
        try {
            $pdo->exec("CREATE DATABASE studylance");
            echo "<p class='success'>✅ 'studylance' database created</p>";
            
            // Now connect to the new database
            $pdo = new PDO("mysql:host=localhost;dbname=studylance", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            echo "<p class='success'>✅ Connected to new studylance database</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Failed to create database: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h3>🔧 Quick Fix Options</h3>";
echo "<a href='fix_database.php' class='btn'>🔄 Complete Database Reset</a>";
echo "<a href='setup_database.php' class='btn'>⚙️ Setup Database</a>";
echo "<a href='test_connection.php' class='btn'>🧪 Test Connection</a>";

echo "<h3>📋 Manual SQL Commands (if needed)</h3>";
echo "<p>If scripts don't work, try these SQL commands in phpMyAdmin:</p>";
echo "<pre>
-- Create database
CREATE DATABASE IF NOT EXISTS studylance;
USE studylance;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client', 'freelancer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    budget DECIMAL(10,2) NOT NULL,
    status ENUM('open', 'in_progress', 'completed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id)
);

-- Create proposals table
CREATE TABLE IF NOT EXISTS proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,
    cover_letter TEXT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (freelancer_id) REFERENCES users(id)
);
</pre>";
?>
