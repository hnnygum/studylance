<?php
/**
 * Send email notification for new message
 */

require_once 'config.php';
include 'email_config_phpmailer.php';

function sendMessageNotification($messageId) {
    try {
        $db = Database::getInstance();
        
        // Get message details with sender and receiver info
        $stmt = $db->query("
            SELECT m.*, 
                   u1.name as sender_name, u1.email as sender_email,
                   u2.name as receiver_name, u2.email as receiver_email
            FROM messages m
            JOIN users u1 ON m.sender_id = u1.id
            JOIN users u2 ON m.receiver_id = u2.id
            WHERE m.id = ?
        ", [$messageId]);
        
        $message = $stmt->fetch();
        
        if (!$message) {
            return false;
        }
        
        // Send notification to receiver
        $subject = "📬 New Message from " . $message['sender_name'];
        $email_body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 20px; text-align: center; border-radius: 10px;'>
                    <h1>📬 New Message!</h1>
                    <p>You have received a new message on StudyLance</p>
                </div>
                
                <div style='background: #f9f9f9; padding: 30px; border-radius: 10px; margin-top: 20px;'>
                    <p>Hello <strong>{$message['receiver_name']}</strong>,</p>
                    <p>You have received a new message from <strong>{$message['sender_name']}</strong> on StudyLance.</p>
                    
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #6a5cff;'>
                        <h4 style='color: #6a5cff; margin-top: 0;'>Message:</h4>
                        <p>" . nl2br(htmlspecialchars($message['message'])) . "</p>
                    </div>
                    
                    <p>Please log in to your StudyLance account to read and reply to this message.</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='http://localhost/studylance/messages_new.php' style='background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                            📬 Read Message
                        </a>
                    </div>
                    
                    <p>Best regards,<br>The StudyLance Team</p>
                </div>
                
                <div style='text-align: center; color: #666; margin-top: 30px; font-size: 12px;'>
                    <p>© 2024 StudyLance. All rights reserved.</p>
                    <p>You're receiving this email because you're a registered user on StudyLance.</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Send email
        if (sendEmail($message['receiver_email'], $subject, $email_body)) {
            error_log("Message notification sent to: " . $message['receiver_email']);
            return true;
        } else {
            error_log("Failed to send message notification to: " . $message['receiver_email']);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Message notification error: " . $e->getMessage());
        return false;
    }
}
?>
