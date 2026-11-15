<?php
session_start();

// Basic check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    die("Access denied");
}

include 'database.php';
include 'email_config.php'; // Include email functionality

$proposal_id = $_GET['id'] ?? 0;

// Simple validation
if (!is_numeric($proposal_id) || $proposal_id <= 0) {
    die("Invalid proposal ID");
}

// Handle POST
if ($_POST['accept'] ?? false) {
    try {
        // Update proposal
        $sql = "UPDATE proposals SET status = 'accepted' WHERE id = ? AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Get job_id and freelancer details
            $sql = "SELECT job_id, freelancer_id FROM proposals WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $job = $result->fetch_assoc();
            $job_id = $job['job_id'];
            $freelancer_id = $job['freelancer_id'];
            
            // Reject others
            $sql = "UPDATE proposals SET status = 'rejected' WHERE job_id = ? AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $job_id, $proposal_id);
            $stmt->execute();
            
            // Close job
            $sql = "UPDATE jobs SET status = 'closed' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $job_id);
            $stmt->execute();
            
            // 📧 SEND EMAIL TO FREELANCER
            try {
                // Get freelancer details for email
                $sql = "SELECT u.name, u.email, j.title as job_title, p.bid_amount 
                        FROM users u 
                        JOIN proposals p ON u.id = p.freelancer_id 
                        JOIN jobs j ON p.job_id = j.id 
                        WHERE p.id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $proposal_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $freelancer_data = $result->fetch_assoc();
                
                if ($freelancer_data) {
                    $subject = "🎉 Proposal Accepted! - " . $freelancer_data['job_title'];
                    $email_body = "
                    <html>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                            <div style='background: linear-gradient(135deg,#27ae60,#229954); color: white; padding: 20px; text-align: center; border-radius: 10px;'>
                                <h1>🎉 Congratulations!</h1>
                                <p>Your proposal has been accepted!</p>
                            </div>
                            
                            <div style='background: #f9f9f9; padding: 30px; border-radius: 10px; margin-top: 20px;'>
                                <p>Hello <strong>{$freelancer_data['name']}</strong>,</p>
                                <p>Great news! The client has accepted your proposal for the project:</p>
                                
                                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #27ae60;'>
                                    <h3 style='color: #27ae60; margin-top: 0;'>{$freelancer_data['job_title']}</h3>
                                    <p><strong>Your Bid:</strong> ₹" . number_format($freelancer_data['bid_amount'], 2) . "</p>
                                </div>
                                
                                <p>The client has reviewed your proposal and selected you for this project. You can now contact the client to discuss project details and get started.</p>
                                
                                <div style='text-align: center; margin: 30px 0;'>
                                    <a href='http://localhost/studylance/freelancer_dashboard_new.php' style='background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                                        📊 View Dashboard
                                    </a>
                                </div>
                                
                                <p>Best regards,<br>The StudyLance Team</p>
                            </div>
                            
                            <div style='text-align: center; color: #666; margin-top: 30px; font-size: 12px;'>
                                <p>© 2024 StudyLance. All rights reserved.</p>
                                <p>You're receiving this email because you're a registered freelancer on StudyLance.</p>
                            </div>
                        </div>
                    </body>
                    </html>";
                    
                    // Send email
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= "From: StudyLance <noreply@studylance.com>\r\n";
                    
                    $email_sent = mail($freelancer_data['email'], $subject, $email_body, $headers);
                    
                    if ($email_sent) {
                        $_SESSION['success'] = "Proposal accepted! Email notification sent to freelancer.";
                    } else {
                        $_SESSION['success'] = "Proposal accepted! (Email notification failed, but proposal was accepted)";
                    }
                }
                
            } catch (Exception $e) {
                error_log("Email sending error: " . $e->getMessage());
                $_SESSION['success'] = "Proposal accepted! (Email notification failed, but proposal was accepted)";
            }
            
            header("Location: client_dashboard_new.php");
            exit();
        } else {
            $error = "Proposal not found or already processed";
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get proposal info
$sql = "SELECT p.*, j.title, u.name as freelancer_name 
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        JOIN users u ON p.freelancer_id = u.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$result = $stmt->get_result();
$proposal = $result->fetch_assoc();

if (!$proposal) {
    die("Proposal not found");
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Accept Proposal</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 20px; }
        .info { margin: 15px 0; padding: 10px; background: #f9f9f9; border-radius: 5px; }
        .bid { font-size: 24px; color: #27ae60; text-align: center; margin: 20px 0; font-weight: bold; }
        .btn { width: 100%; padding: 15px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin: 10px 0; }
        .accept { background: #27ae60; color: white; }
        .cancel { background: #e74c3c; color: white; }
        .error { color: #e74c3c; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .email-notice { background: #e8f5e8; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 12px; color: #27ae60; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Accept Proposal</h1>
        
        <div class="email-notice">
            📧 An email notification will be sent to the freelancer upon acceptance.
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="info">
            <strong>Job:</strong> <?php echo htmlspecialchars($proposal['title']); ?><br>
            <strong>Freelancer:</strong> <?php echo htmlspecialchars($proposal['freelancer_name']); ?><br>
            <strong>Status:</strong> <?php echo htmlspecialchars($proposal['status']); ?>
        </div>
        
        <div class="bid">₹<?php echo number_format($proposal['bid_amount'], 2); ?></div>
        
        <form method="post">
            <input type="hidden" name="accept" value="1">
            <button type="submit" class="btn accept">✅ Accept Proposal & Send Email</button>
        </form>
        
        <a href="client_dashboard_new.php" class="btn cancel">← Cancel</a>
    </div>
</body>
</html>
