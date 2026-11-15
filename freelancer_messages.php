<?php
/**
 * StudyLance Freelancer Messages System
 * Handle freelancer-to-client messaging
 */

require_once 'config.php';

// Check if user is logged in as freelancer
if (!isLoggedIn()) {
    redirect('login_new.php', 'Please login to access messages', 'error');
}

// Check if user is freelancer
if ($_SESSION['role'] !== 'freelancer') {
    redirect('freelancer_dashboard_new.php', 'Access denied. Freelancer access only.', 'error');
}

// Get current user
$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['name'];

// Handle message sending
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $message_text = trim($_POST['message_text']);
    
    if (empty($message_text)) {
        $_SESSION['error'] = "Message cannot be empty";
    } elseif ($receiver_id == $current_user_id) {
        $_SESSION['error'] = "You cannot send message to yourself";
    } else {
        try {
            $db = Database::getInstance();
            
            // Verify receiver exists and is a client
            $stmt = $db->query("SELECT id, name, role FROM users WHERE id = ? AND role = 'client'", [$receiver_id]);
            $receiver = $stmt->fetch();
            
            if (!$receiver) {
                $_SESSION['error'] = "Client not found or access denied";
            } else {
                // Send message
                $stmt = $db->query("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, ?)", 
                    [$current_user_id, $receiver_id, $message_text, date('Y-m-d H:i:s')]);
                
                if ($stmt->rowCount() > 0) {
                    $message_id = $db->lastInsertId();
                    
                    $_SESSION['success'] = "Message sent successfully to " . htmlspecialchars($receiver['name']);
                    
                    // Send email notification to client
                    include 'email_config_phpmailer.php';
                    
                    $subject = "📬 New Message from Freelancer - StudyLance";
                    $email_body = "
                    <html>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                            <div style='background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 20px; text-align: center; border-radius: 10px;'>
                                <h1>📬 New Message!</h1>
                                <p>A freelancer has sent you a message on StudyLance</p>
                            </div>
                            
                            <div style='background: #f9f9f9; padding: 30px; border-radius: 10px; margin-top: 20px;'>
                                <p>Hello <strong>{$receiver['name']}</strong>,</p>
                                <p>You have received a new message from freelancer <strong>{$current_user_name}</strong> on StudyLance.</p>
                                
                                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #6a5cff;'>
                                    <h4 style='color: #6a5cff; margin-top: 0;'>Message:</h4>
                                    <p>" . nl2br(htmlspecialchars($message_text)) . "</p>
                                </div>
                                
                                <p>Please log in to your StudyLance account to read and reply to this message.</p>
                                
                                <div style='text-align: center; margin: 30px 0;'>
                                    <a href='http://localhost/studylance/messages_new.php?user={$current_user_id}' style='background: linear-gradient(135deg,#6a5cff,#a855f7); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                                        📬 Read Message
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
                    
                    // Get client email
                    $stmt = $db->query("SELECT email FROM users WHERE id = ?", [$receiver_id]);
                    $client_data = $stmt->fetch();
                    
                    if ($client_data && sendEmail($client_data['email'], $subject, $email_body)) {
                        error_log("Email notification sent for freelancer message to client: " . $client_data['email']);
                    }
                    
                    redirect('freelancer_messages.php?user=' . $receiver_id, 'Message sent successfully!', 'success');
                } else {
                    $_SESSION['error'] = "Failed to send message";
                }
            }
            
        } catch (Exception $e) {
            error_log("Message sending error: " . $e->getMessage());
            $_SESSION['error'] = "An error occurred while sending the message";
        }
    }
}

