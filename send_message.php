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

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $receiver_id = trim($_POST['receiver_id']);
    $message = trim($_POST['message']);
    $job_id = trim($_POST['job_id'] ?? null);
    
    // Validation
    if (empty($receiver_id) || !is_numeric($receiver_id)) {
        $errors[] = "Valid receiver ID is required";
    }
    
    if (empty($message)) {
        $errors[] = "Message cannot be empty";
    } elseif (strlen($message) > 1000) {
        $errors[] = "Message is too long (max 1000 characters)";
    }
    
    // Verify receiver exists
    if (empty($errors)) {
        $sql = "SELECT id, name FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $receiver_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $errors[] = "Receiver not found";
        }
        $stmt->close();
    }
    
    // Verify job_id if provided
    if (!empty($job_id) && empty($errors)) {
        $sql = "SELECT id FROM jobs WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $errors[] = "Job not found";
        }
        $stmt->close();
    }
    
    // Insert message if no errors
    if (empty($errors)) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, message, job_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($job_id) {
            $stmt->bind_param("iisi", $_SESSION['user_id'], $receiver_id, $message, $job_id);
        } else {
            $stmt->bind_param("iis", $_SESSION['user_id'], $receiver_id, $message);
        }
        
        if ($stmt->execute()) {
            $message_id = $conn->insert_id;
            
            // Get the inserted message details
            $sql = "SELECT m.*, u.name as sender_name FROM messages m 
                    JOIN users u ON m.sender_id = u.id 
                    WHERE m.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $message_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $message_data = $result->fetch_assoc();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => $message_data,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send message']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'errors' => $errors]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
