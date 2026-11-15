<?php
session_start();

// Check if user is logged in and is client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

include 'database.php';

$errors = [];
$success = '';

// Handle proposal action (accept/reject)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $proposal_id = trim($_POST['proposal_id']);
    $action = trim($_POST['action']); // 'accept' or 'reject'
    
    // Validation
    if (empty($proposal_id) || !is_numeric($proposal_id)) {
        $errors[] = "Invalid proposal";
    }
    
    if (!in_array($action, ['accept', 'reject'])) {
        $errors[] = "Invalid action";
    }
    
    // Verify proposal belongs to client's job
    if (empty($errors)) {
        $sql = "SELECT p.*, j.client_id, j.status as job_status 
                FROM proposals p 
                JOIN jobs j ON p.job_id = j.id 
                WHERE p.id = ? AND j.client_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $proposal_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $errors[] = "Proposal not found or access denied";
        } else {
            $proposal = $result->fetch_assoc();
            
            if ($proposal['status'] !== 'pending') {
                $errors[] = "This proposal has already been " . $proposal['status'];
            }
            
            if ($proposal['job_status'] !== 'open') {
                $errors[] = "This job is no longer accepting proposals";
            }
        }
        $stmt->close();
    }
    
    // Process action
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Update proposal status
            $sql = "UPDATE proposals SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $action, $proposal_id);
            $stmt->execute();
            $stmt->close();
            
            if ($action == 'accept') {
                // Reject all other proposals for this job
                $sql = "UPDATE proposals SET status = 'rejected' 
                        WHERE job_id = ? AND id != ? AND status = 'pending'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $proposal['job_id'], $proposal_id);
                $stmt->execute();
                $stmt->close();
                
                // Update job status to in_progress
                $sql = "UPDATE jobs SET status = 'in_progress' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $proposal['job_id']);
                $stmt->execute();
                $stmt->close();
                
                $success = "Proposal accepted! Job status updated to 'In Progress'.";
            } else {
                $success = "Proposal rejected successfully.";
            }
            
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to process action. Please try again.";
        }
    }
}

