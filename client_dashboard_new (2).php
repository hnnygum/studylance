<?php
session_start();

// Check if user is logged in and is client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login_new.php");
    exit();
}

require_once 'config.php';

// Handle job posting
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_job'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget = trim($_POST['budget']);
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Job title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Job description is required";
    }
    
    if (empty($budget) || !is_numeric($budget) || $budget <= 0) {
        $errors[] = "Valid budget is required";
    }
    
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            $sql = "INSERT INTO jobs (title, description, budget, client_id, created_at) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->query($sql, [$title, $description, $budget, $_SESSION['user_id'], date('Y-m-d H:i:s')]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = "Job posted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to post job";
            }
            
            header("Location: client_dashboard_new.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }
    }
}

// Get client's jobs
try {
    $db = Database::getInstance();
    $sql = "SELECT * FROM jobs WHERE client_id = ? ORDER BY created_at DESC";
    $stmt = $db->query($sql, [$_SESSION['user_id']]);
    $jobs = $stmt->fetchAll();
} catch (Exception $e) {
    $jobs = [];
    error_log("Error fetching jobs: " . $e->getMessage());
}

// Get proposals for client's jobs
$proposals = [];
if (!empty($jobs)) {
    try {
        $job_ids = array_column($jobs, 'id');
        $placeholders = str_repeat('?,', count($job_ids));
        $placeholders = rtrim($placeholders, ',');
        
        $sql = "SELECT p.*, j.title as job_title, u.name as freelancer_name, u.email as freelancer_email 
                FROM proposals p 
                JOIN jobs j ON p.job_id = j.id 
                JOIN users u ON p.freelancer_id = u.id 
                WHERE p.job_id IN ($placeholders) 
                ORDER BY p.created_at DESC";
        
        $params = array_merge($job_ids);
        $stmt = $db->query($sql, $params);
        $proposals = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error fetching proposals: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Simple Dashboard Styles - Enhanced Readability */
        .dashboard-container {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, rgba(106,92,255,0.15), rgba(168,85,247,0.15));
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
        }
        
        .header-content h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
            color: white;
        }
        
        .user-info span {
            font-size: 16px;
            font-weight: 500;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            -webkit-app-region: no-drag;
        }
        
        .btn-logout {
            background: linear-gradient(135deg,#e74c3c,#c0392b);
            color: white;
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(231,76,60,0.3);
        }
        
        .btn-view {
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(106,92,255,0.3);
        }
        
        .btn-accept {
            background: linear-gradient(135deg,#27ae60,#229954);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(39,174,60,0.3);
        }
        
        .btn-complete {
            background: linear-gradient(135deg,#f39c12,#e67e22);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(243,156,18,0.3);
        }
        
        .btn-message {
            background: linear-gradient(135deg,#3498db,#2980b9);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-message:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(52,152,219,0.3);
        }
        
        .btn-view:active {
            transform: translateY(0px);
            box-shadow: 0 5px 15px rgba(52,152,219,0.4);
        }
        
        .dashboard-nav {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 20px 0;
            position: sticky;
            top: 80px;
            z-index: 90;
        }
        
        .dashboard-nav ul {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            list-style: none;
            padding: 0 30px;
            gap: 30px;
        }
        
        .dashboard-nav a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            -webkit-app-region: no-drag;
        }
        
        .dashboard-nav a:hover,
        .dashboard-nav a.active {
            background: rgba(106,92,255,0.2);
            color: white;
        }
        
        .dashboard-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 30px;
        }
        
        .section {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .section h2 {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: white;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            -webkit-app-region: no-drag;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6a5cff;
            background: rgba(255,255,255,0.08);
        }
        
        .btn-primary {
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            padding: 15px 30px;
            font-size: 16px;
            width: 100%;
            -webkit-app-region: no-drag;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(106,92,255,0.3);
        }
        
        .success-message,
        .error-message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .success-message {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid rgba(39, 174, 96, 0.3);
            color: #4ade80;
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
        }
        
        .error-messages {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .error-messages p {
            color: #ff6b6b;
            margin: 5px 0;
        }
        
        .no-data {
            text-align: center;
            color: rgba(255,255,255,0.7);
            font-size: 18px;
            padding: 40px 20px;
        }
        
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .job-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(106,92,255,0.2);
            border-color: rgba(106,92,255,0.3);
        }
        
        .job-card h3 {
            font-size: 20px;
            font-weight: 600;
            color: white;
            margin-bottom: 10px;
        }
        
        .job-description {
            color: rgba(255,255,255,0.8);
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .job-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .budget {
            font-size: 18px;
            font-weight: 600;
            color: #6a5cff;
        }
        
        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-open {
            background: linear-gradient(135deg,#27ae60,#229954);
            color: white;
        }
        
        .status-in_progress {
            background: linear-gradient(135deg,#f39c12,#e67e22);
            color: white;
        }
        
        .job-date {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
        }
        
        .proposals-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .proposal-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .proposal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(106,92,255,0.2);
            border-color: rgba(106,92,255,0.3);
        }
        
        .proposal-card h4 {
            font-size: 18px;
            font-weight: 600;
            color: white;
            margin-bottom: 8px;
        }
        
        .freelancer-name {
            color: #6a5cff;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .proposal-text {
            color: rgba(255,255,255,0.8);
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .proposal-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .bid-amount {
            font-size: 16px;
            font-weight: 600;
            color: #6a5cff;
        }
        
        .proposal-date {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
        }
        
        /* Modal Styles - Enhanced Readability */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.85);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            -webkit-backdrop-filter: blur(5px);
            backdrop-filter: blur(5px);
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(106,92,255,0.1), rgba(168,85,247,0.1));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 30px;
            max-width: 700px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            -webkit-app-region: no-drag;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .modal-title {
            font-size: 26px;
            font-weight: 700;
            color: white;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: rgba(255,255,255,0.8);
            font-size: 28px;
            cursor: pointer;
            transition: all 0.3s ease;
            -webkit-app-region: no-drag;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-close:hover {
            color: white;
            background: rgba(255,255,255,0.2);
            transform: scale(1.1);
        }
        
        .modal-body {
            color: rgba(255,255,255,0.9);
            line-height: 1.7;
            font-size: 16px;
        }
        
        .proposal-full-text {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: rgba(255,255,255,0.95);
            text-align: left;
        }
        
        .modal-info {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .modal-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 15px;
        }
        
        .modal-info-label {
            font-weight: 600;
            color: #6a5cff;
        }
        
        .modal-info-value {
            color: white;
            font-weight: 500;
        }
        
        .copy-btn {
            background: linear-gradient(135deg,#3498db,#2980b9);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            -webkit-app-region: no-drag;
            margin: 5px;
            position: relative;
            overflow: hidden;
        }
        
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(52,152,219,0.3);
            background: linear-gradient(135deg,#2980b9,#21618c);
        }
        
        .copy-btn.copied {
            background: linear-gradient(135deg,#27ae60,#229954);
            transform: scale(1.05);
        }
        
        .copy-btn:active {
            transform: translateY(0px);
            box-shadow: 0 5px 15px rgba(52,152,219,0.4);
        }
        
        /* Enhanced scrollbar for readability */
        .proposal-full-text::-webkit-scrollbar {
            width: 12px;
        }
        
        .proposal-full-text::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
            border-radius: 6px;
        }
        
        .proposal-full-text::-webkit-scrollbar-thumb {
            background: rgba(106,92,255,0.4);
            border-radius: 6px;
            border: 2px solid rgba(255,255,255,0.1);
        }
        
                padding: 20px 15px;
            }
            
            .header-content {
                padding: 0 15px;
                flex-direction: column;
                gap: 15px;
            }
            
            .dashboard-nav ul {
                padding: 0 15px;
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .jobs-grid,
            .proposals-list {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                max-width: 95%;
                max-height: 90vh;
            }
            
            .proposal-full-text {
                font-size: 14px;
                padding: 20px;
                max-height: 300px;
            }
        }
        
        @media (max-width: 480px) {
            .modal-content {
                max-width: 98%;
                padding: 20px;
            }
            
            .proposal-full-text {
                font-size: 13px;
                padding: 15px;
                max-height: 250px;
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

    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>StudyLance</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="logout_new.php" class="btn btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <nav class="dashboard-nav">
            <ul>
                <li><a href="client_dashboard_new.php" class="active">Dashboard</a></li>
                <li><a href="#post-job">Post Job</a></li>
                <li><a href="#my-jobs">My Jobs</a></li>
                <li><a href="#proposals">Proposals</a></li>
                <li><a href="messages_new.php">Messages</a></li>
            </ul>
        </nav>

        <main class="dashboard-main">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <?php 
                    echo htmlspecialchars($_SESSION['error_message']);
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section id="post-job" class="section">
                <h2>Post New Job</h2>
                <form method="POST" action="" class="job-form">
                    <input type="hidden" name="post_job" value="1">
                    
                    <div class="form-group">
                        <label for="title">Job Title:</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Job Description:</label>
                        <textarea id="description" name="description" rows="5" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="budget">Budget (₹):</label>
                        <input type="number" id="budget" name="budget" step="0.01" min="1" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Post Job</button>
                </form>
            </section>

            <section id="my-jobs" class="section">
                <h2>My Posted Jobs</h2>
                <?php if (empty($jobs)): ?>
                    <p class="no-data">You haven't posted any jobs yet.</p>
                <?php else: ?>
                    <div class="jobs-grid">
                        <?php foreach ($jobs as $job): ?>
                            <div class="job-card">
                                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                <p class="job-description"><?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 200))); ?>...</p>
                                <div class="job-meta">
                                    <span class="budget">₹<?php echo number_format($job['budget'], 2); ?></span>
                                    <span class="status status-<?php echo str_replace('_', '-', $job['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?></span>
                                </div>
                                <div class="job-date">
                                    Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section id="proposals" class="section">
                <h2>Recent Proposals</h2>
                <?php if (empty($proposals)): ?>
                    <p class="no-data">No proposals received yet.</p>
                <?php else: ?>
                    <div class="proposals-list">
                        <?php foreach ($proposals as $proposal): ?>
                            <div class="proposal-card">
                                <h4><?php echo htmlspecialchars($proposal['job_title']); ?></h4>
                                <p class="freelancer-name">By: <?php echo htmlspecialchars($proposal['freelancer_name']); ?></p>
                                <p class="proposal-text"><?php echo nl2br(htmlspecialchars(substr($proposal['cover_letter'], 0, 150))); ?>...</p>
                                <div class="proposal-meta">
                                    <span class="bid-amount">Bid: ₹<?php echo number_format($proposal['bid_amount'], 2); ?></span>
                                    <span class="status status-<?php echo str_replace('_', '-', $proposal['status']); ?>"><?php echo ucfirst($proposal['status']); ?></span>
                                </div>
                                <div class="proposal-date">
                                    <?php echo date('M j, Y', strtotime($proposal['created_at'])); ?>
                                </div>
                                <div style="margin-top: 15px;">
                                    <button class="btn btn-view" onclick="viewProposal('<?php echo addslashes($proposal['id']); ?>', '<?php echo addslashes($proposal['freelancer_name']); ?>', '<?php echo addslashes($proposal['job_title']); ?>', '<?php echo addslashes(htmlspecialchars_decode($proposal['cover_letter'])); ?>', '<?php echo addslashes($proposal['bid_amount']); ?>', '<?php echo addslashes($proposal['status']); ?>', '<?php echo addslashes($proposal['freelancer_id']); ?>')">
                                        �️ View Proposal
                                    </button>
                                    <?php if ($proposal['status'] === 'pending'): ?>
                                        <button class="btn btn-accept" onclick="acceptProposal(<?php echo $proposal['id']; ?>)" style="margin-left: 10px;">
                                            ✅ Accept Proposal
                                        </button>
                                    <?php elseif ($proposal['status'] === 'accepted'): ?>
                                        <button class="btn btn-complete" onclick="completeProject(<?php echo $proposal['id']; ?>)" style="margin-left: 10px;">
                                            🎯 Mark Complete
                                        </button>
                                        <button class="btn btn-message" onclick="messageFreelancer(<?php echo $proposal['freelancer_id']; ?>)" style="margin-left: 10px;">
                                            💬 Message
                                        </button>
                                    <?php elseif ($proposal['status'] === 'completed'): ?>
                                        <button class="btn btn-message" onclick="messageFreelancer(<?php echo $proposal['freelancer_id']; ?>)" style="margin-left: 10px;">
                                            💬 Message
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Modal for viewing full proposal - Enhanced Readability -->
    <div id="proposalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="proposal-header">
                    <div class="proposal-title">Freelancer's Proposal</div>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
            </div>
            <div class="modal-body">
                <div class="modal-info">
                    <div class="modal-info-row">
                        <span class="modal-info-label">Freelancer:</span>
                        <span class="modal-info-value" id="modalFreelancer"></span>
                    </div>
                    <div class="modal-info-row">
                        <span class="modal-info-label">Job:</span>
                        <span class="modal-info-value" id="modalJob"></span>
                    </div>
                    <div class="modal-info-row">
                        <span class="modal-info-label">Bid Amount:</span>
                        <span class="modal-info-value" id="modalBid"></span>
                    </div>
                </div>
                
                <div class="proposal-full-text" id="modalProposal"></div>
                
                <div class="proposal-actions">
                    <button class="copy-btn" onclick="copyProposal()" id="copyButton">
                        📋 Copy Proposal
                    </button>
                    <button class="copy-btn" onclick="closeModal()">
                        Close
                    </button>
                    <button class="btn btn-accept" id="modalAcceptBtn" onclick="acceptProposal(proposalData.id)" style="margin-left: 10px; display: none;">
                        ✅ Accept Proposal
                    </button>
                    <button class="btn btn-complete" id="modalCompleteBtn" onclick="completeProject(proposalData.id)" style="margin-left: 10px; display: none;">
                        🎯 Mark Complete
                    </button>
                    <button class="btn btn-message" id="modalMessageBtn" onclick="messageFreelancer(proposalData.freelancerId)" style="margin-left: 10px; display: none;">
                        💬 Message
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Store proposal data globally
        let proposalData = {};
        
        // Mac-specific optimizations
        const isMac = /Mac|iPod|iPhone|iPad/.test(navigator.userAgent);
        console.log('Running on Mac:', isMac);
        
        function viewProposal(proposalId, freelancerName, jobTitle, proposalText, bidAmount, status = 'pending', freelancerId = null) {
            console.log('Opening modal for:', {proposalId, freelancerName, jobTitle, bidAmount, status, freelancerId});
            console.log('Proposal text length:', proposalText ? proposalText.length : 'NULL');
            console.log('Proposal text preview:', proposalText ? proposalText.substring(0, 100) : 'NO TEXT');
            
            // Store data globally
            proposalData = {
                id: proposalId,
                freelancer: freelancerName,
                job: jobTitle,
                text: proposalText,
                bid: bidAmount,
                status: status,
                freelancerId: freelancerId
            };
            
            // Set modal content - decode HTML entities for display
            document.getElementById('modalFreelancer').textContent = freelancerName;
            document.getElementById('modalJob').textContent = jobTitle;
            document.getElementById('modalBid').textContent = '₹' + bidAmount;
            
            // Handle proposal text display
            const modalProposal = document.getElementById('modalProposal');
            if (proposalText && proposalText.trim()) {
                modalProposal.innerHTML = proposalText.replace(/\n/g, '<br>');
            } else {
                modalProposal.innerHTML = '<em style="color: #666;">No proposal content available</em>';
            }
            
            console.log('Modal content set, showing modal...');
            
            // Show modal with Mac-specific animation
            const modal = document.getElementById('proposalModal');
            modal.classList.add('active');
            
            // Mac-specific: Force modal to front
            if (isMac) {
                modal.style.zIndex = '10000';
                setTimeout(() => {
                    modal.style.zIndex = '1000';
                }, 100);
            }
            
            console.log('Modal should be visible now');
            
            // Show/hide action buttons based on proposal status
            const acceptBtn = document.getElementById('modalAcceptBtn');
            const completeBtn = document.getElementById('modalCompleteBtn');
            const messageBtn = document.getElementById('modalMessageBtn');
            
            if (acceptBtn && completeBtn && messageBtn) {
                if (status === 'pending') {
                    acceptBtn.style.display = 'inline-block';
                    completeBtn.style.display = 'none';
                    messageBtn.style.display = 'none';
                } else if (status === 'accepted') {
                    acceptBtn.style.display = 'none';
                    completeBtn.style.display = 'inline-block';
                    messageBtn.style.display = 'inline-block';
                } else if (status === 'completed') {
                    acceptBtn.style.display = 'none';
                    completeBtn.style.display = 'none';
                    messageBtn.style.display = 'inline-block';
                } else {
                    acceptBtn.style.display = 'none';
                    completeBtn.style.display = 'none';
                    messageBtn.style.display = 'none';
                }
            }
        }
        
        function closeModal() {
            const modal = document.getElementById('proposalModal');
            modal.classList.remove('active');
            console.log('Modal closed');
        }
        
        function copyProposal() {
            const proposalText = document.getElementById('modalProposal').innerText;
            const copyButton = document.getElementById('copyButton');
            
            console.log('Attempting to copy text:', proposalText.substring(0, 50) + '...');
            
            // Prevent multiple clicks
            if (copyButton.disabled) {
                console.log('Copy already in progress...');
                return;
            }
            
            // Disable button temporarily
            copyButton.disabled = true;
            const originalText = copyButton.textContent;
            
            // Mac-specific clipboard handling
            if (isMac) {
                // Try modern clipboard API first
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(proposalText).then(() => {
                        console.log('Modern clipboard API worked');
                        showCopySuccess();
                    }).catch(err => {
                        console.log('Modern clipboard failed, trying fallback:', err);
                        // Fallback for Mac
                        fallbackCopy(proposalText);
                    });
                } else {
                    fallbackCopy(proposalText);
                }
            } else {
                // Non-Mac browsers
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(proposalText).then(() => {
                        console.log('Clipboard copy successful');
                        showCopySuccess();
                    }).catch(err => {
                        console.log('Clipboard failed, trying fallback:', err);
                        fallbackCopy(proposalText);
                    });
                } else {
                    fallbackCopy(proposalText);
                }
            }
        }
        
        function showCopySuccess() {
            console.log('Copy successful - updating UI');
            
            const copyButton = document.getElementById('copyButton');
            
            // Change button text temporarily
            copyButton.textContent = '✅ Copied!';
            copyButton.classList.add('copied');
            
            setTimeout(() => {
                copyButton.textContent = '📋 Copy Proposal';
                copyButton.classList.remove('copied');
                copyButton.disabled = false; // Re-enable button
            }, 2000);
        }
        
        function fallbackCopy(text) {
            // Fallback method for older browsers/Mac issues
            try {
                // Create temporary textarea
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                textarea.style.top = '0';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                
                // Select and copy
                textarea.select();
                textarea.setSelectionRange(0, 99999);
                
                const successful = document.execCommand('copy');
                document.body.removeChild(textarea);
                
                if (successful) {
                    console.log('Fallback copy successful');
                    showCopySuccess();
                } else {
                    console.error('Fallback copy failed');
                }
            } catch (err) {
                console.error('Fallback copy error:', err);
            }
        }
        
        // Enhanced modal event listeners for Mac
        document.getElementById('proposalModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Enhanced keyboard handling for Mac
        document.addEventListener('keydown', function(e) {
            // Command+W for Mac, Escape for all
            if ((e.key === 'Escape' || (e.key === 'w' && e.metaKey && isMac))) {
                closeModal();
            }
        });
        
        function acceptProposal(proposalId) {
            if (confirm('Are you sure you want to accept this proposal? This will reject all other proposals for this job.')) {
                window.location.href = 'accept_proposal_clean.php?id=' + proposalId;
            }
        }
        
        function completeProject(proposalId) {
            if (confirm('Are you sure you want to mark this project as completed?')) {
                window.location.href = 'complete_project_simple.php?id=' + proposalId;
            }
        }
        
        function messageFreelancer(freelancerId) {
            if (freelancerId) {
                window.location.href = 'messages_new.php?user=' + freelancerId;
            } else {
                alert('Unable to open messaging. Please try again.');
            }
        }
        
        // Debug: Check if modal exists on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, checking modal element...');
            const modal = document.getElementById('proposalModal');
            if (modal) {
                console.log('Modal element found');
                
                // Mac-specific: Add Command+W hint
                if (isMac) {
                    console.log('Mac detected - Command+W will close modal');
                }
            } else {
                console.error('Modal element not found!');
            }
        });
        
        // Prevent text selection during drag on Mac
        document.addEventListener('selectstart', function(e) {
            if (isMac && e.target.closest('.modal-content')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
