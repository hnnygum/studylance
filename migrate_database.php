<?php
/**
 * StudyLance Database Migration
 * Adds missing columns to existing tables
 */

require_once 'config.php';

echo "<h2>StudyLance Database Migration</h2>";

try {
    $db = Database::getInstance();
    
    // Add skills_required column to jobs table if it doesn't exist
    $stmt = $db->query("SHOW COLUMNS FROM jobs LIKE 'skills_required'");
    if ($stmt->rowCount() === 0) {
        $db->query("ALTER TABLE jobs ADD COLUMN skills_required TEXT AFTER description");
        echo "✅ Added skills_required column to jobs table<br>";
    } else {
        echo "ℹ️ skills_required column already exists in jobs table<br>";
    }
    
    // Check if remember_token and remember_expiry columns exist in users table
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'remember_token'");
    if ($stmt->rowCount() === 0) {
        $db->query("ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) NULL AFTER email");
        echo "✅ Added remember_token column to users table<br>";
    } else {
        echo "ℹ️ remember_token column already exists in users table<br>";
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'remember_expiry'");
    if ($stmt->rowCount() === 0) {
        $db->query("ALTER TABLE users ADD COLUMN remember_expiry DATETIME NULL AFTER remember_token");
        echo "✅ Added remember_expiry column to users table<br>";
    } else {
        echo "ℹ️ remember_expiry column already exists in users table<br>";
    }
    
    // Check if is_read column exists in messages table
    $stmt = $db->query("SHOW COLUMNS FROM messages LIKE 'is_read'");
    if ($stmt->rowCount() === 0) {
        $db->query("ALTER TABLE messages ADD COLUMN is_read BOOLEAN DEFAULT FALSE AFTER message");
        echo "✅ Added is_read column to messages table<br>";
    } else {
        echo "ℹ️ is_read column already exists in messages table<br>";
    }
    
    echo "<br><h3>✅ Database migration completed successfully!</h3>";
    echo "<p>All required columns have been added to the database tables.</p>";
    echo "<p><a href='index.html'>Go to Homepage</a> | <a href='login_new.php'>Go to Login</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Migration Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and permissions.</p>";
}
?>
