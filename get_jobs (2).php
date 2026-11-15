<?php
session_start();

// Check if user is logged in and is freelancer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: login_new.php");
    exit();
}

include 'database.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'open';
$budget_min = $_GET['budget_min'] ?? '';
$budget_max = $_GET['budget_max'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ["j.status = 'open'"];
$params = [];
$types = "";

if (!empty($budget_min)) {
    $where_conditions[] = "j.budget >= ?";
    $params[] = $budget_min;
    $types .= "d";
}

if (!empty($budget_max)) {
    $where_conditions[] = "j.budget <= ?";
    $params[] = $budget_max;
    $types .= "d";
}

if (!empty($search)) {
    $where_conditions[] = "(j.title LIKE ? OR j.description LIKE ?)";
    $search_term = "%" . $search . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

$sql = "SELECT j.*, u.name as client_name, u.email as client_email,
        (SELECT COUNT(*) FROM proposals WHERE job_id = j.id) as proposal_count
        FROM jobs j 
        JOIN users u ON j.client_id = u.id 
        $where_clause
        ORDER BY j.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
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
    <title>Browse Jobs - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modern Job Browsing Styles - RUPEES */
        .browse-container {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        .browse-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Left Side - Search & Filters */
        .browse-left {
            background: linear-gradient(135deg, rgba(106,92,255,0.15), rgba(168,85,247,0.15));
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            overflow-y: auto;
        }
        
        .browse-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(106,92,255,0.1), transparent 70%);
            animation: float 20s ease-in-out infinite;
        }
        
        .browse-logo {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .browse-title {
            font-size: 18px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }
        
        .search-section {
            position: relative;
            z-index: 1;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-bottom: 25px;
        }
        
        .search-form {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            color: white;
            font-weight: 500;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            -webkit-app-region: no-drag;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #6a5cff;
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 20px rgba(106,92,255,0.3);
        }
        
        .form-input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .filter-section {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .btn-search {
            width: 100%;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            -webkit-app-region: no-drag;
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(106,92,255,0.4);
        }
        
        .btn-clear {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            background: transparent;
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            -webkit-app-region: no-drag;
            margin-top: 10px;
        }
        
        .btn-clear:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.3);
        }
        
        /* Right Side - Results */
        .browse-right {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-y: auto;
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .results-title {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }
        
        .results-count {
            color: rgba(255,255,255,0.7);
            font-size: 16px;
        }
        
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .job-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(106,92,255,0.2);
            border-color: rgba(106,92,255,0.3);
        }
        
        .job-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 15px;
            line-height: 1.3;
        }
        
        .job-budget {
            font-size: 24px;
            font-weight: 800;
            color: #6a5cff;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .budget-symbol {
            font-size: 18px;
            opacity: 0.8;
        }
        
        .job-description {
            color: rgba(255,255,255,0.8);
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 15px;
        }
        
        .job-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 0;
            border-top: 1px solid rgba(255,255,255,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .client-info {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
        }
        
        .proposal-count {
            background: rgba(106,92,255,0.2);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .job-date {
            color: rgba(255,255,255,0.6);
            font-size: 13px;
        }
        
        .btn-apply {
            padding: 12px 25px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            -webkit-app-region: no-drag;
        }
        
        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(106,92,255,0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            color: rgba(255,255,255,0.7);
        }
        
        .empty-icon {
            font-size: 80px;
            margin-bottom: 30px;
            opacity: 0.5;
        }
        
        .empty-title {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 15px;
        }
        
        .empty-desc {
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn-back {
            padding: 15px 30px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            background: transparent;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            -webkit-app-region: no-drag;
        }
        
        .btn-back:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.3);
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
            .browse-wrapper {
                grid-template-columns: 1fr;
            }
            
            .browse-left {
                padding: 40px 30px;
                min-height: auto;
            }
            
            .browse-right {
                padding: 40px 30px;
            }
        }
        
        @media (max-width: 768px) {
            .browse-wrapper {
                margin: 20px;
            }
            
            .browse-left,
            .browse-right {
                padding: 30px 20px;
            }
            
            .results-title {
                font-size: 24px;
            }
            
            .jobs-grid {
                grid-template-columns: 1fr;
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

    <div class="browse-container">
        <div class="browse-wrapper">
            <!-- Left Side - Search & Filters -->
            <div class="browse-left">
                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>
                
                <div class="search-section">
                    <div class="browse-logo">StudyLance</div>
                    <div class="browse-title">Find Your Perfect Job</div>
                    
                    <div class="section-title">🔍 Search Jobs</div>
                    <form method="GET" action="" class="search-form">
                        <div class="form-group">
                            <label class="form-label" for="search">Keywords</label>
                            <input type="text" 
                                   id="search" 
                                   name="search" 
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Search by title or description...">
                        </div>
                        
                        <button type="submit" class="btn-search">
                            🔍 Search Jobs
                        </button>
                    </form>
                </div>
                
                <div class="filter-section">
                    <div class="section-title">💰 Budget Range</div>
                    <form method="GET" action="">
                        <div class="form-group">
                            <label class="form-label" for="budget_min">Minimum (₹)</label>
                            <input type="number" 
                                   id="budget_min" 
                                   name="budget_min" 
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($budget_min); ?>"
                                   step="0.01" 
                                   min="0" 
                                   placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="budget_max">Maximum (₹)</label>
                            <input type="number" 
                                   id="budget_max" 
                                   name="budget_max" 
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($budget_max); ?>"
                                   step="0.01" 
                                   min="0" 
                                   placeholder="50000.00">
                        </div>
                        
                        <button type="submit" class="btn-search">
                            🎯 Apply Filters
                        </button>
                        
                        <a href="get_jobs.php" class="btn-clear">
                            ✖ Clear Filters
                        </a>
                    </form>
                </div>
            </div>

            <!-- Right Side - Results -->
            <div class="browse-right">
                <div class="results-header">
                    <h1 class="results-title">Available Jobs</h1>
                    <div class="results-count"><?php echo count($jobs); ?> jobs found</div>
                </div>

                <?php if (empty($jobs)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">💼</div>
                        <div class="empty-title">No Jobs Found</div>
                        <div class="empty-desc">
                            Try adjusting your search criteria or check back later for new opportunities.
                        </div>
                        <a href="get_jobs.php" class="btn-back">View All Jobs</a>
                    </div>
                <?php else: ?>
                    <div class="jobs-grid">
                        <?php foreach ($jobs as $job): ?>
                            <div class="job-card">
                                <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                <div class="job-budget">
                                    <span class="budget-symbol">₹</span>
                                    <?php echo number_format($job['budget'], 2); ?>
                                </div>
                                <div class="job-description">
                                    <?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 300))); ?>
                                    <?php if (strlen($job['description']) > 300): ?>
                                        <strong>...</strong>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="job-meta">
                                    <div class="client-info">
                                        👤 <?php echo htmlspecialchars($job['client_name']); ?>
                                    </div>
                                    <div class="proposal-count">
                                        <?php echo $job['proposal_count']; ?> proposal<?php echo $job['proposal_count'] != 1 ? 's' : ''; ?>
                                    </div>
                                </div>
                                
                                <div class="job-footer">
                                    <div class="job-date">
                                        📅 <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                    </div>
                                    <button class="btn-apply" 
                                            onclick="showProposalForm(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars($job['title']); ?>', <?php echo $job['budget']; ?>)">
                                        🚀 Apply Now
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Proposal Modal -->
    <div id="proposalModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Send Proposal</h3>
                <span class="close" onclick="closeProposalForm()">&times;</span>
            </div>
            <form id="proposalForm" action="send_proposal_new.php" method="POST">
                <input type="hidden" id="job_id" name="job_id">
                <input type="hidden" id="job_title" name="job_title">
                <input type="hidden" id="job_budget" name="job_budget">
                
                <div class="job-info">
                    <h4 id="modalJobTitle"></h4>
                    <div class="job-budget-display">
                        <span class="budget-symbol">₹</span>
                        <span id="modalJobBudget"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="portfolio_url">Portfolio/Resume URL *</label>
                    <div class="portfolio-input-wrapper">
                        <input type="url" 
                               id="portfolio_url" 
                               name="portfolio_url" 
                               class="form-input"
                               required
                               placeholder="https://github.com/username or https://yourportfolio.com">
                        <button type="button" class="btn-preview" id="previewBtn" onclick="previewPortfolio()" style="display: none;">🔗 Preview</button>
                    </div>
                    <small style="color: rgba(255,255,255,0.6); font-size: 12px;">Link to your GitHub, Behance, personal website, or resume</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="proposal_text">Additional Details</label>
                    <textarea id="proposal_text" 
                              name="proposal_text" 
                              rows="4" 
                              class="form-input"
                              placeholder="Briefly describe why you're the best fit for this job..."></textarea>
                    <small style="color: rgba(255,255,255,0.6); font-size: 12px;">Optional: Additional information about your proposal</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="bid_amount">Your Bid (₹) *</label>
                    <input type="number" 
                           id="bid_amount" 
                           name="bid_amount" 
                           class="form-input"
                           step="0.01" 
                           min="1" 
                           required
                           placeholder="Enter your bid amount">
                    <small style="color: rgba(255,255,255,0.6); font-size: 12px;">Amount you'll charge for this job</small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-back" onclick="closeProposalForm()">Cancel</button>
                    <button type="submit" class="btn-search">Send Proposal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showProposalForm(jobId, jobTitle, jobBudget) {
            document.getElementById('job_id').value = jobId;
            document.getElementById('job_title').value = jobTitle;
            document.getElementById('job_budget').value = jobBudget;
            document.getElementById('modalJobTitle').textContent = jobTitle;
            document.getElementById('modalJobBudget').textContent = jobBudget.toFixed(2);
            document.getElementById('proposalModal').style.display = 'block';
        }

        function closeProposalForm() {
            document.getElementById('proposalModal').style.display = 'none';
            document.getElementById('proposalForm').reset();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('proposalModal');
            if (event.target == modal) {
                closeProposalForm();
            }
        }

        // Form validation
        document.getElementById('proposalForm').addEventListener('submit', function(e) {
            const portfolioUrl = document.getElementById('portfolio_url').value;
            const bidAmount = document.getElementById('bid_amount').value;
            
            if (!portfolioUrl) {
                alert('Portfolio/Resume URL is required');
                e.preventDefault();
                return false;
            }
            
            if (!isValidUrl(portfolioUrl)) {
                alert('Please enter a valid portfolio URL');
                e.preventDefault();
                return false;
            }
            
            if (!bidAmount || bidAmount <= 0) {
                alert('Please enter a valid bid amount');
                e.preventDefault();
                return false;
            }
        });

        // Portfolio URL validation and preview
        document.getElementById('portfolio_url').addEventListener('input', function() {
            const url = this.value.trim();
            const previewBtn = document.getElementById('previewBtn');
            
            if (url && isValidUrl(url)) {
                previewBtn.style.display = 'inline-block';
                previewBtn.onclick = function() {
                    window.open(url, '_blank');
                };
            } else {
                previewBtn.style.display = 'none';
            }
        });

        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }

        function previewPortfolio() {
            const url = document.getElementById('portfolio_url').value.trim();
            if (url && isValidUrl(url)) {
                window.open(url, '_blank');
            }
        }
    </script>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .modal-header h3 {
            color: white;
            margin: 0;
            font-size: 18px;
        }
        
        .close {
            color: rgba(255,255,255,0.7);
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .close:hover {
            color: white;
        }
        
        .job-info {
            background: rgba(255,255,255,0.05);
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .job-info h4 {
            color: #6a5cff;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .job-budget-display {
            color: #27ae60;
            font-size: 24px;
            font-weight: bold;
        }
        
        .budget-symbol {
            font-size: 18px;
            margin-right: 2px;
        }
        
        .form-group {
            padding: 20px 25px;
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            color: rgba(255,255,255,0.9);
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #6a5cff;
            background: rgba(255,255,255,0.08);
        }
        
        .form-input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .portfolio-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .portfolio-input-wrapper .form-input {
            padding-right: 100px;
        }
        
        .btn-preview {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(106,92,255,0.2);
            border: 1px solid rgba(106,92,255,0.3);
            color: #6a5cff;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-preview:hover {
            background: rgba(106,92,255,0.3);
            color: white;
        }
        
        textarea.form-input {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            padding: 20px 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .btn-back {
            flex: 1;
            padding: 12px 20px;
            background: transparent;
            color: rgba(255,255,255,0.7);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .btn-back:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .btn-search {
            flex: 2;
            padding: 12px 20px;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .btn-search:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(106,92,255,0.3);
        }
    </style>
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

        .modal-content {
            background: linear-gradient(135deg, rgba(106,92,255,0.1), rgba(168,85,247,0.1));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            position: relative;
            -webkit-app-region: no-drag;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .modal-header h3 {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .close {
            background: none;
            border: none;
            color: rgba(255,255,255,0.8);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s ease;
            -webkit-app-region: no-drag;
        }

        .close:hover {
            color: white;
        }

        .job-info {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .job-info h4 {
            font-size: 18px;
            font-weight: 600;
            color: white;
            margin-bottom: 10px;
        }

        .job-budget-display {
            font-size: 20px;
            font-weight: 700;
            color: #6a5cff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .modal-content {
                max-width: 95%;
                padding: 20px;
            }
        }
    </style>
</body>
</html>
