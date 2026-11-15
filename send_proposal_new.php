<?php
/**
 * StudyLance Send Proposal Page - FINAL FIX
 * Uses BOTH proposal_text AND cover_letter columns
 */

require_once 'config.php';

// Require freelancer role
requireRole('freelancer');

// Get job details if specified
$jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;
$job = null;

if ($jobId) {
    try {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM jobs WHERE id = ? AND status = 'open'", [$jobId]);
        $job = $stmt->fetch();
        
        if (!$job) {
            redirect('freelancer_dashboard_new.php', 'Job not found or no longer available', 'error');
        }
    } catch (Exception $e) {
        error_log("Job fetch error: " . $e->getMessage());
        redirect('freelancer_dashboard_new.php', 'Error loading job details', 'error');
    }
}

// Handle proposal submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_proposal'])) {
    $jobId = (int)$_POST['job_id'];
    $bidAmount = filter_var($_POST['bid_amount'] ?? '', FILTER_SANITIZE_NUMBER_FLOAT);
    $coverLetter = sanitize($_POST['cover_letter'] ?? '');
    $estimatedTime = sanitize($_POST['estimated_time'] ?? '');
    
    $errors = [];
    
    // Basic validation
    if (empty($bidAmount) || $bidAmount <= 0) {
        $errors[] = "Please enter a valid bid amount";
    }
    
    if (empty($coverLetter)) {
        $errors[] = "Cover letter is required";
    }
    
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Check if already applied
            $stmt = $db->query("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?", [$jobId, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "You have already applied to this job";
            } else {
                // Insert proposal - proposal_text stores the cover letter content
                $stmt = $db->query(
                    "INSERT INTO proposals (job_id, freelancer_id, bid_amount, proposal_text, estimated_time, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())",
                    [$jobId, $_SESSION['user_id'], $bidAmount, $coverLetter, $estimatedTime]
                );
                
                if ($stmt->rowCount() > 0) {
                    redirect('freelancer_dashboard_new.php', 'Proposal submitted successfully!', 'success');
                } else {
                    $errors[] = "Failed to submit proposal. Please try again.";
                }
            }
            
        } catch (Exception $e) {
            error_log("Proposal submission error: " . $e->getMessage());
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Get available jobs for browsing
try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT * FROM jobs WHERE status = 'open' ORDER BY created_at DESC LIMIT 10");
    $jobs = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Jobs fetch error: " . $e->getMessage());
    $jobs = [];
}

