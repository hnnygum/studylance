<?php
// Email verification handler
require_once 'config.php';

echo "<h1>📧 Email Verification</h1>";

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if ($token && $email) {
    try {
        $db = Database::getInstance();
        
        // Find user with verification token
        $stmt = $db->query("SELECT * FROM users WHERE email = ? AND verification_token = ?", [$email, $token]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Check if token is not expired (24 hours)
            $token_time = strtotime($user['verification_token_created'] ?? '1970-01-01');
            if (time() - $token_time < 86400) { // 24 hours
                // Verify email
                $db->query("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?", [$user['id']]);
                
                echo "<div style='color: green; font-size: 18px;'>";
                echo "✅ <strong>Email verified successfully!</strong><br>";
                echo "</div>";
                echo "<p>Your email address <strong>" . htmlspecialchars($email) . "</strong> has been verified.</p>";
                echo "<p>You can now log in and use all features of StudyLance.</p>";
                
                echo "<br><a href='login_new.php' style='padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px;'>🔐 Go to Login</a>";
                
            } else {
                echo "<div style='color: red;'>";
                echo "❌ <strong>Verification link expired</strong><br>";
                echo "</div>";
                echo "<p>The verification link has expired (24-hour limit).</p>";
                echo "<p>Please request a new verification email.</p>";
            }
            
        } else {
            echo "<div style='color: red;'>";
            echo "❌ <strong>Invalid verification link</strong><br>";
            echo "</div>";
            echo "<p>The verification link is invalid or has already been used.</p>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>";
        echo "❌ <strong>Verification failed</strong><br>";
        echo "</div>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<div style='color: red;'>";
    echo "❌ <strong>Missing verification parameters</strong><br>";
    echo "</div>";
    echo "<p>Invalid verification link. Please check your email for the correct link.</p>";
}

echo "<br><br>";
echo "<a href='index.html'>🏠 Go to Homepage</a>";
?>