// Get proposals for client's jobs
$sql = "SELECT p.*, j.title as job_title, j.budget as job_budget, j.status as job_status,
        u.name as freelancer_name, u.email as freelancer_email
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        JOIN users u ON p.freelancer_id = u.id 
        WHERE j.client_id = ? 
        ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$proposals = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $proposals[] = $row;
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
    <title>Manage Proposals - StudyLance</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>Manage Proposals</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="client_dashboard.php" class="btn btn-secondary">Dashboard</a>
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="proposals-section">
                <h2>Received Proposals</h2>
                
                <?php if (empty($proposals)): ?>
                    <div class="no-data">
                        <h3>No proposals received</h3>
                        <p>When freelancers submit proposals for your jobs, they will appear here.</p>
                        <a href="client_dashboard.php" class="btn btn-primary">Post a New Job</a>
                    </div>
                <?php else: ?>
                    <!-- Filter tabs -->
                    <div class="filter-tabs">
                        <button class="tab-btn active" onclick="filterProposals('all')">All Proposals</button>
                        <button class="tab-btn" onclick="filterProposals('pending')">Pending</button>
                        <button class="tab-btn" onclick="filterProposals('accepted')">Accepted</button>
                        <button class="tab-btn" onclick="filterProposals('rejected')">Rejected</button>
                    </div>

                    <div class="proposals-list">
                        <?php foreach ($proposals as $proposal): ?>
                            <div class="proposal-card status-<?php echo $proposal['status']; ?>" 
                                 data-status="<?php echo $proposal['status']; ?>">
                                <div class="proposal-header">
                                    <div class="proposal-job">
                                        <h3><?php echo htmlspecialchars($proposal['job_title']); ?></h3>
                                        <span class="job-budget">Budget: $<?php echo number_format($proposal['job_budget'], 2); ?></span>
                                    </div>
                                    <div class="proposal-status">
                                        <span class="status-badge status-<?php echo $proposal['status']; ?>">
                                            <?php echo ucfirst($proposal['status']); ?>
                                        </span>
                                        <?php if ($proposal['job_status'] !== 'open'): ?>
                                            <span class="job-status-badge status-<?php echo $proposal['job_status']; ?>">
                                                Job: <?php echo ucfirst($proposal['job_status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="proposal-content">
                                    <div class="freelancer-info">
                                        <h4>Freelancer: <?php echo htmlspecialchars($proposal['freelancer_name']); ?></h4>
                                        <p class="freelancer-email"><?php echo htmlspecialchars($proposal['freelancer_email']); ?></p>
                                    </div>

                                    <div class="proposal-details">
                                        <p class="proposal-text"><?php echo nl2br(htmlspecialchars($proposal['proposal_text'])); ?></p>
                                        
                                        <div class="proposal-meta">
                                            <div class="bid-info">
                                                <span class="bid-amount">Bid: $<?php echo number_format($proposal['bid_amount'], 2); ?></span>
                                                <?php if (!empty($proposal['estimated_time'])): ?>
                                                    <span class="estimated-time">Time: <?php echo htmlspecialchars($proposal['estimated_time']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="proposal-date">
                                                Submitted: <?php echo date('M j, Y g:i A', strtotime($proposal['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($proposal['status'] == 'pending' && $proposal['job_status'] == 'open'): ?>
                                    <div class="proposal-actions">
                                        <form method="POST" action="" onsubmit="return confirmAction('accept', '<?php echo htmlspecialchars($proposal['freelancer_name']); ?>')">
                                            <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="btn btn-success btn-sm">Accept Proposal</button>
                                        </form>
                                        <form method="POST" action="" onsubmit="return confirmAction('reject', '<?php echo htmlspecialchars($proposal['freelancer_name']); ?>')">
                                            <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm">Reject Proposal</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="guidelines-section">
                <h2>Review Guidelines</h2>
                <div class="guidelines-grid">
                    <div class="guideline-card">
                        <h3>Evaluate Proposals</h3>
                        <ul>
                            <li>Review the freelancer's experience and skills</li>
                            <li>Assess their understanding of your requirements</li>
                            <li>Compare bid amounts and estimated timelines</li>
                            <li>Check for clear communication and professionalism</li>
                        </ul>
                    </div>
                    <div class="guideline-card">
                        <h3>Accepting Proposals</h3>
                        <ul>
                            <li>You can only accept one proposal per job</li>
                            <li>Other pending proposals will be automatically rejected</li>
                            <li>Job status will change to "In Progress"</li>
                            <li>You can communicate with the freelancer via messages</li>
                        </ul>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        function filterProposals(status) {
            const proposals = document.querySelectorAll('.proposal-card');
            const tabs = document.querySelectorAll('.tab-btn');
            
            // Update active tab
            tabs.forEach(tab => {
                tab.classList.remove('active');
                if (tab.textContent.toLowerCase().includes(status) || 
                    (status === 'all' && tab.textContent === 'All Proposals')) {
                    tab.classList.add('active');
                }
            });
            
            // Show/hide proposals
            proposals.forEach(proposal => {
                if (status === 'all' || proposal.dataset.status === status) {
                    proposal.style.display = 'block';
                } else {
                    proposal.style.display = 'none';
                }
            });
        }

        function confirmAction(action, freelancerName) {
            const message = action === 'accept' 
                ? `Are you sure you want to accept the proposal from ${freelancerName}? This will reject all other pending proposals for this job.`
                : `Are you sure you want to reject the proposal from ${freelancerName}?`;
            
            return confirm(message);
        }
    </script>

    <style>
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-btn:hover {
            background: #f8f9fa;
        }

        .tab-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .proposal-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            border-left: 4px solid #ddd;
        }

        .proposal-card.status-pending {
            border-left-color: #f39c12;
        }

        .proposal-card.status-accepted {
            border-left-color: #27ae60;
        }

        .proposal-card.status-rejected {
            border-left-color: #e74c3c;
        }

        .proposal-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            padding: 1.5rem 1.5rem 0 1.5rem;
        }

        .proposal-job h3 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
        }

        .job-budget {
            color: #666;
            font-size: 0.9rem;
        }

        .proposal-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .job-status-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .proposal-content {
            padding: 1.5rem;
        }

        .freelancer-info {
            margin-bottom: 1rem;
        }

        .freelancer-info h4 {
            margin: 0 0 0.25rem 0;
            color: #2c3e50;
        }

        .freelancer-email {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }

        .proposal-details {
            margin-bottom: 1rem;
        }

        .proposal-text {
            color: #555;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .proposal-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .bid-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .bid-amount {
            font-weight: bold;
            color: #27ae60;
            font-size: 1.1rem;
        }

        .estimated-time {
            color: #666;
            font-size: 0.9rem;
        }

        .proposal-date {
            color: #999;
            font-size: 0.875rem;
        }

        .proposal-actions {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }

        .proposal-actions form {
            margin: 0;
        }

        .guidelines-section {
            margin-top: 3rem;
        }

        .guidelines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .guideline-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .guideline-card h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .guideline-card ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .guideline-card li {
            margin-bottom: 0.5rem;
            color: #555;
        }

        @media (max-width: 768px) {
            .filter-tabs {
                flex-wrap: wrap;
                gap: 0.25rem;
            }
            
            .tab-btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
            
            .proposal-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .proposal-status {
                align-items: flex-start;
            }
            
            .proposal-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .proposal-actions {
                flex-direction: column;
            }
            
            .guidelines-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
