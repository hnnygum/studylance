<?php
/**
 * Test Database Connection
 */

echo "<h2>Database Connection Test</h2>";

try {
    // Test basic PDO connection
    $dsn = "mysql:host=localhost;dbname=studylance;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ PDO Connection successful<br>";
    
    // Test if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'studylance'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database 'studylance' exists<br>";
    } else {
        echo "❌ Database 'studylance' does not exist<br>";
        echo "<p>Please create the database first:</p>";
        echo "<code>CREATE DATABASE studylance;</code>";
    }
    
    // Test if tables exist
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SHOW TABLES FROM studylance");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<br>📋 Tables found: " . implode(', ', $tables) . "<br>";
        
        $required_tables = ['users', 'jobs', 'proposals', 'messages'];
        foreach ($required_tables as $table) {
            if (in_array($table, $tables)) {
                echo "✅ Table '$table' exists<br>";
            } else {
                echo "❌ Table '$table' missing<br>";
            }
        }
    }
    
    echo "<br><h3>✅ Connection test completed</h3>";
    echo "<p><a href='setup_database.php'>Run Database Setup</a></p>";
    
} catch (PDOException $e) {
    echo "<h3>❌ Connection Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<p>Check your database username/password in config.php</p>";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<p>Database 'studylance' does not exist. Please create it first.</p>";
        echo "<p>In phpMyAdmin, run: <code>CREATE DATABASE studylance;</code></p>";
    } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "<p>MySQL server is not running. Please start XAMPP/MySQL.</p>";
    }
}
?>
