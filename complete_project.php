<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    error_log("Complete Project: User not logged in or not client");
    header("Location: login_new.php");
    exit();
}

include 'database.php';

$proposal_id = trim($_GET['id'] ?? '');

error_log("Complete Project - Proposal ID: " . $proposal_id);

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
        
error_log("Complete Project SQL Query: " . $sql);
error_log("Complete Project Params: proposal_id=" . $proposal_id . ", user_id=" . $_SESSION['user_id']);

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL prepare failed: " . $conn->error);
}
$stmt->bind_param("ii", $proposal_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

error_log("Complete Project Result count: " . $result->num_rows);

if ($result->num_rows == 0) {
    $_SESSION['error_message'] = "Proposal not found or access denied";
    header("Location: client_dashboard_new.php");
    exit();
}

$proposal = $result->fetch_assoc();

if ($proposal['status'] !== 'accepted') {
    $_SESSION['error_message'] = "Only accepted proposals can be marked as completed";
    header("Location: client_dashboard_new.php");
    exit();
}

// Handle project completion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_project'])) {
    try {
        $conn->begin_transaction();
        
        // Update proposal status to completed
        $sql = "UPDATE proposals SET status = 'completed' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        
        // Update job status to completed
        $sql = "UPDATE jobs SET status = 'completed' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $proposal['job_id']);
        $stmt->execute();
        
        $conn->commit();
        
        $_SESSION['success_message'] = "Project marked as completed successfully! The freelancer has been notified.";
        
        header("Location: client_dashboard_new.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Project completion error: " . $e->getMessage());
        $errors[] = "An error occurred while marking the project as completed. Please try again.";
    }
}

$stmt->close();
$conn->close();

error_log("About to output HTML for complete project ID: " . $proposal_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Project - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .complete-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(106,92,255,0.15), rgba(168,85,247,0.15));
        }
        
        .complete-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        .complete-title {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        
        .complete-subtitle {
            color: rgba(255,255,255,0.8);
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .project-info {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
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
        
        .submit-btn {
            width: 100%;
            padding: 20px;
            border: none;
            border-radius: 15px;
            background: linear-gradient(135deg,#27ae60,#229954);
            color: white;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 50px rgba(39,174,60,0.4);
        }
        
        .cancel-btn {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            background: transparent;
            color: rgba(255,255,255,0.8);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .cancel-btn:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.3);
        }
        
        .error-messages {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            color: #ff6b6b;
        }
    </style>
</head>
<body>
    <div class="complete-container">
        <div class="complete-card">
            <h1 class="complete-title">🎯 Complete Project</h1>
            <p class="complete-subtitle">Mark this project as completed and notify the freelancer</p>
            
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <div>❌ <?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="project-info">
                <div class="info-row">
                    <span class="info-label">Project:</span>
                    <span class="info-value"><?php echo htmlspecialchars($proposal['job_title']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Freelancer:</span>
                    <span class="info-value"><?php echo htmlspecialchars($proposal['freelancer_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Bid Amount:</span>
                    <span class="info-value">₹<?php echo number_format($proposal['bid_amount'], 2); ?></span>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="complete_project" value="1">
                
                <button type="submit" class="submit-btn">
                    ✅ Mark Project as Completed
                </button>
            </form>
            
            <a href="client_dashboard_new.php" class="cancel-btn">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
