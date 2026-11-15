<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    $_SESSION['error_message'] = "Access denied";
    header("Location: login_new.php");
    exit();
}

include 'database.php';

$proposal_id = trim($_GET['id'] ?? '');

if (empty($proposal_id) || !is_numeric($proposal_id)) {
    $_SESSION['error_message'] = "Invalid proposal";
    header("Location: client_dashboard_new.php");
    exit();
}

// Get proposal details
$sql = "SELECT p.*, j.client_id, j.status as job_status, j.title as job_title,
        u.name as freelancer_name, u.email as freelancer_email
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        JOIN users u ON p.freelancer_id = u.id
        WHERE p.id = ? AND j.client_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $proposal_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error_message'] = "Proposal not found or access denied";
    header("Location: client_dashboard_new.php");
    exit();
}

$proposal = $result->fetch_assoc();

// Check if proposal is still pending
if ($proposal['status'] !== 'pending') {
    $_SESSION['error_message'] = "This proposal has already been " . $proposal['status'];
    header("Location: client_dashboard_new.php");
    exit();
}

// Handle proposal acceptance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept_proposal'])) {
    try {
        $conn->begin_transaction();
        
        // Update proposal status to accepted
        $sql = "UPDATE proposals SET status = 'accepted' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        
        // Reject all other proposals for this job
        $sql = "UPDATE proposals SET status = 'rejected' 
                WHERE job_id = ? AND id != ? AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $proposal['job_id'], $proposal_id);
        $stmt->execute();
        
        // Update job status to closed
        $sql = "UPDATE jobs SET status = 'closed' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $proposal['job_id']);
        $stmt->execute();
        
        $conn->commit();
        
        $_SESSION['success_message'] = "Proposal accepted successfully! The job has been closed and other proposals have been rejected.";
        header("Location: client_dashboard_new.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "An error occurred while accepting the proposal. Please try again.";
    }
}

$stmt->close();
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(106,92,255,0.15), rgba(168,85,247,0.15));
            padding: 20px;
        }
        
        .accept-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        
        .accept-title {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .proposal-info {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: rgba(255,255,255,0.8);
        }
        
        .info-label {
            font-weight: 600;
        }
        
        .info-value {
            color: white;
        }
        
        .proposal-bid {
            font-size: 24px;
            font-weight: 700;
            color: #27ae60;
            text-align: center;
            margin: 20px 0;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg,#27ae60,#229954);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(39,174,60,0.3);
        }
        
        .cancel-btn {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            background: transparent;
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .cancel-btn:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .error-messages {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            color: #ff6b6b;
        }
    </style>
</head>
<body>
    <div class="accept-container">
        <div class="accept-card">
            <h1 class="accept-title">✅ Accept Proposal</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <div>❌ <?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="proposal-info">
                <div class="info-row">
                    <span class="info-label">Job:</span>
                    <span class="info-value"><?php echo htmlspecialchars($proposal['job_title']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Freelancer:</span>
                    <span class="info-value"><?php echo htmlspecialchars($proposal['freelancer_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Current Status:</span>
                    <span class="info-value"><?php echo ucfirst($proposal['status']); ?></span>
                </div>
            </div>
            
            <div class="proposal-bid">
                <span class="budget-symbol">₹</span>
                <?php echo number_format($proposal['bid_amount'], 2); ?>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="accept_proposal" value="1">
                
                <button type="submit" class="submit-btn">
                    ✅ Accept This Proposal
                </button>
            </form>
            
            <a href="client_dashboard_new.php" class="cancel-btn">
                ← Cancel and Go Back
            </a>
            
            <div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 10px; font-size: 12px; color: rgba(255,255,255,0.6);">
                <strong>Note:</strong> Accepting this proposal will reject all other proposals for this job and mark the job as closed.
            </div>
        </div>
    </div>
</body>
</html>
