<?php
/**
 * StudyLance Accept Proposal - Simple Final Version (No Email)
 * Handles proposal acceptance without email notifications
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $_SESSION['error'] = "Please login to access this page";
    header("Location: login_new.php");
    exit();
}

// Check if user is a client
if ($_SESSION['role'] !== 'client') {
    $_SESSION['error'] = "Access denied. Only clients can accept proposals.";
    header("Location: " . $_SESSION['role'] . "_dashboard_new.php");
    exit();
}

// Get proposal ID from URL
$proposal_id = isset($_GET['id']) ? trim($_GET['id']) : '';

// Validate proposal ID
if (empty($proposal_id) || !is_numeric($proposal_id) || $proposal_id <= 0) {
    $_SESSION['error'] = "Invalid proposal ID";
    header("Location: client_dashboard_new.php");
    exit();
}

// Include database only (no email)
include 'database.php';

$proposal = null;
$error = '';
$success = '';

// Handle POST request for accepting proposal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_proposal'])) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if proposal exists and is pending
        $sql = "SELECT p.*, j.title as job_title, j.client_id, u.name as freelancer_name
                FROM proposals p
                JOIN jobs j ON p.job_id = j.id
                JOIN users u ON p.freelancer_id = u.id
                WHERE p.id = ? AND p.status = 'pending' AND j.client_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $proposal_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Proposal not found, already processed, or you don't have permission to accept it.");
        }
        
        $proposal = $result->fetch_assoc();
        
        // Update the accepted proposal
        $sql = "UPDATE proposals SET status = 'accepted' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to update proposal status.");
        }
        
        // Reject all other pending proposals for this job
        $sql = "UPDATE proposals SET status = 'rejected' WHERE job_id = ? AND id != ? AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $proposal['job_id'], $proposal_id);
        $stmt->execute();
        
        // Close the job
        $sql = "UPDATE jobs SET status = 'closed' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $proposal['job_id']);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Success message (no email)
        $success = "Proposal accepted successfully! The freelancer has been selected for this project.";
        
        // Redirect to dashboard with success message
        $_SESSION['success'] = $success;
        header("Location: client_dashboard_new.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
        error_log("Accept proposal error: " . $e->getMessage());
    }
}

// Get proposal details for display
if (empty($error)) {
    try {
        $sql = "SELECT p.*, j.title as job_title, u.name as freelancer_name
                FROM proposals p
                JOIN jobs j ON p.job_id = j.id
                JOIN users u ON p.freelancer_id = u.id
                WHERE p.id = ? AND j.client_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $proposal_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $proposal = $result->fetch_assoc();
        } else {
            $error = "Proposal not found or you don't have permission to access it.";
        }
        
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
        error_log("Get proposal error: " . $e->getMessage());
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Proposal - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .accept-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
        }
        
        .accept-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .accept-header h1 {
            color: white;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .accept-header p {
            color: rgba(255,255,255,0.7);
            font-size: 16px;
        }
        
        .proposal-details {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
        }
        
        .detail-value {
            color: white;
            font-weight: 500;
        }
        
        .bid-amount {
            font-size: 28px;
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .status-accepted {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }
        
        .status-rejected {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .btn-accept {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg,#27ae60,#229954);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-cancel {
            width: 100%;
            padding: 15px;
            background: transparent;
            color: rgba(255,255,255,0.7);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .btn-cancel:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .error-message {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #e74c3c;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid rgba(39, 174, 96, 0.3);
            color: #27ae60;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .simple-notice {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            color: #3498db;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-wrapper">
            <div class="accept-container">
                <div class="accept-header">
                    <h1>Accept Proposal</h1>
                    <p>Review and accept the freelancer's proposal</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        ❌ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message">
                        ✅ <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($proposal): ?>
                    <div class="simple-notice">
                        📋 Simple acceptance process - No email notifications required.
                    </div>
                    
                    <div class="proposal-details">
                        <div class="detail-row">
                            <span class="detail-label">Job Title</span>
                            <span class="detail-value"><?php echo htmlspecialchars($proposal['job_title']); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Freelancer</span>
                            <span class="detail-value"><?php echo htmlspecialchars($proposal['freelancer_name']); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Bid Amount</span>
                            <span class="detail-value bid-amount">₹<?php echo number_format($proposal['bid_amount'], 2); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Current Status</span>
                            <span class="detail-value">
                                <span class="status-badge status-<?php echo $proposal['status']; ?>">
                                    <?php echo ucfirst($proposal['status']); ?>
                                </span>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Submitted</span>
                            <span class="detail-value"><?php echo date('M j, Y', strtotime($proposal['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($proposal['status'] === 'pending'): ?>
                        <form method="post">
                            <input type="hidden" name="accept_proposal" value="1">
                            <button type="submit" class="btn-accept">
                                ✅ Accept Proposal
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="error-message">
                            ❌ This proposal cannot be accepted. Current status: <?php echo ucfirst($proposal['status']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="client_dashboard_new.php" class="btn-cancel">
                        ← Back to Dashboard
                    </a>
                    
                <?php else: ?>
                    <div class="error-message">
                        ❌ Proposal not found or you don't have permission to access it.
                    </div>
                    
                    <a href="client_dashboard_new.php" class="btn-cancel">
                        ← Back to Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
