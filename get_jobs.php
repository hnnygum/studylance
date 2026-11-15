<?php
session_start();

// Check if user is logged in and is freelancer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: login.php");
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>Browse Jobs</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="freelancer_dashboard.php" class="btn btn-secondary">Dashboard</a>
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <!-- Search and Filter Section -->
            <section class="search-section">
                <h2>Find Jobs</h2>
                <form method="GET" action="" class="search-form">
                    <div class="search-grid">
                        <div class="form-group">
                            <label for="search">Search Jobs</label>
                            <input type="text" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Search by title or description...">
                        </div>
                        
                        <div class="form-group">
                            <label for="budget_min">Min Budget ($)</label>
                            <input type="number" id="budget_min" name="budget_min" 
                                   value="<?php echo htmlspecialchars($budget_min); ?>"
                                   step="0.01" min="0" placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label for="budget_max">Max Budget ($)</label>
                            <input type="number" id="budget_max" name="budget_max" 
                                   value="<?php echo htmlspecialchars($budget_max); ?>"
                                   step="0.01" min="0" placeholder="10000.00">
                        </div>
                        
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="search-actions">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="get_jobs.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Results Section -->
            <section class="results-section">
                <div class="results-header">
                    <h3>Available Jobs (<?php echo count($jobs); ?> found)</h3>
                    <div class="sort-options">
                        <label>Sort by:</label>
                        <select onchange="window.location.href='?sort=' + this.value">
                            <option value="newest">Newest First</option>
                            <option value="budget_high">Budget: High to Low</option>
                            <option value="budget_low">Budget: Low to High</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($jobs)): ?>
                    <div class="no-data">
                        <h3>No jobs found</h3>
                        <p>Try adjusting your search criteria or check back later for new opportunities.</p>
                        <a href="get_jobs.php" class="btn btn-primary">View All Jobs</a>
                    </div>
                <?php else: ?>
                    <div class="jobs-grid">
                        <?php foreach ($jobs as $job): ?>
                            <div class="job-card">
                                <div class="job-header">
                                    <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <span class="budget">$<?php echo number_format($job['budget'], 2); ?></span>
                                </div>
                                
                                <div class="job-description">
                                    <?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 300))); ?>
                                    <?php if (strlen($job['description']) > 300): ?>
                                        <strong>...</strong>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="job-meta">
                                    <div class="client-info">
                                        <strong>Client:</strong> <?php echo htmlspecialchars($job['client_name']); ?>
                                    </div>
                                    <div class="proposal-count">
                                        <?php echo $job['proposal_count']; ?> proposal<?php echo $job['proposal_count'] != 1 ? 's' : ''; ?>
                                    </div>
                                </div>
                                
                                <div class="job-footer">
                                    <div class="job-date">
                                        Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                    </div>
                                    <div class="job-actions">
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="showProposalForm(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars($job['title']); ?>')">
                                            Send Proposal
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Proposal Modal -->
    <div id="proposalModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Send Proposal</h3>
                <span class="close" onclick="closeProposalForm()">&times;</span>
            </div>
            <form id="proposalForm" action="send_proposal.php" method="POST">
                <input type="hidden" id="job_id" name="job_id">
                <input type="hidden" id="job_title" name="job_title">
                
                <div class="job-info">
                    <h4 id="modalJobTitle"></h4>
                </div>
                
                <div class="form-group">
                    <label for="proposal_text">Your Proposal *</label>
                    <textarea id="proposal_text" name="proposal_text" rows="6" required
                              placeholder="Explain why you're the best fit for this job, your relevant experience, and how you plan to complete the work..."></textarea>
                    <small>Minimum 50 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="bid_amount">Your Bid Amount ($) *</label>
                    <input type="number" id="bid_amount" name="bid_amount" 
                           step="0.01" min="1" required
                           placeholder="Enter your bid amount">
                    <small>Amount you'll charge for this job</small>
                </div>
                
                <div class="form-group">
                    <label for="estimated_time">Estimated Completion Time</label>
                    <select id="estimated_time" name="estimated_time">
                        <option value="">Select timeframe</option>
                        <option value="1-3 days">1-3 days</option>
                        <option value="3-7 days">3-7 days</option>
                        <option value="1-2 weeks">1-2 weeks</option>
                        <option value="2-4 weeks">2-4 weeks</option>
                        <option value="1-2 months">1-2 months</option>
                        <option value="2+ months">2+ months</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeProposalForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Proposal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showProposalForm(jobId, jobTitle) {
            document.getElementById('job_id').value = jobId;
            document.getElementById('job_title').value = jobTitle;
            document.getElementById('modalJobTitle').textContent = jobTitle;
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
            const proposalText = document.getElementById('proposal_text').value;
            const bidAmount = document.getElementById('bid_amount').value;
            
            if (proposalText.length < 50) {
                alert('Proposal must be at least 50 characters long');
                e.preventDefault();
                return false;
            }
            
            if (!bidAmount || bidAmount <= 0) {
                alert('Please enter a valid bid amount');
                e.preventDefault();
                return false;
            }
        });
    </script>

    <style>
        .search-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .search-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .search-actions {
            display: flex;
            gap: 0.5rem;
        }

        .results-section {
            margin-bottom: 2rem;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .sort-options {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sort-options select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .job-header h3 {
            margin: 0;
            color: #2c3e50;
            flex: 1;
        }

        .job-header .budget {
            font-weight: bold;
            color: #27ae60;
            font-size: 1.1rem;
            white-space: nowrap;
        }

        .job-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .job-info h4 {
            margin: 0;
            color: #2c3e50;
        }

        @media (max-width: 768px) {
            .search-grid {
                grid-template-columns: 1fr;
            }
            
            .results-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .job-header {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</body>
</html>
