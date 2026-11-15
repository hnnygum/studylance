<?php
/**
 * StudyLance Client Dashboard - WITH EMAIL NOTIFICATIONS
 * Job posting and proposal management for clients
 */

require_once 'config.php';
require_once 'email_config.php';

// Require client role
requireRole('client');

// Handle job posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_job'])) {
    redirect('post_job_new.php');
}

// Handle proposal acceptance/rejection - FIXED WITH EMAIL
if (isset($_GET['action']) && isset($_GET['proposal_id'])) {
    $action = sanitize($_GET['action']);
    $proposalId = (int)$_GET['proposal_id'];
    
    if (in_array($action, ['accept', 'reject'])) {
        try {
            $db = Database::getInstance();
            
            // First, get the proposal details to verify ownership
            $stmt = $db->query("
                SELECT p.*, j.client_id 
                FROM proposals p 
                JOIN jobs j ON p.job_id = j.id 
                WHERE p.id = ? AND j.client_id = ?
            ", [$proposalId, $_SESSION['user_id']]);
            
            $proposal = $stmt->fetch();
            
            if ($proposal) {
                // Update proposal status
                $stmt = $db->query("UPDATE proposals SET status = ? WHERE id = ?", [$action . 'ed', $proposalId]);
                
                if ($stmt->rowCount() > 0) {
                    // If accepting, update job status and send emails
                    if ($action === 'accept') {
                        $stmt = $db->query("UPDATE jobs SET status = 'in_progress' WHERE id = ?", [$proposal['job_id']]);
                        
                        // Send email notifications
                        sendProposalAcceptedNotification($proposalId);
                        
                        redirect('client_dashboard_new.php', "Proposal accepted successfully! Email notifications sent.", 'success');
                    } else {
                        redirect('client_dashboard_new.php', 'Proposal rejected', 'success');
                    }
                } else {
                    redirect('client_dashboard_new.php', 'Failed to update proposal', 'error');
                }
            } else {
                redirect('client_dashboard_new.php', 'Proposal not found or access denied', 'error');
            }
            
        } catch (Exception $e) {
            error_log("Proposal action error: " . $e->getMessage());
            redirect('client_dashboard_new.php', 'Error updating proposal: ' . $e->getMessage(), 'error');
        }
    }
}

// Get dashboard data
try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    // Client stats
    $stmt = $db->query("SELECT COUNT(*) as count FROM jobs WHERE client_id = ?", [$userId]);
    $total_jobs = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM jobs WHERE client_id = ? AND status = 'open'", [$userId]);
    $open_jobs = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM proposals p JOIN jobs j ON p.job_id = j.id WHERE j.client_id = ?", [$userId]);
    $total_proposals = $stmt->fetch()['count'];
    
    // Recent jobs
    $stmt = $db->query("SELECT * FROM jobs WHERE client_id = ? ORDER BY created_at DESC LIMIT 5", [$userId]);
    $jobs = $stmt->fetchAll();
    
    // Recent proposals - FIXED: Include cover_letter/proposal_text
    $stmt = $db->query("
        SELECT p.*, j.title as job_title, u.name as freelancer_name, u.email as freelancer_email, u.id as freelancer_id
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        JOIN users u ON p.freelancer_id = u.id 
        WHERE j.client_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ", [$userId]);
    $proposals = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $total_jobs = $open_jobs = $total_proposals = 0;
    $jobs = $proposals = [];
}

// Get flash message
$flash = getFlashMessage();
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
        /* Modern Client Dashboard Styles */
        .dashboard-container {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Left Side - Navigation & User Info */
        .dashboard-left {
            background: linear-gradient(135deg, rgba(106,92,255,0.15), rgba(168,85,247,0.15));
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(106,92,255,0.1), transparent 70%);
            animation: float 20s ease-in-out infinite;
        }
        
        .dashboard-logo {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .dashboard-title {
            font-size: 18px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }
        
        .nav-section {
            position: relative;
            z-index: 1;
        }
        
        .nav-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-bottom: 25px;
        }
        
        .nav-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 40px;
        }
        
        .nav-item {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
        }
        
        .nav-item:hover,
        .nav-item.active {
            background: rgba(106,92,255,0.2);
            transform: translateY(-5px);
            border-color: rgba(106,92,255,0.3);
            box-shadow: 0 15px 30px rgba(106,92,255,0.2);
        }
        
        .nav-icon {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .nav-label {
            font-size: 14px;
            font-weight: 500;
        }
        
        .quick-actions {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        
        .quick-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
        }
        
        .quick-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .quick-item {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
        }
        
        .quick-item:hover {
            background: rgba(106,92,255,0.2);
            transform: translateY(-3px);
            border-color: rgba(106,92,255,0.3);
        }
        
        .quick-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .quick-label {
            font-size: 12px;
            font-weight: 500;
        }
        
        .user-section {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 25px;
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
        
        /* Right Side - Main Content */
        .dashboard-right {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-y: auto;
        }
        
        .content-header {
            margin-bottom: 40px;
        }
        
        .page-title {
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: rgba(255,255,255,0.7);
            font-size: 18px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(106,92,255,0.2);
            border-color: rgba(106,92,255,0.3);
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: white;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Content Sections */
        .content-section {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }
        
        .section-actions {
            display: flex;
            gap: 15px;
        }
        
        /* Table Styles */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border-radius: 15px;
        }
        
        .modern-table thead {
            background: linear-gradient(135deg, rgba(106,92,255,0.2), rgba(168,85,247,0.2));
        }
        
        .modern-table th {
            padding: 15px 20px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .modern-table tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
        }
        
        .modern-table tbody tr:hover {
            background: rgba(106,92,255,0.05);
        }
        
        .modern-table td {
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status-open {
            background: linear-gradient(135deg,#27ae60,#229954);
            color: white;
        }
        
        .status-in-progress {
            background: linear-gradient(135deg,#f39c12,#e67e22);
            color: white;
        }
        
        .status-completed {
            background: linear-gradient(135deg,#95a5a6,#7f8c8d);
            color: white;
        }
        
        .status-pending {
            background: linear-gradient(135deg,#f39c12,#e67e22);
            color: white;
        }
        
        .status-accepted {
            background: linear-gradient(135deg,#27ae60,#229954);
            color: white;
        }
        
        .status-rejected {
            background: linear-gradient(135deg,#e74c3c,#c0392b);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-action {
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
        
        .btn-primary {
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(106,92,255,0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg,#27ae60,#229954);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(39,174,60,0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg,#e74c3c,#c0392b);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(231,76,60,0.3);
        }
        
        .btn-info {
            background: linear-gradient(135deg,#3498db,#2980b9);
            color: white;
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(52,152,219,0.3);
        }
        
        .btn-message {
            background: linear-gradient(135deg,#8e44ad,#9b59b6);
            color: white;
        }
        
        .btn-message:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(142,68,173,0.3);
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
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.7);
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: white;
        }
        
        .empty-state-desc {
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        /* Proposal Details Modal */
        .proposal-modal {
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
        
        .proposal-modal.active {
            display: flex;
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(106,92,255,0.1), rgba(168,85,247,0.1));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: rgba(255,255,255,0.7);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .modal-close:hover {
            color: white;
        }
        
        .modal-body {
            color: rgba(255,255,255,0.9);
            line-height: 1.6;
        }
        
        .modal-info {
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .modal-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .modal-info-label {
            font-weight: 600;
            color: #6a5cff;
        }
        
        .modal-info-value {
            color: white;
        }
        
        .proposal-text {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .copy-btn {
            background: linear-gradient(135deg,#3498db,#2980b9);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(52,152,219,0.3);
        }
        
        .copy-btn.copied {
            background: linear-gradient(135deg,#27ae60,#229954);
        }
        
        /* Success Message */
        .success-message {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid rgba(39, 174, 96, 0.3);
            color: #4ade80;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
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
            .dashboard-wrapper {
                grid-template-columns: 1fr;
            }
            
            .dashboard-left {
                padding: 40px 30px;
                min-height: auto;
            }
            
            .dashboard-right {
                padding: 40px 30px;
            }
            
            .nav-grid,
            .quick-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-wrapper {
                margin: 20px;
            }
            
            .dashboard-left,
            .dashboard-right {
                padding: 30px 20px;
            }
            
            .page-title {
                font-size: 32px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .modern-table {
                font-size: 12px;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
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
        <div class="dashboard-wrapper">
            <!-- Left Side - Navigation & User Info -->
            <div class="dashboard-left">
                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>
                
                <div class="nav-section">
                    <div class="dashboard-logo">StudyLance</div>
                    <div class="dashboard-title">Client Control Panel</div>
                    
                    <div class="nav-title">Quick Navigation</div>
                    <div class="nav-grid">
                        <a href="#overview" class="nav-item active">
                            <div class="nav-icon">📊</div>
                            <div class="nav-label">Overview</div>
                        </a>
                        <a href="#jobs" class="nav-item">
                            <div class="nav-icon">💼</div>
                            <div class="nav-label">My Jobs</div>
                        </a>
                        <a href="#proposals" class="nav-item">
                            <div class="nav-icon">📄</div>
                            <div class="nav-label">Proposals</div>
                        </a>
                        <a href="post_job_new.php" class="nav-item">
                            <div class="nav-icon">➕</div>
                            <div class="nav-label">Post Job</div>
                        </a>
                        <a href="messages_new.php" class="nav-item">
                            <div class="nav-icon">💬</div>
                            <div class="nav-label">Messages</div>
                        </a>
                        <a href="settings.php" class="nav-item">
                            <div class="nav-icon">⚙️</div>
                            <div class="nav-label">Settings</div>
                        </a>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <div class="quick-title">Quick Actions</div>
                    <div class="quick-grid">
                        <a href="post_job_new.php" class="quick-item">
                            <div class="quick-icon">📝</div>
                            <div class="quick-label">Post Job</div>
                        </a>
                        <a href="#jobs" class="quick-item">
                            <div class="quick-icon">📋</div>
                            <div class="quick-label">View Jobs</div>
                        </a>
                        <a href="#proposals" class="quick-item">
                            <div class="quick-icon">📄</div>
                            <div class="quick-label">Proposals</div>
                        </a>
                        <a href="messages_new.php" class="quick-item">
                            <div class="quick-icon">💬</div>
                            <div class="quick-label">Messages</div>
                        </a>
                    </div>
                </div>
                
                <div class="user-section">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                    <div class="user-role">Client</div>
                    <div class="user-actions">
                        <a href="messages_new.php" class="btn-user btn-messages">💬 Messages</a>
                        <a href="logout_new.php" class="btn-user btn-logout">Logout</a>
                    </div>
                </div>
            </div>

            <!-- Right Side - Main Content -->
            <div class="dashboard-right">
                <!-- Flash Messages -->
                <?php if ($flash): ?>
                    <div class="flash-message flash-<?php echo $flash['type']; ?>">
                        <span><?php echo $flash['type'] === 'success' ? '✅' : '❌'; ?></span>
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="content-header">
                    <h1 class="page-title">Client Dashboard</h1>
                    <p class="page-subtitle">Manage your jobs and find talented freelancers</p>
                </div>

                <!-- Statistics Overview -->
                <section id="overview" class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">💼</div>
                        <div class="stat-value"><?php echo $total_jobs; ?></div>
                        <div class="stat-label">Total Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-value"><?php echo $open_jobs; ?></div>
                        <div class="stat-label">Open Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📄</div>
                        <div class="stat-value"><?php echo $total_proposals; ?></div>
                        <div class="stat-label">Total Proposals</div>
                    </div>
                </section>

                <!-- Recent Jobs -->
                <section id="jobs" class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">My Recent Jobs</h2>
                        <div class="section-actions">
                            <a href="post_job_new.php" class="btn-action btn-primary">➕ Post New Job</a>
                        </div>
                    </div>
                    <?php if (!empty($jobs)): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Budget</th>
                                        <th>Status</th>
                                        <th>Posted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($job['title']); ?></td>
                                            <td>$<?php echo number_format($job['budget'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo str_replace('_', '-', $job['status']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($job['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="send_proposal_new.php?job_id=<?php echo $job['id']; ?>" class="btn-action btn-primary">
                                                        👁️ View
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📝</div>
                            <div class="empty-state-title">No Jobs Posted Yet</div>
                            <div class="empty-state-desc">Start by posting your first job to find talented freelancers.</div>
                            <a href="post_job_new.php" class="btn-action btn-primary">➕ Post Your First Job</a>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Recent Proposals -->
                <section id="proposals" class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Recent Proposals</h2>
                    </div>
                    <?php if (!empty($proposals)): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Freelancer</th>
                                        <th>Job</th>
                                        <th>Bid</th>
                                        <th>Cover Letter</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proposals as $proposal): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($proposal['freelancer_name']); ?></strong><br>
                                                    <small style="color: rgba(255,255,255,0.6);"><?php echo htmlspecialchars($proposal['freelancer_email']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($proposal['job_title']); ?></td>
                                            <td>$<?php echo number_format($proposal['bid_amount'], 2); ?></td>
                                            <td>
                                                <button class="btn-action btn-info" onclick="viewProposal(<?php echo $proposal['id']; ?>)">
                                                    📄 View
                                                </button>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $proposal['status']; ?>">
                                                    <?php echo ucfirst($proposal['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($proposal['status'] === 'pending'): ?>
                                                        <a href="?action=accept&proposal_id=<?php echo $proposal['id']; ?>" class="btn-action btn-success">
                                                            ✅ Accept
                                                            <span class="email-indicator">📧</span>
                                                        </a>
                                                        <a href="?action=reject&proposal_id=<?php echo $proposal['id']; ?>" class="btn-action btn-danger">
                                                            ❌ Reject
                                                        </a>
                                                    <?php elseif ($proposal['status'] === 'accepted'): ?>
                                                        <button class="btn-action btn-message" onclick="messageFreelancer(<?php echo $proposal['freelancer_id']; ?>, '<?php echo htmlspecialchars($proposal['freelancer_name']); ?>', '<?php echo htmlspecialchars($proposal['job_title']); ?>')">
                                                            💬 Message
                                                        </button>
                                                    <?php else: ?>
                                                        <span style="color: #6a5cff; font-style: italic;"><?php echo ucfirst($proposal['status']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📄</div>
                            <div class="empty-state-title">No Proposals Yet</div>
                            <div class="empty-state-desc">Proposals from freelancers will appear here once they apply to your jobs.</div>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>

    <!-- Proposal Modal -->
    <div id="proposalModal" class="proposal-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Proposal Details</h3>
                <button class="modal-close" onclick="closeProposalModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Navigation active state
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Store proposal data
        let proposalData = {};

        // View proposal details
        function viewProposal(proposalId) {
            // Find proposal data from the table
            const proposals = <?php echo json_encode($proposals); ?>;
            const proposal = proposals.find(p => p.id === proposalId);
            
            if (proposal) {
                proposalData = proposal;
                
                // Build modal content
                const modalBody = document.getElementById('modalBody');
                modalBody.innerHTML = `
                    <div class="modal-info">
                        <div class="modal-info-row">
                            <span class="modal-info-label">Freelancer:</span>
                            <span class="modal-info-value">${proposal.freelancer_name}</span>
                        </div>
                        <div class="modal-info-row">
                            <span class="modal-info-label">Email:</span>
                            <span class="modal-info-value">${proposal.freelancer_email}</span>
                        </div>
                        <div class="modal-info-row">
                            <span class="modal-info-label">Job:</span>
                            <span class="modal-info-value">${proposal.job_title}</span>
                        </div>
                        <div class="modal-info-row">
                            <span class="modal-info-label">Bid Amount:</span>
                            <span class="modal-info-value">$${proposal.bid_amount}</span>
                        </div>
                        <div class="modal-info-row">
                            <span class="modal-info-label">Status:</span>
                            <span class="modal-info-value">${proposal.status}</span>
                        </div>
                    </div>
                    
                    <div class="proposal-text" id="proposalText">
                        ${proposal.proposal_text || proposal.cover_letter || 'No cover letter available'}
                    </div>
                    
                    <div class="modal-actions">
                        <button class="copy-btn" onclick="copyProposalText()">
                            📋 Copy Proposal Text
                        </button>
                        <button class="copy-btn" onclick="copyEmail()">
                            📧 Copy Email
                        </button>
                        ${proposal.status === 'accepted' ? `
                            <button class="copy-btn" onclick="messageFreelancer(${proposal.freelancer_id}, '${proposal.freelancer_name}', '${proposal.job_title}')">
                                💬 Message Freelancer
                            </button>
                        ` : ''}
                        <button class="copy-btn" onclick="closeProposalModal()">
                            Close
                        </button>
                    </div>
                `;
                
                // Show modal
                document.getElementById('proposalModal').classList.add('active');
            }
        }

        // Close modal
        function closeProposalModal() {
            document.getElementById('proposalModal').classList.remove('active');
        }

        // Copy proposal text
        function copyProposalText() {
            const text = proposalData.proposal_text || proposalData.cover_letter || '';
            if (text) {
                navigator.clipboard.writeText(text).then(() => {
                    // Change button text temporarily
                    event.target.textContent = '✅ Copied!';
                    event.target.classList.add('copied');
                    
                    setTimeout(() => {
                        event.target.textContent = '📋 Copy Proposal Text';
                        event.target.classList.remove('copied');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            }
        }

        // Copy email
        function copyEmail() {
            const email = proposalData.freelancer_email || '';
            if (email) {
                navigator.clipboard.writeText(email).then(() => {
                    // Change button text temporarily
                    event.target.textContent = '✅ Copied!';
                    event.target.classList.add('copied');
                    
                    setTimeout(() => {
                        event.target.textContent = '📧 Copy Email';
                        event.target.classList.remove('copied');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy email: ', err);
                });
            }
        }

        // Message freelancer
        function messageFreelancer(freelancerId, freelancerName, jobTitle) {
            // Redirect to messages page with the freelancer
            window.location.href = `messages_new.php?user=${freelancerId}`;
        }

        // Close modal when clicking outside
        document.getElementById('proposalModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeProposalModal();
            }
        });
    </script>
</body>
</html>
