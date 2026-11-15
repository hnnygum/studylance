<?php
session_start();

// Check if user is logged in and is freelancer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: login.php");
    exit();
}

include 'database.php';

// Handle proposal submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_proposal'])) {
    $job_id = trim($_POST['job_id']);
    $proposal_text = trim($_POST['proposal_text']);
    $bid_amount = trim($_POST['bid_amount']);
    
    $errors = [];
    
    if (empty($proposal_text)) {
        $errors[] = "Proposal text is required";
    }
    
    if (empty($bid_amount) || !is_numeric($bid_amount) || $bid_amount <= 0) {
        $errors[] = "Valid bid amount is required";
    }
    
    // Check if already submitted proposal for this job
    if (empty($errors)) {
        $sql = "SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "You have already submitted a proposal for this job";
        }
        $stmt->close();
    }
    
    if (empty($errors)) {
        $sql = "INSERT INTO proposals (job_id, freelancer_id, proposal_text, bid_amount) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisd", $job_id, $_SESSION['user_id'], $proposal_text, $bid_amount);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Proposal submitted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to submit proposal";
        }
        $stmt->close();
        
        header("Location: freelancer_dashboard.php");
        exit();
    }
}

// Get available jobs (open jobs)
$sql = "SELECT j.*, u.name as client_name 
        FROM jobs j 
        JOIN users u ON j.client_id = u.id 
        WHERE j.status = 'open' 
        ORDER BY j.created_at DESC";
$result = $conn->query($sql);

$available_jobs = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $available_jobs[] = $row;
    }
}

// Get freelancer's proposals
$sql = "SELECT p.*, j.title as job_title, j.status as job_status, u.name as client_name 
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        JOIN users u ON j.client_id = u.id 
        WHERE p.freelancer_id = ? 
        ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$my_proposals = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $my_proposals[] = $row;
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
    <title>Freelancer Dashboard - StudyLance</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>Freelancer Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <nav class="dashboard-nav">
            <ul>
                <li><a href="freelancer_dashboard.php" class="active">Dashboard</a></li>
                <li><a href="#available-jobs">Available Jobs</a></li>
                <li><a href="#my-proposals">My Proposals</a></li>
                <li><a href="#messages">Messages</a></li>
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
                        <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section id="available-jobs" class="jobs-section">
                <h2>Available Jobs</h2>
                <?php if (empty($available_jobs)): ?>
                    <p class="no-data">No available jobs at the moment.</p>
                <?php else: ?>
                    <div class="jobs-grid">
                        <?php foreach ($available_jobs as $job): ?>
                            <div class="job-card">
                                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                <p class="job-description"><?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 200))); ?>...</p>
                                <div class="job-meta">
                                    <span class="budget">$<?php echo number_format($job['budget'], 2); ?></span>
                                    <span class="client">Posted by <?php echo htmlspecialchars($job['client_name']); ?></span>
                                </div>
                                <div class="job-date">
                                    Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                </div>
                                <div class="job-actions">
                                    <button class="btn btn-primary btn-sm" onclick="showProposalForm(<?php echo $job['id']; ?>)">
                                        Submit Proposal
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section id="my-proposals" class="proposals-section">
                <h2>My Proposals</h2>
                <?php if (empty($my_proposals)): ?>
                    <p class="no-data">You haven't submitted any proposals yet.</p>
                <?php else: ?>
                    <div class="proposals-list">
                        <?php foreach ($my_proposals as $proposal): ?>
                            <div class="proposal-card">
                                <h4><?php echo htmlspecialchars($proposal['job_title']); ?></h4>
                                <p class="client-name">Client: <?php echo htmlspecialchars($proposal['client_name']); ?></p>
                                <p class="proposal-text"><?php echo nl2br(htmlspecialchars(substr($proposal['proposal_text'], 0, 200))); ?>...</p>
                                <div class="proposal-meta">
                                    <span class="bid-amount">Bid: $<?php echo number_format($proposal['bid_amount'], 2); ?></span>
                                    <span class="status status-<?php echo $proposal['status']; ?>"><?php echo ucfirst($proposal['status']); ?></span>
                                </div>
                                <div class="proposal-date">
                                    Submitted: <?php echo date('M j, Y', strtotime($proposal['created_at'])); ?>
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
                <h3>Submit Proposal</h3>
                <span class="close" onclick="closeProposalForm()">&times;</span>
            </div>
            <form method="POST" action="" class="proposal-form">
                <input type="hidden" name="submit_proposal" value="1">
                <input type="hidden" id="job_id" name="job_id">
                
                <div class="form-group">
                    <label for="proposal_text">Your Proposal:</label>
                    <textarea id="proposal_text" name="proposal_text" rows="5" required 
                              placeholder="Explain why you're the best fit for this job..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="bid_amount">Your Bid Amount ($):</label>
                    <input type="number" id="bid_amount" name="bid_amount" step="0.01" min="1" required>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeProposalForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Proposal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showProposalForm(jobId) {
            document.getElementById('job_id').value = jobId;
            document.getElementById('proposalModal').style.display = 'block';
        }

        function closeProposalForm() {
            document.getElementById('proposalModal').style.display = 'none';
            document.getElementById('proposal_text').value = '';
            document.getElementById('bid_amount').value = '';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('proposalModal');
            if (event.target == modal) {
                closeProposalForm();
            }
        }
    </script>
</body>
</html>
