<?php
/**
 * StudyLance Freelancer Dashboard
 * Job browsing and proposal management for freelancers
 */

require_once 'config.php';

// Require freelancer role
requireRole('freelancer');

// Handle proposal submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_proposal'])) {
    $jobId = (int)$_POST['job_id'];
    redirect('send_proposal_new.php?job_id=' . $jobId);
}

// Get dashboard data
try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    // Freelancer stats
    $stmt = $db->query("SELECT COUNT(*) as count FROM proposals WHERE freelancer_id = ?", [$userId]);
    $total_proposals = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM proposals WHERE freelancer_id = ? AND status = 'accepted'", [$userId]);
    $accepted_proposals = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'open'");
    $available_jobs = $stmt->fetch()['count'];
    
    // Available jobs
    $stmt = $db->query("SELECT * FROM jobs WHERE status = 'open' ORDER BY created_at DESC LIMIT 5");
    $jobs = $stmt->fetchAll();
    
    // My proposals
    $stmt = $db->query("
        SELECT p.*, j.title as job_title, j.budget, u.name as client_name 
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        JOIN users u ON j.client_id = u.id 
        WHERE p.freelancer_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ", [$userId]);
    $proposals = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $total_proposals = $accepted_proposals = $available_jobs = 0;
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
    <title>Freelancer Dashboard - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modern Asymmetric Freelancer Dashboard Styles */
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
        
        .btn-messages {
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
        
        /* Job Cards */
        .job-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .job-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            background: rgba(106,92,255,0.1);
            border-color: rgba(106,92,255,0.3);
        }
        
        .job-title {
            font-size: 18px;
            font-weight: 600;
            color: white;
            margin-bottom: 10px;
        }
        
        .job-budget {
            font-size: 24px;
            font-weight: 800;
            color: #6a5cff;
            margin-bottom: 10px;
        }
        
        .job-client {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .job-date {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            margin-bottom: 15px;
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
            
            .job-grid {
                grid-template-columns: 1fr;
            }
            
            .modern-table {
                font-size: 12px;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 10px;
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
                    <div class="dashboard-title">Freelancer Hub</div>
                    
                    <div class="nav-title">Quick Navigation</div>
                    <div class="nav-grid">
                        <a href="#overview" class="nav-item active">
                            <div class="nav-icon">📊</div>
                            <div class="nav-label">Overview</div>
                        </a>
                        <a href="#jobs" class="nav-item">
                            <div class="nav-icon">💼</div>
                            <div class="nav-label">Available Jobs</div>
                        </a>
                        <a href="#proposals" class="nav-item">
                            <div class="nav-icon">📄</div>
                            <div class="nav-label">My Proposals</div>
                        </a>
                        <a href="send_proposal_new.php" class="nav-item">
                            <div class="nav-icon">🔍</div>
                            <div class="nav-label">Browse All</div>
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
                        <a href="send_proposal_new.php" class="quick-item">
                            <div class="quick-icon">🔍</div>
                            <div class="quick-label">Browse Jobs</div>
                        </a>
                        <a href="#jobs" class="quick-item">
                            <div class="quick-icon">💼</div>
                            <div class="quick-label">Latest Jobs</div>
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
                    <div class="user-role">Freelancer</div>
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
                    <h1 class="page-title">Freelancer Dashboard</h1>
                    <p class="page-subtitle">Find jobs and grow your freelancing career</p>
                </div>

                <!-- Statistics Overview -->
                <section id="overview" class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📄</div>
                        <div class="stat-value"><?php echo $total_proposals; ?></div>
                        <div class="stat-label">Total Proposals</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-value"><?php echo $accepted_proposals; ?></div>
                        <div class="stat-label">Accepted Proposals</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💼</div>
                        <div class="stat-value"><?php echo $available_jobs; ?></div>
                        <div class="stat-label">Available Jobs</div>
                    </div>
                </section>

                <!-- Available Jobs -->
                <section id="jobs" class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Latest Available Jobs</h2>
                        <div class="section-actions">
                            <a href="send_proposal_new.php" class="btn-action btn-primary">🔍 Browse All Jobs</a>
                        </div>
                    </div>
                    <?php if (!empty($jobs)): ?>
                        <div class="job-grid">
                            <?php foreach ($jobs as $job): ?>
                                <div class="job-card">
                                    <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <div class="job-budget">$<?php echo number_format($job['budget'], 2); ?></div>
                                    <div class="job-client">👤 Posted by Client</div>
                                    <div class="job-date">📅 <?php echo date('M j, Y', strtotime($job['created_at'])); ?></div>
                                    <form method="POST" action="" style="display: block;">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <input type="hidden" name="submit_proposal" value="1">
                                        <button type="submit" class="btn-action btn-primary" style="width: 100%;">
                                            🚀 Apply Now
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">💼</div>
                            <div class="empty-state-title">No Available Jobs</div>
                            <div class="empty-state-desc">Check back later for new job opportunities.</div>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- My Proposals -->
                <section id="proposals" class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">My Recent Proposals</h2>
                    </div>
                    <?php if (!empty($proposals)): ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Job</th>
                                        <th>Client</th>
                                        <th>My Bid</th>
                                        <th>Status</th>
                                        <th>Posted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proposals as $proposal): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($proposal['job_title']); ?></td>
                                            <td><?php echo htmlspecialchars($proposal['client_name']); ?></td>
                                            <td>$<?php echo number_format($proposal['bid_amount'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $proposal['status']; ?>">
                                                    <?php echo ucfirst($proposal['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($proposal['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📄</div>
                            <div class="empty-state-title">No Proposals Yet</div>
                            <div class="empty-state-desc">Start applying to jobs to see your proposals here.</div>
                            <a href="send_proposal_new.php" class="btn-action btn-primary">🔍 Browse Jobs</a>
                        </div>
                    <?php endif; ?>
                </section>
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
    </script>
</body>
</html>
