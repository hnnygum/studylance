<?php
/**
 * StudyLance Accept Proposal - Clean Version (No Email System)
 * Handles proposal acceptance without any email dependencies
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

// Include database only
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
        
        // Success message (no email - clean system)
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
        
        .clean-notice {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid rgba(39, 174, 96, 0.3);
            color: #27ae60;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        
        .portfolio-url-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .portfolio-link {
            color: #6a5cff;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 1px solid rgba(106,92,255,0.3);
            padding: 4px 8px;
            border-radius: 5px;
            background: rgba(106,92,255,0.1);
        }
        
        .portfolio-link:hover {
            background: rgba(106,92,255,0.2);
            color: white;
            transform: translateY(-1px);
        }
        
        .preview-portfolio-btn {
            background: rgba(52, 152, 219, 0.2);
            border: 1px solid rgba(52, 152, 219, 0.3);
            color: #3498db;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .preview-portfolio-btn:hover {
            background: rgba(52, 152, 219, 0.3);
            color: white;
            transform: translateY(-1px);
        }
        
        .portfolio-preview-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .portfolio-preview-content {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            max-width: 90%;
            max-height: 90%;
            position: relative;
        }
        
        .portfolio-preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .portfolio-preview-title {
            color: white;
            font-size: 18px;
            font-weight: 600;
        }
        
        .portfolio-preview-close {
            color: rgba(255,255,255,0.7);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .portfolio-preview-close:hover {
            color: white;
        }
        
        .portfolio-preview-frame {
            width: 100%;
            height: 70vh;
            border: none;
            border-radius: 10px;
            background: white;
        }
        
        .portfolio-preview-info {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            margin-bottom: 15px;
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
                    <div class="clean-notice">
                        ✅ Clean System - No email dependencies, no conflicts!
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
                        
                        <?php if (!empty($proposal['portfolio_url'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Portfolio URL</span>
                            <span class="detail-value">
                                <div class="portfolio-url-wrapper">
                                    <a href="<?php echo htmlspecialchars($proposal['portfolio_url']); ?>" 
                                       target="_blank" 
                                       class="portfolio-link">
                                        🔗 <?php echo htmlspecialchars(substr($proposal['portfolio_url'], 0, 40)); ?>...
                                    </a>
                                    <button type="button" 
                                            class="preview-portfolio-btn" 
                                            onclick="previewPortfolio('<?php echo htmlspecialchars($proposal['portfolio_url']); ?>')">
                                        👁️ Preview
                                    </button>
                                </div>
                            </span>
                        </div>
                        <?php endif; ?>
                        
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

    <!-- Portfolio Preview Modal -->
    <div id="portfolioPreviewModal" class="portfolio-preview-modal">
        <div class="portfolio-preview-content">
            <div class="portfolio-preview-header">
                <div class="portfolio-preview-title">🔗 Portfolio Preview</div>
                <span class="portfolio-preview-close" onclick="closePortfolioPreview()">&times;</span>
            </div>
            <div class="portfolio-preview-info">
                <p>Previewing freelancer's portfolio website</p>
            </div>
            <iframe id="portfolioFrame" class="portfolio-preview-frame" src=""></iframe>
        </div>
    </div>

    <script>
        function previewPortfolio(url) {
            if (!url) return;
            
            const modal = document.getElementById('portfolioPreviewModal');
            const frame = document.getElementById('portfolioFrame');
            
            // Set the iframe source
            frame.src = url;
            
            // Show the modal
            modal.style.display = 'flex';
            
            // Add loading indicator
            frame.onload = function() {
                console.log('Portfolio loaded successfully');
            };
            
            frame.onerror = function() {
                console.error('Failed to load portfolio');
                alert('Unable to load portfolio. The website may not allow embedding or may not exist.');
            };
        }
        
        function closePortfolioPreview() {
            const modal = document.getElementById('portfolioPreviewModal');
            const frame = document.getElementById('portfolioFrame');
            
            // Hide modal
            modal.style.display = 'none';
            
            // Clear iframe source
            frame.src = '';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('portfolioPreviewModal');
            if (event.target == modal) {
                closePortfolioPreview();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePortfolioPreview();
            }
        });
    </script>
</body>
</html>
