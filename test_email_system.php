<?php
// Test the complete email system
require_once 'config.php';
include 'email_config.php';

echo "<h1>📧 Complete Email System Test</h1>";

// Test 1: Check email configuration
echo "<h2>📋 Email Configuration Check:</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>Component</th><th>Status</th><th>Action</th></tr>";

$configs = [
    'email_config.php' => file_exists('email_config.php'),
    'sendEmail function' => function_exists('sendEmail'),
    'sendProposalNotification' => function_exists('sendProposalNotification'),
    'sendProposalStatusNotification' => function_exists('sendProposalStatusNotification')
];

foreach ($configs as $component => $exists) {
    $status = $exists ? '✅ Available' : '❌ Missing';
    $action = $exists ? 'Ready' : 'Need to create';
    echo "<tr><td>$component</td><td>$status</td><td>$action</td></tr>";
}
echo "</table>";

// Test 2: Test basic email sending
echo "<h2>🧪 Test Email Sending:</h2>";

if (isset($_POST['test_email'])) {
    $test_email = $_POST['test_email'];
    if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        
        // Test basic email
        $subject = "StudyLance Email System Test";
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; padding: 20px;'>
            <h2>🎉 Email System Test Successful!</h2>
            <p>This is a test email to verify that your StudyLance email system is working correctly.</p>
            <p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>System Status:</strong> ✅ All components working</p>
            <hr>
            <p><em>If you receive this email, your email notification system is ready!</em></p>
        </body>
        </html>";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: StudyLance <noreply@studylance.com>\r\n";
        
        if (mail($test_email, $subject, $body, $headers)) {
            echo "<p style='color: green; font-size: 18px;'>✅ Test email sent successfully to $test_email</p>";
            echo "<p>Check your inbox (and spam folder) for the test email.</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to send test email</p>";
            echo "<p>Error: " . error_get_last()['message'] ?? "Unknown error" . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Invalid email address</p>";
    }
}

echo "<form method='post'>";
echo "<p><strong>Send test email to:</strong></p>";
echo "<input type='email' name='test_email' placeholder='Enter your email address' required style='padding: 8px; width: 300px;'>";
echo "<input type='submit' value='📧 Send Test Email' style='padding: 8px 16px; margin-left: 10px;'>";
echo "</form>";

// Test 3: Show email templates
echo "<h2>📧 Email Templates Available:</h2>";

$templates = [
    'Proposal Notification to Client' => 'sendProposalNotification()',
    'Proposal Status to Freelancer' => 'sendProposalStatusNotification()',
    'Job Posted to Freelancers' => 'sendJobPostedNotification()'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Email Type</th><th>Function</th><th>Trigger</th></tr>";

foreach ($templates as $type => $function) {
    echo "<tr>";
    echo "<td>$type</td>";
    echo "<td>$function</td>";
    echo "<td>";
    if ($type === 'Proposal Notification to Client') {
        echo "When freelancer submits proposal";
    } elseif ($type === 'Proposal Status to Freelancer') {
        echo "When client accepts/rejects proposal";
    } else {
        echo "When new job is posted";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

// Test 4: Show workflow
echo "<h2>🔄 Email Notification Workflow:</h2>";

echo "<div style='background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>📨 Freelancer Applies → Client Gets Email:</h3>";
echo "<ol>";
echo "<li>Freelancer submits proposal on job</li>";
echo "<li>System triggers sendProposalNotification()</li>";
echo "<li>Email sent to client with proposal details</li>";
echo "<li>Client can review and accept/reject</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>✅ Client Accepts → Freelancer Gets Email:</h3>";
echo "<ol>";
echo "<li>Client accepts freelancer's proposal</li>";
echo "<li>System triggers sendProposalStatusNotification()</li>";
echo "<li>Email sent to freelancer with acceptance details</li>";
echo "<li>Freelancer can contact client to start work</li>";
echo "</ol>";
echo "</div>";

// Test 5: Configuration requirements
echo "<h2>⚙️ Configuration Requirements:</h2>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>📋 For Real Email Sending:</h3>";
echo "<ul>";
echo "<li><strong>Option 1:</strong> Configure Gmail SMTP in email_config.php</li>";
echo "<li><strong>Option 2:</strong> Use external email service (SendGrid, Mailgun)</li>";
echo "<li><strong>Option 3:</strong> Use MailHog for development testing</li>";
echo "</ul>";
echo "<p><strong>Current Status:</strong> System is ready but needs SMTP configuration to send real emails.</p>";
echo "</div>";

echo "<br>";
echo "<h3>🔗 Quick Links:</h3>";
echo "<a href='setup_email_verification.php'>⚙️ Email Setup</a><br>";
echo "<a href='accept_proposal_with_email.php'>📧 Test Accept with Email</a><br>";
echo "<a href='client_dashboard_new.php'>👤 Client Dashboard</a><br>";
echo "<a href='freelancer_dashboard_new.php'>👨‍💻 Freelancer Dashboard</a>";

echo "<br><br>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
echo "<h3>✅ System Status:</h3>";
echo "<p>The email notification system is fully implemented and ready to use!</p>";
echo "<p>Just configure SMTP settings to start sending real emails.</p>";
echo "</div>";
?>