// Get flash message
$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Proposal - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modern Send Proposal Page Styles */
        .proposal-container {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        .proposal-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Left Side - Job List */
        .proposal-left {
            background: linear-gradient(135deg, rgba(106,92,255,0.15), rgba(168,85,247,0.15));
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .proposal-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(106,92,255,0.1), transparent 70%);
            animation: float 20s ease-in-out infinite;
        }
        
        .proposal-logo {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .proposal-title {
            font-size: 18px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }
        
        .jobs-section {
            position: relative;
            z-index: 1;
            flex: 1;
            overflow-y: auto;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
        }
        
        .job-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .job-item {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
        }
        
        .job-item:hover,
        .job-item.active {
            background: rgba(106,92,255,0.2);
            transform: translateY(-3px);
            border-color: rgba(106,92,255,0.3);
            box-shadow: 0 15px 30px rgba(106,92,255,0.2);
        }
        
        .job-item-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .job-item-budget {
            font-size: 18px;
            font-weight: 700;
            color: #6a5cff;
            margin-bottom: 8px;
        }
        
        .job-item-client {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 8px;
        }
        
        .job-item-date {
            font-size: 11px;
            color: rgba(255,255,255,0.5);
        }
        
        .user-section {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 25px;
            margin-top: 30px;
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
        
        /* Right Side - Proposal Form */
        .proposal-right {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-y: auto;
        }
        
        .job-details {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .job-details-title {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 15px;
        }
        
        .job-details-budget {
            font-size: 32px;
            font-weight: 800;
            color: #6a5cff;
            margin-bottom: 15px;
        }
        
        .job-details-description {
            color: rgba(255,255,255,0.8);
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .job-details-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: rgba(255,255,255,0.6);
        }
        
        .proposal-form {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 30px;
        }
        
        .form-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.8);
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-input,
        .form-textarea {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            font-family: 'Inter', sans-serif;
        }
        
        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #6a5cff;
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 20px rgba(106,92,255,0.3);
            transform: translateY(-2px);
        }
        
        .form-input::placeholder,
        .form-textarea::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .submit-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(106,92,255,0.3);
        }
        
        .submit-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(106,92,255,0.4);
        }
        
        /* Empty States */
        .empty-jobs {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.7);
        }
        
        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: white;
        }
        
        .empty-desc {
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .no-job-selected {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.7);
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
        
        .error-messages {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
        }
        
        .error-item {
            color: #ff6b6b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .error-item:last-child {
            margin-bottom: 0;
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
            .proposal-wrapper {
                grid-template-columns: 1fr;
            }
            
            .proposal-left {
                padding: 40px 30px;
                min-height: auto;
            }
            
            .proposal-right {
                padding: 40px 30px;
            }
        }
        
        @media (max-width: 768px) {
            .proposal-wrapper {
                margin: 20px;
            }
            
            .proposal-left,
            .proposal-right {
                padding: 30px 20px;
            }
            
            .job-details-title {
                font-size: 24px;
            }
            
            .job-details-budget {
                font-size: 28px;
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

    <div class="proposal-container">
        <div class="proposal-wrapper">
            <!-- Left Side - Job List -->
            <div class="proposal-left">
                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>
                
                <div class="proposal-logo">StudyLance</div>
                <div class="proposal-title">Browse available jobs and submit proposals</div>
                
                <div class="jobs-section">
                    <h3 class="section-title">💼 Available Jobs</h3>
                    <?php if (!empty($jobs)): ?>
                        <div class="job-list">
                            <?php foreach ($jobs as $job_item): ?>
                                <a href="send_proposal_new.php?job_id=<?php echo $job_item['id']; ?>" 
                                   class="job-item <?php echo ($job && $job['id'] == $job_item['id']) ? 'active' : ''; ?>">
                                    <div class="job-item-title"><?php echo htmlspecialchars($job_item['title']); ?></div>
                                    <div class="job-item-budget">$<?php echo number_format($job_item['budget'], 2); ?></div>
                                    <div class="job-item-client">👤 Posted by Client</div>
                                    <div class="job-item-date">📅 <?php echo date('M j, Y', strtotime($job_item['created_at'])); ?></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-jobs">
                            <div class="empty-icon">💼</div>
                            <div class="empty-title">No available jobs</div>
                            <div class="empty-desc">Check back later for new opportunities</div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="user-section">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                    <div class="user-role">Freelancer</div>
                    <div class="user-actions">
                        <a href="freelancer_dashboard_new.php" class="btn-user btn-dashboard">Dashboard</a>
                        <a href="logout_new.php" class="btn-user btn-logout">Logout</a>
                    </div>
                </div>
            </div>

            <!-- Right Side - Proposal Form -->
            <div class="proposal-right">
                <!-- Flash Messages -->
                <?php if ($flash): ?>
                    <div class="flash-message flash-<?php echo $flash['type']; ?>">
                        <span><?php echo $flash['type'] === 'success' ? '✅' : '❌'; ?></span>
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>

                <?php if ($job): ?>
                    <!-- Job Details -->
                    <div class="job-details">
                        <h2 class="job-details-title"><?php echo htmlspecialchars($job['title']); ?></h2>
                        <div class="job-details-budget">$<?php echo number_format($job['budget'], 2); ?></div>
                        <div class="job-details-description"><?php echo nl2br(htmlspecialchars($job['description'])); ?></div>
                        <div class="job-details-meta">
                            <span>📅 Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?></span>
                        </div>
                    </div>

                    <!-- Proposal Form -->
                    <form method="POST" action="" class="proposal-form">
                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                        
                        <h3 class="form-title">📝 Submit Your Proposal</h3>
                        
                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                            <div class="error-messages">
                                <?php foreach ($errors as $error): ?>
                                    <div class="error-item">
                                        <span>⚠️</span>
                                        <?php echo $error; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label class="form-label">Your Bid Amount ($)</label>
                            <input type="number" 
                                   name="bid_amount" 
                                   value="<?php echo $_POST['bid_amount'] ?? ''; ?>"
                                   placeholder="Enter your bid amount"
                                   class="form-input"
                                   min="5"
                                   max="10000"
                                   step="0.01"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Cover Letter</label>
                            <textarea name="cover_letter" 
                                      placeholder="Introduce yourself and explain why you're the perfect fit for this job..."
                                      class="form-textarea"
                                      required><?php echo $_POST['cover_letter'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Estimated Time (Optional)</label>
                            <input type="text" 
                                   name="estimated_time" 
                                   value="<?php echo $_POST['estimated_time'] ?? ''; ?>"
                                   placeholder="e.g., 2 weeks, 5 days, 1 month"
                                   class="form-input">
                        </div>
                        
                        <button type="submit" name="submit_proposal" class="submit-button">Submit Proposal</button>
                    </form>
                <?php else: ?>
                    <div class="no-job-selected">
                        <div class="empty-icon">💼</div>
                        <div class="empty-title">Select a job to apply</div>
                        <div class="empty-desc">Choose a job from the left to submit your proposal</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