// Handle message deletion
if (isset($_GET['delete_message'])) {
    $message_id = (int)$_GET['delete_message'];
    
    try {
        $db = Database::getInstance();
        
        // Delete message if it belongs to current user
        $stmt = $db->query("DELETE FROM messages WHERE id = ? AND (sender_id = ? OR receiver_id = ?)", 
            [$message_id, $current_user_id, $current_user_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Message deleted successfully";
        }
        
        redirect('freelancer_messages.php');
        
    } catch (Exception $e) {
        error_log("Message deletion error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while deleting the message";
    }
}

// Get user parameter for messaging
$selected_user_id = isset($_GET['user']) ? (int)$_GET['user'] : null;

// Get messages
try {
    $db = Database::getInstance();
    
    if ($selected_user_id) {
        // Get conversation with specific client
        $stmt = $db->query("
            SELECT m.*, 
                   u.name as sender_name,
                   CASE 
                       WHEN m.sender_id = ? THEN 'sent'
                       ELSE 'received'
                   END as message_type
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at DESC
        ", [$current_user_id, $selected_user_id, $selected_user_id, $current_user_id]);
        
        $messages = $stmt->fetchAll();
        
        // Get client info
        $stmt = $db->query("SELECT name FROM users WHERE id = ? AND role = 'client'", [$selected_user_id]);
        $other_user = $stmt->fetch();
        
    } else {
        // Get all messages for current freelancer
        $stmt = $db->query("
            SELECT m.*, 
                   u.name as sender_name,
                   CASE 
                       WHEN m.sender_id = ? THEN 'sent'
                       ELSE 'received'
                   END as message_type
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = ? OR m.sender_id = ?
            ORDER BY m.created_at DESC
            LIMIT 50
        ", [$current_user_id, $current_user_id, $current_user_id]);
        
        $messages = $stmt->fetchAll();
        $other_user = null;
    }
    
} catch (Exception $e) {
    error_log("Messages retrieval error: " . $e->getMessage());
    $messages = [];
    $other_user = null;
}

// Get all clients for messaging list
try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'client' AND id != ? ORDER BY name", [$current_user_id]);
    $clients = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Clients list error: " . $e->getMessage());
    $clients = [];
}

// Get flash message
$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelancer Messages - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .messages-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
        }
        
        .clients-list {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            height: fit-content;
        }
        
        .messages-area {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
        }
        
        .client-item {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .client-item:hover {
            background: rgba(106,92,255,0.1);
            border-color: rgba(106,92,255,0.3);
        }
        
        .client-item.active {
            background: rgba(106,92,255,0.2);
            border-color: rgba(106,92,255,0.5);
        }
        
        .message-bubble {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 15px;
            max-width: 70%;
        }
        
        .message-sent {
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 15px;
            border-bottom-left-radius: 15px;
        }
        
        .message-received {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            margin-right: auto;
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 12px;
            opacity: 0.7;
        }
        
        .message-text {
            line-height: 1.6;
            word-wrap: break-word;
        }
        
        .compose-form {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: rgba(255,255,255,0.8);
        }
        
        .form-group textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: white;
            resize: vertical;
        }
        
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: white;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-send {
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
        }
        
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106,92,255,0.3);
        }
        
        .no-messages {
            text-align: center;
            padding: 40px;
            color: rgba(255,255,255,0.6);
        }
        
        .freelancer-badge {
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-wrapper">
            <!-- Left Sidebar -->
            <div class="dashboard-left">
                <div class="user-section">
                    <div class="user-name"><?php echo htmlspecialchars($current_user_name); ?></div>
                    <div class="user-role">Freelancer <span class="freelancer-badge">PRO</span></div>
                    <div class="user-actions">
                        <a href="freelancer_dashboard_new.php" class="btn-user btn-dashboard">📊 Dashboard</a>
                        <a href="logout_new.php" class="btn-user btn-logout">Logout</a>
                    </div>
                </div>
                
                <!-- Clients List -->
                <div class="clients-list">
                    <h3 style="color: white; margin-bottom: 15px;">💬 Clients</h3>
                    
                    <?php foreach ($clients as $client): ?>
                        <div class="client-item <?php echo ($selected_user_id == $client['id']) ? 'active' : ''; ?>" 
                             onclick="selectClient(<?php echo $client['id']; ?>)">
                            <div style="color: white; font-weight: 500;"><?php echo htmlspecialchars($client['name']); ?></div>
                            <div style="color: rgba(255,255,255,0.6); font-size: 12px;">
                                Client
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Messages Area -->
            <div class="messages-area">
                <div class="content-header">
                    <h1 class="page-title">
                        <?php echo $other_user ? 'Messages with ' . htmlspecialchars($other_user['name']) : 'All Messages'; ?>
                    </h1>
                    <p class="page-subtitle">
                        <?php echo $other_user ? 'Your conversation with client ' . htmlspecialchars($other_user['name']) : 'All your messages with clients'; ?>
                    </p>
                </div>
                
                <!-- Flash Messages -->
                <?php if ($flash): ?>
                    <div class="flash-message flash-<?php echo $flash['type']; ?>">
                        <span><?php echo $flash['type'] === 'success' ? '✅' : '❌'; ?></span>
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Messages -->
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message-bubble <?php echo $message['message_type']; ?>">
                            <div class="message-header">
                                <span><?php echo htmlspecialchars($message['sender_name']); ?></span>
                                <span><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                                <?php if ($message['sender_id'] == $current_user_id): ?>
                                    <a href="freelancer_messages.php?delete_message=<?php echo $message['id']; ?>" 
                                       style="color: white; text-decoration: none;" 
                                       onclick="return confirm('Are you sure you want to delete this message?')">
                                        🗑️ Delete
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="message-text">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <h3>📭 No messages yet</h3>
                        <p>Start a conversation by selecting a client from the list and sending a message.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Compose Message Form -->
                <?php if ($selected_user_id): ?>
                    <div class="compose-form">
                        <h3 style="color: white; margin-bottom: 15px;">✍️ Compose Message</h3>
                        
                        <form method="post">
                            <div class="form-group">
                                <label for="receiver_id">Send to:</label>
                                <select name="receiver_id" id="receiver_id" required>
                                    <option value="<?php echo $selected_user_id; ?>">
                                        <?php echo htmlspecialchars($other_user['name']); ?> (Client)
                                    </option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="message_text">Message:</label>
                                <textarea name="message_text" id="message_text" required 
                                          placeholder="Type your message to the client..." 
                                          maxlength="1000"></textarea>
                            </div>
                            
                            <button type="submit" name="send_message" class="btn btn-send">
                                📤 Send Message to Client
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function selectClient(clientId) {
            window.location.href = 'freelancer_messages.php?user=' + clientId;
        }
        
        // Auto-scroll to latest message
        window.addEventListener('load', function() {
            const messagesContainer = document.querySelector('.messages-area');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        });
        
        // Character counter for message textarea
        const messageTextarea = document.getElementById('message_text');
        if (messageTextarea) {
            messageTextarea.addEventListener('input', function() {
                const remaining = 1000 - this.value.length;
                // You can add a character counter display here if needed
            });
        }
    </script>
</body>
</html>
