<?php
session_start();

// Check if freelancer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    die("Access denied");
}

include 'database.php';
include 'email_config.php'; // Include email functionality

$job_id = $_POST['job_id'] ?? 0;
$freelancer_id = $_SESSION['user_id'];
$proposal_text = $_POST['proposal_text'] ?? '';
$bid_amount = $_POST['bid_amount'] ?? 0;
$estimated_time = $_POST['estimated_time'] ?? '';

// Validate inputs
if (!is_numeric($job_id) || $job_id <= 0) {
    die("Invalid job ID");
}

if (empty($proposal_text) || empty($bid_amount)) {
    die("Proposal text and bid amount are required");
}

if (!is_numeric($bid_amount) || $bid_amount <= 0) {
    die("Invalid bid amount");
}

// Handle proposal submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Insert proposal
        $sql = "INSERT INTO proposals (job_id, freelancer_id, proposal_text, cover_letter, bid_amount, estimated_time, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissds", $job_id, $freelancer_id, $proposal_text, $proposal_text, $bid_amount, $estimated_time);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $proposal_id = $conn->insert_id;
            
            // 📧 SEND EMAIL TO CLIENT
            try {
                // Get client and job details
                $sql = "SELECT j.title, j.budget, j.client_id, u.name as client_name, u.email as client_email, 
                               f.name as freelancer_name
                        FROM jobs j 
                        JOIN users u ON j.client_id = u.id 
                        JOIN users f ON ? = f.id
                        WHERE j.id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $freelancer_id, $job_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $job_data = $result->fetch_assoc();
                
                if ($job_data) {
                    $subject = "📄 New Proposal Received - " . $job_data['title'];
                    $email_body = "
                    <html>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                            <div style='background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 20px; text-align: center; border-radius: 10px;'>
                                <h1>📄 New Proposal Received!</h1>
                                <p>A freelancer has applied to your job</p>
                            </div>
                            
                            <div style='background: #f9f9f9; padding: 30px; border-radius: 10px; margin-top: 20px;'>
                                <p>Hello <strong>{$job_data['client_name']}</strong>,</p>
                                <p>Great news! You've received a new proposal for your job:</p>
                                
                                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #6a5cff;'>
                                    <h3 style='color: #6a5cff; margin-top: 0;'>{$job_data['title']}</h3>
                                    <p><strong>Freelancer:</strong> {$job_data['freelancer_name']}</p>
                                    <p><strong>Bid Amount:</strong> ₹" . number_format($bid_amount, 2) . "</p>";
                    
                    if ($estimated_time) {
                        $email_body .= "<p><strong>Estimated Time:</strong> " . htmlspecialchars($estimated_time) . "</p>";
                    }
                    
                    $email_body .= "
                                </div>
                                
                                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f39c12;'>
                                    <h4 style='color: #f39c12; margin-top: 0;'>Proposal Details:</h4>
                                    <p>" . nl2br(htmlspecialchars($proposal_text)) . "</p>
                                </div>
                                
                                <p>Review the proposal carefully and decide if this freelancer is the right fit for your project.</p>
                                
                                <div style='text-align: center; margin: 30px 0;'>
                                    <a href='http://localhost/studylance/client_dashboard_new.php' style='background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                                        👀 Review Proposal
                                    </a>
                                </div>
                                
                                <p>Best regards,<br>The StudyLance Team</p>
                            </div>
                            
                            <div style='text-align: center; color: #666; margin-top: 30px; font-size: 12px;'>
                                <p>© 2024 StudyLance. All rights reserved.</p>
                                <p>You're receiving this email because you're a registered client on StudyLance.</p>
                            </div>
                        </div>
                    </body>
                    </html>";
                    
                    // Send email
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= "From: StudyLance <noreply@studylance.com>\r\n";
                    
                    $email_sent = mail($job_data['client_email'], $subject, $email_body, $headers);
                    
                    if ($email_sent) {
                        $_SESSION['success'] = "Proposal submitted successfully! Email notification sent to client.";
                    } else {
                        $_SESSION['success'] = "Proposal submitted successfully! (Email notification failed, but proposal was submitted)";
                    }
                }
                
            } catch (Exception $e) {
                error_log("Email sending error: " . $e->getMessage());
                $_SESSION['success'] = "Proposal submitted successfully! (Email notification failed, but proposal was submitted)";
            }
            
            header("Location: freelancer_dashboard_new.php");
            exit();
        } else {
            $error = "Failed to submit proposal";
        }
        
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Proposal</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        textarea { height: 120px; resize: vertical; }
        .btn { width: 100%; padding: 15px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin: 10px 0; }
        .submit { background: #6a5cff; color: white; }
        .cancel { background: #e74c3c; color: white; }
        .error { color: #e74c3c; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .email-notice { background: #e8f5e8; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 12px; color: #27ae60; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Submit Proposal</h1>
        
        <div class="email-notice">
            📧 An email notification will be sent to the client when you submit this proposal.
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
            
            <div class="form-group">
                <label for="proposal_text">Proposal Details *</label>
                <textarea name="proposal_text" id="proposal_text" required placeholder="Describe why you're the best fit for this job..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="bid_amount">Bid Amount (₹) *</label>
                <input type="number" name="bid_amount" id="bid_amount" step="0.01" min="0" required placeholder="Enter your bid amount">
            </div>
            
            <div class="form-group">
                <label for="estimated_time">Estimated Time</label>
                <input type="text" name="estimated_time" id="estimated_time" placeholder="e.g., 3 days, 1 week">
            </div>
            
            <button type="submit" class="btn submit">📤 Submit Proposal & Send Email</button>
        </form>
        
        <a href="freelancer_dashboard_new.php" class="btn cancel">← Cancel</a>
    </div>
</body>
</html>
