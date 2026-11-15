<?php
session_start();

// Basic check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    die("Access denied");
}

include 'database.php';

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
            // Get job_id
            $sql = "SELECT job_id FROM proposals WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $job = $result->fetch_assoc();
            $job_id = $job['job_id'];
            
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
            
            $_SESSION['success'] = "Proposal accepted!";
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Accept Proposal</h1>
        
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
            <button type="submit" class="btn accept">✅ Accept Proposal</button>
        </form>
        
        <a href="client_dashboard_new.php" class="btn cancel">← Cancel</a>
    </div>
</body>
</html>
