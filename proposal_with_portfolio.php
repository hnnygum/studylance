<?php
/**
 * StudyLance Proposal Submission with Portfolio URL
 * Handle freelancer job applications with portfolio link
 */

require_once 'config.php';

// Check if user is logged in as freelancer
if (!isLoggedIn()) {
    redirect('login_new.php', 'Please login to submit a proposal', 'error');
}

// Check if user is freelancer
if ($_SESSION['role'] !== 'freelancer') {
    redirect('freelancer_dashboard_new.php', 'Access denied. Freelancer access only.', 'error');
}

$job_id = $_GET['job_id'] ?? 0;
$freelancer_id = $_SESSION['user_id'];

// Validate job ID
if (!is_numeric($job_id) || $job_id <= 0) {
    redirect('freelancer_dashboard_new.php', 'Invalid job ID', 'error');
}

// Get job details
try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT * FROM jobs WHERE id = ?", [$job_id]);
    $job = $stmt->fetch();
    
    if (!$job) {
        redirect('freelancer_dashboard_new.php', 'Job not found', 'error');
    }
    
    // Check if already applied
    $stmt = $db->query("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?", [$job_id, $freelancer_id]);
    if ($stmt->fetch()) {
        redirect('freelancer_dashboard_new.php', 'You have already applied to this job', 'error');
    }
    
} catch (Exception $e) {
    error_log("Job fetch error: " . $e->getMessage());
    redirect('freelancer_dashboard_new.php', 'Database error', 'error');
}

// Handle proposal submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_proposal'])) {
    $bid_amount = $_POST['bid_amount'] ?? '';
    $portfolio_url = $_POST['portfolio_url'] ?? '';
    $estimated_time = $_POST['estimated_time'] ?? '';
    $proposal_text = $_POST['proposal_text'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($bid_amount) || !is_numeric($bid_amount) || $bid_amount <= 0) {
        $errors[] = "Please enter a valid bid amount";
    }
    
    if (empty($portfolio_url)) {
        $errors[] = "Portfolio URL is required";
    } elseif (!filter_var($portfolio_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid portfolio URL";
    }
    
    if (empty($estimated_time)) {
        $errors[] = "Estimated time is required";
    }
    
    if (empty($proposal_text)) {
        $errors[] = "Proposal details are required";
    } elseif (strlen($proposal_text) < 50) {
        $errors[] = "Proposal must be at least 50 characters long";
    }
    
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Insert proposal with portfolio URL
            $stmt = $db->query(
                "INSERT INTO proposals (job_id, freelancer_id, bid_amount, portfolio_url, estimated_time, proposal_text, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)",
                [$job_id, $freelancer_id, $bid_amount, $portfolio_url, $estimated_time, $proposal_text, date('Y-m-d H:i:s')]
            );
            
            if ($stmt->rowCount() > 0) {
                redirect('freelancer_dashboard_new.php', 'Proposal submitted successfully!', 'success');
            } else {
                $errors[] = "Failed to submit proposal. Please try again.";
            }
            
        } catch (Exception $e) {
            error_log("Proposal submission error: " . $e->getMessage());
            $errors[] = "Database error occurred. Please try again.";
        }
    }
}

