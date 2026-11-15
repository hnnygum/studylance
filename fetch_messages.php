<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

include 'database.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$other_user_id = $_GET['other_user_id'] ?? null;
$job_id = $_GET['job_id'] ?? null;
$last_message_id = $_GET['last_message_id'] ?? 0;
$limit = $_GET['limit'] ?? 50;

$errors = [];

// Validate parameters
if (empty($other_user_id) || !is_numeric($other_user_id)) {
    $errors[] = "Valid other_user_id is required";
}

// Verify other user exists
if (empty($errors)) {
    $sql = "SELECT id, name, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $other_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $errors[] = "User not found";
    } else {
        $other_user = $result->fetch_assoc();
    }
    $stmt->close();
}

// Verify job_id if provided
if (!empty($job_id) && empty($errors)) {
    $sql = "SELECT id, title, client_id FROM jobs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $errors[] = "Job not found";
    } else {
        $job = $result->fetch_assoc();
        
        // Verify user is part of this job (either client or has proposal)
        if ($job['client_id'] != $user_id) {
            $sql = "SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $job_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                $errors[] = "Access denied to this job conversation";
            }
            $stmt->close();
        }
    }
    $stmt->close();
}

// Fetch messages if no errors
if (empty($errors)) {
    // Build query
    $where_conditions = [
        "(sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)"
    ];
    $params = [$user_id, $other_user_id, $other_user_id, $user_id];
    $types = "iiii";
    
    // Add job filter if specified
    if (!empty($job_id)) {
        $where_conditions[] = "(job_id = ? OR job_id IS NULL)";
        $params[] = $job_id;
        $types .= "i";
    }
    
    // Add last_message_id filter for incremental loading
    if ($last_message_id > 0) {
        $where_conditions[] = "id > ?";
        $params[] = $last_message_id;
        $types .= "i";
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    $sql = "SELECT m.*, u.name as sender_name, u.role as sender_role,
            j.title as job_title
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            LEFT JOIN jobs j ON m.job_id = j.id 
            $where_clause
            ORDER BY m.created_at ASC 
            LIMIT ?";
    
    $params[] = $limit;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $row['id'],
            'sender_id' => $row['sender_id'],
            'sender_name' => $row['sender_name'],
            'sender_role' => $row['sender_role'],
            'message' => $row['message'],
            'job_id' => $row['job_id'],
            'job_title' => $row['job_title'],
            'created_at' => $row['created_at'],
            'is_mine' => $row['sender_id'] == $user_id
        ];
    }
    
    $stmt->close();
    
    // Get conversation summary
    $unread_count = 0;
    $sql = "SELECT COUNT(*) as count FROM messages 
            WHERE sender_id = ? AND receiver_id = ? AND id > COALESCE(
                (SELECT MAX(id) FROM messages WHERE sender_id = ? AND receiver_id = ?), 0
            )";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $other_user_id, $user_id, $user_id, $other_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_data = $result->fetch_assoc();
    $unread_count = $unread_data['count'];
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'other_user' => $other_user,
        'job' => $job ?? null,
        'unread_count' => $unread_count,
        'total_messages' => count($messages)
    ]);
    
} else {
    echo json_encode(['success' => false, 'errors' => $errors]);
}

$conn->close();
?>
