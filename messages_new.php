<?php
/**
 * StudyLance Messages Page - PROPER PERSPECTIVE CHAT
 * Shows correct left/right based on user role
 */

require_once 'config.php';
require_once 'email_config.php';

// Require login
if (!isLoggedIn()) {
    redirect('login_new.php');
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = (int)$_POST['receiver_id'];
    $message = sanitize($_POST['message'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($message)) {
        $errors[] = "Message cannot be empty";
    } elseif (strlen($message) < 2) {
        $errors[] = "Message must be at least 2 characters";
    } elseif (strlen($message) > 1000) {
        $errors[] = "Message cannot exceed 1000 characters";
    }
    
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Insert message
            $stmt = $db->query(
                "INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, ?)",
                [$_SESSION['user_id'], $receiverId, $message, date('Y-m-d H:i:s')]
            );
            
            if ($stmt->rowCount() > 0) {
                // Get message ID for email notification
                $messageId = $db->lastInsertId();
                
                // Send email notification
                sendMessageNotification($messageId);
                
                redirect('messages_new.php?user=' . $receiverId, 'Message sent successfully!', 'success');
            } else {
                $errors[] = "Failed to send message. Please try again.";
            }
            
        } catch (Exception $e) {
            error_log("Message sending error: " . $e->getMessage());
            $errors[] = "Database error occurred: " . $e->getMessage();
        }
    }
}