// Get flash message
$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Proposal - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .proposal-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 40px;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
        }
        
        .proposal-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .proposal-header h1 {
            color: white;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .proposal-header p {
            color: rgba(255,255,255,0.7);
            font-size: 16px;
        }
        
        .job-details {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .job-title {
            color: #6a5cff;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .job-budget {
            font-size: 20px;
            color: #27ae60;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .job-description {
            color: rgba(255,255,255,0.8);
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #6a5cff;
            background: rgba(255,255,255,0.08);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .portfolio-input-group {
            position: relative;
        }
        
        .portfolio-input-group input {
            padding-right: 120px;
        }
        
        .portfolio-preview {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(106,92,255,0.2);
            border: 1px solid rgba(106,92,255,0.3);
            color: #6a5cff;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .portfolio-preview:hover {
            background: rgba(106,92,255,0.3);
            color: white;
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106,92,255,0.3);
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
        }
        
        .success-message {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid rgba(39, 174, 96, 0.3);
            color: #27ae60;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .portfolio-notice {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            color: #3498db;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .character-count {
            text-align: right;
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            margin-top: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-wrapper">
            <div class="proposal-container">
                <div class="proposal-header">
                    <h1>Submit Proposal</h1>
                    <p>Apply for this job with your portfolio and proposal</p>
                </div>
                
                <!-- Flash Messages -->
                <?php if ($flash): ?>
                    <div class="flash-message flash-<?php echo $flash['type']; ?>">
                        <span><?php echo $flash['type'] === 'success' ? '✅' : '❌'; ?></span>
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Job Details -->
                <div class="job-details">
                    <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                    <div class="job-budget">Budget: ₹<?php echo number_format($job['budget'], 2); ?></div>
                    <div class="job-description"><?php echo nl2br(htmlspecialchars($job['description'])); ?></div>
                    <div style="color: rgba(255,255,255,0.6); font-size: 14px;">
                        Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                    </div>
                </div>
                
                <!-- Portfolio Notice -->
                <div class="portfolio-notice">
                    📋 <strong>Portfolio URL Required:</strong> Please provide a link to your online portfolio (GitHub, Behance, personal website, etc.) to showcase your work.
                </div>
                
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <div>❌ <?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Proposal Form -->
                <form method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bid_amount">Your Bid Amount (₹) *</label>
                            <input type="number" name="bid_amount" id="bid_amount" step="0.01" min="0" required
                                   value="<?php echo htmlspecialchars($_POST['bid_amount'] ?? ''); ?>"
                                   placeholder="Enter your bid amount">
                        </div>
                        
                        <div class="form-group">
                            <label for="estimated_time">Estimated Time *</label>
                            <input type="text" name="estimated_time" id="estimated_time" required
                                   value="<?php echo htmlspecialchars($_POST['estimated_time'] ?? ''); ?>"
                                   placeholder="e.g., 3 days, 1 week, 2 months">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="portfolio_url">Portfolio URL *</label>
                        <div class="portfolio-input-group">
                            <input type="url" name="portfolio_url" id="portfolio_url" required
                                   value="<?php echo htmlspecialchars($_POST['portfolio_url'] ?? ''); ?>"
                                   placeholder="https://your-portfolio.com">
                            <a href="#" class="portfolio-preview" id="portfolioPreview" target="_blank" style="display: none;">🔗 Preview</a>
                        </div>
                        <small style="color: rgba(255,255,255,0.6);">Link to your GitHub, Behance, personal website, or other portfolio</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="proposal_text">Proposal Details *</label>
                        <textarea name="proposal_text" id="proposal_text" required
                                  placeholder="Describe why you're the best fit for this job. Include your relevant experience, approach, and what makes you the ideal candidate..."><?php echo htmlspecialchars($_POST['proposal_text'] ?? ''); ?></textarea>
                        <div class="character-count">
                            <span id="charCount">0</span> / 1000 characters
                        </div>
                    </div>
                    
                    <button type="submit" name="submit_proposal" class="btn-submit">
                        📤 Submit Proposal
                    </button>
                    
                    <a href="freelancer_dashboard_new.php" class="btn-cancel">
                        ← Cancel
                    </a>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Character counter
        const proposalText = document.getElementById('proposal_text');
        const charCount = document.getElementById('charCount');
        
        if (proposalText && charCount) {
            proposalText.addEventListener('input', function() {
                const count = this.value.length;
                charCount.textContent = count;
                
                if (count > 1000) {
                    charCount.style.color = '#e74c3c';
                } else if (count > 800) {
                    charCount.style.color = '#f39c12';
                } else {
                    charCount.style.color = 'rgba(255,255,255,0.5)';
                }
            });
            
            // Initial count
            charCount.textContent = proposalText.value.length;
        }
        
        // Portfolio URL preview
        const portfolioUrl = document.getElementById('portfolio_url');
        const portfolioPreview = document.getElementById('portfolioPreview');
        
        if (portfolioUrl && portfolioPreview) {
            portfolioUrl.addEventListener('input', function() {
                const url = this.value.trim();
                
                if (url && isValidUrl(url)) {
                    portfolioPreview.href = url;
                    portfolioPreview.style.display = 'inline-block';
                } else {
                    portfolioPreview.style.display = 'none';
                }
            });
            
            // Check initial value
            if (portfolioUrl.value.trim()) {
                const url = portfolioUrl.value.trim();
                if (isValidUrl(url)) {
                    portfolioPreview.href = url;
                    portfolioPreview.style.display = 'inline-block';
                }
            }
        }
        
        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }
        
        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const portfolioUrl = document.getElementById('portfolio_url').value.trim();
                
                if (!portfolioUrl) {
                    e.preventDefault();
                    alert('Portfolio URL is required');
                    return false;
                }
                
                if (!isValidUrl(portfolioUrl)) {
                    e.preventDefault();
                    alert('Please enter a valid portfolio URL');
                    return false;
                }
            });
        }
    </script>
</body>
</html>
