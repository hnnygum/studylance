<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'database.php';

// Get current user info
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// Get conversations (people user has messaged with)
$sql = "SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as other_user_id,
        u.name as other_user_name,
        u.role as other_user_role,
        MAX(m.created_at) as last_message_time,
        (SELECT COUNT(*) FROM messages 
         WHERE sender_id = other_user_id AND receiver_id = ? AND is_read = 0) as unread_count
        FROM messages m 
        JOIN users u ON (
            CASE 
                WHEN m.sender_id = ? THEN m.receiver_id 
                ELSE m.sender_id 
            END = u.id
        )
        WHERE (m.sender_id = ? OR m.receiver_id = ?)
        GROUP BY other_user_id, u.name, u.role
        ORDER BY last_message_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$conversations = [];
while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}
$stmt->close();

// Get job-related conversations if client or freelancer
$job_conversations = [];
if ($current_user_role === 'client') {
    $sql = "SELECT DISTINCT j.id, j.title, u.name as freelancer_name, u.id as freelancer_id,
            MAX(p.created_at) as last_proposal_time
            FROM jobs j
            LEFT JOIN proposals p ON j.id = p.job_id
            LEFT JOIN users u ON p.freelancer_id = u.id
            WHERE j.client_id = ? AND p.id IS NOT NULL
            GROUP BY j.id, j.title, u.name, u.id
            ORDER BY last_proposal_time DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $job_conversations[] = $row;
    }
    $stmt->close();
} elseif ($current_user_role === 'freelancer') {
    $sql = "SELECT DISTINCT j.id, j.title, u.name as client_name, u.id as client_id,
            MAX(p.created_at) as last_proposal_time
            FROM proposals p
            JOIN jobs j ON p.job_id = j.id
            JOIN users u ON j.client_id = u.id
            WHERE p.freelancer_id = ?
            GROUP BY j.id, j.title, u.name, u.id
            ORDER BY last_proposal_time DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $job_conversations[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - StudyLance</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>Messages</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="<?php echo $current_user_role . '_dashboard.php'; ?>" class="btn btn-secondary">Dashboard</a>
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="messages-container">
                <!-- Conversations Sidebar -->
                <aside class="conversations-sidebar">
                    <div class="sidebar-header">
                        <h2>Conversations</h2>
                        <div class="search-box">
                            <input type="text" id="searchConversations" placeholder="Search conversations...">
                        </div>
                    </div>
                    
                    <div class="conversations-list">
                        <!-- Recent Conversations -->
                        <div class="conversation-section">
                            <h3>Recent Messages</h3>
                            <?php if (empty($conversations)): ?>
                                <p class="no-conversations">No conversations yet</p>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                    <div class="conversation-item" 
                                         data-user-id="<?php echo $conv['other_user_id']; ?>"
                                         onclick="openConversation(<?php echo $conv['other_user_id']; ?>, '<?php echo htmlspecialchars($conv['other_user_name']); ?>')">
                                        <div class="conversation-avatar">
                                            <?php echo strtoupper(substr($conv['other_user_name'], 0, 2)); ?>
                                        </div>
                                        <div class="conversation-info">
                                            <div class="conversation-name">
                                                <?php echo htmlspecialchars($conv['other_user_name']); ?>
                                                <span class="role-badge role-<?php echo $conv['other_user_role']; ?>">
                                                    <?php echo ucfirst($conv['other_user_role']); ?>
                                                </span>
                                            </div>
                                            <div class="conversation-time">
                                                <?php echo timeAgo($conv['last_message_time']); ?>
                                            </div>
                                        </div>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <div class="unread-badge">
                                                <?php echo $conv['unread_count']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Job Conversations -->
                        <?php if (!empty($job_conversations)): ?>
                            <div class="conversation-section">
                                <h3>Job Conversations</h3>
                                <?php foreach ($job_conversations as $job_conv): ?>
                                    <div class="conversation-item job-conversation"
                                         onclick="openJobConversation(<?php 
                                            echo $current_user_role === 'client' ? $job_conv['freelancer_id'] : $job_conv['client_id']; 
                                         ?>, '<?php 
                                            echo htmlspecialchars($current_user_role === 'client' ? $job_conv['freelancer_name'] : $job_conv['client_name']); 
                                         ?>', <?php echo $job_conv['id']; ?>, '<?php echo htmlspecialchars($job_conv['title']); ?>')">
                                        <div class="conversation-avatar job-avatar">
                                            <i class="job-icon">💼</i>
                                        </div>
                                        <div class="conversation-info">
                                            <div class="conversation-name">
                                                <?php echo htmlspecialchars($job_conv['title']); ?>
                                            </div>
                                            <div class="conversation-meta">
                                                with <?php echo htmlspecialchars($current_user_role === 'client' ? $job_conv['freelancer_name'] : $job_conv['client_name']); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </aside>

                <!-- Chat Area -->
                <section class="chat-area" id="chatArea">
                    <div class="chat-welcome" id="chatWelcome">
                        <div class="welcome-content">
                            <h2>Welcome to Messages</h2>
                            <p>Select a conversation to start messaging</p>
                            <div class="messaging-tips">
                                <h4>Tips:</h4>
                                <ul>
                                    <li>Be professional and respectful</li>
                                    <li>Response time matters</li>
                                    <li>Clarify project details</li>
                                    <li>Keep work-related discussions here</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="chat-container" id="chatContainer" style="display: none;">
                        <div class="chat-header" id="chatHeader">
                            <div class="chat-user-info">
                                <div class="chat-avatar" id="chatAvatar"></div>
                                <div class="chat-details">
                                    <h3 id="chatName"></h3>
                                    <span class="chat-status" id="chatStatus"></span>
                                </div>
                            </div>
                            <div class="chat-actions">
                                <button class="btn btn-secondary btn-sm" onclick="refreshMessages()">
                                    🔄 Refresh
                                </button>
                            </div>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <div class="loading-messages">Loading messages...</div>
                        </div>

                        <div class="chat-input-container">
                            <form id="messageForm" onsubmit="sendMessage(event)">
                                <div class="input-group">
                                    <textarea id="messageInput" 
                                              placeholder="Type your message..." 
                                              rows="1"
                                              maxlength="1000"
                                              required></textarea>
                                    <button type="submit" class="btn btn-primary" id="sendButton">
                                        Send
                                    </button>
                                </div>
                                <div class="input-footer">
                                    <span class="character-count">
                                        <span id="charCount">0</span>/1000
                                    </span>
                                    <span class="typing-indicator" id="typingIndicator"></span>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        // Global variables
        let currentUserId = <?php echo $current_user_id; ?>;
        let currentOtherUserId = null;
        let currentJobId = null;
        let lastMessageId = 0;
        let refreshInterval = null;
        let isTyping = false;
        let typingTimeout = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
            setupAutoResize();
        });

        function setupEventListeners() {
            // Search conversations
            document.getElementById('searchConversations').addEventListener('input', function(e) {
                filterConversations(e.target.value);
            });

            // Character count
            document.getElementById('messageInput').addEventListener('input', function(e) {
                document.getElementById('charCount').textContent = e.target.value.length;
                
                // Typing indicator
                if (e.target.value.length > 0 && !isTyping) {
                    isTyping = true;
                    showTypingIndicator();
                }
                
                clearTimeout(typingTimeout);
                typingTimeout = setTimeout(() => {
                    isTyping = false;
                    hideTypingIndicator();
                }, 1000);
            });
        }

        function setupAutoResize() {
            const textarea = document.getElementById('messageInput');
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }

        function openConversation(userId, userName) {
            currentOtherUserId = userId;
            currentJobId = null;
            
            // Update chat header
            document.getElementById('chatName').textContent = userName;
            document.getElementById('chatAvatar').textContent = userName.substring(0, 2).toUpperCase();
            document.getElementById('chatStatus').textContent = 'Online';
            
            // Show chat container
            document.getElementById('chatWelcome').style.display = 'none';
            document.getElementById('chatContainer').style.display = 'flex';
            
            // Load messages
            loadMessages();
            
            // Start auto-refresh
            startAutoRefresh();
            
            // Mark as read (optional)
            markAsRead();
        }

        function openJobConversation(userId, userName, jobId, jobTitle) {
            currentOtherUserId = userId;
            currentJobId = jobId;
            
            // Update chat header
            document.getElementById('chatName').textContent = userName + ' - ' + jobTitle;
            document.getElementById('chatAvatar').innerHTML = '💼';
            document.getElementById('chatStatus').textContent = 'Job Conversation';
            
            // Show chat container
            document.getElementById('chatWelcome').style.display = 'none';
            document.getElementById('chatContainer').style.display = 'flex';
            
            // Load messages
            loadMessages();
            
            // Start auto-refresh
            startAutoRefresh();
        }

        function loadMessages() {
            const params = new URLSearchParams({
                other_user_id: currentOtherUserId
            });
            
            if (currentJobId) {
                params.append('job_id', currentJobId);
            }
            
            if (lastMessageId > 0) {
                params.append('last_message_id', lastMessageId);
            }
            
            fetch('fetch_messages.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMessages(data.messages);
                        if (data.messages.length > 0) {
                            lastMessageId = Math.max(...data.messages.map(m => m.id));
                        }
                    } else {
                        console.error('Error loading messages:', data.errors);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('chatMessages').innerHTML = 
                        '<div class="error-message">Failed to load messages</div>';
                });
        }

        function displayMessages(messages) {
            const messagesContainer = document.getElementById('chatMessages');
            
            if (messages.length === 0) {
                messagesContainer.innerHTML = '<div class="no-messages">No messages yet. Start the conversation!</div>';
                return;
            }
            
            let html = '';
            messages.forEach(message => {
                const messageClass = message.is_mine ? 'message-sent' : 'message-received';
                const senderName = message.is_mine ? 'You' : message.sender_name;
                
                html += `
                    <div class="message ${messageClass}" data-message-id="${message.id}">
                        <div class="message-content">
                            <div class="message-header">
                                <span class="sender-name">${senderName}</span>
                                <span class="message-time">${formatTime(message.created_at)}</span>
                            </div>
                            <div class="message-text">${escapeHtml(message.message)}</div>
                            ${message.job_title ? `<div class="message-job">📁 ${message.job_title}</div>` : ''}
                        </div>
                    </div>
                `;
            });
            
            messagesContainer.innerHTML = html;
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function sendMessage(event) {
            event.preventDefault();
            
            const messageInput = document.getElementById('messageInput');
            const messageText = messageInput.value.trim();
            
            if (!messageText || !currentOtherUserId) return;
            
            const formData = new FormData();
            formData.append('receiver_id', currentOtherUserId);
            formData.append('message', messageText);
            
            if (currentJobId) {
                formData.append('job_id', currentJobId);
            }
            
            // Disable send button
            const sendButton = document.getElementById('sendButton');
            sendButton.disabled = true;
            sendButton.textContent = 'Sending...';
            
            fetch('send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    document.getElementById('charCount').textContent = '0';
                    messageInput.style.height = 'auto';
                    
                    // Add message immediately for better UX
                    addMessageToChat(data.message);
                    
                    // Update last message ID
                    lastMessageId = data.message.id;
                } else {
                    alert('Failed to send message: ' + (data.errors?.join(', ') || data.error));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to send message. Please try again.');
            })
            .finally(() => {
                sendButton.disabled = false;
                sendButton.textContent = 'Send';
            });
        }

        function addMessageToChat(message) {
            const messagesContainer = document.getElementById('chatMessages');
            
            // Remove "no messages" message if exists
            const noMessages = messagesContainer.querySelector('.no-messages');
            if (noMessages) {
                noMessages.remove();
            }
            
            const messageHtml = `
                <div class="message message-sent" data-message-id="${message.id}">
                    <div class="message-content">
                        <div class="message-header">
                            <span class="sender-name">You</span>
                            <span class="message-time">${formatTime(message.created_at)}</span>
                        </div>
                        <div class="message-text">${escapeHtml(message.message)}</div>
                    </div>
                </div>
            `;
            
            messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function refreshMessages() {
            if (currentOtherUserId) {
                loadMessages();
            }
        }

        function startAutoRefresh() {
            // Clear existing interval
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            
            // Start new interval (every 2 seconds)
            refreshInterval = setInterval(() => {
                if (currentOtherUserId) {
                    loadMessages();
                }
            }, 2000);
        }

        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
            }
        }

        function filterConversations(searchTerm) {
            const conversations = document.querySelectorAll('.conversation-item');
            const term = searchTerm.toLowerCase();
            
            conversations.forEach(conv => {
                const name = conv.querySelector('.conversation-name')?.textContent.toLowerCase() || '';
                const meta = conv.querySelector('.conversation-meta')?.textContent.toLowerCase() || '';
                
                if (name.includes(term) || meta.includes(term)) {
                    conv.style.display = 'flex';
                } else {
                    conv.style.display = 'none';
                }
            });
        }

        function showTypingIndicator() {
            document.getElementById('typingIndicator').textContent = 'typing...';
        }

        function hideTypingIndicator() {
            document.getElementById('typingIndicator').textContent = '';
        }

        function markAsRead() {
            // Optional: Implement marking messages as read
            // This would require updating the database
        }

        // Utility functions
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return diffMins + ' min ago';
            
            const diffHours = Math.floor(diffMins / 60);
            if (diffHours < 24) return diffHours + ' hour' + (diffHours > 1 ? 's' : '') + ' ago';
            
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function timeAgo(timestamp) {
            return formatTime(timestamp);
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            stopAutoRefresh();
        });
    </script>

    <style>
        .messages-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            height: calc(100vh - 120px);
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .conversations-sidebar {
            border-right: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }

        .sidebar-header h2 {
            margin: 0 0 1rem 0;
            font-size: 1.25rem;
            color: #2c3e50;
        }

        .search-box input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }

        .conversation-section {
            padding: 1rem;
        }

        .conversation-section h3 {
            margin: 0 0 1rem 0;
            font-size: 0.875rem;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }

        .conversation-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-bottom: 0.5rem;
        }

        .conversation-item:hover {
            background-color: #f8f9fa;
        }

        .conversation-item.active {
            background-color: #e3f2fd;
            border-left: 3px solid #3498db;
        }

        .conversation-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 0.75rem;
            font-size: 0.875rem;
        }

        .job-avatar {
            background: #27ae60;
        }

        .conversation-info {
            flex: 1;
        }

        .conversation-name {
            font-weight: 500;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .conversation-time {
            font-size: 0.75rem;
            color: #999;
            margin-top: 0.25rem;
        }

        .conversation-meta {
            font-size: 0.75rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .unread-badge {
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .no-conversations {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 2rem 0;
        }

        .chat-area {
            display: flex;
            flex-direction: column;
        }

        .chat-welcome {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }

        .welcome-content h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .welcome-content p {
            color: #666;
            margin-bottom: 2rem;
        }

        .messaging-tips {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: left;
            max-width: 400px;
        }

        .messaging-tips h4 {
            margin: 0 0 1rem 0;
            color: #2c3e50;
        }

        .messaging-tips ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .messaging-tips li {
            margin-bottom: 0.5rem;
            color: #555;
        }

        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            background: white;
        }

        .chat-user-info {
            display: flex;
            align-items: center;
        }

        .chat-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 0.75rem;
        }

        .chat-details h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .chat-status {
            font-size: 0.875rem;
            color: #27ae60;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 1rem;
            max-width: 70%;
        }

        .message-sent {
            margin-left: auto;
        }

        .message-received {
            margin-right: auto;
        }

        .message-content {
            background: white;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .message-sent .message-content {
            background: #3498db;
            color: white;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
        }

        .message-sent .message-header {
            color: rgba(255,255,255,0.8);
        }

        .message-received .message-header {
            color: #666;
        }

        .sender-name {
            font-weight: 500;
        }

        .message-text {
            line-height: 1.4;
            word-wrap: break-word;
        }

        .message-job {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(0,0,0,0.1);
            color: #666;
        }

        .message-sent .message-job {
            border-top-color: rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.8);
        }

        .no-messages {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 2rem;
        }

        .loading-messages {
            text-align: center;
            color: #666;
            padding: 2rem;
        }

        .chat-input-container {
            padding: 1rem;
            background: white;
            border-top: 1px solid #eee;
        }

        .input-group {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .input-group textarea {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: none;
            min-height: 44px;
            max-height: 120px;
            font-family: inherit;
        }

        .input-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        .input-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: #999;
        }

        .typing-indicator {
            color: #3498db;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .messages-container {
                grid-template-columns: 1fr;
            }
            
            .conversations-sidebar {
                display: none;
            }
            
            .conversations-sidebar.mobile-open {
                display: flex;
                position: absolute;
                top: 0;
                left: 0;
                width: 80%;
                height: 100%;
                z-index: 1000;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            
            .message {
                max-width: 85%;
            }
            
            .chat-header {
                padding: 0.75rem;
            }
            
            .chat-messages {
                padding: 0.75rem;
            }
            
            .chat-input-container {
                padding: 0.75rem;
            }
        }
    </style>
</body>
</html>

<?php
// Helper function for time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hour' . (floor($diff/3600) > 1 ? 's' : '') . ' ago';
    return date('M j', $time);
}
?>