// Get conversations
try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    // Get all conversations (people user has messaged with)
    $stmt = $db->query("
        SELECT DISTINCT 
            CASE 
                WHEN sender_id = ? THEN receiver_id 
                ELSE sender_id 
            END as other_user_id,
            u.name, u.email, u.role,
            MAX(m.created_at) as last_message_time,
            (SELECT COUNT(*) FROM messages WHERE sender_id = ? AND receiver_id = u.id AND is_read = 0) as unread_count,
            (SELECT message FROM messages WHERE (
                (sender_id = ? AND receiver_id = u.id) OR 
                (receiver_id = ? AND sender_id = u.id)
            ) ORDER BY created_at DESC LIMIT 1) as last_message
        FROM messages m
        JOIN users u ON (
            (m.sender_id = ? AND m.receiver_id = u.id) OR 
            (m.receiver_id = ? AND m.sender_id = u.id)
        )
        WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
        GROUP BY other_user_id, u.name, u.email, u.role
        ORDER BY last_message_time DESC
    ", [$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
    
    $conversations = $stmt->fetchAll();
    
    // Get selected conversation
    $selectedUser = isset($_GET['user']) ? (int)$_GET['user'] : null;
    $selectedUserDetails = null;
    $messages = [];
    
    if ($selectedUser) {
        // Get selected user details
        $stmt = $db->query("SELECT id, name, email, role FROM users WHERE id = ?", [$selectedUser]);
        $selectedUserDetails = $stmt->fetch();
        
        if ($selectedUserDetails) {
            // Get messages with this user
            $stmt = $db->query("
                SELECT m.*, u.name as sender_name, u.role as sender_role
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                WHERE (
                    (m.sender_id = ? AND m.receiver_id = ?) OR 
                    (m.sender_id = ? AND m.receiver_id = ?)
                )
                ORDER BY m.created_at ASC
            ", [$userId, $selectedUser, $selectedUser, $userId]);
            
            $messages = $stmt->fetchAll();
            
            // Mark messages as read
            $db->query("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?", [$selectedUser, $userId]);
        }
    }
    
} catch (Exception $e) {
    error_log("Messages error: " . $e->getMessage());
    $conversations = [];
    $selectedUserDetails = null;
    $messages = [];
}

// Get flash message
$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modern Messages Page Styles - PROPER PERSPECTIVE */
        .messages-container {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        .messages-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Left Side - Conversation List */
        .messages-left {
            background: linear-gradient(135deg, rgba(106,92,255,0.15), rgba(168,85,247,0.15));
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .messages-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(106,92,255,0.1), transparent 70%);
            animation: float 20s ease-in-out infinite;
        }
        
        .messages-logo {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .messages-title {
            font-size: 18px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }
        
        .conversations-section {
            position: relative;
            z-index: 1;
            flex: 1;
            overflow-y: auto;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
        }
        
        .conversation-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .conversation-item {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            position: relative;
        }
        
        .conversation-item:hover,
        .conversation-item.active {
            background: rgba(106,92,255,0.2);
            transform: translateY(-3px);
            border-color: rgba(106,92,255,0.3);
            box-shadow: 0 15px 30px rgba(106,92,255,0.2);
        }
        
        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .conversation-info {
            flex: 1;
        }
        
        .conversation-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 2px;
        }
        
        .conversation-role {
            font-size: 12px;
            color: #6a5cff;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .conversation-time {
            font-size: 11px;
            color: rgba(255,255,255,0.5);
            white-space: nowrap;
        }
        
        .conversation-last-message {
            font-size: 14px;
            color: rgba(255,255,255,0.7);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 1.4;
        }
        
        .unread-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg,#e74c3c,#c0392b);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }
        
        .user-section {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 25px;
            margin-top: 30px;
            position: relative;
            z-index: 1;
        }
        
        .user-name {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }
        
        .user-role {
            color: #6a5cff;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-user {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-dashboard {
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
        }
        
        .btn-logout {
            background: linear-gradient(135deg,#e74c3c,#c0392b);
            color: white;
        }
        
        .btn-user:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        /* Right Side - Chat Interface */
        .messages-right {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .chat-header {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .chat-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        
        .chat-details {
            color: white;
        }
        
        .chat-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .chat-role {
            font-size: 14px;
            color: #6a5cff;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .chat-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-chat {
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-info {
            background: linear-gradient(135deg,#3498db,#2980b9);
            color: white;
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(52,152,219,0.3);
        }
        
        /* PROPER PERSPECTIVE CHAT BUBBLES */
        .chat-messages {
            flex: 1;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            display: flex;
            flex-direction: column;
            max-width: 70%;
            position: relative;
        }
        
        /* ALWAYS show YOUR messages on the right */
        .message.my-message {
            align-self: flex-end;
            align-items: flex-end;
        }
        
        /* ALWAYS show OTHER messages on the left */
        .message.other-message {
            align-self: flex-start;
            align-items: flex-start;
        }
        
        .message-bubble {
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* YOUR messages - always right, purple gradient */
        .message.my-message .message-bubble {
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        /* OTHER messages - always left, white background */
        .message.other-message .message-bubble {
            background: rgba(255,255,255,0.9);
            color: #333;
            border-bottom-left-radius: 4px;
        }
        
        .message-info {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
            font-size: 12px;
            color: rgba(255,255,255,0.7);
        }
        
        .message.my-message .message-info {
            justify-content: flex-end;
        }
        
        .message.other-message .message-info {
            justify-content: flex-start;
        }
        
        .message-sender {
            font-weight: 600;
            color: #6a5cff;
            background: rgba(106,92,255,0.1);
            padding: 2px 8px;
            border-radius: 10px;
            white-space: nowrap;
        }
        
        .message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 4px;
        }
        
        .message.my-message .message-time {
            color: rgba(255,255,255,0.7);
            align-self: flex-end;
        }
        
        .message.other-message .message-time {
            color: rgba(255,255,255,0.7);
            align-self: flex-start;
        }
        
        .message-content {
            line-height: 1.4;
            margin-bottom: 5px;
        }
        
        .message.my-message .message-content {
            color: white;
        }
        
        .message.other-message .message-content {
            color: #333;
        }
        
        /* Chat bubbles with tails */
        .message-bubble::after {
            content: '';
            position: absolute;
            width: 0;
            height: 0;
            border: 6px solid transparent;
        }
        
        .message.my-message .message-bubble::after {
            border-bottom-color: #6a5cff;
            bottom: 0;
            right: -6px;
        }
        
        .message.other-message .message-bubble::after {
            border-bottom-color: rgba(255,255,255,0.9);
            bottom: 0;
            left: -6px;
        }
        
        /* Typing indicator */
        .typing-indicator {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: rgba(255,255,255,0.9);
            border-radius: 18px;
            border-bottom-left-radius: 4px;
            color: #666;
            font-size: 14px;
            align-self: flex-start;
            max-width: 70%;
        }
        
        .typing-indicator.show {
            display: flex;
        }
        
        .typing-dots {
            display: flex;
            gap: 4px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #666;
            animation: typing 1.4s infinite;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% { opacity: 0.3; }
            30% { opacity: 1; }
        }
        
        /* Message Form */
        .message-form {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 25px;
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        
        .message-input-group {
            flex: 1;
        }
        
        .message-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            resize: none;
            min-height: 50px;
        }
        
        .message-input:focus {
            outline: none;
            border-color: #6a5cff;
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 20px rgba(106,92,255,0.3);
            transform: translateY(-2px);
        }
        
        .message-input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .send-button {
            padding: 15px 25px;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(106,92,255,0.3);
            white-space: nowrap;
        }
        
        .send-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(106,92,255,0.4);
        }
        
        /* Empty State */
        .empty-chat {
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.7);
            text-align: center;
        }
        
        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: white;
        }
        
        .empty-desc {
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        /* Flash Messages */
        .flash-message {
            padding: 18px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.2);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .flash-success {
            background: rgba(39, 174, 96, 0.1);
            border-color: rgba(39, 174, 96, 0.3);
            color: #4ade80;
        }
        
        .flash-error {
            background: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
        }
        
        .error-messages {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
        }
        
        .error-item {
            color: #ff6b6b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .error-item:last-child {
            margin-bottom: 0;
        }
        
        /* Email Notification Indicator */
        .email-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(52, 152, 219, 0.2);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            color: #3498db;
            margin-left: 10px;
        }
        
        /* Date Separator */
        .date-separator {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        
        .date-separator::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255,255,255,0.2);
        }
        
        .date-separator span {
            background: rgba(255,255,255,0.05);
            padding: 5px 15px;
            border-radius: 15px;
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            font-weight: 600;
            position: relative;
        }
        
        /* Perspective Indicator */
        .perspective-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(106,92,255,0.2);
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 11px;
            color: white;
            font-weight: 600;
            z-index: 10;
        }
        
        /* Floating Shapes */
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(106,92,255,0.2), rgba(168,85,247,0.2));
            animation: float 15s ease-in-out infinite;
        }
        
        .shape-1 {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 60px;
            height: 60px;
            top: 60%;
            left: 70%;
            animation-delay: 3s;
        }
        
        .shape-3 {
            width: 80px;
            height: 80px;
            top: 80%;
            left: 30%;
            animation-delay: 6s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .messages-wrapper {
                grid-template-columns: 1fr;
            }
            
            .messages-left {
                padding: 40px 30px;
                min-height: auto;
            }
            
            .messages-right {
                padding: 40px 30px;
            }
        }
        
        @media (max-width: 768px) {
            .messages-wrapper {
                margin: 20px;
            }
            
            .messages-left,
            .messages-right {
                padding: 30px 20px;
            }
            
            .message-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .send-button {
                width: 100%;
            }
            
            .message {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <!-- Background Video -->
    <video autoplay muted loop playsinline class="bg-video">
        <source src="video.mp4" type="video/mp4">
    </video>
    
    <!-- Overlay -->
    <div class="overlay"></div>

    <div class="messages-container">
        <div class="messages-wrapper">
            <!-- Left Side - Conversation List -->
            <div class="messages-left">
                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>
                
                <div class="messages-logo">StudyLance</div>
                <div class="messages-title">Messages and conversations</div>
                
                <div class="conversations-section">
                    <h3 class="section-title">💬 Conversations</h3>
                    <?php if (!empty($conversations)): ?>
                        <div class="conversation-list">
                            <?php foreach ($conversations as $conversation): ?>
                                <a href="messages_new.php?user=<?php echo $conversation['other_user_id']; ?>" 
                                   class="conversation-item <?php echo ($selectedUser == $conversation['other_user_id']) ? 'active' : ''; ?>">
                                    <div class="conversation-header">
                                        <div class="conversation-info">
                                            <div class="conversation-name"><?php echo htmlspecialchars($conversation['name']); ?></div>
                                            <div class="conversation-role"><?php echo ucfirst(htmlspecialchars($conversation['role'])); ?></div>
                                        </div>
                                        <div class="conversation-time"><?php echo date('g:i A', strtotime($conversation['last_message_time'])); ?></div>
                                    </div>
                                    <div class="conversation-last-message">
                                        <?php echo htmlspecialchars(substr($conversation['last_message'] ?? 'No messages yet', 0, 50)); ?>
                                    </div>
                                    <?php if ($conversation['unread_count'] > 0): ?>
                                        <div class="unread-badge"><?php echo $conversation['unread_count']; ?></div>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-chat">
                            <div class="empty-icon">💬</div>
                            <div class="empty-title">No Conversations Yet</div>
                            <div class="empty-desc">Start messaging to see conversations here</div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="user-section">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                    <div class="user-role"><?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></div>
                    <div class="user-actions">
                        <a href="<?php echo $_SESSION['role'] . '_dashboard_new.php'; ?>" class="btn-user btn-dashboard">Dashboard</a>
                        <a href="logout_new.php" class="btn-user btn-logout">Logout</a>
                    </div>
                </div>
            </div>

            <!-- Right Side - Chat Interface -->
            <div class="messages-right">
                <!-- Flash Messages -->
                <?php if ($flash): ?>
                    <div class="flash-message flash-<?php echo $flash['type']; ?>">
                        <span><?php echo $flash['type'] === 'success' ? '✅' : '❌'; ?></span>
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>

                <?php if ($selectedUserDetails): ?>
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <div class="chat-user-info">
                            <div class="chat-avatar">
                                <?php echo strtoupper(substr($selectedUserDetails['name'], 0, 1)); ?>
                            </div>
                            <div class="chat-details">
                                <div class="chat-name"><?php echo htmlspecialchars($selectedUserDetails['name']); ?></div>
                                <div class="chat-role"><?php echo ucfirst(htmlspecialchars($selectedUserDetails['role'])); ?></div>
                            </div>
                        </div>
                        <div class="chat-actions">
                            <a href="#" class="btn-chat btn-info" onclick="copyEmail()">
                                📧 Copy Email
                            </a>
                        </div>
                    </div>

                    <!-- Chat Messages -->
                    <div class="chat-messages" id="chatMessages">
                        <!-- Perspective Indicator -->
                        <div class="perspective-indicator">
                            You: <?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?>
                        </div>
                        
                        <?php if (!empty($messages)): ?>
                            <?php 
                            $lastDate = null;
                            foreach ($messages as $message): 
                                $currentDate = date('Y-m-d', strtotime($message['created_at']));
                                if ($lastDate !== $currentDate) {
                                    $lastDate = $currentDate;
                                    if ($currentDate === date('Y-m-d')) {
                                        $dateLabel = 'Today';
                                    } elseif ($currentDate === date('Y-m-d', strtotime('-1 day'))) {
                                        $dateLabel = 'Yesterday';
                                    } else {
                                        $dateLabel = date('F j, Y', strtotime($message['created_at']));
                                    }
                            ?>
                                <div class="date-separator">
                                    <span><?php echo $dateLabel; ?></span>
                                </div>
                            <?php } ?>
                            
                            <!-- PROPER PERSPECTIVE LOGIC -->
                            <div class="message <?php echo ($message['sender_id'] == $_SESSION['user_id']) ? 'my-message' : 'other-message'; ?>">
                                <div class="message-info">
                                    <?php if ($message['sender_id'] != $_SESSION['user_id']): ?>
                                        <span class="message-sender"><?php echo htmlspecialchars($message['sender_name']); ?> (<?php echo ucfirst(htmlspecialchars($message['sender_role'])); ?>)</span>
                                    <?php else: ?>
                                        <span class="message-sender">You (<?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="message-bubble">
                                    <div class="message-content"><?php echo nl2br(htmlspecialchars($message['message'])); ?></div>
                                </div>
                                <div class="message-time"><?php echo date('g:i A', strtotime($message['created_at'])); ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-chat">
                                <div class="empty-icon">💬</div>
                                <div class="empty-title">No Messages Yet</div>
                                <div class="empty-desc">Start the conversation with a message</div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Typing Indicator (hidden by default) -->
                        <div class="typing-indicator" id="typingIndicator">
                            <div class="typing-dots">
                                <div class="typing-dot"></div>
                                <div class="typing-dot"></div>
                                <div class="typing-dot"></div>
                            </div>
                            <span>typing...</span>
                        </div>
                    </div>

                    <!-- Message Form -->
                    <form method="POST" action="" class="message-form">
                        <input type="hidden" name="receiver_id" value="<?php echo $selectedUserDetails['id']; ?>">
                        
                        <div class="message-input-group">
                            <textarea name="message" 
                                      placeholder="Type your message here..."
                                      class="message-input"
                                      required><?php echo $_POST['message'] ?? ''; ?></textarea>
                        </div>
                        
                        <button type="submit" name="send_message" class="send-button">
                            Send 📧
                        </button>
                    </form>

                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="error-messages">
                            <?php foreach ($errors as $error): ?>
                                <div class="error-item">
                                    <span>⚠️</span>
                                    <?php echo $error; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-chat">
                        <div class="empty-icon">💬</div>
                        <div class="empty-title">Select a Conversation</div>
                        <div class="empty-desc">Choose a conversation from the left to start messaging</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Copy email function
        function copyEmail() {
            const email = '<?php echo $selectedUserDetails['email'] ?? ''; ?>';
            if (email) {
                navigator.clipboard.writeText(email).then(() => {
                    // Change button text temporarily
                    event.target.textContent = '✅ Copied!';
                    
                    setTimeout(() => {
                        event.target.textContent = '📧 Copy Email';
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy email: ', err);
                });
            }
        }

        // Auto-scroll to bottom of messages
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Scroll to bottom when page loads
        window.addEventListener('load', scrollToBottom);

        // Scroll to bottom when new messages are added
        const observer = new MutationObserver(scrollToBottom);
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            observer.observe(chatMessages, { childList: true });
        }

        // Show typing indicator when user is typing
        const messageInput = document.querySelector('.message-input');
        let typingTimeout;

        messageInput.addEventListener('input', function() {
            clearTimeout(typingTimeout);
            
            // Show typing indicator
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator && messageInput.value.trim()) {
                // Note: In a real app, this would be sent via WebSocket to the other user
                // For now, we'll just simulate it locally
                // typingIndicator.classList.add('show');
            }
            
            typingTimeout = setTimeout(() => {
                // Hide typing indicator after user stops typing
                // typingIndicator.classList.remove('show');
            }, 1000);
        });

        // Handle Enter key for sending messages
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.form.submit();
            }
        });
    </script>
</body>
</html>
